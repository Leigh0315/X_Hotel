<?php
// === 後端設定 ===
// 告訴前端，這個 API 回傳的是 JSON 格式
header('Content-Type: application/json');
// 啟用 PHP Session，以便在登入成功後儲存使用者狀態
session_start();

// === 資料庫連線資訊 ===
$db_host = '127.0.0.1'; 
$db_name = 'Ｘ＿Hotel';    
$db_user = 'root';       
$db_pass = '';       
$charset = 'utf8mb4';

// Data Source Name (DSN)
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // 發生錯誤時拋出例外
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // 預設取得關聯式陣列
    PDO::ATTR_EMULATE_PREPARES   => false,                  // 禁用模擬預處理，使用真正的預處理
];

// 初始化回應陣列
$response = ['success' => false, 'message' => ''];

try {
    // === 接收並解碼前端傳來的 JSON 資料 ===
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    // 基本驗證：確保帳號和密碼都有收到
    if (!isset($data['username']) || !isset($data['password'])) {
        throw new Exception('無效的請求，缺少帳號或密碼。');
    }

    $username = $data['username'];
    $password = $data['password'];

    // === 資料庫操作 ===
    // 建立 PDO 物件
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);

    
    $stmt = $pdo->prepare("SELECT UserID, Username, PasswordHash FROM Users WHERE Username = ?");
    
    // 綁定參數並執行
    $stmt->execute([$username]);
    
    // 取得使用者資料
    $user = $stmt->fetch();

    // === 驗證邏輯 ===
    // 檢查是否有找到使用者
    //  使用 password_verify() 比對使用者輸入的密碼與資料庫中的雜湊值
    if ($user && password_verify($password, $user['PasswordHash'])) {
        // 驗證成功
        $response['success'] = true;
        $response['message'] = '登入成功！即將跳轉至主頁。';

        // 將使用者資訊存入 Session，以便在其他頁面使用
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['loggedin'] = true;

    } else {
        // 驗證失敗 (帳號錯誤或密碼錯誤)
        // 出於安全考量，不要明確告知是帳號錯還是密碼錯
        $response['message'] = '您輸入的帳號或密碼有誤，請重新輸入。';
    }

} catch (PDOException $e) {
    // 資料庫連線或查詢錯誤
    $response['message'] = '資料庫錯誤：' . $e->getMessage();
    // 在生產環境中，您可能不想顯示詳細的錯誤訊息給使用者
    // $response['message'] = '系統發生錯誤，請稍後再試。';

} catch (Exception $e) {
    // 其他程式邏輯錯誤 (例如 JSON 解碼失敗)
    $response['message'] = '系統錯誤：' . $e->getMessage();
}

// === 回傳 JSON 結果給前端 ===
echo json_encode($response);
?>