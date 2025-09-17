<?php

// 引入 PHPMailer 的類別
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


require 'vendor/autoload.php';

// 建立一個 PHPMailer 的新物件
$mail = new PHPMailer(true); // `true` 會啟用例外處理

try {
    //Server settings (伺服器設定)
    $mail->SMTPDebug = SMTP::DEBUG_OFF;      // 關閉詳細的 debug 訊息，上線時建議使用
    $mail->isSMTP();                         // 設定使用 SMTP 協定發信
    $mail->Host       = 'smtp.gmail.com';    // 設定 SMTP 伺服器 (以 Gmail 為例)
    $mail->SMTPAuth   = true;                // 啟用 SMTP 驗證
    $mail->Username   = 'leighmingchin@gmail.com'; // 您 Gmail 帳號
    $mail->Password   = '';    //  Gmail「應用程式密碼」
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // 啟用 SSL 加密
    $mail->Port       = 465;                 // SMTP port

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