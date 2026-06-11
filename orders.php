<?php
require_once __DIR__ . '/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // 沒登入的話，直接踢去登入頁
    exit;
}

$db = getDbConnection();
$user_id = $_SESSION['user_id']; 
$user_name = $_SESSION['user_name']; // 抓取當前登入的會員姓名

// 1. 取得當前點選的狀態篩選（預設為 '全部'）
$current_status = isset($_GET['status']) ? trim($_GET['status']) : '全部';

// 2. 建立基礎 SQL（使用你提供的新 JOIN 語法）
$sql = "SELECT c.category, i.name, i.img, o.quantity, o.sum, o.status, o.time, o.id
        FROM \"Order\" o
        JOIN \"item\" i ON o.item_id = i.id
        JOIN \"category\" c ON i.category_id = c.id
        WHERE o.user_name = :user_name";

$params = [':user_name' => $user_name];

// 3. 如果不是選 '全部'，就動態加上 status 篩選條件
if ($current_status !== '全部') {
    $sql .= " AND o.status = :status";
    $params[':status'] = $current_status;
}

$sql .= " ORDER BY o.id ASC"; // 依照你的需求，由舊到新正序排列 (ASC)

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $order_records = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Orders query failed: ' . $e->getMessage());
    $order_records = [];
}

// 4. 計算最上方的「總支出」與「進行中筆數」
try {
    $stats_stmt = $db->prepare("
        SELECT o.sum, o.status 
        FROM \"Order\" o
        WHERE o.user_name = ?
    ");
    $stats_stmt->execute([$user_name]);
    $all_user_orders = $stats_stmt->fetchAll();

    $total_spend = 0;
    $processing_count = 0;

    foreach ($all_user_orders as $o) {
        $total_spend += $o['sum']; // 金額欄位改用 sum
        
        // 只要不是「訂單已完成」，就屬於進行中（待付款、待出貨、待收貨）
        if ($o['status'] !== '訂單已完成') {
            $processing_count++;
        }
    }
} catch (PDOException $e) {
    $total_spend = 0;
    $processing_count = 0;
}

// 💡 狀態與 CSS Class 的對應對照表
$status_css_map = [
    '待付款' => 'status-unpaid',
    '待出貨' => 'status-pending', 
    '待收貨' => 'status-shipping',
    '訂單已完成' => 'status-completed'
];
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的訂單紀錄 | 二手交易平台</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/orders.css">
</head>

<body>
    <?php include 'header.php'; ?>

    <main class="container">
        <div class="page-header">
            <div>
                <h1>📋 我的訂單</h1>
                <p>查看您所有的購買紀錄與運送進度</p>
            </div>
            <div class="stats-summary">
                <div class="stat-item">
                    <span class="stat-label">累計總支出</span>
                    <span class="stat-value">$<?= number_format($total_spend) ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">進行中訂單</span>
                    <span class="stat-value"><?= $processing_count ?> 筆</span>
                </div>
            </div>
        </div>

        <div class="filter-tabs" style="margin-bottom: 20px; display: flex; gap: 15px;">
            <a href="orders.php?status=全部" class="filter-tab <?= $current_status === '全部' ? 'active' : '' ?>">全部</a>
            <a href="orders.php?status=待付款" class="filter-tab <?= $current_status === '待付款' ? 'active' : '' ?>">待付款</a>
            <a href="orders.php?status=待出貨" class="filter-tab <?= $current_status === '待出貨' ? 'active' : '' ?>">待出貨</a>
            <a href="orders.php?status=待收貨" class="filter-tab <?= $current_status === '待收貨' ? 'active' : '' ?>">待收貨</a>
            <a href="orders.php?status=訂單已完成" class="filter-tab <?= $current_status === '訂單已完成' ? 'active' : '' ?>">訂單已完成</a>
        </div>

        <div class="records-list">
            <?php if (empty($order_records)): ?>
                <div style="text-align: center; padding: 60px; color: #94a3b8; background: white; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <p style="font-size: 48px; margin-bottom: 10px;">📦</p>
                    <p>目前沒有「<?= htmlspecialchars($current_status) ?>」的訂單紀錄喔！</p>
                </div>
            <?php else: ?>
                <?php foreach ($order_records as $order):
                    $current_class = $status_css_map[$order['status']] ?? 'status-default';
                ?>
                    <div class="record-card">
                        <div class="record-info">
                            <div class="record-main">
                                <div class="product-thumb" style="display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    <?php if (!empty($order['img'])): ?>
                                        <img src="<?= htmlspecialchars($order['img']) ?>" alt="<?= htmlspecialchars($order['name'] ?? '商品圖片') ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                                    <?php else: ?>
                                        <span style="font-size: 24px;">📦</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-details">
                                    <h3 class="product-name"><?= htmlspecialchars($order['name'] ?? '未命名商品') ?></h3>
                                    <span style="font-size: 11px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #64748b;">
                                        <?= htmlspecialchars($order['category']) ?>
                                    </span>
                                    <p style="font-size: 12px; color: #94a3b8; margin-top: 6px;">數量：<?= htmlspecialchars($order['quantity']) ?></p>
                                </div>
                            </div>
                            <div class="record-price">
                                <span class="price-label">實付金額</span>
                                <span class="price-value">$<?= number_format($order['sum']) ?></span>
                            </div>
                            <div class="record-status">
                                <span class="status-tag <?= $current_class ?>"><?= htmlspecialchars($order['status']) ?></span>
                            </div>
                        </div>

                        <?php if ($order['status'] === '待付款'): ?>
                            <div class="record-actions">
                                <a href="checkout.php?order_id=<?= urlencode($order['id']) ?>" class="btn-primary-sm" style="text-decoration: none; display: inline-block;">
                                    💳 結帳
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>