<?php
session_start();
include 'lang.php';
include 'db.php';

echo "<h1>Debug Login Test</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_phone = $_POST['email_or_phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'user';
    
    echo "<h2>Received POST Data</h2>";
    echo "Email/Phone: $email_or_phone<br>";
    echo "Password: " . str_repeat('*', strlen($password)) . "<br>";
    echo "User Type: $user_type<br>";
    
    // Try the query
    if ($user_type === 'worker') {
        $query = "SELECT * FROM workers WHERE (email = $1 OR phone = $1)";
    } else {
        $query = "SELECT * FROM users WHERE user_type = '$user_type' AND (email = $1 OR phone = $1)";
    }
    
    echo "<h2>Query</h2>";
    echo htmlspecialchars($query) . "<br>";
    
    $result = function_exists('_demo_pg_query_params') 
        ? _demo_pg_query_params($db, $query, [$email_or_phone])
        : @pg_query_params($db, $query, [$email_or_phone]);
    echo "<h2>Query Result</h2>";
    echo "pg_query_params returned: " . gettype($result) . "<br>";
    
    if ($result) {
        $num_rows = function_exists('_demo_pg_num_rows')
            ? _demo_pg_num_rows($result)
            : @pg_num_rows($result);
        echo "pg_num_rows: $num_rows<br>";
        
        if ($num_rows > 0) {
            $user = function_exists('_demo_pg_fetch_assoc')
                ? _demo_pg_fetch_assoc($result)
                : @pg_fetch_assoc($result);
            echo "<h2>User Data</h2>";
            echo "User ID: " . $user['id'] . "<br>";
            echo "User Name: " . $user['name'] . "<br>";
            echo "Stored Password: " . substr($user['password_hash'] ?? $user['password'] ?? '', 0, 20) . "...<br>";
            
            $stored_password = $user['password_hash'] ?? $user['password'] ?? null;
            $pwd_verify = password_verify($password, $stored_password);
            echo "password_verify result: " . ($pwd_verify ? 'TRUE' : 'FALSE') . "<br>";
        }
    }
} else {
    echo "This is a test debug page. Send a POST request with email_or_phone, password, and user_type.";
}

?>
<hr>
<h2>Test Form</h2>
<form method="POST" action="">
    <label>Email/Phone: <input type="text" name="email_or_phone" value="worker@test.com"></label><br>
    <label>Password: <input type="password" name="password" value="Worker@123456"></label><br>
    <label>User Type: 
        <select name="user_type">
            <option value="user">User</option>
            <option value="worker" selected>Worker</option>
            <option value="admin">Admin</option>
        </select>
    </label><br>
    <button type="submit">Test Login</button>
</form>
