<?php
// 設定標頭
header('Content-Type: application/json');

// --- 資料庫連線設定 ---
$db_host = '127.0.0.1';
$db_name = 'Ｘ＿Hotel';
$db_user = 'root';
$db_pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => '資料庫連線失敗。']);
    exit();
}

// --- 步驟 1: 接收資料 ---
$json_data = file_get_contents('php://input');
$data = json_decode($json_data);

$response = [];

// --- 步驟 2: 處理資料並寫入資料庫 ---
try {
    // **安全性檢查**
    if (empty($data->firstName) || empty($data->email) || empty($data->roomType) || empty($data->checkInDate) || empty($data->checkOutDate) || empty($data->numGuests) || empty($data->numRooms) || !$data->agreedToTerms) {
        throw new Exception("缺少必要的訂房人資訊或未同意服務條款。");
    }
    
    if ($data->createMember === true && empty($data->password)) {
        throw new Exception("註冊會員請務必填寫密碼。");
    }

    $pdo->beginTransaction();

    $userId = null;

    // **處理會員註冊與登入**
    if ($data->createMember === true) {
        $stmt = $pdo->prepare("SELECT UserID FROM Users WHERE Email = ?");
        $stmt->execute([$data->email]);
        if ($stmt->fetch()) {
            throw new Exception("此電子郵件已被註冊，請直接登入或使用其他郵件。");
        }
        $passwordHash = password_hash($data->password, PASSWORD_DEFAULT);
        
        
        $stmt = $pdo->prepare(
            "INSERT INTO Users (Username, PasswordHash, Email, FirstName, LastName, Phone, Country, Address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data->email, 
            $passwordHash, 
            $data->email, 
            $data->firstName, 
            $data->lastName,
            $data->phone,
            $data->country,
            $data->address
        ]);
        $userId = $pdo->lastInsertId();

        $stmt_member = $pdo->prepare("INSERT INTO Members (UserID, MembershipLevel) VALUES (?, ?)");
        $stmt_member->execute([$userId, 'Standard']);

    } else {
        $stmt = $pdo->prepare("SELECT UserID FROM Users WHERE Email = ?");
        $stmt->execute([$data->email]);
        $existingUser = $stmt->fetch();
        if ($existingUser) {
            $userId = $existingUser['UserID'];
        }
    }

    // 如果是訪客訂房（使用者記錄不存在）
    if ($userId === null) {
        // 在新增訪客記錄時，也加入電話、國家和地址
        $stmt = $pdo->prepare(
            "INSERT INTO Users (Username, Email, PasswordHash, FirstName, LastName, Phone, Country, Address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data->email, 
            $data->email, 
            '', 
            $data->firstName, 
            $data->lastName,
            $data->phone,
            $data->country,
            $data->address
        ]);
        $userId = $pdo->lastInsertId();
    }
    
    // **寫入訂單資料**
    $stmt = $pdo->prepare(
        "INSERT INTO Bookings (UserID, RoomType, CheckInDate, CheckOutDate, TotalPrice, NumGuests, NumRooms, AgreedToTerms) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $userId,
        $data->roomType,
        $data->checkInDate,
        $data->checkOutDate,
        $data->totalPrice,
        $data->numGuests,
        $data->numRooms,
        $data->agreedToTerms
    ]);
    
    $pdo->commit();

    $response['status'] = 'success';
    $response['message'] = '預訂成功！';

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400); 
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

// --- 步驟 3: 回傳結果 ---
echo json_encode($response);