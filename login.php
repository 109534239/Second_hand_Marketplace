<?php
require_once __DIR__ . '/db.php';
session_start();
$db = getDbConnection();

// --- 登入邏輯 ---
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
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            // 使用 SESSION 帶回錯誤訊息
            $_SESSION['auth_message'] = "帳號或密碼錯誤";
            $_SESSION['target_tab'] = "login";
            header("Location: index.php");
            exit;
        }
    }

    if ($_POST['action'] === 'register') {
        $account  = $_POST['account'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $name     = $_POST['name'];
        $role     = $_POST['role'];
        $phoneno  = $_POST['phoneno'];
        $email    = $_POST['email'];

        // 【新增】1. 先檢查 account 是否已經重複
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM \"User\" WHERE account = ?");
        $checkStmt->execute([$account]);
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            // 帳號重複，留在註冊分頁
            $_SESSION['auth_message'] = "註冊失敗：此帳號已存在！";
            $_SESSION['target_tab'] = "register";
            header("Location: index.php");
            exit;
        }

        // 2. 帳號沒重複，進行註冊
        try {
            $sql = "INSERT INTO \"User\" (account, password, name, role, phoneno, email) VALUES (?, ?, ?, ?, ?, ?)";
            $db->prepare($sql)->execute([$account, $password, $name, $role, $phoneno, $email]);
            
            // 註冊成功，跳回登入分頁
            $_SESSION['auth_message'] = "註冊成功，請登入！";
            $_SESSION['target_tab'] = "login";
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