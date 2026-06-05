<?php
require_once __DIR__ . '/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // 沒登入的話，直接踢去登入頁
    exit;
}

$db = getDbConnection();

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
        'status' => '運送中',
        'status_class' => 'status-shipping'
    ],
    [
        'id' => 'ORD-2024003',
        'p_name' => '露營必備 摺疊小桌',
        'price' => 450,
        'seller' => '戶外大叔',
        'date' => '2024-05-15',
        'status' => '已完成',
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
                    <span class="stat-value">$25,950</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">進行中訂單</span>
                    <span class="stat-value">2 筆</span>
                </div>
            </div>
        </div>

        <div class="filter-tabs">
            <button class="filter-tab active">全部</button>
            <button class="filter-tab">待付款</button>
            <button class="filter-tab">待收貨</button>
            <button class="filter-tab">已完成</button>
            <button class="filter-tab">已取消</button>
        </div>

        <div class="records-list">
            <?php foreach ($order_records as $order): ?>
                <div class="record-card">
                    <div class="record-info">
                        <div class="record-main">
                            <div class="product-thumb">📦</div>
                            <div class="product-details">
                                <span class="order-id">單號：<?= $order['id'] ?></span>
                                <h3 class="product-name"><?= $order['p_name'] ?></h3>
                                <p class="seller-info">賣家：<?= $order['seller'] ?> | 下單日期：<?= $order['date'] ?></p>
                            </div>
                        </div>
                        <div class="record-price">
                            <span class="price-label">實付金額</span>
                            <span class="price-value">$<?= number_format($order['price']) ?></span>
                        </div>
                        <div class="record-status">
                            <span class="status-tag <?= $order['status_class'] ?>"><?= $order['status'] ?></span>
                        </div>
                    </div>
                    <div class="record-actions">
                        <button class="btn-detail">訂單詳情</button>
                        <?php if ($order['status'] == '待付款'): ?>
                            <button class="btn-primary-sm">立即付款</button>
                        <?php elseif ($order['status'] == '運送中'): ?>
                            <button class="btn-primary-sm">確認收貨</button>
                        <?php elseif ($order['status'] == '已完成'): ?>
                            <button class="btn-secondary-sm">評價商品</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>

</html>