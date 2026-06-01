<?php
// 如果這個檔案被單獨引入，確保 session 已經啟動
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<style>
    /* 導覽列容器 */
    header {
        background: #fff;
        padding: 15px 50px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .nav-container {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        font-size: 24px;
        font-weight: bold;
        color: #ff385c;
        text-decoration: none;
    }

    /* 選單與連結 */
    .nav-menu {
        display: flex;
        align-items: center;
    }

    .user-controls {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .nav-link {
        text-decoration: none;
        color: #555;
        font-weight: 600;
        font-size: 15px;
        transition: color 0.3s;
    }

    .nav-link:hover {
        color: #ff385c;
    }

    .welcome-msg {
        font-size: 14px;
        color: #888;
        border-left: 1px solid #ddd;
        padding-left: 15px;
    }

    /* 按鈕樣式 */
    .btn-login, .btn-logout {
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        font-size: 14px;
        transition: 0.3s;
    }

    .btn-login {
        background: #ff385c;
        color: #fff;
        border: none;
    }

    .btn-login:hover {
        background: #e33150;
    }

    .btn-logout {
        background: transparent;
        border: 1px solid #ddd;
        color: #555;
        text-decoration: none;
    }

    .btn-logout:hover {
        background: #f5f5f5;
    }
</style>

<header>
    <div class="nav-container">
        <a href="index.php" class="logo">🎉 二手交易平台</a>

        <nav class="nav-menu">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-controls">
                    <?php if ($_SESSION['role'] == 1): // 買家 ?>
                        <a href="orders.php" class="nav-link">訂單紀錄</a>
                        <!-- <a href="favorites.php" class="nav-link">我的收藏</a> -->
                    <?php else: // 賣家 ?>
                        <a href="upload.php" class="nav-link">上架商品</a>
                        <a href="sales.php" class="nav-link">交易紀錄</a>
                    <?php endif; ?>

                    <span class="welcome-msg">歡迎，<?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <a href="logout.php" class="btn-logout">登出</a>
                </div>
            <?php else: ?>
                <button type="button" class="btn-login" id="loginBtn">登入 / 註冊</button>
            <?php endif; ?>
        </nav>
    </div>
</header>