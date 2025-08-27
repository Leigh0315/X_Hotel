<?php
/**
 * process_account.php
 * 後端會員資料處理腳本
 * 負責處理會員資料的讀取 (GET) 與更新 (UPDATE) 請求。
 * 必須在使用者登入 (Session 存在) 的狀態下運作。
 */

// --- 錯誤回報機制 (開發時建議開啟) ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ===================================================================
// 1. 啟動 SESSION 並進行身分驗證
// ===================================================================
session_start();

// 檢查使用者是否登入，如果沒有 'user_id' 的 session，則拒絕存取
if (!isset($_SESSION['user_id'])) {
    // 回傳 HTTP 401 Unauthorized 錯誤，並中斷執行
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => '存取被拒絕，請先登入。']);
    exit();
}

// 從 Session 中獲取當前登入的使用者 ID
$current_user_id = $_SESSION['user_id'];


// ===================================================================
// 2. 資料庫連線 (與 process_createA.php 相同)
// ===================================================================
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'Ｘ＿Hotel'; //

header('Content-Type: application/json');

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗: ' . $conn->connect_error]);
    exit();
}
$conn->set_charset("utf8mb4"); //


// ===================================================================
// 3. 根據 action 參數決定執行哪個操作
// ===================================================================
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_data':
        handle_get_data($conn, $current_user_id);
        break;
    
    case 'update_data':
        handle_update_data($conn, $current_user_id);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => '無效的操作請求。']);
        break;
}

// 關閉資料庫連線
$conn->close();


// ===================================================================
// 函式庫: 處理具體操作
// ===================================================================

/**
 * 處理獲取會員資料的請求
 * @param mysqli $conn 資料庫連線物件
 * @param int $user_id 當前登入的使用者 ID
 */
function handle_get_data($conn, $user_id) {
    // 從 Users 資料表查詢此 UserID 的資料
    $stmt = $conn->prepare("SELECT Username, FirstName, LastName, Email, Phone, Country, Address FROM Users WHERE UserID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        // 為了符合前端 input 的 name，我們將資料庫的 Country 欄位對應成 nationality
        $response_data = [
            'lastName' => $user_data['LastName'],
            'firstName' => $user_data['FirstName'],
            'username' => $user_data['Username'],
            'address' => $user_data['Address'],
            'phone' => $user_data['Phone'],
            'nationality' => $user_data['Country'] // 欄位名稱轉換
        ];
        echo json_encode(['success' => true, 'data' => $response_data]);
    } else {
        echo json_encode(['success' => false, 'message' => '找不到該會員的資料。']);
    }
    $stmt->close();
}

/**
 * 處理更新會員資料的請求
 * @param mysqli $conn 資料庫連線物件
 * @param int $user_id 當前登入的使用者 ID
 */
function handle_update_data($conn, $user_id) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => '錯誤：只接受 POST 請求。']);
        return;
    }

    // 獲取從前端傳來的 JSON 資料
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'message' => '錯誤：無法解析傳入的資料。']);
        return;
    }

    // 提取資料
    $lastName = $input['lastName'] ?? '';
    $firstName = $input['firstName'] ?? '';
    $phone = $input['phone'] ?? '';
    $address = $input['address'] ?? '';
    // 注意：Username (帳號) 通常不允許修改，所以我們不處理它

    // 動態建立 SQL UPDATE 語句
    $fields = [];
    $params = [];
    $types = '';

    // 添加基本資料欄位
    if (!empty($lastName)) { $fields[] = "LastName = ?"; $params[] = $lastName; $types .= 's'; }
    if (!empty($firstName)) { $fields[] = "FirstName = ?"; $params[] = $firstName; $types .= 's'; }
    if (!empty($phone)) { $fields[] = "Phone = ?"; $params[] = $phone; $types .= 's'; }
    if (!empty($address)) { $fields[] = "Address = ?"; $params[] = $address; $types .= 's'; }
    
    // 特別處理密碼：只有在使用者輸入新密碼時才更新
    if (!empty($input['password'])) {
        $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT); //
        $fields[] = "PasswordHash = ?";
        $params[] = $passwordHash;
        $types .= 's';
    }

    // 如果沒有任何需要更新的欄位，則直接回傳成功
    if (empty($fields)) {
        echo json_encode(['success' => true, 'message' => '沒有需要更新的資料。']);
        return;
    }

    // 組合 SQL 語句
    $sql = "UPDATE Users SET " . implode(', ', $fields) . " WHERE UserID = ?";
    $params[] = $user_id; // 最後一個參數是 UserID
    $types .= 'i';
    
    // 準備並執行更新
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params); // 使用 ... splat 運算子綁定動態參數

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => '會員資料更新成功！']);
    } else {
        echo json_encode(['success' => false, 'message' => '資料更新失敗: ' . $stmt->error]);
    }
    $stmt->close();
}
?>