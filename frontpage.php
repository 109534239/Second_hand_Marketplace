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
        ['id' => 1, 'category' => '時尚服飾與精品'],
        ['id' => 2, 'category' => '3D數位與電子產品'],
        ['id' => 3, 'category' => '遊戲與動漫週邊'],
        ['id' => 4, 'category' => '書籍、音樂與影音娛樂'],
        ['id' => 5, 'category' => '居家生活與家電'],
        ['id' => 6, 'category' => '運動、戶外與交通工具'],
        ['id' => 7, 'category' => '母嬰與兒童用品'],
        ['id' => 8, 'category' => '收藏品、古董與藝術'],
    ];
}

// 💡 前端即時篩選優化：一次撈出全部商品，交由瀏覽器 JavaScript 來秒速過濾，不再重新整理網頁
$sql = "SELECT i.*, c.category as category_name 
        FROM public.item i
        LEFT JOIN public.category c ON i.category_id = c.id
        ORDER BY i.id DESC";

try {
    $stmt_items = $db->query($sql);
    $products = $stmt_items->fetchAll();
} catch (PDOException $e) {
    error_log('Product query failed: ' . $e->getMessage());
    $products = [];
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

        /* 將原本按鈕樣式改為清除/重設按鈕 */
        .search-btn-main {
            background: #64748b;
            color: white;
            border: none;
            padding: 0 20px;
            height: 50px;
            margin: 5px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .search-btn-main:hover {
            background: #475569;
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

        .product-title {
            font-size: 15.5px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e293b;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            -webkit-overflow: hidden;
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

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            padding-top: 12px;
            border-top: 1px solid #f1f5f9;
        }

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
                    <select id="category-dropdown" class="category-select-inline">
                        <option value="all" data-icon="📂" data-name="全部商品">📂 全部商品</option>
                        <?php foreach ($categories as $category): ?>
                            <?php
                            $c_name = $category['category'];
                            $c_icon = $iconMap[$c_name] ?? '📂';
                            ?>
                            <option value="<?= $category['id'] ?>" data-icon="<?= $c_icon ?>" data-name="<?= htmlspecialchars($c_name) ?>">
                                <?= $c_icon ?> <?= htmlspecialchars($c_name, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="search-divider"></div>

                    <input type="text" id="mainSearchInput" class="search-input-main" placeholder="搜尋二手寶物...">

                    <button type="button" id="resetBtn" class="search-btn-main">重設</button>
                </div>
            </div>
        </div>

        <div class="section-title">
            <h2 id="dynamicTitle">🔥 最新上架的寶物</h2>
            <div class="section-underline"></div>
        </div>

        <div class="product-grid" id="productGrid">
            <div id="noResults" style="grid-column: 1/-1; text-align: center; padding: 50px; color: #888; display: none;">
                <p>找不到對應的寶物，換個關鍵字試試看吧！</p>
            </div>

            <?php if (!empty($products)): ?>
                <?php foreach ($products as $p): ?>
                    <a href="item.php?id=<?= $p['id'] ?>"
                        class="product-card-link product-item-card"
                        data-catid="<?= $p['category_id'] ?>"
                        data-title="<?= htmlspecialchars(mb_strtolower($p['name'])) ?>"
                        data-catname="<?= htmlspecialchars(mb_strtolower($p['category_name'] ?? '未分類')) ?>">
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
        const searchInput = document.getElementById('mainSearchInput');
        const categoryDropdown = document.getElementById('category-dropdown');
        const resetBtn = document.getElementById('resetBtn');
        const dynamicTitle = document.getElementById('dynamicTitle');
        const noResults = document.getElementById('noResults');

        // 核心動態篩選函式
        function filterProducts() {
            const keyword = searchInput.value.trim().toLowerCase();
            const selectedCatId = categoryDropdown.value; // 'all' 或是 具體數字ID
            let visibleCount = 0;

            // 抓取頁面上現有的所有商品卡片
            const productCards = document.querySelectorAll('.product-item-card');

            productCards.forEach(card => {
                const title = card.dataset.title;
                const catName = card.dataset.catname;
                const cardCatId = card.dataset.catid;

                // 條件一：關鍵字必須滿足「商品名稱包含關鍵字」或是「分類包含關鍵字」
                const matchesKeyword = keyword === '' || title.includes(keyword) || catName.includes(keyword);

                // 條件二：分類必須滿足「全部商品」或是「卡片的分類 ID 符合下拉選單選中的 ID」
                const matchesCategory = selectedCatId === 'all' || cardCatId === selectedCatId;

                // 雙重符合則顯示，否則隱藏
                if (matchesKeyword && matchesCategory) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // 控制「找不到寶物」的提示顯示狀態
            noResults.style.display = visibleCount === 0 ? 'block' : 'none';

            // 動態更新大標題文字與圖示
            updateTitleText(keyword);
        }

        // 動態更新大標題的輔助函式
        function updateTitleText(keyword) {
            if (keyword !== '') {
                dynamicTitle.innerText = `🔍 搜尋「${keyword}」的結果`;
            } else {
                // 獲取目前選中的 option 的額外 data 屬性
                const selectedOption = categoryDropdown.options[categoryDropdown.selectedIndex];
                const catName = selectedOption.dataset.name;
                const catIcon = selectedOption.dataset.icon;

                if (categoryDropdown.value === 'all') {
                    dynamicTitle.innerText = `🔥 最新上架的寶物`;
                } else {
                    dynamicTitle.innerText = `${catIcon} 正在查看「${catName}」`;
                }
            }
        }

        // 監聽輸入框打字事件（即時觸發）
        searchInput.addEventListener('input', filterProducts);

        // 監聽下拉選單切換事件
        categoryDropdown.addEventListener('change', filterProducts);

        // 重設按鈕一鍵清空
        resetBtn.addEventListener('click', () => {
            searchInput.value = '';
            categoryDropdown.value = 'all';
            filterProducts();
        });
    </script>
</body>

</html>