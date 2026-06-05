<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<style>
    header {
        background: #fff;
        padding: 15px 50px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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

    .btn-logout {
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        font-size: 14px;
        transition: 0.3s;
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
                    <a href="index.php" class="nav-link">瀏覽/搜尋商品</a>
                    <a href="orders.php" class="nav-link">建立訂單</a>
                    <a href="orders.php" class="nav-link">管理個人訂單</a>
                    <span class="welcome-msg">歡迎，<?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <a href="logout.php" class="btn-logout">登出</a>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>