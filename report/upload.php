<?php
session_start();

include "../db.php";

if (!isset($_SESSION['id'])) {
    die("User is not logged in. Session ID is missing.");
}

$user_id = $_SESSION['id'];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "Invalid request.";
    exit();
}

// Get form data
$location = $_POST['location'] ?? '';
$description = $_POST['ai_description'] ?? '';

if (empty($location) || empty($description)) {
    echo "Location and description are required.";
    exit();
}


// =========================
// UPLOAD IMAGE
// =========================

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


// =========================
// DEFAULT VALUES
// =========================

$issue_type = "General";
$ai_description = $description;
$status = "Pending";


// =========================
// INSERT REPORT
// =========================

$sql = "INSERT INTO reports
        (user_id, issue_type, location, description, image, ai_description, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "Database error: " . $conn->error;
    exit();
}

$stmt->bind_param(
    "issssss",
    $user_id,
    $issue_type,
    $location,
    $description,
    $imageName,
    $ai_description,
    $status
);


if ($stmt->execute()) {

    echo "Report submitted successfully!";

} else {

    echo "Database error: " . $stmt->error;

}

$stmt->close();
$conn->close();
?>