<?php
require __DIR__ . '/Library/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/Library');
$dotenv->load();

$host = "localhost";
$user = "root";
$password = "";
$database = "cityguardian";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$geminiApiKey = $_ENV['GEMINI_API_KEY'];

?>