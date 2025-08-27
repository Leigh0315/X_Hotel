<?php
// =================================================================
// 檔案: verify.php (全新的「除錯模式」版本)
// =================================================================

date_default_timezone_set('Asia/Taipei'); // 同樣設定時區以確保一致
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 資料庫連線資訊 ---
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'Ｘ＿Hotel'; // 【檢查】請確認您的資料庫名稱

// --- 建立資料庫連線 ---
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("<h1>❌ 資料庫連線失敗</h1><p>" . $conn->connect_error . "</p>");
}
$conn->set_charset("utf8mb4");

// 1. 從網址取得 token
$token = $_GET['token'] ?? null;
if (!$token) {
    die("<h1>❌ 驗證失敗</h1><p>缺少驗證碼參數。</p>");
}

try {
    // --- 開始除錯 ---
    // 步驟 A: 先只用 token 找找看，不管有沒有過期或驗證過
    $stmt_debug = $conn->prepare("SELECT * FROM Users WHERE VerificationToken = ? LIMIT 1");
    $stmt_debug->bind_param("s", $token);
    $stmt_debug->execute();
    $user_debug_info = $stmt_debug->get_result()->fetch_assoc();
    $stmt_debug->close();

    if (!$user_debug_info) {
        // 如果連 token 都找不到，那就是最根本的問題
        die("<h1>❌ 驗證失敗</h1><p>原因：資料庫中完全找不到這個驗證碼。</p><p>請確認您點擊的是最新的驗證信。</p>");
    }

    // 如果找到了，我們來分析為什麼驗證會失敗
    $is_expired = strtotime($user_debug_info['TokenExpiresAt']) < time();
    $is_already_verified = $user_debug_info['UserStatus'] !== 'Unverified';

    if ($is_already_verified) {
        die("<h1>ℹ️ 提示</h1><p>您的帳號 ({$user_debug_info['Email']}) 其實已經啟用過了，不需要重複驗證。請直接登入。</p>");
    }

    if ($is_expired) {
        die("<h1>❌ 驗證失敗</h1><p>原因：這個驗證連結已經過期了。</p><p>到期時間：{$user_debug_info['TokenExpiresAt']}</p>");
    }
    // --- 除錯結束 ---


    // --- 如果通過所有除錯檢查，才執行正式的驗證流程 ---
    $conn->begin_transaction();
    
    // 更新使用者狀態
    $stmt_update = $conn->prepare("UPDATE Users SET UserStatus = 'Active', VerificationToken = NULL, TokenExpiresAt = NULL WHERE UserID = ?");
    $stmt_update->bind_param("i", $user_debug_info['UserID']);
    $stmt_update->execute();
    $stmt_update->close();

    // 加入 Members 表格
    $stmt_member = $conn->prepare("INSERT INTO Members (UserID, MemberStatus) VALUES (?, 'Active')");
    $stmt_member->bind_param("i", $user_debug_info['UserID']);
    $stmt_member->execute();
    $stmt_member->close();

    // 提交事務
    $conn->commit();

    // 幫使用者登入
    $_SESSION['user_id'] = $user_debug_info['UserID'];
    $_SESSION['username'] = $user_debug_info['Username'];

    // 顯示成功訊息
    echo "<h1>✅ 驗證成功！</h1>";
    echo "<p>您的帳號 ({$user_debug_info['Email']}) 已經成功啟用。</p>";
    // 【檢查】請確認這裡的路徑是否正確
    echo "<p>系統已為您自動登入，<a href='http://localhost/S_presentation/presentation_html/hotel_login.html'>點此前往登入頁面</a>。</p>"; 

} catch (Exception $e) {
    $conn->rollback();
    die("<h1>❌ 伺服器錯誤</h1><p>驗證過程中發生錯誤，請稍後再試。</p>");
}

$conn->close();
?>