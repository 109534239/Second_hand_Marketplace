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
                    <label>📍 寄送地址</label>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                        <select id="tw_city" class="form-control" required onchange="updateDistricts()">
                            <option value="">請選擇縣市</option>
                        </select>
                        <select id="tw_district" class="form-control" required>
                            <option value="">請選擇區域</option>
                        </select>
                    </div>

                    <input type="text" id="detail_address" class="form-control" placeholder="請輸入剩下的路名、巷弄、門牌號碼" required>

                    <input type="hidden" id="address" name="address" value="">
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
    <script>
        // 🇹🇼 台灣各縣市鄉鎮市區數據清單
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
            "台東縣": ["台東市", "成功鎮", "關山鎮", "卑南鄉", "大武鄉", "太麻里鄉", "東河鄉", "長濱鄉", "鹿野鄉", "池上鄉", "綠島鄉", "延平鄉", "海端鄉", "達仁鄉", "金峰鄉", "蘭嶼鄉"],
            "澎湖縣": ["馬公市", "湖西鄉", "白沙鄉", "西嶼鄉", "望安鄉", "七美鄉"],
            "金門縣": ["金城鎮", "金沙鎮", "金湖鎮", "金寧鄉", "烈嶼鄉", "烏坵鄉"],
            "連江縣": ["南竿鄉", "北竿鄉", "莒光鄉", "東引鄉"]
        };

        // 初始化：把所有縣市丟進第一個下拉選單
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

        // 當選了縣市，連動更新鄉鎮市區選單
        function updateDistricts() {
            districtSelect.innerHTML = '<option value="">請選擇區域</option>'; // 先清空舊的
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

        // 核心組裝：把「縣市 + 區域 + 詳細地址」綁在一起，即時塞進 hidden input
        function combineFinalAddress() {
            const city = citySelect.value;
            const dist = districtSelect.value;
            const detail = detailInput.value.trim();

            if (city && dist && detail) {
                hiddenAddressInput.value = city + dist + detail;
            } else {
                hiddenAddressInput.value = ""; // 如果沒填滿，後端會觸發 required 阻擋
            }
        }

        // 監聽詳細地址打字、區域更換時，自動同步更新
        detailInput.addEventListener('input', combineFinalAddress);
        districtSelect.addEventListener('change', combineFinalAddress);

        // 貼心連動：如果表單準備送出，做最後一次確認組裝
        document.querySelector('form').addEventListener('submit', function(e) {
            combineFinalAddress();
            if (!hiddenAddressInput.value) {
                alert('❌ 請完整選擇縣市、區域並填寫詳細地址！');
                e.preventDefault();
            }
        });
    </script>
</body>

</html>