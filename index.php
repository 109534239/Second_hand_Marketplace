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
$db = getDbConnection();

// 1. 取得篩選參數
$cat_id = isset($_GET['cat']) ? intval($_GET['cat']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

// 2. 建立基礎 SQL (關聯 item 與 category 資料表)
// 假設 item 資料表有這些欄位：p_name, price, condition, location, img_url
$sql = "SELECT i.*, c.category as category_name 
        FROM public.item i
        LEFT JOIN public.category c ON i.category_id = c.id
        WHERE 1=1"; // 方便後面接 AND

$params = [];

// 3. 判斷是否有分類篩選
if ($cat_id) {
    $sql .= " AND i.category_id = :cat_id";
    $params[':cat_id'] = $cat_id;
}

// 4. 判斷是否有搜尋關鍵字
if ($search) {
    $sql .= " AND (i.p_name ILIKE :search OR i.p_desc ILIKE :search)"; // ILIKE 是 PostgreSQL 不分大小寫搜尋
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY i.id DESC"; // 最新上架排前面

try {
    $stmt_items = $db->prepare($sql);
    $stmt_items->execute($params);
    $products = $stmt_items->fetchAll();
} catch (PDOException $e) {
    error_log('Product query failed: ' . $e->getMessage());
    $products = [];
}

// 找出當前選擇的分類名稱與圖示
$current_cat_name = "";
$current_cat_icon = "📂";
if ($cat_id) {
    foreach ($categories as $cat) {
        if ($cat['id'] == $cat_id) {
            $current_cat_name = $cat['category'];
            $current_cat_icon = $iconMap[$current_cat_name] ?? '📂';
            break;
        }
    }
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
            margin: 2% auto;
            /* 稍微往上一點 */
            padding: 20px;
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

        .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #64748b;
        }

        .link-register {
            color: #ff385c;
            font-weight: 700;
            cursor: pointer;
            text-decoration: underline;
            transition: 0.3s;
        }

        .link-register:hover {
            color: #e33150;
            text-shadow: 0 0 10px rgba(255, 56, 92, 0.1);
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
                    <select id="category-dropdown" class="category-select-inline" onchange="handleCategoryChange(this)">
                        <option value="" selected>📂 全部商品</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="?cat=<?= $category['id'] ?>">
                                <?= htmlspecialchars($category['category'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="search-divider"></div>

                    <input type="text" id="mainSearchInput" class="search-input-main" placeholder="搜尋二手寶物...">

                    <button type="button" class="search-btn-main" onclick="executeSearch()">搜尋</button>
                </div>
            </div>
        </div>

        <!-- 商品推薦區塊 -->
        <div class="section-title">
            <h2>
                <?php
                if ($search) {
                    echo "🔍 搜尋「" . htmlspecialchars($search) . "」的結果";
                } elseif ($cat_id && !empty($current_cat_name)) {
                    echo $current_cat_icon . " 正在查看「" . htmlspecialchars($current_cat_name) . "」";
                } else {
                    echo "🔥 最新上架的寶物";
                }
                ?>
            </h2>
            <div class="section-underline"></div>
        </div>

        <div class="product-grid">
            <?php if (empty($products)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #888;">
                    <p>找不到對應的寶物，換個關鍵字試試看吧！</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $p): ?>
                    <div class="product-card">
                        <img src="<?= !empty($p['img_url']) ? htmlspecialchars($p['img_url']) : 'https://via.placeholder.com/300x300/f1f5f9/64748b?text=無圖片' ?>"
                            alt="商品圖片" class="product-img">

                        <div class="product-info">
                            <div class="product-title"><?= htmlspecialchars($p['p_name']) ?></div>
                            <div class="product-price">$<?= number_format($p['price']) ?></div>
                            <div class="product-footer">
                                <span class="product-condition"><?= htmlspecialchars($p['condition'] ?? '二手') ?></span>
                                <span class="product-location"><?= htmlspecialchars($p['location'] ?? '台灣') ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
                <button class="auth-tab active" id="tab-login" onclick="switchTab('login')">買/賣家登入</button>
                <button class="auth-tab" id="tab-admin" onclick="switchTab('admin')">管理員登入</button>
            </div>

            <form id="loginForm" action="login.php" method="POST">
                <input type="hidden" name="action" value="login">
                <input type="text" name="account" placeholder="買/賣家帳號" required>
                <input type="password" name="password" placeholder="密碼" required>
                <button type="submit" class="btn-submit-login">立即登入</button>
                <div class="auth-footer">
                    還沒有帳號嗎？ <span class="link-register" onclick="switchTab('register')">立即加入會員</span>
                </div>

            </form>

            <form id="adminLoginForm" action="login.php" method="POST" style="display:none;">
                <input type="hidden" name="action" value="login">
                <input type="text" name="account" placeholder="管理員帳號" required>
                <input type="password" name="password" placeholder="管理員密碼" required>
                <button type="submit" class="btn-submit-login" style="background: linear-gradient(135deg, #475569, #1e293b);">管理員驗證</button>
            </form>

            <form id="registerForm" action="login.php" method="POST" style="display:none;">
                <input type="hidden" name="action" value="register">
                <input type="text" name="account" placeholder="設定帳號" required>
                <input type="password" name="password" placeholder="設定密碼" required>
                <input type="text" name="name" placeholder="真實姓名" required>

                <input type="email" name="email" placeholder="電子郵件 (example@mail.com)" required>

                <input type="tel" name="phoneno" placeholder="手機號碼 (0912345678)"
                    pattern="09[0-9]{8}" title="請輸入正確的 10 位手機號碼，例如: 0912345678" required>

                <div class="role-selection">
                    <p>註冊身份</p>
                    <div class="role-group">
                        <label class="role-label"><input type="radio" name="role" value="1" checked> 買家</label>
                        <label class="role-label"><input type="radio" name="role" value="2"> 賣家</label>
                        <!-- <label class="role-label"><input type="radio" name="role" value="3"> 管理員</label> -->
                    </div>
                </div>

                <button type="submit" class="btn-submit-login">完成註冊</button>
            </form>
        </div>
    </div>

    <script>
        function handleCategoryChange(selectElement) {
            const targetUrl = selectElement.value;
            if (targetUrl !== "") {
                window.location.href = targetUrl;
            }
        }

        // 頁面載入完成後執行
        window.addEventListener('DOMContentLoaded', (event) => {
            const urlParams = new URLSearchParams(window.location.search);
            const categoryDropdown = document.getElementById('category-dropdown');

            // 如果網址裡有 cat 參數
            if (urlParams.has('cat')) {
                // 等待 1 秒後將下拉選單歸位到 "全部商品" (index 0)
                setTimeout(() => {
                    if (categoryDropdown) categoryDropdown.selectedIndex = 0;
                }, 1000);
            }
        });

        function executeSearch() {
            const keyword = document.getElementById('mainSearchInput').value.trim();
            if (keyword !== "") {
                window.location.href = "index.php?search=" + encodeURIComponent(keyword);
            }
        }

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

        function switchTab(type) {
    const loginForm = document.getElementById('loginForm');
    const adminForm = document.getElementById('adminLoginForm');
    const regForm = document.getElementById('registerForm');

    const tabs = document.querySelectorAll('.auth-tab');
    tabs.forEach(t => t.classList.remove('active'));

    // 先隱藏所有表單
    loginForm.style.display = 'none';
    adminForm.style.display = 'none';
    regForm.style.display = 'none';

    if (type === 'login') {
        loginForm.style.display = 'block';
        const tab = document.getElementById('tab-login');
        if(tab) tab.classList.add('active');
    } else if (type === 'admin') {
        adminForm.style.display = 'block';
        const tab = document.getElementById('tab-admin');
        if(tab) tab.classList.add('active');
    } else if (type === 'register') { // 確保型態正確
        regForm.style.display = 'block';
        // 如果你有給立即加入會員的按鈕或標籤加 id="tab-register" 再加這行，若沒有則免
        const tab = document.getElementById('tab-register');
        if(tab) tab.classList.add('active');
    }
}

// --- 自動觸發彈窗與訊息 (改為讀取 SESSION) ---
<?php if (!empty($_SESSION['auth_message'])): ?>
    // 確保在 DOM 載入後執行，避免抓不到 modal 元素
    window.addEventListener('DOMContentLoaded', (event) => {
        alert("<?php echo $_SESSION['auth_message']; ?>");
        
        const modal = document.getElementById("loginModal");
        if (modal) {
            modal.style.display = "block";
        }
        
        switchTab("<?php echo $_SESSION['target_tab']; ?>");
    });
    
    <?php 
    // 顯示完後，立即把訊息從 Session 清除，避免重整網頁重覆跳出
    unset($_SESSION['auth_message']);
    unset($_SESSION['target_tab']);
    ?>
<?php endif; ?>
    </script>
</body>

</html>