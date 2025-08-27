<?php
// =================================================================
// SMTP 連線獨立偵錯腳本
// 目的：單獨測試 PHPMailer 連線到 Google SMTP 伺服器的過程。
// =================================================================

// --- 顯示所有錯誤，方便除錯 ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- 引入 Composer Autoloader ---
// 【重要】請根據您的專案結構，只保留下面其中一個 `require`
require __DIR__ . '/vendor/autoload.php';      // 方案 A: `vendor` 資料夾在同一層
// require __DIR__ . '/../vendor/autoload.php';   // 方案 B: `vendor` 資料夾在上一層

// 引入 PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- 建立 PHPMailer 物件 ---
$mail = new PHPMailer(true);

try {
    echo "<h1>SMTP 偵錯開始...</h1>";
    echo "<pre>"; // 使用 <pre> 標籤讓輸出格式更易讀

    // --- 伺服器設定 ---
    // 啟用詳細的偵錯輸出 (這是最重要的部分)
    $mail->SMTPDebug = 2; // 1=顯示錯誤和訊息, 2=只顯示訊息(最詳細)
    
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'leighmingchin@gmail.com'; // 【修改】您的 Gmail 帳號
    $mail->Password   = 'pjuoaakmsigwerwx';        // 【修改】您的 16 位應用程式密碼
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // --- 信件內容設定 ---
    $mail->setFrom('no-reply@yourhotel.com', 'X-Hotel 測試員');
    $mail->addAddress('test@example.com', '測試收件人'); // 這裡用一個假信箱即可
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'SMTP 連線測試';
    $mail->Body    = '這是一封測試信件。';

    echo "設定完成，準備寄送...\n";

    // --- 執行寄送 ---
    $mail->send();

    echo "\n</pre>";
    echo "<h2>✅ 測試成功！</h2><p>PHPMailer 似乎已成功送出信件，沒有拋出例外。</p>";

} catch (Exception $e) {
    echo "\n</pre>";
    echo "<h2>❌ 測試失敗！</h2>";
    echo "<p>PHPMailer 捕捉到一個錯誤：</p>";
    echo "<strong>錯誤訊息:</strong> " . $mail->ErrorInfo;
}
?>
