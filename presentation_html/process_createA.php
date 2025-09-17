<?php
date_default_timezone_set('Asia/Taipei');
// =================================================================
// 檔案 1: process_createA.php (修改後的版本)
// 說明: 整合了 PHPMailer，用於處理新使用者註冊並寄送驗證信。
// =================================================================

// --- 引入 Composer & PHPMailer ---
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 資料庫連線資訊 ---
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'Ｘ＿Hotel'; 

// --- HTTP Header ---
header('Content-Type: application/json');

// --- 建立資料庫連線 ---
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗: ' . $conn->connect_error]);
    exit();
}
$conn->set_charset("utf8mb4");

// --- 檢查請求方法 ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '錯誤：只接受 POST 請求。']);
    exit();
}

// --- 從 POST 請求中獲取資料 ---
$lastName = $_POST['lastName'] ?? '';
$firstName = $_POST['firstName'] ?? '';
$username = $_POST['username'] ?? ''; //
$email = $_POST['username'] ?? '';    // 
$password = $_POST['password'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$nationality = $_POST['nationality'] ?? '';

// --- 伺服器端基本驗證 ---
if (empty($username) || empty($password) || empty($lastName) || empty($firstName)) {
    echo json_encode(['success' => false, 'message' => '錯誤：姓名、帳號和密碼為必填欄位。']);
    exit();
}

// --- 檢查使用者名稱或 Email 是否已被註冊 ---
$stmt_check = $conn->prepare("SELECT UserID FROM Users WHERE Username = ? OR Email = ?");
$stmt_check->bind_param("ss", $username, $email);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => '此帳號或 Email 已被註冊，請使用其他帳號。']);
    $stmt_check->close();
    $conn->close();
    exit();
}
$stmt_check->close();

// --- 密碼加密 & 產生 Token ---
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$verification_token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

// --- 開始事務處理 ---
$conn->begin_transaction();

try {
    // 1. 新增資料到 `Users` 資料表 (包含驗證資訊)
    $sql = "INSERT INTO Users (Username, PasswordHash, FirstName, LastName, Email, Phone, Country, Address, VerificationToken, TokenExpiresAt, UserStatus) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Unverified')";
    $stmt_users = $conn->prepare($sql);
    $stmt_users->bind_param("ssssssssss", $username, $passwordHash, $firstName, $lastName, $email, $phone, $nationality, $address, $verification_token, $expires_at);
    $stmt_users->execute();
    
    // 【重要】註冊時不再直接加入 Members 表格，也不再自動登入
    
    // 提交事務
    $conn->commit();

    // 2. 寄送驗證信 (資料庫操作成功後才執行)
    $mail = new PHPMailer(true);
    // 伺服器設定
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'leighmingchin@gmail.com'; // Gmail 帳號
    $mail->Password   = '';         // 16 位應用程式密碼
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    // 信件內容設定
    $mail->setFrom('no-reply@yourhotel.com', 'X-Hotel飯店訂房網');
    $mail->addAddress($email, "{$lastName} {$firstName}");
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = '歡迎來到 X-Hotel飯店訂房網！請驗證您的電子信箱';
    
    $verification_link = "http://localhost/S_presentation/presentation_html/verify.php?token=" . $verification_token;
    $mail->Body = "<h3>親愛的 {$lastName} {$firstName}，您好！</h3><p>感謝您的註冊，請點擊下方的連結來啟用您的帳號：</p><p><a href='{$verification_link}' style='padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>立即啟用帳號</a></p><p>此連結將在一小時後失效。</p>";
    $mail->send();

    // 回傳成功訊息給前端
    echo json_encode(['success' => true, 'message' => '註冊成功！一封驗證信已寄到您的信箱，請前往收信並啟用帳號。']);

} catch (Exception $e) {
    // 如果過程中發生任何錯誤，則回滾事務
    $conn->rollback();
    // 回傳錯誤訊息
    echo json_encode(['success' => false, 'message' => '伺服器錯誤，註冊失敗: ' . $e->getMessage()]);
}

// --- 關閉連線 ---
if (isset($stmt_users)) $stmt_users->close();
$conn->close();
?>