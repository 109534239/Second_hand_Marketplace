<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/login.php';

?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上架商品 | 二手交易平台</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/upload.css">
</head>

<body>
    <!-- 頂部導覽列 -->
    <?php include 'header.php'; ?>

    <!-- 主要內容區 -->
    <main>
        <div class="upload-container">
            <div class="form-header">
                <h2>📦 上架新寶物</h2>
                <p>填寫詳細資訊，讓你的寶物更快找到新主人！</p>
            </div>

            <form id="uploadForm" class="upload-form">

                <div class="form-group">
                    <label for="p-name">商品名稱</label>
                    <input type="text" id="p-name" placeholder="例如：iPhone 13 128G 藍色 九成新" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="p-category">商品類別</label>
                        <select id="p-category" required>
                            <option value="">請選擇類別</option>
                            <option value="3c">數位3C</option>
                            <option value="fashion">流行服飾</option>
                            <option value="game">遊戲動漫</option>
                            <option value="home">居家生活</option>
                            <option value="book">課本網路</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="p-price">價格 (TWD)</label>
                        <input type="number" id="p-price" placeholder="請輸入金額" min="0" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="p-stock">庫存數量</label>
                        <input type="number" id="p-stock" value="1" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="p-expiry">預計下架時間</label>
                        <input type="date" id="p-expiry" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="p-desc">商品描述</label>
                    <textarea id="p-desc" rows="6" placeholder="請詳細描述商品的狀況、規格、購買時間等..." required></textarea>
                </div>

                <div class="form-group">
                    <label>商品圖片</label>
                    <div class="image-upload-wrapper">
                        <input type="file" id="imageInput" accept="image/*" multiple hidden>
                        <div class="image-dropzone" onclick="document.getElementById('imageInput').click()">
                            <div class="upload-icon">📸</div>
                            <p>點擊或拖曳圖片至此 (最多5張)</p>
                        </div>
                        <div id="imagePreview" class="image-preview-grid"></div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="history.back()">取消上架</button>
                    <button type="submit" class="btn-action-main">確認發佈商品</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // 圖片預覽功能
        const imageInput = document.getElementById('imageInput');
        const imagePreview = document.getElementById('imagePreview');

        imageInput.addEventListener('change', function() {
            imagePreview.innerHTML = '';
            const files = Array.from(this.files);

            files.slice(0, 5).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `<img src="${e.target.result}">`;
                    imagePreview.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
        });
    </script>
</body>

</html>