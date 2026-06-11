<?php
// db.php
// PostgreSQL connection for your Render-hosted database.

$host = 'dpg-d8budlbbc2fs738lur90-a.singapore-postgres.render.com';
$port = '5432';
$dbname = 'second_hand';
$user = 'second_hand_user';
$password = '5F5py6JfyAjCuFRUOgyyY0rE8NEWpwdP'; // <-- 請替換成 Render 上顯示的正確密碼

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection error. Please check the logs.');
}

// Example helper function
function getDbConnection(): PDO
{
    global $pdo;
    return $pdo;
}
