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

    echo "<script>
            alert('Password reset successful. You can now login.');
            window.location='LogIn.php';
          </script>";
    exit();

}
?>

<!-- HTML FORM -->
 <!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>

    <div class="ResetPassword-box">
        <form method="POST">
            <h1>Reset Password</h1>
            <div class="input-box">
                <input type="password" id="password" name="password" placeholder="New Password" required>
                <i class='bx bx-low-vision' id="eyeicon"></i>
            </div>

            <button type="submit" class="btn">Reset Password</button>

        </form>
    </div>

</body>
<script>    
    let eyeicon = document.getElementById("eyeicon");
    let password = document.getElementById("password");

    eyeicon.onclick = function() {
        if (password.type == "password") {
            password.type = "text";
            eyeicon.classList.add("bx-show");
            eyeicon.classList.remove("bx-low-vision");
        } else {
            password.type = "password";
            eyeicon.classList.add("bx-low-vision");
            eyeicon.classList.remove("bx-show");
        }
    }

</script>

</html>
