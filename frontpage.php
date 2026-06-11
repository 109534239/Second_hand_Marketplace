<?php
require_once __DIR__ . '/db.php';
session_start();

// 💡 關鍵修正：如果沒有登入（Session 沒東西），就強制跳轉到登入頁，不要留在本頁
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$db = getDbConnection();

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
    $stmt = $db->query('SELECT id, category FROM public.category ORDER BY id');
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

// 1. 取得篩選參數
$cat_id = isset($_GET['cat']) ? intval($_GET['cat']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

// 2. 建立基礎 SQL (將 c.category 分類名稱也選進來)
$sql = "SELECT i.*, c.category as category_name 
        FROM public.item i
        LEFT JOIN public.category c ON i.category_id = c.id
        WHERE 1=1";

$params = [];

if ($cat_id) {
    $sql .= " AND i.category_id = :cat_id";
    $params[':cat_id'] = $cat_id;
}

if ($search) {
    // 💡 終極優化：同時搜尋「商品名稱(i.name)」與「分類名稱(c.category)」
    // 這樣一來，搜尋「嬰」或「嬰兒」，就會因為它屬於「母嬰與兒童用品」而被神奇地搜尋出來！
    $sql .= " AND (i.name ILIKE :search OR c.category ILIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY i.id DESC";

try {
    $stmt_items = $db->prepare($sql);
    $stmt_items->execute($params);
    $products = $stmt_items->fetchAll();
} catch (PDOException $e) {
    error_log('Product query failed: ' . $e->getMessage());
    $products = [];
}

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
    <link rel="stylesheet" href="css/frontpage.css">
    <style>
        main {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 30px;
        }

        .main-search-wrapper {
            margin: 40px 0 50px 0;
            display: flex;
            justify-content: center;
        }

        .control-panel {
            display: flex;
            justify-content: center;
            margin-bottom: 60px;
        }

        .search-combined-bar {
            display: flex;
            align-items: center;
            background: white;
            width: 100vw;
            max-width: 800px;
            height: 60px;
            border-radius: 15px;
            padding: 0 5px 0 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }

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

        .search-divider {
            width: 1px;
            height: 24px;
            background-color: #edf2f7;
        }

        .search-input-main {
            flex: 1;
            border: none;
            outline: none;
            padding: 0 20px;
            font-size: 16px;
            background: transparent;
        }

        .search-btn-main {
            background: #ff385c;
            color: white;
            border: none;
            padding: 0 30px;
            height: 50px;
            margin: 5px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .search-btn-main:hover {
            background: #e31c5f;
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
            margin-bottom: 30px;
        }

        /* 商品網格 */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 30px;
        }

        .product-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
            border-radius: 20px;
        }

        .product-card {
            background-color: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.02), 0 2px 6px rgba(15, 23, 42, 0.02);
            transition: transform 0.35s cubic-bezier(0.215, 0.610, 0.355, 1), box-shadow 0.35s ease-out;
            border: 1px solid #f1f5f9;
            position: relative;
        }

        .product-card-link:hover .product-card {
            transform: translateY(-8px);
            box-shadow: 0 20px 35px -10px rgba(15, 23, 42, 0.12), 0 10px 20px -5px rgba(255, 56, 92, 0.03);
            border-color: rgba(255, 56, 92, 0.15);
        }

        .product-img {
            width: 100%;
            height: 240px;
            object-fit: cover;
            background-color: #f8fafc;
            transition: transform 0.5s ease;
        }

        .product-card-link:hover .product-img {
            transform: scale(1.04);
        }

        .product-info {
            padding: 20px;
        }

        /* 💡 修正排版：標題與價格上下分開，避免揉在框裡被擠扁 */
        .product-title {
            font-size: 15.5px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e293b;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.45;
            height: 45px;
        }

        .product-price {
            font-size: 22px;
            color: #ff385c;
            font-weight: 800;
            margin-bottom: 12px;
            display: flex;
            align-items: baseline;
            letter-spacing: -0.5px;
        }

        /* 卡片底部小標籤區 */
        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            padding-top: 12px;
            border-top: 1px solid #f1f5f9;
        }

        /* 日系分類小標籤 */
        .product-cat-tag {
            background-color: #f1f5f9;
            color: #64748b;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <main>
        <div class="control-panel">
            <div class="main-search-wrapper">
                <div class="search-combined-bar">
                    <select id="category-dropdown" class="category-select-inline" onchange="handleCategoryChange(this)">
                        <option value="frontpage.php" <?= !$cat_id ? 'selected' : '' ?>>📂 全部商品</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="?cat=<?= $category['id'] ?>" <?= $cat_id == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['category'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="search-divider"></div>

                    <input type="text" id="mainSearchInput" class="search-input-main"
                        placeholder="搜尋二手寶物..."
                        value="<?= htmlspecialchars($search ?? '') ?>"
                        onkeydown="if(event.key==='Enter') executeSearch()">

                    <button type="button" class="search-btn-main" onclick="executeSearch()">搜尋</button>
                </div>
            </div>
        </div>

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
                    <a href="item.php?id=<?= $p['id'] ?>" class="product-card-link">
                        <div class="product-card">
                            <img src="<?= !empty($p['img']) ? htmlspecialchars($p['img']) : 'https://via.placeholder.com/300x300/f1f5f9/64748b?text=無圖片' ?>"
                                alt="商品圖片" class="product-img">

                            <div class="product-info">
                                <div class="product-title"><?= htmlspecialchars($p['name']) ?></div>
                                <div class="product-price">$<?= number_format($p['price']) ?></div>

                                <div class="product-footer">
                                    <span class="product-cat-tag">
                                        <?= htmlspecialchars($p['category_name'] ?? '未分類') ?>
                                    </span>
                                    <span style="color: #94a3b8; font-size:11px;">查看詳情 →</span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function handleCategoryChange(selectElement) {
            const targetUrl = selectElement.value;
            if (targetUrl !== "") {
                window.location.href = targetUrl;
            }
        }

        function executeSearch() {
            const keyword = document.getElementById('mainSearchInput').value.trim();
            const urlParams = new URLSearchParams(window.location.search);
            const cat = urlParams.get('cat');

            let targetUrl = "frontpage.php?";
            if (cat) {
                targetUrl += "cat=" + cat + "&";
            }

            if (keyword !== "") {
                targetUrl += "search=" + encodeURIComponent(keyword);
                window.location.href = targetUrl;
            } else {
                window.location.href = cat ? "frontpage.php?cat=" + cat : "frontpage.php";
            }
        }
    </script>
</body>

</html>