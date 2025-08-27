<?php
// =================================================================
// 最終生產版 v4：整合資料庫與郵件功能
// 目的：在確認資料庫連線正常後，重新啟用 PHPMailer 寄信功能。
// =================================================================

// --- 輸出緩衝開始 ---
ob_start();

// --- 引入 Composer Autoloader ---
// 【重要】請根據您的專案結構，只保留下面其中一個 `require`
require __DIR__ . '/vendor/autoload.php';      // 方案 A: `vendor` 資料夾在同一層
// require __DIR__ . '/../vendor/autoload.php';   // 方案 B: `vendor` 資料夾在上一層
// require __DIR__ . '/../../vendor/autoload.php'; // 方案 C: `vendor` 資料夾在更上層

// 引入 PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- 預設回應 ---
$response = ['success' => false, 'message' => '發生未知的伺服器錯誤。'];
$conn = null;

try {
    // --- 資料庫連線資訊 ---
    $db_host = '127.0.0.1';
    $db_user = 'root';
    $db_pass = '';
    // 使用修正後的全形資料庫名稱
    $db_name = 'Ｘ＿Hotel';

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception('資料庫連線失敗: ' . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // --- 接收前端資料 ---
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('請輸入有效的電子信箱格式。');
    }

    // --- 檢查 Email 是否存在 ---
    $stmt_check = $conn->prepare("SELECT UserID, FirstName, LastName FROM Users WHERE Email = ? LIMIT 1");
    if ($stmt_check === false) { throw new Exception("資料庫查詢準備失敗 (SELECT): " . $conn->error); }
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $user = $result->fetch_assoc();
    $stmt_check->close();

    if ($user) {
        // --- 只有當使用者存在時，才執行更新與寄信 ---
        $token = bin2hex(random_bytes(32));
        date_default_timezone_set('Asia/Taipei');
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // 更新資料庫中的 Token
        $stmt_update = $conn->prepare("UPDATE Users SET ResetToken = ?, ResetTokenExpiresAt = ? WHERE UserID = ?");
        if ($stmt_update === false) { throw new Exception("資料庫查詢準備失敗 (UPDATE): " . $conn->error); }
        $stmt_update->bind_param("ssi", $token, $expires_at, $user['UserID']);
        $stmt_update->execute();
        $stmt_update->close();

        // =================================================================
        // 【重新啟用】郵件功能
        // =================================================================
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'leighmingchin@gmail.com'; // 【修改】您的 Gmail 帳號
        $mail->Password   = 'pjuoaakmsigwerwx';        // 【修改】您的 16 位應用程式密碼
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        
        $mail->setFrom('no-reply@yourhotel.com', 'X-Hotel飯店訂房網');
        $mail->addAddress($email, "{$user['LastName']} {$user['FirstName']}");
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'X-Hotel飯店訂房網 - 密碼重設請求';
        
        // 【修改】請確認您的專案路徑正確
        $reset_link = "http://localhost/S_presentation/presentation_html/reset_password.php?token=" . $token;
        
        $mail->Body = "<h3>親愛的 {$user['LastName']} {$user['FirstName']}，您好！</h3><p>我們收到了您的密碼重設請求。請點擊下方的連結來設定您的新密碼：</p><p><a href='{$reset_link}' style='padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>重設密碼</a></p><p>此連結將在 15 分鐘後失效。如果您沒有提出此請求，請忽略此郵件。</p>";
        
        $mail->send();
    }
    
    // 如果程式順利執行到這裡，都設定為成功的回應
    $response = ['success' => true, 'message' => '如果此帳號存在，一封密碼重設信件將會寄到您的信箱。'];

} catch (Exception $e) {
    // 如果 try 區塊中任何地方發生錯誤，都會被這裡捕捉
    error_log("Password Reset Script Error: " . $e->getMessage());
    // 更新回應的訊息為詳細的錯誤內容
    $response['message'] = '後端錯誤: ' . $e->getMessage();

} finally {
    // 無論成功或失敗，都確保關閉資料庫連線
    if ($conn !== null && $conn instanceof mysqli) {
        $conn->close();
    }
}

// --- 清理並送出最終回應 ---
ob_end_clean();

// 設定 Header 為 JSON
header('Content-Type: application/json');

// 將 $response 陣列編碼為 JSON 字串並輸出
echo json_encode($response);

// 結束程式
exit();
?>
