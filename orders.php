<?php
require_once __DIR__ . '/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // 沒登入的話，直接踢去登入頁
    exit;
}

$db = getDbConnection();
$user_name = $_SESSION['user_name']; // 抓當前登入的會員姓名

// 1. 取得當前點選的狀態篩選（預設為 '全部'）
$current_status = isset($_GET['status']) ? trim($_GET['status']) : '全部';

// 2. 建立基礎 SQL（根據你的資料表結構，這裡假設資料表叫 public."Order"，買家欄位是 buyer_id）
$sql = "SELECT * FROM public.\"Order\" WHERE buyer_id = :buyer_id";
$params = [':buyer_id' => $user_name];

// 3. 如果不是選 '全部'，就加上 status 篩選條件
if ($current_status !== '全部') {
    $sql .= " AND status = :status";
    $params[':status'] = $current_status;
}

$sql .= " ORDER BY id ASC"; // 按照訂單編號升序排列

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $order_records = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Orders query failed: ' . $e->getMessage());
    $order_records = [];
}

// 4. 為了計算最上方的「總支出」與「進行中筆數」，另外抓取該會員的全部訂單統計
try {
    $stats_stmt = $db->prepare("SELECT price, status FROM public.\"Order\" WHERE buyer_id = ?");
    $stats_stmt->execute([$user_name]);
    $all_user_orders = $stats_stmt->fetchAll();

    $total_spend = 0;
    $processing_count = 0;

    foreach ($all_user_orders as $o) {
        $total_spend += $o['price'];
        // 只要不是「訂單已完成」或「已取消」，都算進行中
        if ($o['status'] !== '訂單已完成' && $o['status'] !== '已取消') {
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
    '待出貨' => 'status-pending', // 你可以自訂這個 class
    '待收貨' => 'status-shipping',
    '訂單已完成' => 'status-completed',
    '已取消' => 'status-cancelled'
];
// 模擬買家資料 (之後改為 SQL: SELECT * FROM orders WHERE buyer_id = ...)
$order_records = [
    [
        'id' => 'ORD-2024005',
        'p_name' => '二手極新 PS5 光碟版 主機',
        'price' => 13000,
        'seller' => '電玩達人',
        'date' => '2024-05-22',
        'status' => '待付款',
        'status_class' => 'status-unpaid'
    ],
    [
        'id' => 'ORD-2024001',
        'p_name' => '九成新 iPhone 13 128G 藍色',
        'price' => 12500,
        'seller' => '手機急先鋒',
        'date' => '2024-05-20',
        'status' => '待收貨',
        'status_class' => 'status-shipping'
    ],
    [
        'id' => 'ORD-2024003',
        'p_name' => '露營必備 摺疊小桌',
        'price' => 450,
        'seller' => '戶外大叔',
        'date' => '2024-05-15',
        'status' => '訂單已完成',
        'status_class' => 'status-completed'
    ]
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

        <div class="filter-tabs">
            <a href="orders.php?status=全部" class="filter-tab <?= $current_status === '全部' ? 'active' : '' ?>">全部</a>
            <a href="orders.php?status=待付款" class="filter-tab <?= $current_status === '待付款' ? 'active' : '' ?>">待付款</a>
            <a href="orders.php?status=待出貨" class="filter-tab <?= $current_status === '待出貨' ? 'active' : '' ?>">待出貨</a>
            <a href="orders.php?status=待收貨" class="filter-tab <?= $current_status === '待收貨' ? 'active' : '' ?>">待收貨</a>
            <a href="orders.php?status=訂單已完成" class="filter-tab <?= $current_status === '訂單已完成' ? 'active' : '' ?>">訂單已完成</a>
        </div>

        <div class="records-list">
            <?php if (empty($order_records)): ?>
                <div style="text-align: center; padding: 60px; color: #94a3b8; background: white; border-radius: 16px;">
                    <p style="font-size: 48px; margin-bottom: 10px;">📦</p>
                    <p>目前沒有「<?= htmlspecialchars($current_status) ?>」的訂單紀錄紀錄喔！</p>
                </div>
            <?php else: ?>
                <?php foreach ($order_records as $order):
                    // 自動取得對應的 CSS Class，如果對照表沒有就給預設值
                    $current_class = $status_css_map[$order['status']] ?? 'status-default';
                ?>
                    <div class="record-card">
                        <div class="record-info">
                            <div class="record-main">
                                <div class="product-thumb">📦</div>
                                <div class="product-details">
                                    <h3 class="product-name"><?= htmlspecialchars($order['p_name'] ?? '未命名商品') ?></h3>
                                    <p style="font-size: 12px; color: #94a3b8; margin-top: 4px;">訂單編號：<?= htmlspecialchars($order['id']) ?></p>
                                </div>
                            </div>
                            <div class="record-price">
                                <span class="price-label">實付金額</span>
                                <span class="price-value">$<?= number_format($order['price']) ?></span>
                            </div>
                            <div class="record-status">
                                <span class="status-tag <?= $current_class ?>"><?= htmlspecialchars($order['status']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>