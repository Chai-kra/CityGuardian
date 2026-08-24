<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . "/../db.php";

function pageForRole(string $role): string
{
    return $role === "admin" ? "caseReview.php" : "uploadpage.php";
}

if (isset($_SESSION["id"])) {
    header("Location: " . pageForRole((string) ($_SESSION["role"] ?? "user")));
    exit();
}

if (empty($_SESSION["register_csrf"])) {
    $_SESSION["register_csrf"] = bin2hex(random_bytes(32));
}

$error = "";
$email = "";
$registrationSuccess = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = strtolower(trim((string) ($_POST["email"] ?? "")));
    $password = (string) ($_POST["password"] ?? "");
    $confirmPassword = (string) ($_POST["confirmPassword"] ?? "");
    $csrfToken = (string) ($_POST["csrf_token"] ?? "");

    if (!hash_equals((string) $_SESSION["register_csrf"], $csrfToken)) {
        $error = "Your registration form expired. Please refresh and try again.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($email) > 190) {
        $error = "Your email address is too long.";
    } elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";
    } elseif (!preg_match("/[A-Za-z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $error = "Password must contain at least one letter and one number.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");

        if (!$check) {
            $error = "Unable to register right now. Please try again later.";
        } else {
            $check->bind_param("s", $email);
            $check->execute();
            $result = $check->get_result();
            $emailExists = $result->num_rows > 0;
            $check->close();

            if ($emailExists) {
                $error = "This email is already registered. Please log in instead.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $role = "user";
                $stmt = $conn->prepare(
                    "INSERT INTO users (email, password, role) VALUES (?, ?, ?)"
                );

                if (!$stmt || $hashedPassword === false) {
                    $error = "Unable to create your account. Please try again later.";
                } else {
                    $stmt->bind_param("sss", $email, $hashedPassword, $role);

                    try {
                        $saved = $stmt->execute();
                    } catch (mysqli_sql_exception $exception) {
                        $saved = false;
                        error_log("Registration failed: " . $exception->getMessage());
                    }

                    if ($saved) {
                        unset($_SESSION["register_csrf"]);
                        $registrationSuccess = true;
                    } elseif ($stmt->errno === 1062) {
                        $error = "This email is already registered. Please log in instead.";
                    } else {
                        $error = "Unable to create your account. Please try again later.";
                    }

                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | AI City Guardian</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="auth-page">
    <div class="auth-layout">
        <section class="auth-showcase" aria-labelledby="showcase-title">
            <a class="auth-brand" href="../index.php" aria-label="AI City Guardian home">
                <span class="auth-brand-icon"><i class="bx bxs-shield-alt-2"></i></span>
                <span>AI City Guardian</span>
            </a>

            <div class="showcase-copy">
                <span class="auth-badge"><i class="bx bx-user-plus"></i> Join your city community</span>
                <h1 id="showcase-title">One account to report, track and improve your city.</h1>
                <p>Create your citizen account and stay connected with every report from submission until resolution.</p>

                <div class="auth-benefits">
                    <div class="benefit-item">
                        <span><i class="bx bx-map-pin"></i></span>
                        <div><strong>Report issues</strong><small>Add photos and locations</small></div>
                    </div>
                    <div class="benefit-item">
                        <span><i class="bx bx-line-chart"></i></span>
                        <div><strong>Track progress</strong><small>See every status change</small></div>
                    </div>
                    <div class="benefit-item">
                        <span><i class="bx bx-message-square-dots"></i></span>
                        <div><strong>Get feedback</strong><small>Read department updates</small></div>
                    </div>
                </div>
            </div>

            <p class="showcase-footer"><i class="bx bx-lock-alt"></i> Your password is securely hashed before storage.</p>
        </section>

        <main class="auth-main">
            <div class="auth-card">
                <a class="auth-mobile-brand" href="../index.php">
                    <i class="bx bxs-shield-alt-2"></i> AI City Guardian
                </a>

                <div class="auth-heading">
                    <span class="auth-eyebrow">Create account</span>
                    <h2>Join AI City Guardian</h2>
                    <p>Register your details to start reporting city issues.</p>
                </div>

                <?php if ($error !== ""): ?>
                    <div class="auth-alert" role="alert">
                        <i class="bx bx-error-circle"></i>
                        <span><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></span>
                    </div>
                <?php endif; ?>

                <form class="auth-form" action="Register.php" method="POST" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($_SESSION["register_csrf"] ?? ""), ENT_QUOTES, "UTF-8"); ?>">

                    <div class="auth-field">
                        <label for="email">Email address</label>
                        <div class="auth-input">
                            <i class="bx bx-envelope"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?php echo htmlspecialchars($email, ENT_QUOTES, "UTF-8"); ?>"
                                placeholder="name@example.com"
                                autocomplete="email"
                                inputmode="email"
                                maxlength="190"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="password">Password</label>
                        <div class="auth-input">
                            <i class="bx bx-lock-alt"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Create a password"
                                autocomplete="new-password"
                                minlength="8"
                                pattern="(?=.*[A-Za-z])(?=.*[0-9]).{8,}"
                                title="Use at least 8 characters with one letter and one number"
                                required
                            >
                            <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password" aria-pressed="false">
                                <i class="bx bx-hide"></i>
                            </button>
                        </div>
                        <small class="auth-field-help"><i class="bx bx-info-circle"></i> At least 8 characters with one letter and one number.</small>
                    </div>

                    <div class="auth-field">
                        <label for="confirmPassword">Confirm password</label>
                        <div class="auth-input">
                            <i class="bx bx-check-shield"></i>
                            <input
                                type="password"
                                id="confirmPassword"
                                name="confirmPassword"
                                placeholder="Enter the password again"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >
                            <button class="password-toggle" type="button" data-password-toggle="confirmPassword" aria-label="Show password" aria-pressed="false">
                                <i class="bx bx-hide"></i>
                            </button>
                        </div>
                    </div>

                    <button class="auth-submit" type="submit" id="registerButton">
                        <span>Create my account</span>
                        <i class="bx bx-right-arrow-alt"></i>
                    </button>
                </form>

                <p class="auth-register">Already have an account? <a href="LogIn.php">Log in</a></p>
            </div>
        </main>
    </div>

    <div class="loader-overlay<?php echo $registrationSuccess ? " active" : ""; ?>" id="loaderOverlay" aria-live="polite" aria-hidden="<?php echo $registrationSuccess ? "false" : "true"; ?>">
        <div class="loader-ship">
            <span><span></span><span></span><span></span><span></span></span>
            <div class="loader-base"><span></span><div class="loader-face"></div></div>
        </div>
        <div class="longfazers"><span></span><span></span><span></span><span></span></div>
        <h1>Redirecting</h1>
    </div>

    <script>
        const registerForm = document.getElementById("registerForm");
        const registerButton = document.getElementById("registerButton");
        const password = document.getElementById("password");
        const confirmPassword = document.getElementById("confirmPassword");

        document.querySelectorAll("[data-password-toggle]").forEach(function (button) {
            button.addEventListener("click", function () {
                const input = document.getElementById(this.dataset.passwordToggle);
                const showing = input.type === "text";
                input.type = showing ? "password" : "text";
                this.setAttribute("aria-pressed", String(!showing));
                this.setAttribute("aria-label", showing ? "Show password" : "Hide password");
                this.querySelector("i").className = showing ? "bx bx-hide" : "bx bx-show";
                input.focus();
            });
        });

        function validateMatchingPasswords() {
            const different = confirmPassword.value !== "" && password.value !== confirmPassword.value;
            confirmPassword.setCustomValidity(different ? "Passwords do not match." : "");
        }

        password.addEventListener("input", validateMatchingPasswords);
        confirmPassword.addEventListener("input", validateMatchingPasswords);

        registerForm.addEventListener("submit", function () {
            validateMatchingPasswords();

            if (!registerForm.checkValidity()) {
                return;
            }

            registerButton.disabled = true;
            registerButton.querySelector("span").textContent = "Creating account…";
            registerButton.querySelector("i").className = "bx bx-loader-alt bx-spin";
        });

        <?php if ($registrationSuccess): ?>
        setTimeout(function () {
            window.location.replace("LogIn.php");
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>