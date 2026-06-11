<?php
require_once __DIR__ . '/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = getDbConnection();
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$address = isset($_GET['address']) ? trim($_GET['address']) : '';
$user_name = $_SESSION['user_name'];

// 💡 修正：驗證訂單改回 status 
$stmt = $db->prepare("SELECT * FROM public.\"Order\" WHERE id = ? AND user_name = ? AND status = '待付款'");
$stmt->execute([$order_id, $user_name]);
$order = $stmt->fetch();

if (!$order || empty($address)) {
    echo "<script>alert('無效的結帳請求！'); window.location.href='orders.php';</script>";
    exit;
}

// 處理 3 秒後的信用卡扣款請求（AJAX 異步連動）
if (isset($_GET['action']) && $_GET['action'] === 'process_payment') {
    header('Content-Type: application/json');

    $is_success = true;

    if ($is_success) {
        try {
            $db->beginTransaction();

            // 產生符合 'YYYY-MM-DD HH:MM:SS' 的當下時間字串
            $current_time = date('Y-m-d H:i:s');

            // 💡 修正：修改 Order 資料表，欄位改回標準的 status
            $update_stmt = $db->prepare("
                UPDATE public.\"Order\" 
                SET status = '待出貨', time = ? 
                WHERE id = ?
            ");
            $update_stmt->execute([$current_time, $order_id]);

            // 3. 寫入 payment 資料表
            $pay_stmt = $db->prepare("
                INSERT INTO public.payment (order_id, payment, address) 
                VALUES (?, ?, ?)
            ");
            $pay_stmt->execute([$order_id, '信用卡', $address]);

            $db->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => '資料庫處理失敗：' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '信用額度不足或授權拒絕！']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安全線上刷卡 | 二手交易平台</title>
    <link rel="stylesheet" href="css/index.css">
    <style>
        .credit-card-box {
            max-width: 450px;
            margin: 50px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        .card-visual {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.2);
        }

        .card-chip {
            width: 45px;
            height: 35px;
            border-radius: 6px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fcd34d 0%, #f59e0b 100%);
        }

        .card-number-display {
            font-size: 20px;
            letter-spacing: 3px;
            font-family: monospace;
            margin-bottom: 15px;
        }

        .card-info-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .card-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
            margin-bottom: 15px;
        }

        /* 轉圈圈動畫遮罩 */
        .loading-overlay {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .btn-submit-card {
            width: 100%;
            padding: 14px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
            transition: 0.2s;
        }

        .btn-submit-card:hover {
            background: #059669;
            transform: translateY(-1px);
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="credit-card-box">
        <div class="loading-overlay" id="loadingOverlay">
            <div class="spinner"></div>
            <h3 style="color: #1e293b; font-weight:700;">🏦 銀行安全連線授權中...</h3>
            <p style="color: #64748b; font-size:13px; margin-top:5px;">正在進行加密扣款，請勿整理網頁</p>
        </div>

        <h2 style="font-size:20px; font-weight:700; margin-bottom:15px; color:#1e293b;">💳 線上刷卡驗證</h2>
        <p style="font-size:13px; color:#64748b; margin-bottom:20px;">應付總金額：<strong style="color:#ff385c; font-size:16px;">$<?= number_format($order['sum']) ?></strong></p>

        <div class="card-visual">
            <div class="card-chip"></div>
            <div class="card-number-display" id="mirror_number">•••• •••• •••• ••••</div>
            <div class="card-info-row">
                <div>持卡人姓名<br><span style="color:white; font-weight:600;" id="mirror_name">CARDHOLDER</span></div>
                <div>到期日<br><span style="color:white; font-weight:600;" id="mirror_date">MM/YY</span></div>
            </div>
        </div>

        <form id="paymentForm" onsubmit="handleCreditCardPay(event)">
            <label class="form-label">卡號</label>
            <input type="text" class="card-input" maxlength="19" placeholder="4571 2345 6789 0123" required oninput="syncCardNumber(this)">

            <div class="input-row">
                <div>
                    <label class="form-label">有效日期</label>
                    <input type="text" id="card_expiry" class="card-input" maxlength="5" placeholder="MM/YY" required
                        oninput="formatExpiry(this)"
                        onblur="validateExpiry(this)">
                </div>
                <div>
                    <label class="form-label">安全碼 (CVC)</label>
                    <input type="password" class="card-input" maxlength="3" placeholder="123" required>
                </div>
            </div>

            <button type="submit" class="btn-submit-card">確認安全付款 $<?= number_format($order['sum']) ?></button>
        </form>
    </div>

    <script>
        function syncCardNumber(input) {
            let num = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let matches = num.match(/\d{4,16}/g);
            let match = matches && matches[0] || '';
            let parts = [];
            for (let i = 0, len = match.length; i < len; i += 4) {
                parts.push(match.substring(i, i + 4));
            }
            input.value = parts.length > 0 ? parts.join(' ') : num;
            document.getElementById('mirror_number').innerText = input.value || '•••• •••• •••• ••••';
        }

        function formatExpiry(input) {
            let code = input.value.replace(/\D/g, '');
            if (code.length > 2) {
                input.value = code.substring(0, 2) + '/' + code.substring(2, 4);
            } else {
                input.value = code;
            }
            document.getElementById('mirror_date').innerText = input.value || 'MM/YY';
        }

        function validateExpiry(input) {
            let val = input.value;
            if (val === '') return;
            if (val.length < 5 || !val.includes('/')) {
                alert('❌ 有效日期格式不正確，請輸入 MM/YY（例如 05/26）');
                resetExpiryInput(input);
                return;
            }
            let parts = val.split('/');
            let month = parseInt(parts[0], 10);
            let year = parseInt(parts[1], 10);

            if (isNaN(month) || month < 1 || month > 12) {
                alert('❌ 月份輸入錯誤！請輸入 01 到 12 之間的月份。');
                resetExpiryInput(input);
                return;
            }
            if (isNaN(year) || year < 21 || year > 32) {
                alert('❌ 年份輸入錯誤！必須在 21 到 32 之間。');
                resetExpiryInput(input);
                return;
            }
        }

        function resetExpiryInput(input) {
            input.value = '';
            document.getElementById('mirror_date').innerText = 'MM/YY';
            setTimeout(() => input.focus(), 10);
        }

        function handleCreditCardPay(event) {
            event.preventDefault();
            document.getElementById('loadingOverlay').style.display = 'flex';

            const orderId = "<?= $order_id ?>";
            const address = encodeURIComponent("<?= $address ?>");

            setTimeout(() => {
                fetch(`card.php?order_id=${orderId}&address=${address}&action=process_payment`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('🎉 信用卡刷卡成功！已成功扣款。');
                            window.location.href = 'orders.php';
                        } else {
                            alert('❌ 付款未成功：' + (data.message || '銀行拒絕授權'));
                            window.location.href = `checkout.php?order_id=${orderId}`;
                        }
                    })
                    .catch(error => {
                        alert('❌ 網路通訊失敗，請重新嘗試。');
                        document.getElementById('loadingOverlay').style.display = 'none';
                    });
            }, 2000);
        }
    </script>
</body>

</html>