<?php
require_once "classify.php"; 

header('Content-Type: application/json');

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "error" => "No image uploaded"]);
    exit;
}

$imagePath = $_FILES['image']['tmp_name'];
$userLocation = $_POST['location'] ?? '';

try {
    $result = generateAIDescription($imagePath, $userLocation);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>