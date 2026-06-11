<?php
require_once __DIR__ . '/db.php';
session_start();

// 1. 檢查登入狀態
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username']) && !isset($_SESSION['user_name'])) {
    echo "<script>alert('請先登入！'); window.location.href='index.php';</script>";
    exit;
}

// 💡 雙重保險：不管你的登入頁面是用 username 還是 user_name，這邊都自動幫你相容撈取
$user_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? '預設會員';

// 🔍 如果還是抓不到，強行拋出錯誤，不允許空名稱送出
if (empty($user_name)) {
    echo "<script>alert('❌ 錯誤：無法識別您的登入帳號名稱，請重新登入！'); window.location.href='index.php';</script>";
    exit;
}

// 2. 接收 POST 參數
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

if ($item_id <= 0 || $quantity <= 0) {
    echo "<script>alert('無效的商品或數量！'); window.location.href='frontpage.php';</script>";
    exit;
}

$db = getDbConnection();
// 💡 強制開啟 PDO 錯誤提示模式，這樣有任何 SQL 錯誤才會噴出來
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $db->beginTransaction();

    // 3. 撈取商品最新資訊
    $stmt = $db->prepare("SELECT price, inventory FROM public.item WHERE id = ? FOR UPDATE");
    $stmt->execute([$item_id]);
    $product = $stmt->fetch();

    if (!$product) {
        throw new Exception('商品不存在！');
    }

    $current_inventory = intval($product['inventory']);
    $price = intval($product['price']);

    if ($current_inventory < $quantity) {
        throw new Exception('抱歉，該商品庫存不足，無法完成下單！');
    }

    // 4. 計算總金額
    $sum = $price * $quantity;

    // 5. 設定時間與狀態
    $status = '待付款';
    $current_time = date('Y-m-d H:i:s');

    // 💡 修正點：在雙引號字串中，PostgreSQL 的大寫表名 "Order" 必須用反斜線轉義
    $sql_order = "INSERT INTO public.\"Order\" (user_name, item_id, quantity, sum, status, time) 
                  VALUES (?, ?, ?, ?, ?, ?)";

    $stmt_order = $db->prepare($sql_order);
    $stmt_order->execute([$user_name, $item_id, $quantity, $sum, $status, $current_time]);

    // 💡 【核心修改點 1】：成功寫入 Order 表後，立即抓取資料庫剛生成的訂單 ID 流水號
    $new_order_id = $db->lastInsertId();

    // 6. 更新商品庫存
    $new_inventory = $current_inventory - $quantity;
    $sql_update_item = "UPDATE public.item SET inventory = ? WHERE id = ?";
    $stmt_update = $db->prepare($sql_update_item);
    $stmt_update->execute([$new_inventory, $item_id]);

    $db->commit();

    // 💡 【核心修改點 2】：不要跳去 orders.php，直接帶著 order_id 跳往結帳頁面！
    echo "<script>window.location.href='checkout.php?order_id=" . $new_order_id . "';</script>";
    exit;
} catch (Exception $e) {
    // 發生錯誤時還原資料
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    // 💡 核心除錯：直接印出詳細的資料庫錯誤訊息，不要只顯示「下單失敗」
    $error_msg = addslashes($e->getMessage());
    echo "<script>
            alert('❌ 資料庫錯誤報告：\\n" . $error_msg . "'); 
            window.location.href='item.php?id=" . $item_id . "';
          </script>";
    exit;
}
