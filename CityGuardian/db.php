<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "cityguardian";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Remove this line later after testing
echo "Database connected successfully!";

?>