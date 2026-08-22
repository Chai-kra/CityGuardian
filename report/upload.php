<?php
session_start();

include "../db.php";
require_once "classify.php";
require_once "department_mapping.php";

if (!isset($_SESSION['id'])) {
    die("User is not logged in. Session ID is missing.");
}

$user_id = $_SESSION['id'];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "Invalid request.";
    exit();
}

$issue = null;
$location = $_POST['location'] ?? '';
$description = $_POST['ai_description'] ?? '';

if (empty($location) || empty($description)) {
    echo "Location and description are required.";
    exit();
}


// =========================
// UPLOAD IMAGE
// =========================

$imageName = "";

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

$aiDescription = $description;
$aiPriority = null;
$aiDepartment = "DBKL Engineering Department";
$aiConfidence = null;

$status = "Pending";


// =========================
// AI CLASSIFICATION
// =========================

if ($imageName !== "") {

    $result = classifyIssue(
        $imagePath,
        $description,
        $location
    );

    if (isset($result['success'])) {

        // AI description
        $aiDescription = $result['data']['description'] ?? $description;


        // AI priority
        $priorityMap = [
            'critical' => 'Critical',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low'
        ];

        $rawPriority = strtolower(
            $result['data']['priority'] ?? ''
        );

        $aiPriority = $priorityMap[$rawPriority] ?? null;


        // AI confidence
        if (isset($result['data']['confidence'])) {

            $aiConfidence = round(
                $result['data']['confidence'] * 100,
                2
            );
        }


        // AI issue
        $aiIssueType = $result['data']['issue'] ?? 'General';

        $issue = $aiIssueType;


        // Other AI information
        $facilityType = $result['data']['facility_type'] ?? null;
        $roadType = $result['data']['road_type'] ?? null;
        $floodSource = $result['data']['flood_source'] ?? null;


        // Determine department
        $aiDepartment = determineDepartment(
            $aiIssueType,
            $facilityType,
            $roadType,
            $floodSource
        );

    } else {

        error_log(
            "AI classification failed: " .
            json_encode($result)
        );
    }
}


// =========================
// INSERT REPORT
// =========================

$sql = "INSERT INTO reports
(
    user_id,
    issue_type,
    location,
    description,
    image,
    ai_description,
    ai_priority,
    ai_department,
    ai_confidence,
    status
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "Database error: " . $conn->error;
    exit();
}


$stmt->bind_param(
    "isssssssss",
    $user_id,
    $issue,
    $location,
    $description,
    $imageName,
    $aiDescription,
    $aiPriority,
    $aiDepartment,
    $aiConfidence,
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