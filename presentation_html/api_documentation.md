# X Hotel 網站後端 API 文件

## 概覽

本文件定義了 X Hotel 網站前端與後端 PHP 伺服器之間的應用程式介面 (API)。所有回應 (Response) 均為 JSON 格式。

- **資料庫名稱**: `Ｘ＿Hotel` (使用全形Ｘ)
- **資料庫連線**: 專案中同時使用了 `mysqli` 和 `PDO` 兩種方式進行資料庫連線，請確保兩者的連線資訊 (`$db_host`, `$db_user`, etc.) 皆設定正確。

---

## 1. 會員認證 (Authentication)

#### 1.1. 使用者登入

- **Endpoint**: `POST /process_login.php`
- **功能說明**: 驗證使用者提交的帳號密碼。成功後，在伺服器端建立 `Session` 以保持登入狀態。
- **請求 (Request)**:
    - **Header**: `Content-Type: application/json`
    - **Body**:
        ```json
        {
          "username": "user@example.com",
          "password": "your_password"
        }
        ```
- **回應 (Response)**:
    - **成功**:
        ```json
        {
          "success": true,
          "message": "登入成功！即將跳轉至主頁。"
        }
        ```
    - **失敗**:
        ```json
        {
          "success": false,
          "message": "您輸入的帳號或密碼有誤，請重新輸入。"
        }
        ```

#### 1.2. 使用者註冊

- **Endpoint**: `POST /process_createA.php`
- **功能說明**: 建立新的使用者帳號，將密碼進行雜湊處理後存入資料庫，並寄送一封含有驗證連結的 Email。
- **請求 (Request)**:
    - **Header**: `Content-Type: multipart/form-data` (由瀏覽器 `FormData` 自動設定)
    - **Body (Form Fields)**:
        - `lastName` (string): 姓氏
        - `firstName` (string): 名字
        - `username` (string): 帳號 (即 Email)
        - `password` (string): 密碼
        - `phone` (string): 電話
        - `address` (string): 地址
        - `nationality` (string): 國籍
- **回應 (Response)**:
    - **成功**:
        ```json
        {
          "success": true,
          "message": "註冊成功！一封驗證信已寄到您的信箱，請前往收信並啟用帳號。"
        }
        ```
    - **失敗 (帳號已存在)**:
        ```json
        {
          "success": false,
          "message": "此帳號或 Email 已被註冊，請使用其他帳號。"
        }
        ```

#### 1.3. 帳號 Email 驗證

- **Endpoint**: `GET /verify.php`
- **功能說明**: 使用者點擊註冊信中的連結後，由此頁面進行處理。驗證 `token` 的有效性，若成功則啟用帳號，並將使用者狀態設為 'Active'。
- **請求 (Request)**:
    - **URL Parameters**:
        - `token` (string): 來自驗證信的獨一無二驗證碼。
- **回應 (Response)**:
    - **成功**: 顯示 HTML 成功頁面，並提示使用者前往登入。
    - **失敗**: 顯示 HTML 錯誤頁面，說明連結無效、過期或帳號已啟用。

---

## 2. 密碼管理 (Password Management)

#### 2.1. 忘記密碼 (發送重設連結)

- **Endpoint**: `POST /process_forgot_password.php`
- **功能說明**: 接收使用者 Email。如果該 Email 存在於資料庫，則產生一個有時效性的重設密碼 `token`，並透過 Email 寄送重設連結給使用者。
- **請求 (Request)**:
    - **Header**: `Content-Type: application/json`
    - **Body**:
        ```json
        {
          "email": "user@example.com"
        }
        ```
- **回應 (Response)**:
    - **成功/使用者存在**:
        ```json
        {
          "success": true,
          "message": "如果此帳號存在，一封密碼重設信件將會寄到您的信箱。"
        }
        ```
    - **失敗 (無效輸入)**:
        ```json
        {
          "success": false,
          "message": "請輸入有效的電子信箱格式。"
        }
        ```

#### 2.2. 重設密碼 (更新密碼)

- **Endpoint**: `POST /process_reset_password.php`
- **功能說明**: 驗證從重設密碼頁面提交的 `token`，如果有效，則將使用者的新密碼雜湊後更新至資料庫，並清除 `token`。
- **請求 (Request)**:
    - **Header**: `Content-Type: application/json`
    - **Body**:
        ```json
        {
          "token": "the_reset_token_from_url",
          "password": "new_password",
          "confirm_password": "new_password"
        }
        ```
- **回應 (Response)**:
    - **成功**:
        ```json
        {
          "success": true,
          "message": "密碼已成功更新！請 <a href=\"hotel_login.html\">點此前往登入</a>。"
        }
        ```
    - **失敗 (Token 無效或密碼不符)**:
        ```json
        {
          "success": false,
          "message": "此連結已失效，請重新申請密碼重設。"
        }
        ```
        或
        ```json
        {
          "success": false,
          "message": "兩次輸入的密碼不相符。"
        }
        ```
---

## 3. 會員資料 (User Account)

#### 3.1. 獲取會員資料

- **Endpoint**: `GET /process_account.php?action=get_data`
- **前置條件**: 使用者必須處於登入狀態 (`Session` 必須存在)。
- **功能說明**: 獲取當前登入會員的詳細資料，用於填充會員資料頁面的表單。
- **請求 (Request)**: 無
- **回應 (Response)**:
    - **成功**:
        ```json
        {
          "success": true,
          "data": {
            "lastName": "林",
            "firstName": "小明",
            "username": "leighmingchin@gmail.com",
            "address": "台北市信義區松信路60號",
            "phone": "0912345678",
            "nationality": "台灣"
          }
        }
        ```
    - **失敗 (未登入)**:
        - `HTTP 401 Unauthorized`
        ```json
        {
          "success": false,
          "message": "存取被拒絕，請先登入。"
        }
        ```

#### 3.2. 更新會員資料

- **Endpoint**: `POST /process_account.php?action=update_data`
- **前置條件**: 使用者必須處於登入狀態 (`Session` 必須存在)。
- **功能說明**: 更新會員的個人資料。如果請求中包含 `password` 欄位，則會一併更新密碼。
- **請求 (Request)**:
    - **Header**: `Content-Type: application/json`
    - **Body**:
        ```json
        {
          "lastName": "陳",
          "firstName": "大文",
          "phone": "0987654321",
          "address": "台中市西屯區市政路1號",
          "password": "optional_new_password"
        }
        ```
- **回應 (Response)**:
    - **成功**:
        ```json
        {
          "success": true,
          "message": "會員資料更新成功！"
        }
        ```
    - **失敗**:
        ```json
        {
          "success": false,
          "message": "資料更新失敗: [錯誤詳情]"
        }
        ```

---

## 4. 訂房流程 (Booking)

#### 4.1. 獲取已登入會員資料 (用於自動填寫)

- **Endpoint**: `GET /get_user_data.php`
- **前置條件**: 使用者必須處於登入狀態 (`Session` 必須存在)。
- **功能說明**: 在結帳頁面 (`hotel_payment.html`) 載入時呼叫，用來自動填寫已登入會員的訂房人資訊。
- **請求 (Request)**: 無
- **回應 (Response)**:
    - **成功**:
        ```json
        {
          "status": "success",
          "data": {
            "FirstName": "小明",
            "LastName": "林",
            "Email": "leighmingchin@gmail.com",
            "Phone": "0912345678",
            "Country": "台灣",
            "Address": "台北市信義區松信路60號"
          }
        }
        ```
    - **失敗 (未登入)**:
        ```json
        {
          "status": "error",
          "message": "使用者未登入，無法獲取資料。"
        }
        ```

#### 4.2. 處理訂單

- **Endpoint**: `POST /process_booking.php`
- **功能說明**: 接收來自結帳頁面的所有訂房資訊與付款人資料，並寫入 `Users` 與 `Bookings` 資料表。如果使用者勾選註冊會員，則會在此一併建立帳號。
- **請求 (Request)**:
    - **Header**: `Content-Type: application/json`
    - **Body**:
        ```json
        {
          "firstName": "大文",
          "lastName": "陳",
          "phone": "0912345678",
          "email": "guest@example.com",
          "country": "台灣",
          "address": "...",
          "roomType": "尊享客房",
          "checkInDate": "2025-06-03",
          "checkOutDate": "2025-06-04",
          "totalPrice": 7564,
          "numGuests": "1",
          "numRooms": "1",
          "agreedToTerms": true,
          "createMember": false,
          "password": ""
        }
        ```
- **回應 (Response)**:
    - **成功**:
        ```json
        {
          "status": "success",
          "message": "預訂成功！"
        }
        ```
    - **失敗**:
        - `HTTP 400 Bad Request`
        ```json
        {
          "status": "error",
          "message": "缺少必要的訂房人資訊或未同意服務條款。"
        }
        ```