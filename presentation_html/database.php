<?php
// =================================================================
// 檔案 1: database.php
// 說明: 專門用來處理資料庫連線。請將此檔案與下面兩個檔案放在同一個資料夾。
// =================================================================

// --- 資料庫連線設定 ---
$db_host = 'localhost';     // 主機名稱
$db_name = 'YOUR_DATABASE_NAME'; // 【修改】您的資料庫名稱
$db_user = 'YOUR_USERNAME';       // 【修改】資料庫使用者名稱
$db_pass = 'YOUR_PASSWORD';       // 【修改】資料庫使用者密碼
$charset = 'utf8mb4';       // 字元集

// DSN (Data Source Name)
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$charset";

// PDO 連線選項
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // 建立 PDO 連線物件
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (\PDOException $e) {
    // 在真實環境中，您應該記錄錯誤日誌，而不是直接顯示錯誤
    error_log($e->getMessage());
    die("資料庫連線失敗，請稍後再試。");
}
?>
