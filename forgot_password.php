<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/db.php';
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
            echo "Reset email sent!";
        } catch (Exception $e) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        }

    } else {
        echo "Email not found";
    }
}
?>

<form method="POST">
    <input type="email" name="email" required>
    <button type="submit">Send Reset Link</button>
</form>