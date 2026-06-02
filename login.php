<?php
require_once __DIR__ . '/db.php';
session_start();
$db = getDbConnection();
$auth_message = "";
$target_tab = "login"; // 預設跳回登入頁籤

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
            $auth_message = "帳號或密碼錯誤";
            $target_tab = "login";
        }
    }

    if ($_POST['action'] === 'register') {
        $account  = $_POST['account'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $name     = $_POST['name'];
        $role     = $_POST['role'];
        $phoneno  = $_POST['phoneno'];
        $email    = $_POST['email'];

        try {
            $sql = "INSERT INTO \"User\" (account, password, name, role, phoneno, email) VALUES (?, ?, ?, ?, ?, ?)";
            $db->prepare($sql)->execute([$account, $password, $name, $role, $phoneno, $email]);
            $auth_message = "註冊成功，請登入！";
            $target_tab = "login";
        } catch (Exception $e) {
            $auth_message = "註冊失敗，帳號可能已存在";
            $target_tab = "register";
        }
    }
}
?>