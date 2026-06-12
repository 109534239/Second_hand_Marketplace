<?php
require_once __DIR__ . '/db.php';
session_start();

// 強制將 PHP 時區設為台灣台北時間
date_default_timezone_set('Asia/Taipei');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = getDbConnection();
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$user_name = $_SESSION['user_name'];

// 驗證訂單
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

// 異步 AJAX：處理信用卡安全扣款
if (isset($_GET['action']) && $_GET['action'] === 'process_credit_pay') {
    header('Content-Type: application/json');
    $address = isset($_GET['address']) ? trim($_GET['address']) : '';

    if (empty($address)) {
        echo json_encode(['success' => false, 'message' => '未提供有效的寄送地址！']);
        exit;
    }

    // 機率機制：1~100 隨機數，1 或 2 為失敗（2%），其餘為成功（98%）
    $chance = rand(1, 100);

    if ($chance <= 2) {
        // ❌ 付款失敗（2%）：不改狀態也不改資料表，直接回傳失敗
        echo json_encode(['success' => false, 'message' => '銀行端授權失敗，請檢查卡號或額度。']);
        exit;
    } else {
        // 🎉 付款成功（98%）：更新狀態並進資料表
        try {
            $db->beginTransaction();
            $current_time = date('Y-m-d H:i:s');

            // 更新訂單狀態為待出貨
            $update_stmt = $db->prepare("UPDATE public.\"Order\" SET status = '待出貨', time = ? WHERE id = ?");
            $update_stmt->execute([$current_time, $order_id]);

            // 寫入付款紀錄
            $pay_stmt = $db->prepare("INSERT INTO public.payment (order_id, payment, address) VALUES (?, ?, ?)");
            $pay_stmt->execute([$order_id, '信用卡', $address]);

            $db->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => '資料庫處理失敗：' . $e->getMessage()]);
        }
        exit;
    }
}

// 同步表單：處理貨到付款 (COD) 送出
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cod';

    if (empty($address)) {
        echo "<script>alert('請填寫完整寄送地址！');</script>";
    } else {
        if ($payment_method === 'cod') {
            try {
                $db->beginTransaction();
                $current_time = date('Y-m-d H:i:s');

                // 更新訂單狀態
                $update_stmt = $db->prepare("UPDATE public.\"Order\" SET status = '待出貨', time = ? WHERE id = ?");
                $update_stmt->execute([$current_time, $order_id]);

                // 寫入付款紀錄
                $pay_stmt = $db->prepare("INSERT INTO public.payment (order_id, payment, address) VALUES (?, ?, ?)");
                $pay_stmt->execute([$order_id, '貨到付款', $address]);

                $db->commit();
                echo "<script>alert('🎉 下單成功！付款方式：貨到付款，即將為您出貨。'); window.location.href='orders.php';</script>";
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                echo "<script>alert('❌ 系統寫入失敗：" . addslashes($e->getMessage()) . "');</script>";
            }
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
            position: relative;
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

        .form-label {
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
            margin-bottom: 20px;
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

        .credit-card-panel {
            display: none;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px dashed #e2e8f0;
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

        .input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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

        .btn-pay.btn-credit {
            background: #10b981;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
        }

        .btn-pay.btn-credit:hover {
            background: #059669;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .loading-overlay {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.96);
            border-radius: 16px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 100;
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
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <main class="container">
        <div class="checkout-container">
            <div class="loading-overlay" id="loadingOverlay">
                <div class="spinner"></div>
                <h3 style="color: #1e293b; font-weight:700;">🏦 銀行安全連線授權中...</h3>
                <p style="color: #64748b; font-size:13px; margin-top:5px;">正在進行安全扣款驗證，請勿關閉網頁</p>
            </div>

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

            <form id="mainCheckoutForm" method="POST" action="" onsubmit="handleFormSubmit(event)">

                <div class="form-group">
                    <label class="form-label">📍 寄送地址</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                        <select id="tw_city" class="form-control" required onchange="updateDistricts()">
                            <option value="">請選擇縣市</option>
                        </select>
                        <select id="tw_district" class="form-control" required>
                            <option value="">請選擇區域</option>
                        </select>
                    </div>
                    <input type="text" id="detail_address" class="form-control" placeholder="請輸入路名、巷弄、門牌號碼" required>

                    <input type="hidden" id="address" name="address" value="">
                </div>

                <div class="form-group">
                    <label class="form-label">💳 付款方式</label>
                    <div class="payment-options">
                        <label>
                            <input type="radio" name="payment_method" value="cod" checked onchange="togglePaymentPanel()">
                            <div class="payment-card">📦 貨到付款</div>
                        </label>
                        <label>
                            <input type="radio" name="payment_method" value="credit_card" onchange="togglePaymentPanel()">
                            <div class="payment-card">💳 信用卡付款</div>
                        </label>
                    </div>
                </div>

                <div id="cod_panel">
                    <button type="submit" class="btn-pay">確認下單，貨到付款</button>
                </div>

                <div id="credit_panel" class="credit-card-panel">
                    <div class="card-visual">
                        <div class="card-chip"></div>
                        <div class="card-number-display" id="mirror_number">•••• •••• •••• ••••</div>
                        <div class="card-info-row">
                            <div>到期日<br><span style="color:white; font-weight:600;" id="mirror_date">MM/YY</span></div>
                        </div>
                    </div>

                    <label class="form-label">卡號</label>
                    <input type="text" id="cc_num" class="form-control" style="margin-bottom:15px;" maxlength="19" placeholder="4571 2345 6789 0123" oninput="syncCardNumber(this)">

                    <div class="input-row" style="margin-bottom:15px;">
                        <div>
                            <label class="form-label">有效日期</label>
                            <input type="text" id="card_expiry" class="form-control" maxlength="5" placeholder="MM/YY" oninput="formatExpiry(this)" onblur="validateExpiry(this)">
                        </div>
                        <div>
                            <label class="form-label">安全碼 (CVC)</label>
                            <input type="password" id="cc_cvc" class="form-control" maxlength="3" placeholder="123">
                        </div>
                    </div>

                    <button type="button" class="btn-pay btn-credit" onclick="handleCreditCardPay()">確認安全付款 $<?= number_format($order['sum']) ?></button>
                </div>

            </form>
        </div>
    </main>

    <script>
        const twData = {
            "台北市": ["中正區", "大同區", "中山區", "松山區", "大安區", "萬華區", "信義區", "士林區", "北投區", "內湖區", "南港區", "文山區"],
            "新北市": ["板橋區", "三重區", "中和區", "永和區", "新莊區", "新店區", "樹林區", "鶯歌區", "三峽區", "淡水區", "汐止區", "瑞芳區", "土城區", "蘆洲區", "五股區", "泰山區", "林口區", "深坑區", "石碇區", "坪林區", "三芝區", "石門區", "八里區", "平溪區", "雙溪區", "貢寮區", "金山區", "萬里區", "烏來區"],
            "桃園市": ["桃園區", "中壢區", "大溪區", "楊梅區", "蘆竹區", "大園區", "龜山區", "八德區", "龍潭區", "平鎮區", "新屋區", "觀音區", "復興區"],
            "台中市": ["中區", "東區", "南區", "西區", "北區", "北屯區", "西屯區", "南屯區", "太平區", "大里區", "霧峰區", "烏日區", "丰原區", "后里區", "石岡區", "東勢區", "和平區", "新社區", "潭子區", "大雅區", "神岡區", "大肚區", "沙鹿區", "龍井區", "梧棲區", "清水區", "大甲區", "外埔區", "大安區"],
            "台南市": ["中西區", "東區", "南區", "北區", "安平區", "安南區", "永康區", "歸仁區", "新化區", "左鎮區", "玉井區", "楠西區", "南化區", "仁德區", "關廟區", "龍崎區", "官田區", "麻豆區", "佳里區", "西港區", "七股區", "將軍區", "學甲區", "北門區", "新營區", "後壁區", "白河區", "東山區", "六甲區", "下營區", "柳營區", "鹽水區", "善化區", "大內區", "山上區", "新市區", "安定區"],
            "高雄市": ["新興區", "前金區", "苓雅區", "鹽埕區", "鼓山區", "旗津區", "前鎮區", "三民區", "楠梓區", "小港區", "左營區", "仁武區", "大社區", "岡山區", "路竹區", "阿蓮區", "田寮區", "燕巢區", "橋頭區", "梓官區", "彌陀區", "永安區", "湖內區", "鳳山區", "大寮區", "林園區", "鳥松區", "大樹區", "旗山區", "美濃區", "六龜區", "內門區", "杉林區", "甲仙區", "桃源區", "那瑪夏區", "茂林區", "茄萣區"],
            "基隆市": ["仁愛區", "信義區", "中正區", "中山區", "安樂區", "暖暖區", "七堵區"],
            "新竹市": ["東區", "北區", "香山區"],
            "嘉義市": ["東區", "西區"],
            "新竹縣": ["竹北市", "竹東鎮", "新埔鎮", "關西鎮", "湖口鄉", "新豐鄉", "芎林鄉", "橫山鄉", "北埔鄉", "寶山鄉", "峨眉鄉", "尖石鄉", "五峰鄉"],
            "苗栗縣": ["苗栗市", "頭份市", "竹南鎮", "後龍鎮", "通霄鎮", "苑裡鎮", "卓蘭鎮", "造橋鄉", "西湖鄉", "頭屋鄉", "公館鄉", "銅鑼鄉", "三義鄉", "大湖鄉", "獅潭鄉", "三灣鄉", "南庄鄉", "泰安鄉"],
            "彰化縣": ["彰化市", "鹿港鎮", "和美鎮", "北斗鎮", "員林市", "溪湖鎮", "田中鎮", "二林鎮", "線西鄉", "伸港鄉", "福興鄉", "秀水鄉", "花壇鄉", "芬園鄉", "大村鄉", "埔鹽鄉", "埔心鄉", "永靖鄉", "社頭鄉", "二水鄉", "田尾鄉", "埤頭鄉", "芳苑鄉", "大城鄉", "竹塘鄉", "溪州鄉"],
            "南投縣": ["南投市", "埔里鎮", "草屯鎮", "竹山鎮", "集集鎮", "名間鄉", "鹿谷鄉", "中寮鄉", "魚池鄉", "國姓鄉", "水里鄉", "信義鄉", "仁愛鄉"],
            "雲林縣": ["斗六市", "斗南鎮", "虎尾鎮", "西螺鎮", "土庫鎮", "北港鎮", "古坑鄉", "大埤鄉", "莿桐鄉", "林內鄉", "二崙鄉", "崙背鄉", "麥寮鄉", "東勢鄉", "褒忠鄉", "台西鄉", "元長鄉", "四湖鄉", "口湖鄉", "水林鄉"],
            "嘉義縣": ["太保市", "朴子市", "布袋鎮", "大林鎮", "民雄鄉", "溪口鄉", "新港鄉", "六腳鄉", "東石鄉", "義竹鄉", "鹿草鄉", "水上鄉", "中埔鄉", "竹崎鄉", "梅山鄉", "番路鄉", "大埔鄉", "阿里山鄉"],
            "屏東縣": ["屏東市", "潮州鎮", "東港鎮", "恆春鎮", "萬丹鄉", "長治鄉", "麟洛鄉", "九如鄉", "里港鄉", "高樹鄉", "鹽埔鄉", "內埔鄉", "萬巒鄉", "竹田鄉", "內埤鄉", "枋寮鄉", "新園鄉", "崁頂鄉", "林邊鄉", "南州鄉", "佳冬鄉", "琉球鄉", "車城鄉", "滿州鄉", "枋山鄉", "三地門鄉", "霧台鄉", "瑪家鄉", "泰武鄉", "來義鄉", "春日鄉", "獅子鄉", "牡丹鄉"],
            "宜蘭縣": ["宜蘭市", "羅東鎮", "蘇澳鎮", "頭城鎮", "礁溪鄉", "壯圍鄉", "員山鄉", "冬山鄉", "五結鄉", "三星鄉", "大同鄉", "南澳鄉"],
            "花蓮縣": ["花蓮市", "鳳林鎮", "玉里鎮", "新城鄉", "吉安鄉", "壽豐鄉", "光復鄉", "豐濱鄉", "瑞穗鄉", "富里鄉", "秀林鄉", "萬榮鄉", "卓溪鄉"],
            "台東縣": ["台東市", "成功鎮", "關山鎮", "卑名鄉", "大武鄉", "太麻里鄉", "東河鄉", "長濱鄉", "鹿野鄉", "池上鄉", "綠島鄉", "延平鄉", "海端鄉", "達仁鄉", "金峰鄉", "蘭嶼鄉"],
            "澎湖縣": ["馬公市", "湖西鄉", "白沙鄉", "西嶼鄉", "望安鄉", "七美鄉"],
            "金門縣": ["金城鎮", "金沙鎮", "金湖鎮", "金寧鄉", "烈嶼鄉", "烏坵鄉"],
            "連江縣": ["南竿鄉", "北竿鄉", "莒光鄉", "東引鄉"]
        };

        const citySelect = document.getElementById('tw_city');
        const districtSelect = document.getElementById('tw_district');
        const detailInput = document.getElementById('detail_address');
        const hiddenAddressInput = document.getElementById('address');

        for (let city in twData) {
            let opt = document.createElement('option');
            opt.value = city;
            opt.innerText = city;
            citySelect.appendChild(opt);
        }

        function updateDistricts() {
            districtSelect.innerHTML = '<option value="">請選擇區域</option>';
            const selectedCity = citySelect.value;
            if (selectedCity && twData[selectedCity]) {
                twData[selectedCity].forEach(dist => {
                    let opt = document.createElement('option');
                    opt.value = dist;
                    opt.innerText = dist;
                    districtSelect.appendChild(opt);
                });
            }
            combineFinalAddress();
        }

        function combineFinalAddress() {
            const city = citySelect.value;
            const dist = districtSelect.value;
            const detail = detailInput.value.trim();
            hiddenAddressInput.value = (city && dist && detail) ? (city + dist + detail) : "";
        }

        detailInput.addEventListener('input', combineFinalAddress);
        districtSelect.addEventListener('change', combineFinalAddress);

        function togglePaymentPanel() {
            const method = document.querySelector('input[name="payment_method"]:checked').value;
            const codPanel = document.getElementById('cod_panel');
            const creditPanel = document.getElementById('credit_panel');

            // 💡 移除原本對 cc_name 的引用
            const ccFields = [document.getElementById('cc_num'), document.getElementById('card_expiry'), document.getElementById('cc_cvc')];

            if (method === 'credit_card') {
                codPanel.style.display = 'none';
                creditPanel.style.display = 'block';
                ccFields.forEach(f => {
                    if (f) f.required = true;
                });
            } else {
                codPanel.style.display = 'block';
                creditPanel.style.display = 'none';
                ccFields.forEach(f => {
                    if (f) f.required = false;
                });
            }
        }

        function syncCardNumber(input) {
            let num = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let parts = [];
            for (let i = 0; i < num.length; i += 4) {
                parts.push(num.substring(i, i + 4));
            }
            input.value = parts.join(' ');
            document.getElementById('mirror_number').innerText = input.value || '•••• •••• •••• ••••';
        }

        function formatExpiry(input) {
            let code = input.value.replace(/\D/g, '');
            input.value = code.length > 2 ? code.substring(0, 2) + '/' + code.substring(2, 4) : code;
            document.getElementById('mirror_date').innerText = input.value || 'MM/YY';
        }

        function validateExpiry(input) {
            let val = input.value;
            if (val === '') return;
            if (val.length < 5 || !val.includes('/')) {
                alert('❌ 有效日期格式不正確，請輸入 MM/YY（例如 05/26）');
                input.value = '';
                return;
            }
        }

        function handleFormSubmit(event) {
            combineFinalAddress();
            if (!hiddenAddressInput.value) {
                alert('❌ 請完整填寫台灣縣市與詳細收件地址！');
                event.preventDefault();
                return false;
            }
            return true;
        }

        function handleCreditCardPay() {
            combineFinalAddress();
            const fullAddress = hiddenAddressInput.value;

            if (!fullAddress) {
                alert('❌ 請先選擇寄送縣市、區域並填寫完整收件地址！');
                return;
            }

            const elNum = document.getElementById('cc_num');
            const elExpiry = document.getElementById('card_expiry');
            const elCvc = document.getElementById('cc_cvc');

            // 💡 驗證條件同步移除了 elName (持卡人姓名)
            if (!elNum || !elExpiry || !elCvc ||
                !elNum.value.trim() || !elExpiry.value.trim() || !elCvc.value.trim()) {
                alert('❌ 請完整填寫所有的信用卡欄位資訊！');
                return;
            }

            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.style.display = 'flex';
            }

            const orderId = "<?= $order_id ?>";
            const encodedAddress = encodeURIComponent(fullAddress);

            setTimeout(() => {
                const currentFile = window.location.pathname.split('/').pop();

                fetch(`${currentFile}?order_id=${orderId}&address=${encodedAddress}&action=process_credit_pay`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('網路回應不正常');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert('🎉 信用卡授權扣款成功！即將為您安排出貨。');
                            window.location.href = 'orders.php';
                        } else {
                            alert('❌ 付款失敗：' + (data.message || '銀行端拒絕交易'));
                            window.location.href = `checkout.php?order_id=${orderId}`;
                        }
                    })
                    .catch(error => {
                        alert('❌ 網路通訊異常或系統超時，請重新送出。');
                        if (overlay) overlay.style.display = 'none';
                    });
            }, 1500);
        }
    </script>
</body>

</html>