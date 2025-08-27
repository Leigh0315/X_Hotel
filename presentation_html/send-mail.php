<?php

// 引入 PHPMailer 的類別
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 這是最重要的一行！載入 Composer 的自動載入器
// 這會讓您能夠使用所有透過 Composer 安裝的套件，而不需要手動 require 任何檔案
require 'vendor/autoload.php';

// 建立一個 PHPMailer 的新物件
$mail = new PHPMailer(true); // `true` 會啟用例外處理

try {
    //Server settings (伺服器設定)
    $mail->SMTPDebug = SMTP::DEBUG_OFF;      // 關閉詳細的 debug 訊息，上線時建議使用
    $mail->isSMTP();                         // 設定使用 SMTP 協定發信
    $mail->Host       = 'smtp.gmail.com';    // 設定您的 SMTP 伺服器 (以 Gmail 為例)
    $mail->SMTPAuth   = true;                // 啟用 SMTP 驗證
    $mail->Username   = 'leighmingchin@gmail.com'; // 您的 Gmail 帳號
    $mail->Password   = 'pjuoaakmsigwerwx';    // 【重要】您的 Gmail「應用程式密碼」，不是登入密碼！
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // 啟用 SSL 加密
    $mail->Port       = 465;                 // SMTP port，使用 SSL 時通常為 465

    //Recipients (收件人設定)
    $mail->setFrom('from@example.com', '寄件人名稱'); // 寄件人 Email 和名稱
    $mail->addAddress('leighmingchin@gmail.com', '我'); // 新增一個收件人
    // $mail->addReplyTo('info@example.com', 'Information'); // 設定回信的 Email
    // $mail->addCC('cc@example.com'); // 新增副本
    // $mail->addBCC('bcc@example.com'); // 新增密件副本

    //Content (信件內容設定)
    $mail->isHTML(true); // 設定信件內容為 HTML 格式
    $mail->CharSet = 'UTF-8'; // 設定郵件編碼為 UTF-8
    $mail->Subject = '來自 PHPMailer 的測試信件'; // 信件主旨
    $mail->Body    = '這是一封使用 <b>PHPMailer 搭配 Composer</b> 寄出的 HTML 信件！'; // HTML 格式的信件內容
    $mail->AltBody = '這是給不支援 HTML 的郵件客戶端看的純文字內容'; // 純文字格式的信件內容

    $mail->send();
    echo '測試信件已成功寄出！';
} catch (Exception $e) {
    echo "信件無法寄出. Mailer Error: {$mail->ErrorInfo}";
}

?>