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
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | 二手交易平台</title>
    <link rel="stylesheet" href="css/frontpage.css">
    <link rel="stylesheet" href="css/item.css">
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
                                    value="1" min="1" max="<?= $inventory ?>" oninput="validateQuantity(this)">
                                <button type="button" class="qty-btn" onclick="changeQuantity(1)">+</button>
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
            checkButtonState();
        }

        function validateQuantity(input) {
            let val = parseInt(input.value);

            if (isNaN(val)) return; // 允許使用者暫時刪除數字

            if (val < 1) {
                input.value = 1;
            } else if (val > maxInventory) {
                alert('抱歉，購買數量不能超過現有庫存！');
                input.value = maxInventory;
            }
            checkButtonState();
        }

        // 💡 結帳按鈕點擊事件：驗證成功就 return true 讓表單送出到 create_order.php
        function handleOrderSubmit(event) {
            const qtyInput = document.getElementById('purchase_qty');
            if (!qtyInput) {
                event.preventDefault();
                return false;
            }

            let finalQty = parseInt(qtyInput.value);

            // 如果輸入框是空的或不合法，禁止送出
            if (isNaN(finalQty) || finalQty > maxInventory || finalQty < 1) {
                alert('購買數量不正確，請重新確認！');
                checkButtonState();
                event.preventDefault(); // 攔截不轉跳
                return false;
            }

            return true; // ✅ 驗證通過，放行表單送出
        }
    </script>
</body>

</html>