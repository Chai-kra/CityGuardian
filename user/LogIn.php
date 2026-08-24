<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . "/../db.php";

const REMEMBER_COOKIE = "cityguardian_remember";
const REMEMBER_DAYS = 30;

function isHttpsRequest(): bool
{
    return !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";
}

function pageForRole(string $role): string
{
    return $role === "admin" ? "caseReview.php" : "uploadpage.php";
}

function startUserSession(array $user): void
{
    $_SESSION["id"] = (int) $user["id"];
    $_SESSION["email"] = (string) $user["email"];
    $_SESSION["role"] = (string) $user["role"];
    $_SESSION["department"] = (string) ($user["department"] ?? "");
}

function rememberCookieOptions(int $expires): array
{
    return [
        "expires" => $expires,
        "path" => "/",
        "secure" => isHttpsRequest(),
        "httponly" => true,
        "samesite" => "Lax"
    ];
}

function clearRememberCookie(): void
{
    setcookie(REMEMBER_COOKIE, "", rememberCookieOptions(time() - 3600));
    unset($_COOKIE[REMEMBER_COOKIE]);
}

function deleteRememberToken(mysqli $conn, string $selector): void
{
    $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");

    if ($stmt) {
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $stmt->close();
    }
}

function createRememberToken(mysqli $conn, int $userId): bool
{
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $tokenHash = hash("sha256", $validator);
    $expiresTimestamp = time() + (REMEMBER_DAYS * 86400);
    $expiresAt = date("Y-m-d H:i:s", $expiresTimestamp);

    $cleanup = $conn->prepare("DELETE FROM remember_tokens WHERE expires_at <= NOW()");
    if ($cleanup) {
        $cleanup->execute();
        $cleanup->close();
    }

    $stmt = $conn->prepare(
        "INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at)
         VALUES (?, ?, ?, ?)"
    );

    if (!$stmt) {
        error_log("Remember Me table is unavailable: " . $conn->error);
        return false;
    }

    $stmt->bind_param("isss", $userId, $selector, $tokenHash, $expiresAt);
    $saved = $stmt->execute();
    $stmt->close();

    if (!$saved) {
        return false;
    }

    setcookie(
        REMEMBER_COOKIE,
        $selector . ":" . $validator,
        rememberCookieOptions($expiresTimestamp)
    );

    return true;
}

function tryRememberedLogin(mysqli $conn): bool
{
    $cookie = $_COOKIE[REMEMBER_COOKIE] ?? "";

    if ($cookie === "") {
        return false;
    }

    $parts = explode(":", $cookie, 2);
    $selector = $parts[0] ?? "";
    $validator = $parts[1] ?? "";

    $validFormat = strlen($selector) === 24
        && strlen($validator) === 64
        && ctype_xdigit($selector)
        && ctype_xdigit($validator);

    if (!$validFormat) {
        clearRememberCookie();
        return false;
    }

    $stmt = $conn->prepare(
        "SELECT u.id, u.email, u.role, u.department,
                rt.token_hash, rt.expires_at
         FROM remember_tokens rt
         INNER JOIN users u ON u.id = rt.user_id
         WHERE rt.selector = ?
         LIMIT 1"
    );

    if (!$stmt) {
        clearRememberCookie();
        return false;
    }

    $stmt->bind_param("s", $selector);
    $stmt->execute();
    $result = $stmt->get_result();
    $rememberedUser = $result->fetch_assoc();
    $stmt->close();

    $notExpired = $rememberedUser
        && strtotime((string) $rememberedUser["expires_at"]) > time();

    $validToken = $notExpired && hash_equals(
        (string) $rememberedUser["token_hash"],
        hash("sha256", $validator)
    );

    if (!$validToken) {
        deleteRememberToken($conn, $selector);
        clearRememberCookie();
        return false;
    }

    session_regenerate_id(true);
    startUserSession($rememberedUser);

    $update = $conn->prepare(
        "UPDATE remember_tokens SET last_used_at = NOW() WHERE selector = ?"
    );

    if ($update) {
        $update->bind_param("s", $selector);
        $update->execute();
        $update->close();
    }

    return true;
}

if (isset($_SESSION["id"])) {
    header("Location: " . pageForRole((string) ($_SESSION["role"] ?? "user")));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "GET" && tryRememberedLogin($conn)) {
    header("Location: " . pageForRole((string) $_SESSION["role"]));
    exit();
}

if (empty($_SESSION["login_csrf"])) {
    $_SESSION["login_csrf"] = bin2hex(random_bytes(32));
}

$error = "";
$email = "";
$loginSuccess = false;
$redirectPage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim((string) ($_POST["email"] ?? ""));
    $password = (string) ($_POST["password"] ?? "");
    $csrfToken = (string) ($_POST["csrf_token"] ?? "");
    $remember = isset($_POST["remember"]);

    if (!hash_equals((string) $_SESSION["login_csrf"], $csrfToken)) {
        $error = "Your login form expired. Please refresh and try again.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password === "") {
        $error = "Please enter your password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, email, password, role, department
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = "Unable to sign in right now. Please try again later.";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($password, (string) $user["password"])) {
                $error = "Invalid email or password.";
            } else {
                session_regenerate_id(true);
                startUserSession($user);
                unset($_SESSION["login_csrf"]);

                if ($remember) {
                    createRememberToken($conn, (int) $user["id"]);
                }

                $loginSuccess = true;
                $redirectPage = pageForRole((string) $user["role"]);
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
    <title>Log In | AI City Guardian</title>
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
                <span class="auth-badge"><i class="bx bx-pulse"></i> Smarter city reporting</span>
                <h1 id="showcase-title">Help make every neighbourhood safer and better.</h1>
                <p>Report local issues, follow their progress and receive clear updates from the responsible department.</p>

                <div class="auth-benefits">
                    <div class="benefit-item">
                        <span><i class="bx bx-camera"></i></span>
                        <div><strong>Report quickly</strong><small>Upload a photo and location</small></div>
                    </div>
                    <div class="benefit-item">
                        <span><i class="bx bx-bot"></i></span>
                        <div><strong>AI-assisted</strong><small>Smart issue classification</small></div>
                    </div>
                    <div class="benefit-item">
                        <span><i class="bx bx-bell"></i></span>
                        <div><strong>Stay informed</strong><small>Track status and feedback</small></div>
                    </div>
                </div>
            </div>

            <p class="showcase-footer"><i class="bx bx-lock-alt"></i> Your account is protected with secure authentication.</p>
        </section>

        <main class="auth-main">
            <div class="auth-card">
                <a class="auth-mobile-brand" href="../index.php">
                    <i class="bx bxs-shield-alt-2"></i> AI City Guardian
                </a>

                <div class="auth-heading">
                    <span class="auth-eyebrow">Welcome back</span>
                    <h2>Log in to your account</h2>
                    <p>Enter your details to continue to the city portal.</p>
                </div>

                <?php if ($error !== ""): ?>
                    <div class="auth-alert" role="alert">
                        <i class="bx bx-error-circle"></i>
                        <span><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></span>
                    </div>
                <?php endif; ?>

                <form class="auth-form" action="LogIn.php" method="POST" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($_SESSION["login_csrf"] ?? ""), ENT_QUOTES, "UTF-8"); ?>">

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
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="auth-field">
                        <div class="field-label-row">
                            <label for="password">Password</label>
                            <a href="forgot_password.php">Forgot password?</a>
                        </div>
                        <div class="auth-input">
                            <i class="bx bx-lock-alt"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >
                            <button class="password-toggle" type="button" id="passwordToggle" aria-label="Show password" aria-pressed="false">
                                <i class="bx bx-hide"></i>
                            </button>
                        </div>
                    </div>

                    <label class="remember-option">
                        <input type="checkbox" name="remember" value="1">
                        <span class="custom-checkbox"><i class="bx bx-check"></i></span>
                        <span><strong>Remember me</strong><small>Stay logged in for 30 days on this device</small></span>
                    </label>

                    <button class="auth-submit" type="submit" id="loginButton">
                        <span>Log in securely</span>
                        <i class="bx bx-right-arrow-alt"></i>
                    </button>
                </form>

                <p class="auth-register">New to AI City Guardian? <a href="Register.php">Create an account</a></p>
            </div>
        </main>
    </div>

    <div class="auth-loader<?php echo $loginSuccess ? " is-active" : ""; ?>" id="loaderOverlay" aria-live="polite" aria-hidden="<?php echo $loginSuccess ? "false" : "true"; ?>">
        <div class="loader-card">
            <span class="loader-shield"><i class="bx bxs-shield-alt-2"></i></span>
            <span class="loader-ring"></span>
            <strong>Login successful</strong>
            <small>Opening your dashboard…</small>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById("password");
        const passwordToggle = document.getElementById("passwordToggle");
        const loginForm = document.getElementById("loginForm");
        const loginButton = document.getElementById("loginButton");

        passwordToggle.addEventListener("click", function () {
            const showing = passwordInput.type === "text";
            passwordInput.type = showing ? "password" : "text";
            this.setAttribute("aria-pressed", String(!showing));
            this.setAttribute("aria-label", showing ? "Show password" : "Hide password");
            this.querySelector("i").className = showing ? "bx bx-hide" : "bx bx-show";
            passwordInput.focus();
        });

        loginForm.addEventListener("submit", function () {
            loginButton.disabled = true;
            loginButton.classList.add("is-loading");
            loginButton.querySelector("span").textContent = "Signing in…";
            loginButton.querySelector("i").className = "bx bx-loader-alt bx-spin";
        });

        <?php if ($loginSuccess): ?>
        setTimeout(function () {
            window.location.replace(<?php echo json_encode($redirectPage); ?>);
        }, 900);
        <?php endif; ?>
    </script>
</body>
</html>