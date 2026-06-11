<?php
require_once __DIR__ . '/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = getDbConnection();
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$user_name = $_SESSION['user_name'];

// 💡 修正：將查詢條件改回標準的 status
$stmt = $db->prepare("
    SELECT o.*, i.name as item_name 
    FROM public.\"Order\" o
    JOIN public.item i ON o.item_id = i.id
    WHERE o.id = ? AND o.user_name = ? AND o.status = '待付款'
");
$stmt->execute([$order_id, $user_name]);
$order = $stmt->fetch();

if (!$order) {
    echo "<script>alert('訂單不存在或已完成付款！'); window.location.href='orders.php';</script>";
    exit;
}

// 處理表單送出
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';

    if (empty($address)) {
        echo "<script>alert('請填寫寄送地址！');</script>";
    } else {
        if ($payment_method === 'cod') {
            try {
                $db->beginTransaction();

                // 產生符合 'YYYY-MM-DD HH:MM:SS' 的當下時間
                $current_time = date('Y-m-d H:i:s');

                // 💡 修正：更新訂單狀態改回 status 
                $update_stmt = $db->prepare("
                    UPDATE public.\"Order\" 
                    SET status = '待出貨', time = ? 
                    WHERE id = ?
                ");
                $update_stmt->execute([$current_time, $order_id]);

                // 寫入 payment 資料表
                $pay_stmt = $db->prepare("
                    INSERT INTO public.payment (order_id, payment, address) 
                    VALUES (?, ?, ?)
                ");
                $pay_stmt->execute([$order_id, '貨到付款', $address]);

                $db->commit();
                echo "<script>alert('🎉 下單成功！付款方式：貨到付款，即將為您出貨。'); window.location.href='orders.php';</script>";
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                echo "<script>alert('❌ 系統寫入失敗：" . addslashes($e->getMessage()) . "');</script>";
            }
        } elseif ($payment_method === 'credit_card') {
            header("Location: card.php?order_id=" . urlencode($order_id) . "&address=" . urlencode($address));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>填寫結帳資訊 | 二手交易平台</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/orders.css">
    <style>
        .checkout-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .checkout-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-summary-box {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #475569;
        }

        .summary-row.total {
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-weight: 700;
            color: #ff385c;
            font-size: 18px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 15px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
            transition: 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .payment-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }

        .payment-card {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: 0.2s;
            font-weight: 600;
            color: #475569;
            text-align: center;
            justify-content: center;
        }

        .payment-options input[type="radio"] {
            display: none;
        }

        .payment-options input[type="radio"]:checked+.payment-card {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .btn-pay {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.3);
            transition: 0.2s;
            margin-top: 15px;
        }

        .btn-pay:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <main class="container">
        <div class="checkout-container">
            <h2 class="checkout-title">🛍️ 訂單結帳確認</h2>

            <div class="order-summary-box">
                <div class="summary-row">
                    <span>購買商品：</span>
                    <strong><?= htmlspecialchars($order['item_name']) ?></strong>
                </div>
                <div class="summary-row">
                    <span>購買數量：</span>
                    <span><?= htmlspecialchars($order['quantity']) ?> 件</span>
                </div>
                <div class="summary-row total">
                    <span>應付總金額：</span>
                    <span>$<?= number_format($order['sum']) ?></span>
                </div>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="address">📍 寄送地址</label>
                    <input type="text" id="address" name="address" class="form-control" placeholder="請輸入完整的收件地址" required value="<?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?>">
                </div>

                <div class="form-group">
                    <label>💳 付款方式</label>
                    <div class="payment-options">
                        <label>
                            <input type="radio" name="payment_method" value="cod" checked>
                            <div class="payment-card">📦 貨到付款</div>
                        </label>
                        <label>
                            <input type="radio" name="payment_method" value="credit_card">
                            <div class="payment-card">💳 信用卡付款</div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-pay">確認結帳，送出訂單</button>
            </form>
        </div>
    </main>
</body>

</html>