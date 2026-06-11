<?php
require_once __DIR__ . '/db.php';
session_start();

// 如果需要強制登入才能看商品，可以解開以下註解：
/*
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
*/

$db = getDbConnection();

// 1. 抓取網址後面的 id 參數
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // 2. 去資料庫查這個商品的所有詳細資訊
    $stmt = $db->prepare("SELECT * FROM public.item WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo "<script>alert('找不到該商品！'); window.location.href='frontpage.php';</script>";
        exit;
    }
} else {
    echo "<script>alert('無效的商品 ID！'); window.location.href='frontpage.php';</script>";
    exit;
}

// 庫存防呆判斷
$inventory = intval($product['inventory'] ?? 0);
$is_out_of_stock = ($inventory <= 0);
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | 二手交易平台</title>
    <link rel="stylesheet" href="css/frontpage.css">
    <style>
        /* ==========================================================================
           ✨ 商品詳情頁專屬高階質感排版
           ========================================================================== */
        body {
            background-color: #f8fafc;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            color: #1e293b;
        }

        .item-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            box-sizing: border-box;
        }

        /* 返回按鈕 */
        .btn-back {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: #64748b;
            font-size: 14.5px;
            font-weight: 600;
            margin-bottom: 25px;
            transition: color 0.2s;
        }

        .btn-back:hover {
            color: #ff385c;
        }

        /* 詳情頁主卡片（左右分欄） */
        .detail-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04),
                0 0 0 1px rgba(15, 23, 42, 0.01);
            display: flex;
            flex-wrap: wrap;
            /* 支援手機版斷行 */
            overflow: hidden;
        }

        /* 左側：商品大圖區 */
        .detail-gallery {
            flex: 1 1 500px;
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 450px;
            max-height: 600px;
            overflow: hidden;
        }

        .detail-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* 右側：商品資訊購買區 */
        .detail-info-panel {
            flex: 1 1 450px;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }

        /* 商品標題 */
        .item-title {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.4;
            margin: 0 0 20px 0;
        }

        /* 價格區塊 */
        .price-tag {
            font-size: 32px;
            font-weight: 800;
            color: #ff385c;
            margin-bottom: 30px;
            display: flex;
            align-items: baseline;
        }

        /* 分隔線 */
        .divider {
            height: 1px;
            background-color: #f1f5f9;
            margin-bottom: 25px;
        }

        /* 規格明細（庫存等） */
        .spec-group {
            margin-bottom: 35px;
        }

        .spec-item {
            display: flex;
            align-items: center;
            font-size: 15px;
            color: #475569;
            margin-bottom: 12px;
        }

        .spec-label {
            color: #94a3b8;
            width: 80px;
            font-weight: 500;
        }

        .badge-inventory {
            background-color: #f1f5f9;
            color: #1e293b;
            padding: 4px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13.5px;
        }

        /* 庫存告急或完售樣式 */
        .badge-inventory.danger {
            background-color: #fee2e2;
            color: #ef4444;
        }

        /* 送出訂單按鈕表單 */
        .order-form {
            margin-top: auto;
            /* 讓按鈕美美地貼在最下方 */
        }

        .btn-submit-order {
            width: 100%;
            padding: 16px 20px;
            background: linear-gradient(135deg, #ff385c 0%, #ff6040 100%);
            color: #ffffff;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(255, 56, 92, 0.25);
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit-order:hover {
            background: linear-gradient(135deg, #f42c50 0%, #f44e2b 100%);
            box-shadow: 0 6px 22px rgba(255, 56, 92, 0.4);
            transform: translateY(-2px);
        }

        /* 完售按鈕樣式 */
        .btn-submit-order:disabled {
            background: #cbd5e1 !important;
            color: #94a3b8 !important;
            box-shadow: none !important;
            cursor: not-allowed;
            transform: none !important;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <main class="item-container">
        <a href="frontpage.php" class="btn-back">🔙 返回商品列表</a>

        <div class="detail-card">

            <div class="detail-gallery">
                <img src="<?= !empty($product['img']) ? htmlspecialchars($product['img']) : 'https://via.placeholder.com/600x600/f1f5f9/64748b?text=無圖片' ?>"
                    alt="<?= htmlspecialchars($product['name']) ?>" class="detail-img">
            </div>

            <div class="detail-info-panel">
                <h1 class="item-title"><?= htmlspecialchars($product['name']) ?></h1>

                <div class="price-tag">
                    <span>$<?= number_format($product['price']) ?></span>
                </div>

                <div class="divider"></div>

                <div class="spec-group">
                    <div class="spec-item">
                        <span class="spec-label">商品庫存</span>
                        <span class="badge-inventory <?= $is_out_of_stock ? 'danger' : '' ?>">
                            <?= $is_out_of_stock ? '已售完' : $inventory . ' 件' ?>
                        </span>
                    </div>
                </div>

                <form action="create_order.php" method="POST" class="order-form">
                    <input type="hidden" name="item_id" value="<?= $product['id'] ?>">

                    <?php if (!$is_out_of_stock): ?>
                        <div class="quantity-section">
                            <span class="spec-label">購買數量</span>
                            <div class="quantity-counter">
                                <button type="button" class="qty-btn" onclick="changeQuantity(-1)">−</button>
                                <input type="number" id="purchase_qty" name="quantity" class="qty-input"
                                    value="1" min="1" max="<?= $inventory ?>" onchange="validateQuantity(this)">
                                <button type="button" class="qty-btn" onclick="changeQuantity(1)">+</button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn-submit-order" <?= $is_out_of_stock ? 'disabled' : '' ?>>
                        <?= $is_out_of_stock ? '❌ 商品已售完' : '🛍️ 立即送出訂單' ?>
                    </button>
                </form>
            </div>

        </div>
    </main>

    <script>
        const maxInventory = <?= $inventory ?>;

        function changeQuantity(amount) {
            const qtyInput = document.getElementById('purchase_qty');
            if (!qtyInput) return;

            let currentVal = parseInt(qtyInput.value) || 1;
            let newVal = currentVal + amount;

            // 限制不能小於 1，且不能大於庫存
            if (newVal < 1) newVal = 1;
            if (newVal > maxInventory) newVal = maxInventory;

            qtyInput.value = newVal;
        }

        function validateQuantity(input) {
            let val = parseInt(input.value);

            // 如果輸入空值或非數字，強制回歸 1
            if (isNaN(val) || val < 1) {
                input.value = 1;
            } else if (val > maxInventory) {
                // 超過庫存，強制等於最大庫存並提示
                alert('抱歉，購買數量不能超過現有庫存！');
                input.value = maxInventory;
            }
        }
    </script>
</body>

</html>