<?php
include "db.php";
require_once "classify.php"; 

$issue = null;
$location = $_POST['location'];
$description = $_POST['description'];

$image = "";

if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){

    $image = time() . "_" . $_FILES['image']['name'];

    move_uploaded_file(
        $_FILES['image']['tmp_name'],
        "uploads/" . $image
    );
}

$aiDescription = null;
$aiPriority = null;
$aiDepartment = "DBKL Engineering Department"; // default agency
$aiConfidence = null;

if ($image !== "") {
    $result = classifyIssue("uploads/" . $image);

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

        // Basic department routing: potholes on federal roads go to JKR
        $aiIssueType = $result['data']['issue'] ?? '';
        $issue = $aiIssueType;
        if ($aiIssueType === 'pothole') {
            // Only relevant if you're passing lat/lng from the form (see note below)
            // $aiDepartment = checkRoadClassification($lat, $lng) === 'federal' ? 'JKR' : 'DBKL';
        }
    } else {
        error_log("AI classification failed: " . json_encode($result));
    }
}

$sql = "INSERT INTO reports
(issue_type, location, description, image, ai_description, ai_priority, ai_department, ai_confidence)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param("ssssssss",
    $issue,
    $location,
    $description,
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