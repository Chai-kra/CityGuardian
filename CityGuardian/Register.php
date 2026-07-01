<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirmPassword"];

    if ($password != $confirmPassword) {
        echo "<script>alert('Passwords do not match!');</script>";
    } else {

        // Check if email already exists
        $check = $conn->prepare("SELECT * FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('Email already registered!');</script>";
        } else {

            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $password);

            if ($stmt->execute()) {
                echo "<script>
                        alert('Registration successful!');
                        window.location='login.php';
                      </script>";
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
        }

        $check->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Page</title>

    <link rel="stylesheet" href="style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>

<div class="register-box">

<form action="Register.php" method="POST">

<h1>Register</h1>

<div class="input-box">
<input type="email" name="email" placeholder="Enter your email" required>
<i class='bx bxs-envelope'></i>
</div>

<div class="input-box">
<input type="password" name="password" placeholder="Enter your password" required>
<i class='bx bxs-lock-alt'></i>
</div>

<div class="input-box">
<input type="password" name="confirmPassword" placeholder="Confirm your password" required>
<i class='bx bxs-lock-alt'></i>
</div>

<button type="submit" class="btn">Register</button>

<div class="login-link">
<p>Already have an account?
<a href="login.php">Log In</a>
</p>
</div>

</form>

</div>

</body>
</html>