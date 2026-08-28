<?php
session_start();

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/classify.php";
require_once __DIR__ . "/department_mapping.php";


/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
*/

function stopRequest($message, $statusCode = 400)
{
    http_response_code($statusCode);
    exit($message);
}

function textLength($text)
{
    return function_exists("mb_strlen")
        ? mb_strlen($text, "UTF-8")
        : strlen($text);
}


/*
|--------------------------------------------------------------------------
| Calculate distance between two GPS coordinates
| Returns distance in metres
|--------------------------------------------------------------------------
*/

function distanceInMeters($lat1, $lon1, $lat2, $lon2)
{
    $earthRadius = 6371000;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a =
        sin($dLat / 2) * sin($dLat / 2)
        +
        cos(deg2rad($lat1))
        * cos(deg2rad($lat2))
        * sin($dLon / 2)
        * sin($dLon / 2);

    $c = 2 * atan2(
        sqrt($a),
        sqrt(1 - $a)
    );

    return $earthRadius * $c;
}


/*
|--------------------------------------------------------------------------
| Authentication / request validation
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["id"])) {
    stopRequest("User is not logged in.", 401);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    stopRequest("Invalid request.", 405);
}


/*
|--------------------------------------------------------------------------
| Get submitted data
|--------------------------------------------------------------------------
*/

$userId = (int) $_SESSION["id"];

$location = trim($_POST["location"] ?? "");
$description = trim($_POST["ai_description"] ?? "");
$extraDetails = trim($_POST["extra_details"] ?? "");

$latitude = isset($_POST["latitude"]) && is_numeric($_POST["latitude"])
    ? (float) $_POST["latitude"]
    : null;

$longitude = isset($_POST["longitude"]) && is_numeric($_POST["longitude"])
    ? (float) $_POST["longitude"]
    : null;


/*
|--------------------------------------------------------------------------
| Validate submitted information
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Image upload
|--------------------------------------------------------------------------
*/

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

    $imageInfo = @getimagesize(
        $_FILES["image"]["tmp_name"]
    );

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
        stopRequest(
            "The upload folder could not be created.",
            500
        );
    }

    try {
        $imageName = bin2hex(
            random_bytes(16)
        );
    } catch (Throwable $error) {
        $imageName = uniqid(
            "report_",
            true
        );
    }

    $imageName .= "." . $allowedTypes[$mimeType];

    $imagePath = $uploadDirectory . $imageName;

    if (!move_uploaded_file(
        $_FILES["image"]["tmp_name"],
        $imagePath
    )) {
        stopRequest(
            "Failed to save the uploaded image.",
            500
        );
    }
}


/*
|--------------------------------------------------------------------------
| AI classification defaults
|--------------------------------------------------------------------------
*/

$aiDescription = $description;

$aiPriority = null;

$aiDepartment = "DBKL Engineering Department";

$aiConfidence = null;

$status = "Pending";


/*
|--------------------------------------------------------------------------
| AI classification
|--------------------------------------------------------------------------
*/

if ($imageName !== "") {

    $result = classifyIssue(
        $imagePath,
        $description,
        $location
    );

    if (!empty($result["success"])) {

        $data = $result["data"] ?? [];


        /*
        |--------------------------------------------------------------------------
        | AI description
        |--------------------------------------------------------------------------
        */

        $aiDescription =
            $data["description"]
            ?? $description;


        /*
        |--------------------------------------------------------------------------
        | AI priority
        |--------------------------------------------------------------------------
        */

        $priorityMap = [
            "critical" => "Critical",
            "high" => "High",
            "medium" => "Medium",
            "low" => "Low"
        ];

        $rawPriority =
            strtolower(
                trim($data["priority"] ?? "")
            );

        $aiPriority =
            $priorityMap[$rawPriority]
            ?? null;


        /*
        |--------------------------------------------------------------------------
        | AI confidence
        |--------------------------------------------------------------------------
        */

        if (
            isset($data["confidence"]) &&
            is_numeric($data["confidence"])
        ) {

            $confidence =
                (float) $data["confidence"];

            $aiConfidence = round(
                $confidence <= 1
                    ? $confidence * 100
                    : $confidence,
                2
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Issue type
        |--------------------------------------------------------------------------
        */

        $issue =
            $data["issue"]
            ?? "General";


        /*
        |--------------------------------------------------------------------------
        | Determine department
        |--------------------------------------------------------------------------
        */

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


/*
|--------------------------------------------------------------------------
| CASE GROUPING
|--------------------------------------------------------------------------
|
| A report joins an existing case when:
|
| 1. Same issue type
| 2. Same department
| 3. GPS coordinates are within 50 metres
|
| Otherwise, a new case is created.
|
|--------------------------------------------------------------------------
*/

$caseGroupId = null;

$groupingRadius = 50;


/*
|--------------------------------------------------------------------------
| Look for an existing nearby case
|--------------------------------------------------------------------------
*/

if (
    $latitude !== null &&
    $longitude !== null
) {

    /*
    |--------------------------------------------------------------------------
    | Approximate bounding box
    |--------------------------------------------------------------------------
    */

    $latitudeRange = 0.0006;
    $longitudeRange = 0.0006;

    $minLatitude =
        $latitude - $latitudeRange;

    $maxLatitude =
        $latitude + $latitudeRange;

    $minLongitude =
        $longitude - $longitudeRange;

    $maxLongitude =
        $longitude + $longitudeRange;


    /*
    |--------------------------------------------------------------------------
    | Find possible matching cases
    |--------------------------------------------------------------------------
    */

    $caseSql = "
        SELECT
            case_id,
            issue_type,
            ai_department,
            ai_priority,
            latitude,
            longitude,
            location,
            status,
            submission_count
        FROM cases
        WHERE issue_type = ?
        AND ai_department = ?
        AND latitude BETWEEN ? AND ?
        AND longitude BETWEEN ? AND ?
        ORDER BY case_id ASC
    ";

    $caseStmt = $conn->prepare($caseSql);

    if (!$caseStmt) {

        if (
            $imagePath !== "" &&
            file_exists($imagePath)
        ) {
            unlink($imagePath);
        }

        stopRequest(
            "Database error while checking cases: " .
            $conn->error,
            500
        );
    }

    $caseStmt->bind_param(
        "ssdddd",
        $issue,
        $aiDepartment,
        $minLatitude,
        $maxLatitude,
        $minLongitude,
        $maxLongitude
    );

    if (!$caseStmt->execute()) {

        $error = $caseStmt->error;

        $caseStmt->close();

        if (
            $imagePath !== "" &&
            file_exists($imagePath)
        ) {
            unlink($imagePath);
        }

        stopRequest(
            "Database error while checking cases: " .
            $error,
            500
        );
    }

    $caseResult =
        $caseStmt->get_result();


    /*
    |--------------------------------------------------------------------------
    | Check exact GPS distance
    |--------------------------------------------------------------------------
    */

    while ($case = $caseResult->fetch_assoc()) {

        if (
            !is_numeric($case["latitude"]) ||
            !is_numeric($case["longitude"])
        ) {
            continue;
        }

        $distance = distanceInMeters(
            $latitude,
            $longitude,
            (float) $case["latitude"],
            (float) $case["longitude"]
        );


        /*
        |--------------------------------------------------------------------------
        | Matching case found
        |--------------------------------------------------------------------------
        */

        if ($distance <= $groupingRadius) {

            $caseGroupId =
                (int) $case["case_id"];

            break;
        }
    }

    $caseStmt->close();
}


/*
|--------------------------------------------------------------------------
| Create a new case if no matching case was found
|--------------------------------------------------------------------------
*/

if ($caseGroupId === null) {

    $caseSql = "
        INSERT INTO cases (
            issue_type,
            ai_department,
            ai_priority,
            latitude,
            longitude,
            location,
            status,
            submission_count
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ";

    $caseStmt =
        $conn->prepare($caseSql);

    if (!$caseStmt) {

        if (
            $imagePath !== "" &&
            file_exists($imagePath)
        ) {
            unlink($imagePath);
        }

        stopRequest(
            "Database error while creating case: " .
            $conn->error,
            500
        );
    }

    $caseStmt->bind_param(
        "sssddss",
        $issue,
        $aiDepartment,
        $aiPriority,
        $latitude,
        $longitude,
        $location,
        $status
    );

    if (!$caseStmt->execute()) {

        $error = $caseStmt->error;

        $caseStmt->close();

        if (
            $imagePath !== "" &&
            file_exists($imagePath)
        ) {
            unlink($imagePath);
        }

        stopRequest(
            "Database error while creating case: " .
            $error,
            500
        );
    }

    /*
    |--------------------------------------------------------------------------
    | The newly created cases.case_id becomes
    | reports.case_group_id
    |--------------------------------------------------------------------------
    */

    $caseGroupId =
        (int) $conn->insert_id;

    $caseStmt->close();

} else {

    /*
    |--------------------------------------------------------------------------
    | Existing case found
    |
    | Increase submission count
    |--------------------------------------------------------------------------
    */

    $updateCaseSql = "
        UPDATE cases
        SET submission_count = submission_count + 1
        WHERE case_id = ?
    ";

    $updateCaseStmt =
        $conn->prepare($updateCaseSql);

    if (!$updateCaseStmt) {

        if (
            $imagePath !== "" &&
            file_exists($imagePath)
        ) {
            unlink($imagePath);
        }

        stopRequest(
            "Database error while updating case: " .
            $conn->error,
            500
        );
    }

    $updateCaseStmt->bind_param(
        "i",
        $caseGroupId
    );

    if (!$updateCaseStmt->execute()) {

        $error = $updateCaseStmt->error;

        $updateCaseStmt->close();

        if (
            $imagePath !== "" &&
            file_exists($imagePath)
        ) {
            unlink($imagePath);
        }

        stopRequest(
            "Database error while updating case: " .
            $error,
            500
        );
    }

    $updateCaseStmt->close();
}


/*
|--------------------------------------------------------------------------
| Insert report
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| The reports table uses:
|
|     case_group_id
|
| This points to:
|
|     cases.case_id
|
|--------------------------------------------------------------------------
*/

$sql = "
    INSERT INTO reports (
        user_id,
        case_group_id,
        issue_type,
        location,
        extra_details,
        image,
        ai_description,
        ai_priority,
        ai_department,
        ai_confidence,
        status,
        latitude,
        longitude
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    if (
        $imagePath !== "" &&
        file_exists($imagePath)
    ) {
        unlink($imagePath);
    }

    stopRequest(
        "Database error: " .
        $conn->error,
        500
    );
}


/*
|--------------------------------------------------------------------------
| Bind report values
|--------------------------------------------------------------------------
*/

$stmt->bind_param(
    "iisssssssssdd",
    $userId,
    $caseGroupId,
    $issue,
    $location,
    $extraDetails,
    $imageName,
    $aiDescription,
    $aiPriority,
    $aiDepartment,
    $aiConfidence,
    $status,
    $latitude,
    $longitude
);


/*
|--------------------------------------------------------------------------
| Execute report insertion
|--------------------------------------------------------------------------
*/

if (!$stmt->execute()) {

    if (
        $imagePath !== "" &&
        file_exists($imagePath)
    ) {
        unlink($imagePath);
    }

    stopRequest(
        "Database error: " .
        $stmt->error,
        500
    );
}


/*
|--------------------------------------------------------------------------
| Finish
|--------------------------------------------------------------------------
*/

$stmt->close();

$conn->close();

echo "Report submitted successfully!";
?>