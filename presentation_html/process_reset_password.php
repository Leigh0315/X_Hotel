<?php
// --- 輸出緩衝開始 ---
ob_start();

// --- 基本設定 ---
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

// --- 資料庫連線資訊 ---
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
// 【關鍵修正】將資料庫名稱改為您其他檔案中使用的全形版本
$db_name = 'Ｘ＿Hotel';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗。']);
    exit();
}
$conn->set_charset("utf8mb4");

// --- 接收前端資料 ---
$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? '';
$password = $data['password'] ?? '';
$confirm_password = $data['confirm_password'] ?? '';

// --- 伺服器端驗證 ---
if (empty($token) || empty($password) || empty($confirm_password)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => '所有欄位皆為必填。']);
    exit();
}
if ($password !== $confirm_password) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => '兩次輸入的密碼不相符。']);
    exit();
}

// --- 處理流程 ---
try {
    $conn->begin_transaction();

    // 再次驗證 Token 是否有效
    $stmt_check = $conn->prepare("SELECT UserID FROM Users WHERE ResetToken = ? AND ResetTokenExpiresAt > NOW() LIMIT 1");
    $stmt_check->bind_param("s", $token);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows !== 1) {
        throw new Exception('此連結已失效，請重新申請密碼重設。');
    }
    $stmt_check->close();

    // 加密新密碼
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // 更新密碼並清除 Token
    $stmt_update = $conn->prepare("UPDATE Users SET PasswordHash = ?, ResetToken = NULL, ResetTokenExpiresAt = NULL WHERE ResetToken = ?");
    $stmt_update->bind_param("ss", $passwordHash, $token);
    $stmt_update->execute();

    if ($stmt_update->affected_rows > 0) {
        $conn->commit();
        ob_clean();
        // 【修改】請將 'hotel_login.html' 換成您登入頁面的真實路徑
        echo json_encode(['success' => true, 'message' => '密碼已成功更新！請 <a href="hotel_login.html">點此前往登入</a>。']);
    } else {
        throw new Exception("更新密碼失敗，資料庫未回報任何變更。");
    }
    $stmt_update->close();

} catch (Exception $e) {
    $conn->rollback();
    ob_clean();
    error_log("Password Reset Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系統發生錯誤：' . $e->getMessage()]);
}

$conn->close();
ob_end_flush();
?>