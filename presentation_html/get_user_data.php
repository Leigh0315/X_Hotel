<?php
// 安全性更高的版本，使用伺服器端的 Session 進行驗證
session_start();
header('Content-Type: application/json');

// 1. 核心安全檢查：檢查伺服器 Session 中是否有 user_id
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => '使用者未登入，無法獲取資料。']);
    exit();
}

// --- 資料庫連線設定 ---
$db_host = '127.0.0.1';
$db_name = 'Ｘ＿Hotel';
$db_user = 'root';
$db_pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => '資料庫連線失敗。']);
    exit();
}

// 2. 直接從 Session 獲取登入者的 UserID，而不是從前端接收
$userId = $_SESSION['user_id'];
$response = [];

try {
    // 3. 使用安全的 UserID 查詢使用者自己的資料
    $stmt = $pdo->prepare("SELECT FirstName, LastName, Email, Phone, Country, Address FROM Users WHERE UserID = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user) {
        $response['status'] = 'success';
        $response['data'] = $user;
    } else {
        $response['status'] = 'error';
        $response['message'] = '在資料庫中找不到該使用者。';
    }

} catch (Exception $e) {
    http_response_code(500);
    $response['status'] = 'error';
    $response['message'] = '伺服器查詢錯誤: ' . $e->getMessage();
}

echo json_encode($response);
?>