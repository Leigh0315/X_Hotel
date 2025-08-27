<?php
// =================================================================
// 檔案 2: register.php
// 說明: 處理新使用者註冊、存入資料庫並寄送驗證信。
// =================================================================

// 引入必要的檔案
require 'database.php'; // 引入資料庫連線
require 'vendor/autoload.php'; // 引入 Composer

// 引入 PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// --- 模擬從前端接收到的資料 ---
// (未來這些資料會來自 $_POST)
$username = 'testuser_' . time();
$password = 'password123'; // 測試用的明文密碼
$firstname = '測試';
$lastname = '使用者';
$email = 'YOUR_TEST_RECIPIENT_EMAIL@example.com'; // 【修改】請填寫一個您能收信的測試信箱
$phone = '0912345678';
$country = 'Taiwan';
$address = '測試地址123號';

// --- 開始執行主要邏輯 ---
try {
    // 1. 產生一個安全的驗證碼 (Token)
    $verification_token = bin2hex(random_bytes(32));

    // 2. 準備 SQL 語句 (完全符合您的 Users 表格結構)
    $sql = "INSERT INTO Users (Username, PasswordHash, FirstName, LastName, Email, Phone, Country, Address, VerificationToken, TokenExpiresAt) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);

    // 3. 將資料寫入資料庫
    $hashed_password = password_hash($password, PASSWORD_DEFAULT); // 將密碼雜湊後再存入
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // 設定 Token 一小時後過期
    
    $stmt->execute([
        $username, 
        $hashed_password, 
        $firstname, 
        $lastname, 
        $email, 
        $phone, 
        $country, 
        $address, 
        $verification_token, 
        $expires_at
    ]);

    // 4. 準備寄送驗證信
    $mail = new PHPMailer(true);

    // 伺服器設定 (與您之前測試成功的一樣)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'YOUR_GMAIL_ACCOUNT@gmail.com'; // 【修改】您的 Gmail 帳號
    $mail->Password   = 'xxxxxxxxxxxxxxxx';         // 【修改】您的 16 位應用程式密碼
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // 信件內容設定
    $mail->setFrom('no-reply@yourhotel.com', 'XX飯店訂房網');
    $mail->addAddress($email, "{$lastname} {$firstname}"); // 收件人是剛剛註冊的使用者
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = '歡迎來到 XX飯店訂房網！請驗證您的電子信箱';

    // 建立包含 Token 的驗證連結
    // 【注意】請將 'yourdomain.com/path/to' 換成您專案的真實網址路徑
    $verification_link = "http://yourdomain.com/path/to/verify.php?token=" . $verification_token;
    $mail->Body    = "
        <h3>親愛的 {$lastname} {$firstname}，您好！</h3>
        <p>感謝您註冊 XX飯店訂房網。只差最後一步，請點擊下方的連結來啟用您的帳號：</p>
        <p><a href='{$verification_link}' style='padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>立即啟用帳號</a></p>
        <p>如果按鈕無法點擊，請複製以下網址到瀏覽器中開啟：</p>
        <p>{$verification_link}</p>
        <p>此連結將在一小時後失效。</p>";

    // 5. 寄出信件
    $mail->send();

    echo "✅ 註冊成功！驗證信已寄到 {$email}，請前往收信。";

} catch (PDOException $e) {
    // 捕捉資料庫相關的錯誤
    if ($e->errorInfo[1] == 1062) { // 1062 是重複鍵值的錯誤代碼
        echo "❌ 註冊失敗：這個 Email 或使用者名稱已經被註冊過了。";
    } else {
        echo "❌ 資料庫錯誤，註冊失敗。";
        // error_log($e->getMessage()); // 寫入日誌
    }
} catch (Exception $e) {
    // 捕捉 PHPMailer 相關的錯誤
    echo "❌ 註冊成功，但驗證信無法寄出. Mailer Error: {$mail->ErrorInfo}";
    // error_log($mail->ErrorInfo); // 寫入日誌
}
?>