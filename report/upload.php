<?php
session_start();

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/classify.php";
require_once __DIR__ . "/department_mapping.php";

function stopRequest($message, $statusCode = 400) {
    http_response_code($statusCode);
    exit($message);
}

function textLength($text) {
    return function_exists("mb_strlen")
        ? mb_strlen($text, "UTF-8")
        : strlen($text);
}

if (!isset($_SESSION["id"])) {
    stopRequest("User is not logged in.", 401);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    stopRequest("Invalid request.", 405);
}

$userId = (int) $_SESSION["id"];
$location = trim($_POST["location"] ?? "");
$description = trim($_POST["ai_description"] ?? "");
$extraDetails = trim($_POST["extra_details"] ?? "");

if ($location === "") {
    stopRequest("Location is required.");
}

if ($description === "") {
    stopRequest("Description is required.");
}

if (textLength($description) < 10) {
    stopRequest("Description must contain at least 10 characters.");
}

if (textLength($description) > 1200) {
    stopRequest("Description cannot exceed 1200 characters.");
}

if (textLength($extraDetails) > 600) {
    stopRequest("Extra details cannot exceed 600 characters.");
}

$issue = "General";
$imageName = "";
$imagePath = "";

if (
    isset($_FILES["image"]) &&
    $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
) {
    if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
        stopRequest("The image could not be uploaded.");
    }

    if ($_FILES["image"]["size"] > 8 * 1024 * 1024) {
        stopRequest("Image must be smaller than 8 MB.");
    }

    $imageInfo = @getimagesize($_FILES["image"]["tmp_name"]);

    if ($imageInfo === false) {
        stopRequest("The selected file is not a valid image.");
    }

    $mimeType = $imageInfo["mime"] ?? "";

    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($allowedTypes[$mimeType])) {
        stopRequest("Only JPG, PNG and WEBP images are allowed.");
    }

    $uploadDirectory = __DIR__ . "/uploads/";

    if (
        !is_dir($uploadDirectory) &&
        !mkdir($uploadDirectory, 0755, true)
    ) {
        stopRequest("The upload folder could not be created.", 500);
    }

    try {
        $imageName = bin2hex(random_bytes(16));
    } catch (Throwable $error) {
        $imageName = uniqid("report_", true);
    }

    $imageName .= "." . $allowedTypes[$mimeType];
    $imagePath = $uploadDirectory . $imageName;

    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath)) {
        stopRequest("Failed to save the uploaded image.", 500);
    }
}

$aiDescription = $description;
$aiPriority = null;
$aiDepartment = "DBKL Engineering Department";
$aiConfidence = null;
$status = "Pending";

if ($imageName !== "") {
    $result = classifyIssue(
        $imagePath,
        $description,
        $location
    );

    if (!empty($result["success"])) {
        $data = $result["data"] ?? [];

        $aiDescription = $data["description"] ?? $description;

        $priorityMap = [
            "critical" => "Critical",
            "high" => "High",
            "medium" => "Medium",
            "low" => "Low"
        ];

        $rawPriority = strtolower($data["priority"] ?? "");
        $aiPriority = $priorityMap[$rawPriority] ?? null;

        if (
            isset($data["confidence"]) &&
            is_numeric($data["confidence"])
        ) {
            $confidence = (float) $data["confidence"];

            $aiConfidence = round(
                $confidence <= 1
                    ? $confidence * 100
                    : $confidence,
                2
            );
        }

        $issue = $data["issue"] ?? "General";

        $aiDepartment = determineDepartment(
            $issue,
            $data["facility_type"] ?? null,
            $data["road_type"] ?? null,
            $data["flood_source"] ?? null
        );
    } else {
        error_log(
            "AI classification failed: " .
            json_encode($result)
        );
    }
}

$sql = "INSERT INTO reports (
    user_id,
    issue_type,
    location,
    description,
    extra_details,
    image,
    ai_description,
    ai_priority,
    ai_department,
    ai_confidence,
    status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    if ($imagePath !== "" && file_exists($imagePath)) {
        unlink($imagePath);
    }

    stopRequest(
        "Database error: " . $conn->error,
        500
    );
}

$stmt->bind_param(
    "issssssssss",
    $userId,
    $issue,
    $location,
    $description,
    $extraDetails,
    $imageName,
    $aiDescription,
    $aiPriority,
    $aiDepartment,
    $aiConfidence,
    $status
);

if (!$stmt->execute()) {
    if ($imagePath !== "" && file_exists($imagePath)) {
        unlink($imagePath);
    }

    stopRequest(
        "Database error: " . $stmt->error,
        500
    );
}

$stmt->close();
$conn->close();

echo "Report submitted successfully!";