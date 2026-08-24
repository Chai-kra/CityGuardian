<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . "/../db.php";

const REMEMBER_COOKIE = "cityguardian_remember";

function isHttpsRequest(): bool
{
    return !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";
}

$rememberCookie = $_COOKIE[REMEMBER_COOKIE] ?? "";

if ($rememberCookie !== "") {
    $parts = explode(":", $rememberCookie, 2);
    $selector = $parts[0] ?? "";

    if (strlen($selector) === 24 && ctype_xdigit($selector)) {
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");

        if ($stmt) {
            $stmt->bind_param("s", $selector);
            $stmt->execute();
            $stmt->close();
        }
    }
}

setcookie(REMEMBER_COOKIE, "", [
    "expires" => time() - 3600,
    "path" => "/",
    "secure" => isHttpsRequest(),
    "httponly" => true,
    "samesite" => "Lax"
]);

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(session_name(), "", [
        "expires" => time() - 42000,
        "path" => $params["path"],
        "domain" => $params["domain"],
        "secure" => (bool) $params["secure"],
        "httponly" => (bool) $params["httponly"],
        "samesite" => "Lax"
    ]);
}

session_destroy();
$conn->close();

header("Location: LogIn.php");
exit();
?>