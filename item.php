<?php
require_once __DIR__ . '/db.php';
session_start();

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

// 💡 取得商品單價，供 JavaScript 計算總價使用
$product_price = intval($product['price'] ?? 0);
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | 二手交易平台</title>
    <link rel="stylesheet" href="css/frontpage.css">
    <link rel="stylesheet" href="css/item.css">
    <style>
        /* 💡 這裡附帶總金額的精美樣式，你可以移到你的 css/item.css 裡面 */
        .total-price-wrapper {
            margin-left: auto;
            /* 讓總金額自動推到最右邊對齊 */
            font-size: 15px;
            color: #475569;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .total-price-display {
            font-size: 22px;
            font-weight: 800;
            color: #ff385c;
            /* 與價格同色系的高階粉紅 */
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <main class="item-container">
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

                <form action="create_order.php" method="POST" class="order-form" onsubmit="return handleOrderSubmit(event)">
                    <input type="hidden" name="item_id" value="<?= $product['id'] ?>">

                    <?php if (!$is_out_of_stock): ?>
                        <div class="quantity-section">
                            <span class="spec-label">購買數量</span>
                            <div class="quantity-counter">
                                <button type="button" class="qty-btn" onclick="changeQuantity(-1)">−</button>
                                <input type="number" id="purchase_qty" name="quantity" class="qty-input"
                                    value="1" min="1" max="<?= $inventory ?>"
                                    oninput="validateQuantity(this)"
                                    onblur="forceMinOne(this)">
                                <button type="button" class="qty-btn" onclick="changeQuantity(1)">+</button>
                            </div>

                            <div class="total-price-wrapper">
                                <span>總金額:</span>
                                <span class="total-price-display" id="total_price_show">$<?= number_format($product_price) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn-submit-order" <?= $is_out_of_stock ? 'disabled' : '' ?>>
                        <?= $is_out_of_stock ? '❌ 商品已售完' : '🛍️ 結帳' ?>
                    </button>
                </form>
            </div>

        </div>
    </main>

    <script>
        const maxInventory = <?= $inventory ?>;
        const itemPrice = <?= $product_price ?>; // 💡 讓 JavaScript 記住商品單價

        // 💡 核心功能：計算並即時渲染總金額與千分位
        function updateTotalPrice() {
            const qtyInput = document.getElementById('purchase_qty');
            const priceShow = document.getElementById('total_price_show');
            if (!qtyInput || !priceShow) return;

            let qty = parseInt(qtyInput.value) || 0; // 如果是空值暫時當 0 計算
            let total = qty * itemPrice;

            // 格式化為千分位 (例如: $1,250)
            priceShow.innerText = '$' + total.toLocaleString();
        }

        function checkButtonState() {
            const qtyInput = document.getElementById('purchase_qty');
            const submitBtn = document.querySelector('.btn-submit-order');
            if (!qtyInput || !submitBtn) return;

            let val = parseInt(qtyInput.value);

            if (isNaN(val) || val > maxInventory || val < 1) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = "0.5";
                submitBtn.style.cursor = "not-allowed";
            } else {
                submitBtn.disabled = false;
                submitBtn.style.opacity = "1";
                submitBtn.style.cursor = "pointer";
            }
        }

        function changeQuantity(amount) {
            const qtyInput = document.getElementById('purchase_qty');
            if (!qtyInput) return;

            let currentVal = parseInt(qtyInput.value) || 1;
            let newVal = currentVal + amount;

            if (newVal < 1) newVal = 1;
            if (newVal > maxInventory) newVal = maxInventory;

            qtyInput.value = newVal;

            // 💡 每次加減都要更新狀態與總金額
            checkButtonState();
            updateTotalPrice();
        }

        function validateQuantity(input) {
            let val = parseInt(input.value);

            if (isNaN(input.value) || input.value === "") {
                checkButtonState();
                updateTotalPrice(); // 打字刪光時也會同步歸 0
                return;
            }

            if (val < 1) {
                input.value = 1;
            } else if (val > maxInventory) {
                alert('抱歉，購買數量不能超過現有庫存！');
                input.value = maxInventory;
            }

            checkButtonState();
            updateTotalPrice(); // 💡 即時手動輸入時更新總金額
        }

        function forceMinOne(input) {
            let val = parseInt(input.value);
            if (isNaN(val) || val < 1) {
                input.value = 1;
            }
            checkButtonState();
            updateTotalPrice(); // 💡 滑鼠移開防呆校正後再算一次總金額
        }

        function handleOrderSubmit(event) {
            const qtyInput = document.getElementById('purchase_qty');
            if (!qtyInput) return true;

            let finalQty = parseInt(qtyInput.value);

            if (isNaN(finalQty) || finalQty < 1) {
                qtyInput.value = 1;
                finalQty = 1;
            }

            if (finalQty > maxInventory) {
                alert('購買數量不正確，請重新確認！');
                qtyInput.value = maxInventory;
                checkButtonState();
                updateTotalPrice();
                event.preventDefault();
                return false;
            }

            return true;
        }
    </script>
</body>

</html>