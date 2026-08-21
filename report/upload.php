<?php
session_start();
include "../db.php";

if (!isset($_SESSION['id'])) {
    echo "Please login first.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "Invalid request.";
    exit();
}

$user_id = $_SESSION['id'];

$location = $_POST['location'] ?? '';
$description = $_POST['ai_description'] ?? '';
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;

if (empty($location) || empty($description)) {
    echo "Location and description are required.";
    exit();
}

$imageName = '';

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

    $uploadDir = "uploads/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);

    $imageName = uniqid() . "." . $extension;

    $imagePath = $uploadDir . $imageName;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
        echo "Failed to upload image.";
        exit();
    }
}

$status = "Action Needed";
$priority = "Medium";

$sql = "INSERT INTO reports
        (id, location, description, latitude, longitude, image, status, priority)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "Database error: " . $conn->error;
    exit();
}

$stmt->bind_param(
    "issddsss",
    $id,
    $location,
    $description,
    $latitude,
    $longitude,
    $imageName,
    $status,
    $priority
);

if ($stmt->execute()) {
    echo "Report submitted successfully!";
} else {
    echo "Database error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>