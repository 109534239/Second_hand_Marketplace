<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/login.php';

$iconMap = [
    '時尚服飾與精品' => '👗',
    '3D數位與電子產品' => '💻',
    '遊戲與動漫週邊' => '🎮',
    '書籍、音樂與影音娛樂' => '📚',
    '居家生活與家電' => '🏠',
    '運動、戶外與交通工具' => '🚴',
    '母嬰與兒童用品' => '🧸',
    '收藏品、古董與藝術' => '🎨',
];

try {
    $stmt = getDbConnection()->query('SELECT id, category FROM public.category ORDER BY id');
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Category query failed: ' . $e->getMessage());
    $categories = [];
}

if (empty($categories)) {
    $categories = [
        ['category' => '時尚服飾與精品'],
        ['category' => '3D數位與電子產品'],
        ['category' => '遊戲與動漫週邊'],
        ['category' => '書籍、音樂與影音娛樂'],
        ['category' => '居家生活與家電'],
        ['category' => '運動、戶外與交通工具'],
        ['category' => '母嬰與兒童用品'],
        ['category' => '收藏品、古董與藝術'],
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎉 二手交易平台</title>
    <link rel="stylesheet" href="css/index.css">
    <style>
        /* ==========================================================================
   主要內容區
   ========================================================================== */
        main {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 30px;
        }

        /* 🚀 現代化搜尋與分類組合框 (Combined Search Bar) */
        .main-search-wrapper {
            margin: 40px 0 50px 0;
            display: flex;
            justify-content: center;
        }

        /* 1. 統一容器寬度與對齊 */
        .control-panel {
            display: flex;
            justify-content: center;
            margin-bottom: 60px;
            /* 增加與下方商品的距離，產生呼吸感 */
        }

        /* 2. 讓搜尋框與商品標題「視覺對齊」 */
        .search-combined-bar {
            display: flex;
            align-items: center;
            background: white;
            width: 100vw;
            max-width: 800px;
            /* 固定一個舒服的寬度 */
            height: 60px;
            border-radius: 15px;
            /* 改為稍微方潤的圓角，避免過於像藥丸 */
            padding: 0 5px 0 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }

        /* 3. 分類選單樣式微調 */
        .category-select-inline {
            border: none;
            outline: none;
            padding: 0 15px;
            font-size: 15px;
            font-weight: 600;
            color: #2d3748;
            background: transparent;
            cursor: pointer;
            width: 180px;
            height: 100%;
        }

        /* 4. 分割線 */
        .search-divider {
            width: 1px;
            height: 24px;
            background-color: #edf2f7;
        }

        /* 5. 輸入框 */
        .search-input-main {
            flex: 1;
            border: none;
            outline: none;
            padding: 0 20px;
            font-size: 16px;
            background: transparent;
        }

        /* 6. 搜尋按鈕樣式升級 */
        .search-btn-main {
            background: #ff385c;
            color: white;
            border: none;
            padding: 0 30px;
            height: 50px;
            /* 比外框矮一點點，產生層次感 */
            margin: 5px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .search-btn-main:hover {
            background: #e31c5f;
        }

        /* 7. 優化標題視覺 */
        .section-header {
            margin-bottom: 30px;
            text-align: left;
        }

        .section-title {
            font-size: 22px;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 8px;
        }

        .section-underline {
            width: 50px;
            height: 4px;
            background: #ff385c;
            border-radius: 2px;
        }

        /* ==========================================================================
   🖼️ 登入/註冊 Modal (彈窗) 優化
   ========================================================================== */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            /* 稍微往上一點 */
            padding: 40px;
            border-radius: 30px;
            width: 520px;
            /* 加寬處理，解決跑版 */
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: modalSlideIn 0.4s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(40px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .close-modal {
            position: absolute;
            top: 25px;
            right: 25px;
            font-size: 28px;
            cursor: pointer;
            color: #cbd5e0;
            transition: 0.2s;
        }

        .close-modal:hover {
            color: #ff385c;
        }

        /* --- 頁籤切換 --- */
        .auth-tabs {
            display: flex;
            background: #f1f5f9;
            padding: 6px;
            border-radius: 16px;
            margin-bottom: 30px;
        }

        .auth-tab {
            flex: 1;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            background: transparent;
            color: #64748b;
            transition: 0.3s;
            border-radius: 12px;
        }

        .auth-tab.active {
            background: white;
            color: #ff385c;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        /* --- 表單輸入 --- */
        .modal-content input[type="text"],
        .modal-content input[type="password"],
        .modal-content input[type="email"],
        .modal-content input[type="tel"] {
            width: 100%;
            padding: 14px 20px;
            margin-bottom: 15px;
            border: 2px solid #f1f5f9;
            border-radius: 14px;
            outline: none;
            font-size: 15px;
            background: #f8fafc;
            transition: 0.3s;
        }

        .modal-content input:focus {
            border-color: #ff385c;
            background: white;
            box-shadow: 0 0 0 4px rgba(255, 56, 92, 0.1);
        }

        /* --- 註冊身份選擇器 (優化重點) --- */
        .role-selection {
            margin-bottom: 25px;
        }

        .role-selection p {
            font-size: 15px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 12px;
            margin-left: 5px;
        }

        .role-group {
            display: flex;
            gap: 15px;
        }

        .role-label {
            flex: 1;
            position: relative;
            cursor: pointer;
            background: #f8fafc;
            border: 2px solid #f1f5f9;
            padding: 12px;
            border-radius: 14px;
            text-align: center;
            font-weight: 600;
            color: #64748b;
            transition: 0.3s;
        }

        .role-label input {
            display: none;
        }

        /* 隱藏原本圓圈 */
        .role-label:hover {
            background: #fff5f7;
        }

        /* 選中時的樣式 */
        .role-label:has(input:checked) {
            border-color: #ff385c;
            background: #fff5f7;
            color: #ff385c;
        }

        /* --- 提交按鈕 --- */
        .btn-submit-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ff385c, #ff7b29);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 800;
            font-size: 17px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-submit-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 56, 92, 0.4);
        }
    </style>
</head>

<body>
    <!-- 頂部導覽列 -->
    <?php include 'header.php'; ?>

    <!-- 主要內容區 -->
    <main>
        <!-- 分類標籤列 -->
        <div class="control-panel">
            <div class="main-search-wrapper">
                <div class="search-combined-bar">
                    <select id="category-dropdown" class="category-select-inline" onchange="location = this.value;">
                        <option value="">📂 全部商品</option>
                        <?php foreach ($categories as $category):
                            $name = $category['category'] ?? '';
                            $icon = $iconMap[$name] ?? '📦';
                        ?>
                            <option value="?cat=<?= $category['id'] ?>">
                                <?= htmlspecialchars($icon . ' ' . $name, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="search-divider"></div>

                    <input type="text" class="search-input-main" placeholder="搜尋二手寶物...例如：iPhone、Switch">

                    <button type="button" class="search-btn-main">搜尋</button>
                </div>
            </div>
        </div>

        <!-- 商品推薦區塊 -->
        <div class="section-title">
            <h2>🔥 最新上架的寶物</h2>
        </div>

        <!-- 商品網格 -->
        <div class="product-grid">

            <!-- 商品卡片 1 -->
            <div class="product-card">
                <img src="https://via.placeholder.com/300x300/ff385c/ffffff?text=iPhone+13" alt="商品圖片"
                    class="product-img">
                <div class="product-info">
                    <div class="product-title">九成新 iPhone 13 128G 藍色 功能皆正常 無傷</div>
                    <div class="product-price">$12,500</div>
                    <div class="product-footer">
                        <span class="product-condition">95新</span>
                        <span class="product-location">台北市</span>
                    </div>
                </div>
            </div>

            <!-- 商品卡片 2 -->
            <div class="product-card">
                <img src="https://via.placeholder.com/300x300/ff385c/ffffff?text=Switch" alt="商品圖片" class="product-img">
                <div class="product-info">
                    <div class="product-title">Nintendo Switch 電力加強版 附兩個遊戲片（動森、瑪利歐賽車）</div>
                    <div class="product-price">$5,500</div>
                    <div class="product-footer">
                        <span class="product-condition">二手</span>
                        <span class="product-location">台中市</span>
                    </div>
                </div>
            </div>

            <!-- 商品卡片 3 -->
            <div class="product-card">
                <img src="https://via.placeholder.com/300x300/ff385c/ffffff?text=Book" alt="商品圖片" class="product-img">
                <div class="product-info">
                    <div class="product-title">【大學課本】微積分 Calculus 經典原文書 第九版</div>
                    <div class="product-price">$400</div>
                    <div class="product-footer">
                        <span class="product-condition">微劃記</span>
                        <span class="product-location">台南市</span>
                    </div>
                </div>
            </div>

            <!-- 商品卡片 4 -->
            <div class="product-card">
                <img src="https://via.placeholder.com/300x300/ff385c/ffffff?text=Jacket" alt="商品圖片" class="product-img">
                <div class="product-info">
                    <div class="product-title">古著工裝外套 寬鬆版型 男女皆可穿 僅試穿</div>
                    <div class="product-price">$680</div>
                    <div class="product-footer">
                        <span class="product-condition">全新</span>
                        <span class="product-location">高雄市</span>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- 頁尾 -->
    <footer>
        <p>© 2026 二手交易平台版權所有</p>
    </footer>

    <!-- 登入彈出視窗 -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeModal">&times;</span>

            <div class="auth-tabs">
                <button class="auth-tab active" id="tab-login" onclick="switchTab('login')">登入</button>
                <button class="auth-tab" id="tab-register" onclick="switchTab('register')">註冊</button>
            </div>

            <form id="loginForm" action="login.php" method="POST">
                <input type="hidden" name="action" value="login">
                <input type="text" name="account" placeholder="帳號" required>
                <input type="password" name="password" placeholder="密碼" required>
                <button type="submit" class="btn-submit-login">立即登入</button>
            </form>

            <form id="registerForm" action="login.php" method="POST" style="display:none;">
                <input type="hidden" name="action" value="register">
                <input type="text" name="account" placeholder="帳號" required>
                <input type="password" name="password" placeholder="密碼" required>
                <input type="text" name="name" placeholder="真實姓名" required>
                <input type="email" name="email" placeholder="電子郵件" required>
                <input type="tel" name="phoneno" placeholder="電話號碼" required>

                <div class="role-selection">
                    <p>註冊身份</p>
                    <div class="role-group">
                        <label class="role-label">
                            <input type="radio" name="role" value="1" checked> 🙋 我是買家
                        </label>
                        <label class="role-label">
                            <input type="radio" name="role" value="2"> 🏪 我是賣家
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-submit-login">完成註冊</button>
            </form>
        </div>
    </div>

    <script>
        // 控制彈窗顯示與關閉
        const modal = document.getElementById("loginModal");
        const btn = document.getElementById("loginBtn");
        const span = document.getElementById("closeModal");

        if (btn) {
            btn.onclick = function() {
                modal.style.display = "block";
            }
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // 切換 登入/註冊 頁籤
        function switchTab(type) {
            const loginForm = document.getElementById('loginForm');
            const regForm = document.getElementById('registerForm');
            const tabLogin = document.getElementById('tab-login');
            const tabReg = document.getElementById('tab-register');

            if (type === 'login') {
                loginForm.style.display = 'block';
                regForm.style.display = 'none';
                tabLogin.classList.add('active');
                tabReg.classList.remove('active');
            } else {
                loginForm.style.display = 'none';
                regForm.style.display = 'block';
                tabReg.classList.add('active');
                tabLogin.classList.remove('active');
            }
        }
    </script>
</body>

</html>