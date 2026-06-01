<?php
require_once __DIR__ . '/db.php';
session_start();

// 權限檢查：沒登入或不是賣家 (role=2) 就踢回去
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 2) {
    header("Location: index.php");
    exit;
}

$db = getDbConnection();

// 模擬資料 (之後你可以改為 SQL 查詢: SELECT * FROM orders WHERE seller_id = ...)
$sales_records = [
    [
        'id' => 'ORD-2024001',
        'p_name' => '九成新 iPhone 13 128G 藍色',
        'price' => 12500,
        'buyer' => '小明',
        'date' => '2024-05-20',
        'status' => '待出貨',
        'status_class' => 'status-pending'
    ],
    [
        'id' => 'ORD-2024002',
        'p_name' => 'Nintendo Switch 電力加強版',
        'price' => 5500,
        'buyer' => '阿華',
        'date' => '2024-05-18',
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
    <title>賣家交易紀錄 | 二手交易平台</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/sales.css">
</head>

<body>
    <?php include 'header.php'; ?>

    <main class="container">
        <div class="page-header">
            <div>
                <h1>💰 交易紀錄</h1>
                <p>管理您的訂單資訊與銷售狀態</p>
            </div>
            <div class="stats-summary">
                <div class="stat-item">
                    <span class="stat-label">累積銷售額</span>
                    <span class="stat-value">$18,000</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">成交訂單</span>
                    <span class="stat-value">2 筆</span>
                </div>
            </div>
        </div>

        <div class="filter-tabs">
            <button class="filter-tab active">全部</button>
            <button class="filter-tab">待付款</button>
            <button class="filter-tab">待出貨</button>
            <button class="filter-tab">已完成</button>
            <button class="filter-tab">已取消</button>
        </div>

        <div class="records-list">
            <?php foreach ($sales_records as $order): ?>
                <div class="record-card">
                    <div class="record-info">
                        <div class="record-main">
                            <div class="product-thumb">📸</div>
                            <div class="product-details">
                                <span class="order-id">訂單編號：<?= $order['id'] ?></span>
                                <h3 class="product-name"><?= $order['p_name'] ?></h3>
                                <p class="buyer-info">買家：<?= $order['buyer'] ?> | 成交日期：<?= $order['date'] ?></p>
                            </div>
                        </div>
                        <div class="record-price">
                            <span class="price-label">成交金額</span>
                            <span class="price-value">$<?= number_format($order['price']) ?></span>
                        </div>
                        <div class="record-status">
                            <span class="status-tag <?= $order['status_class'] ?>"><?= $order['status'] ?></span>
                        </div>
                    </div>
                    <div class="record-actions">
                        <button class="btn-detail">查看詳情</button>
                        <?php if ($order['status'] == '待出貨'): ?>
                            <button class="btn-primary-sm">立即出貨</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>

</html>