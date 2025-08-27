<?php
// --- 基本設定 ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 資料庫連線資訊 ---
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
// 【關鍵修正】將資料庫名稱改為您其他檔案中使用的全形版本
$db_name = 'Ｘ＿Hotel';

// 建立資料庫連線
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("<h1>❌ 系統錯誤</h1><p>無法連線至資料庫。</p>");
}
$conn->set_charset("utf8mb4");

// --- 驗證 Token ---
$token = $_GET['token'] ?? '';
$is_token_valid = false;
$error_message = '';

if (empty($token)) {
    $error_message = '缺少權杖，無法進行操作。';
} else {
    // 檢查 token 是否存在且未過期
    $stmt = $conn->prepare("SELECT UserID FROM Users WHERE ResetToken = ? AND ResetTokenExpiresAt > NOW() LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $is_token_valid = true;
    } else {
        $error_message = '此密碼重設連結無效或已過期。請重新申請。';
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>X Hotel - 重設密碼</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html { height: 100%; margin: 0; display: flex; justify-content: center; align-items: center; background-color: #8B7D74; }
        .container-box { background-color: white; padding: 40px 50px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); width: 100%; max-width: 450px; text-align: left; }
        .container-box h2 { margin-top: 5px; font-size: 32px; font-weight: bold; }
        .container-box p { margin-top: 10px; margin-bottom: 30px; color: #666; }
        .input-field { width: 100%; padding: 15px; margin-bottom: 15px; border: none; background-color: #f0f0f0; border-radius: 10px; font-size: 16px; }
        .submit-btn { background-color: #A98E81; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-size: 16px; width: 100%; transition: background-color 0.3s; }
        .submit-btn:hover { background-color: #8B7D74; }
        .message { display: none; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container-box">
        <h2 id="pageTitle">設定新密碼</h2>
        <?php if ($is_token_valid): ?>
            <!-- 【優化】將表單與提示文字包在一個容器中，方便一起隱藏 -->
            <div id="formContainer">
                <p>請輸入您的新密碼。</p>
                <form id="resetPasswordForm">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <input type="password" id="password" class="input-field" placeholder="輸入新密碼" required>
                    <input type="password" id="confirm_password" class="input-field" placeholder="確認新密碼" required>
                    <button type="submit" class="submit-btn">更新密碼</button>
                </form>
            </div>
            <!-- 【優化】將訊息框移到表單容器外 -->
            <div id="messageBox" class="alert message" role="alert"></div>
        <?php else: ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
            <a href="forgot_password.html">返回申請頁面</a>
        <?php endif; ?>
    </div>

    <?php if ($is_token_valid): ?>
    <script>
        document.getElementById('resetPasswordForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;
            const token = this.querySelector('input[name="token"]').value;
            const messageBox = document.getElementById('messageBox');
            
            if (password !== confirm_password) {
                messageBox.style.display = 'block';
                messageBox.className = 'alert alert-danger message';
                messageBox.textContent = '兩次輸入的密碼不相符，請重新確認。';
                return;
            }

            fetch('process_reset_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: token, password: password, confirm_password: confirm_password })
            })
            .then(response => response.json())
            .then(result => {
                // 【優化】根據後端回傳結果，提供更清晰的介面變化
                if (result.success) {
                    // 1. 隱藏整個表單容器
                    document.getElementById('formContainer').style.display = 'none';
                    // 2. 更改主標題
                    document.getElementById('pageTitle').textContent = '✅ 更新成功！';
                    // 3. 顯示成功訊息
                    messageBox.style.display = 'block';
                    messageBox.className = 'alert alert-success message';
                    messageBox.innerHTML = result.message; // 使用 innerHTML 以便顯示連結
                } else {
                    // 如果失敗，只顯示錯誤訊息
                    messageBox.style.display = 'block';
                    messageBox.className = 'alert alert-danger message';
                    messageBox.innerHTML = result.message;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageBox.style.display = 'block';
                messageBox.className = 'alert alert-danger message';
                messageBox.textContent = '發生網路錯誤，請稍後再試。';
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>