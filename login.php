<?php
require_once __DIR__ . '/db.php';
session_start();
$db = getDbConnection();

// 如果已經登入過，直接打開 login.php 就該自動去首頁
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$display_tab = isset($_SESSION['target_tab']) ? $_SESSION['target_tab'] : 'login';
$alert_message = '';

if (!empty($_SESSION['auth_message'])) {
    $alert_message = $_SESSION['auth_message'];
    unset($_SESSION['auth_message']);
    unset($_SESSION['target_tab']);
}

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $account = $_POST['account'];
        $password = $_POST['password'];
        $stmt = $db->prepare("SELECT * FROM \"User\" WHERE account = ?");
        $stmt->execute([$account]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['account'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: index.php");
            exit;
        } else {
            $_SESSION['auth_message'] = "帳號或密碼錯誤";
            $_SESSION['target_tab'] = "login";
            header("Location: login.php");
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
            header("Location: login.php");
            exit;
        }

        // 2. 進行註冊
        try {
            $sql = "INSERT INTO \"User\" (account, password, name, role, phoneno, email) VALUES (?, ?, ?, 1, ?, ?)";
            $db->prepare($sql)->execute([$account, $password, $name, $phoneno, $email]);

            $_SESSION['auth_message'] = "註冊成功，請登入！";
            $_SESSION['target_tab'] = "login";
            header("Location: login.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['auth_message'] = "註冊失敗，系統伺服器錯誤";
            $_SESSION['target_tab'] = "register";
            header("Location: login.php");
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
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="login-page-container">
        <div class="login-card">

            <div class="auth-tabs">
                <button class="auth-tab" id="tab-login" onclick="switchTab('login')">會員登入</button>
                <button class="auth-tab" id="tab-register" onclick="switchTab('register')">加入會員</button>
            </div>

            <form id="loginForm" action="login.php" method="POST" style="display:none;">
                <input type="hidden" name="action" value="login">
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

            <form id="registerForm" action="login.php" method="POST" style="display:none;">
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
                    已經有帳號了？ <span class="link-register" onclick="switchTab('login')">返回登入</span>
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
            loginForm.style.display = 'none';
            regForm.style.display = 'none';

            if (type === 'login') {
                loginForm.style.display = 'block';
                document.getElementById('tab-login').classList.add('active');
            } else if (type === 'register') {
                regForm.style.display = 'block';
                document.getElementById('tab-register').classList.add('active');
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