<?php
require 'db.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Invalid reset link.");
}

// 1. Check token validity
$stmt = $conn->prepare("SELECT id FROM users WHERE reset_token=?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Token is invalid or expired.");
}

// 2. If form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $newPassword = $_POST['password'];

    // IMPORTANT: hash password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // 3. Update password + clear token
    $stmt = $conn->prepare("
        UPDATE users 
        SET password=?, reset_token=NULL, reset_expires=NULL 
        WHERE reset_token=?
    ");

    $stmt->bind_param("ss", $hashedPassword, $token);
    $stmt->execute();

    echo "Password reset successful. You can now login.";
    exit;
}
?>

<!-- HTML FORM -->
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>

<h2>Reset Password</h2>

<form method="POST">
    <input type="password" name="password" placeholder="New Password" required>
    <button type="submit">Reset Password</button>
</form>

</body>
</html>