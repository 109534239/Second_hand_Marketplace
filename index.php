<?php
require_once __DIR__ . '/db.php';
session_start();
$db = getDbConnection();

// 如果已經登入過，直接打開 frontpage.php 就該自動去首頁
if (isset($_SESSION['user_id'])) {
    header("Location: frontpage.php");
    exit;
}

$display_tab = isset($_SESSION['target_tab']) ? $_SESSION['target_tab'] : 'index';
$alert_message = '';

if (!empty($_SESSION['auth_message'])) {
    $alert_message = $_SESSION['auth_message'];
    unset($_SESSION['auth_message']);
    unset($_SESSION['target_tab']);
}

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'index') {
        $account = $_POST['account'];
        $password = $_POST['password'];
        $stmt = $db->prepare("SELECT * FROM \"User\" WHERE account = ?");
        $stmt->execute([$account]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['account'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: frontpage.php");
            exit;
        } else {
            $_SESSION['auth_message'] = "帳號或密碼錯誤";
            $_SESSION['target_tab'] = "index";
            header("Location: index.php");
            exit;
        }
    }

    if ($_POST['action'] === 'register') {
        $account  = $_POST['account'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $name     = $_POST['name'];
        $phoneno  = $_POST['phoneno'];
        $email    = $_POST['email'];

        // 1. 檢查帳號是否重複
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM \"User\" WHERE account = ?");
        $checkStmt->execute([$account]);
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            $_SESSION['auth_message'] = "註冊失敗：此帳號已存在！";
            $_SESSION['target_tab'] = "register";
            header("Location: index.php");
            exit;
        }

        // 2. 進行註冊
        try {
            $sql = "INSERT INTO \"User\" (account, password, name, role, phoneno, email) VALUES (?, ?, ?, 1, ?, ?)";
            $db->prepare($sql)->execute([$account, $password, $name, $phoneno, $email]);

            $_SESSION['auth_message'] = "註冊成功，請登入！";
            $_SESSION['target_tab'] = "index";
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['auth_message'] = "註冊失敗，系統伺服器錯誤";
            $_SESSION['target_tab'] = "register";
            header("Location: index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>歡迎來到二手交易平台</title>
    <!-- <link rel="stylesheet" href="css/index.css"> -->
    <style>
        body {
            background-color: #f7f9fc !important;
            /* 強制背景色 */
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
        }

        .login-page-container {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            min-height: calc(100vh - 70px) !important;
            /* 動態扣除頂部 header 高度 */
            padding: 40px 20px;
            box-sizing: border-box;
            background: radial-gradient(circle at 10% 20%, rgba(255, 56, 92, 0.04) 0%, rgba(247, 249, 252, 1) 90%) !important;
        }

        .login-card {
            background-color: #ffffff !important;
            padding: 45px 40px !important;
            border-radius: 24px !important;
            width: 100% !important;
            max-width: 460px !important;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(15, 23, 42, 0.04) !important;
            box-sizing: border-box !important;
            transition: transform 0.3s ease, box-shadow 0.3s ease !important;
        }

        .login-card:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 30px 50px -20px rgba(15, 23, 42, 0.15),
                0 0 0 1px rgba(255, 56, 92, 0.1) !important;
        }

        .auth-tabs {
            display: flex !important;
            background: #f1f5f9 !important;
            padding: 5px !important;
            border-radius: 14px !important;
            margin-bottom: 35px !important;
        }

        .auth-tab {
            flex: 1 !important;
            border: none !important;
            padding: 12px 10px !important;
            font-size: 14px !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            background: transparent !important;
            color: #64748b !important;
            transition: all 0.25s ease !important;
            border-radius: 10px !important;
            text-align: center !important;
        }

        .auth-tab.active {
            background: #ffffff !important;
            color: #ff385c !important;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08) !important;
        }

        .input-group {
            position: relative !important;
            margin-bottom: 18px !important;
            width: 100% !important;
        }

        .login-card input[type="text"],
        .login-card input[type="password"],
        .login-card input[type="email"],
        .login-card input[type="tel"] {
            width: 100% !important;
            padding: 14px 16px !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 12px !important;
            outline: none !important;
            font-size: 14.5px !important;
            background: #f8fafc !important;
            color: #1e293b !important;
            transition: all 0.2s ease !important;
            box-sizing: border-box !important;
        }

        .login-card input::placeholder {
            color: #94a3b8 !important;
        }

        .login-card input:focus {
            border-color: #ff385c !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(255, 56, 92, 0.1) !important;
        }

        .btn-submit-login {
            width: 100% !important;
            padding: 15px !important;
            background: linear-gradient(135deg, #ff385c 0%, #ff6040 100%) !important;
            color: white !important;
            border: none !important;
            border-radius: 12px !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            margin-top: 10px !important;
            box-shadow: 0 4px 12px rgba(255, 56, 92, 0.2) !important;
        }

        .btn-submit-login:hover {
            background: linear-gradient(135deg, #f42c50 0%, #f44e2b 100%) !important;
            box-shadow: 0 6px 20px rgba(255, 56, 92, 0.35) !important;
            transform: translateY(-1px) !important;
        }

        .auth-footer {
            text-align: center !important;
            margin-top: 25px !important;
            font-size: 13.5px !important;
            color: #64748b !important;
        }

        .link-register {
            color: #ff385c !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            text-decoration: none !important;
            margin-left: 3px !important;
        }

        .link-register:hover {
            color: #e31c5f !important;
            text-decoration: underline !important;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="login-page-container">
        <div class="login-card">

            <div class="auth-tabs">
                <button class="auth-tab" id="tab-login" onclick="switchTab('index')">會員登入</button>
                <button class="auth-tab" id="tab-register" onclick="switchTab('register')">加入會員</button>
            </div>

            <form id="loginForm" action="index.php" method="POST" style="display:none;">
                <input type="hidden" name="action" value="index">
                <div class="input-group">
                    <input type="text" name="account" placeholder="會員帳號" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="密碼" required>
                </div>
                <button type="submit" class="btn-submit-login">立即登入</button>
                <div class="auth-footer">
                    還沒有帳號嗎？ <span class="link-register" onclick="switchTab('register')">立即加入會員</span>
                </div>
            </form>

            <form id="registerForm" action="index.php" method="POST" style="display:none;">
                <input type="hidden" name="action" value="register">
                <div class="input-group">
                    <input type="text" name="account" placeholder="設定帳號" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="設定密碼" required>
                </div>
                <div class="input-group">
                    <input type="text" name="name" placeholder="真實姓名" required>
                </div>
                <div class="input-group">
                    <input type="email" name="email" placeholder="電子郵件 (example@mail.com)" required>
                </div>
                <div class="input-group">
                    <input type="tel" name="phoneno" placeholder="手機號碼 (0912345678)"
                        pattern="09[0-9]{8}" title="請輸入正確的 10 位手機號碼，例如: 0912345678" required>
                </div>

                <button type="submit" class="btn-submit-login">完成註冊</button>
                <div class="auth-footer">
                    已經有帳號了？ <span class="link-register" onclick="switchTab('index')">返回登入</span>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(type) {
            const loginForm = document.getElementById('loginForm');
            const regForm = document.getElementById('registerForm');
            const tabs = document.querySelectorAll('.auth-tab');

            tabs.forEach(t => t.classList.remove('active'));
            if (loginForm) loginForm.style.display = 'none';
            if (regForm) regForm.style.display = 'none';

            if (type === 'index') {
                if (loginForm) loginForm.style.display = 'block';
                const tabLogin = document.getElementById('tab-login');
                if (tabLogin) tabLogin.classList.add('active');
            } else if (type === 'register') {
                if (regForm) regForm.style.display = 'block';
                const tabRegister = document.getElementById('tab-register');
                if (tabRegister) tabRegister.classList.add('active');
            }
        }

        window.addEventListener('DOMContentLoaded', (event) => {
            switchTab("<?php echo $display_tab; ?>");
            <?php if (!empty($alert_message)): ?>
                alert("<?php echo $alert_message; ?>");
            <?php endif; ?>
        });
    </script>
</body>

</html>