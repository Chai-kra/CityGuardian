<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/db.php';
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $token = bin2hex(random_bytes(50));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $stmt = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE email=?");
        $stmt->bind_param("sss", $token, $expires, $email);
        $stmt->execute();

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'cityguardian7@gmail.com';
            $mail->Password = 'rzajfgavaqhxrkji';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('cityguardian7@gmail.com', 'CityGuardian');
            $mail->addAddress($email);

            $resetLink = "http://localhost/CityGuardian/reset_password.php?token=$token";

            $mail->isHTML(true);
            $mail->Subject = "Reset Password";
            $mail->Body = "<a href='$resetLink'>Reset Password</a>";

            $mail->send();
            $message = "Reset email sent!";
        } catch (Exception $e) {
            $message = "Mailer Error: " . $mail->ErrorInfo;
        }

    } else {
        $message = "Email not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="ForgotPassword-box">
        <form method="POST">
            <h1>Forgot Password?</h1>
            <div class="input-box">
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
                <i class='bx bxs-envelope' ></i>
            </div>

            <?php if (!empty($message)): ?>
                <p class="message-text"><?php echo $message; ?></p>
            <?php endif; ?>

            <button type="submit" class="btn">Send Reset Link</button>

        </form>
    </div>
</body>