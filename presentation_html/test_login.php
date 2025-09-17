<?php

$test_password = 'password123'; 


$hashed_password = password_hash($test_password, PASSWORD_DEFAULT);


echo "<h1>測試登入資訊</h1>";
echo "<p><b>請用這個密碼來測試登入：</b> " . htmlspecialchars($test_password) . "</p>";
echo "<p><b>請複製下面這個新的雜湊值，去更新資料庫：</b></p>";
echo '<textarea rows="3" style="width: 100%;" readonly>' . $hashed_password . '</textarea>';
?>