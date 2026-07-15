<?php
include "db.php";
require_once "classify.php"; 
require_once "department_mapping.php";

$issue = null;
$location = $_POST['location'];
$description = ""; // remove
$aiDescription = $_POST['ai_description'] ?? '';
$image = "";

if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){

    $image = time() . "_" . $_FILES['image']['name'];

    move_uploaded_file(
        $_FILES['image']['tmp_name'],
        "uploads/" . $image
    );
}

$aiPriority = null;
$aiDepartment = "DBKL Engineering Department"; // default fallback
$aiConfidence = null;

if ($image !== "" && trim($aiDescription) !== "") {
    $result = classifyIssue("uploads/" . $image, $aiDescription, $location);

    if (isset($result['success'])) {
        $aiDescription = $result['data']['description'] ?? null;

        // Map lowercase priority from Gemini to your ENUM's capitalized format
        $priorityMap = [
            'critical' => 'Critical',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low'
        ];
        $rawPriority = strtolower($result['data']['priority'] ?? '');
        $aiPriority = $priorityMap[$rawPriority] ?? null;

        $aiConfidence = null;
        if (isset($result['data']['confidence'])) {
            $aiConfidence = round($result['data']['confidence'] * 100, 2); // 0.85 -> 85.00
        };

        $aiIssueType = $result['data']['issue'] ?? '';
        $facilityType = $result['data']['facility_type'] ?? null;
        $roadType = $result['data']['road_type'] ?? null;
        $floodSource = $result['data']['flood_source'] ?? null;
        $issue = $aiIssueType;

        $aiDepartment = determineDepartment($aiIssueType, $facilityType, $roadType, $floodSource);

    } else {
        error_log("AI classification failed: " . json_encode($result));
    }
} else {
    error_log("Submit called without an image or without a reviewed ai_description.");
}

$sql = "INSERT INTO reports
(issue_type, location, description, image, ai_description, ai_priority, ai_department, ai_confidence)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
// remove description and 1 question mark (?)

$stmt = $conn->prepare($sql);

$stmt->bind_param("ssssssss",
    $issue,
    $location,
    $description, // remove
    $image,
    $aiDescription,
    $aiPriority,
    $aiDepartment,
    $aiConfidence
);

if($stmt->execute()){
    echo "Report submitted successfully!";
}else{
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>