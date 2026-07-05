<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];

    // Find user by email only
    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        // Compare entered password with hashed password
        if (password_verify($password, $user["password"])) {

            header("Location: ReportPage.php");
            exit();

        } else {
            echo "Invalid email or password.";
        }

    } else {
        echo "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogIn Page</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="login-box">
        <form action="login.php" method="POST">
            <h1>Log In</h1>
            <div class="input-box">
              <input type="email" id="email" name="email" placeholder="Enter your email" required>
                <i class='bx bxs-envelope' ></i>
            </div>
            <div class="input-box">
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <i class='bx bxs-lock-alt' ></i>
            </div>

            <div class="remember-forgot">
                <label><input type="checkbox"> Remember me</label>
                <a href="forgot_password.php">Forgot Password?</a>
            </div>

            <button type="submit" class="btn" >Log In</button>

            <div class="register-link">
                <p>Don't have an account? <a href="Register.php" class="register-link">Register</a></p>
            </div>
        </form>
    </div>
</body>

<script>    


</script>

</html>