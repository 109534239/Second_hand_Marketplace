<?php
require_once __DIR__ . '/db.php';
$db = getDbConnection();

// 1. 抓取網址後面的 id 參數
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // 2. 去資料庫查這個商品的所有詳細資訊
    $stmt = $db->prepare("SELECT * FROM public.item WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo "找不到該商品！";
        exit;
    }
} else {
    echo "無效的商品 ID！";
    exit;
}

// 接下來就可以在 HTML 裡用 $product['name']、$product['price'] 顯示細節了！
?>