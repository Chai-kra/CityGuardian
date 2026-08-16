<!-- -->
<?php
session_start();
include "../db.php";

$error = "";
$loginSuccess = false;
$redirectPage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $_SESSION['error'] = "Please enter a valid email address.";
        header("Location: ../user/LogIn.php");
        exit();

    }

    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {

            // Prevent session fixation by regenerating the session ID on login
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            $loginSuccess = true;

            if ($user['role'] === 'admin') {
                $redirectPage = "caseReview.php";
            } else {
                // ReportPage.php lives in report/, not user/
                $redirectPage = "../report/ReportPage.php";
            }

        } else {

            $_SESSION['error'] = "Invalid email or password.";
            header("Location: ../user/LogIn.php");
            exit();

        }

    } else {

        $_SESSION['error'] = "Invalid email or password.";
        header("Location: ../user/LogIn.php");
        exit();

    }
}

$error = $_SESSION['error'] ?? "";
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>LogIn Page</title>

    <link rel="stylesheet" href="../css/style.css">

    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
          rel="stylesheet">

</head>

<body>

<div class="login-box">

    <form action="../user/LogIn.php" method="POST">

        <h1>Log In</h1>

        <div class="input-box">

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                required
            >

            <i class="bx bxs-envelope"></i>

        </div>


        <div class="input-box">

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                required
            >

            <i class="bx bx-low-vision"
               id="eyeicon"></i>

        </div>


        <?php if (!empty($error)): ?>

            <p class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </p>

        <?php endif; ?>


        <div class="remember-forgot">

            <label>
                <input type="checkbox" name="remember">
                Remember me
            </label>

            <a href="../user/forgot_password.php">
                Forgot Password?
            </a>

        </div>


        <button type="submit" class="btn">
            Log In
        </button>


        <div class="register-link">

            <p>
                Don't have an account?

                <a href="../user/Register.php">
                    Register
                </a>
            </p>

        </div>

    </form>

</div>


<div
    class="loader-overlay<?php echo $loginSuccess ? ' active' : ''; ?>"
    id="loaderOverlay"
>

    <div class="loader-ship">

        <span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
        </span>

        <div class="loader-base">

            <span></span>

            <div class="loader-face"></div>

        </div>

    </div>


    <div class="longfazers">

        <span></span>
        <span></span>
        <span></span>
        <span></span>

    </div>


    <h1>
        Redirecting
    </h1>

</div>


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

};


<?php if ($loginSuccess): ?>

setTimeout(function() {

    window.location.href = "<?php echo $redirectPage; ?>";

}, 3000);

<?php endif; ?>

</script>

</body>

</html>