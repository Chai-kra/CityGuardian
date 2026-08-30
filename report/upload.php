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
    global $conn, $imagePath;

    /* Roll back any case/report work and remove a file uploaded in this request. */
    if (isset($conn) && $conn instanceof mysqli) {
        @$conn->rollback();
    }

    if (
        isset($imagePath) &&
        is_string($imagePath) &&
        $imagePath !== "" &&
        file_exists($imagePath)
    ) {
        @unlink($imagePath);
    }

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

    /* Protect the square root from tiny floating-point overshoots. */
    $a = max(0, min(1, $a));

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

if ($latitude === null || $longitude === null) {
    stopRequest(
        "Please select the report location on the map or use your current location."
    );
}

if (
    $latitude < -90 ||
    $latitude > 90 ||
    $longitude < -180 ||
    $longitude > 180
) {
    stopRequest("The selected location coordinates are invalid.");
}


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

$aiDepartmentId = null;

$assignedDepartmentId = null;

$assignedBranchId = null;

$assignmentDistanceKm = null;

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
| Normalize the AI department and assign the nearest eligible branch
|--------------------------------------------------------------------------
|
| The legacy text field is kept for compatibility, but the routing source
| of truth is the normalized department/branch ID pair. A report cannot be
| routed accurately without coordinates, so location coordinates are
| required before this point.
|
|--------------------------------------------------------------------------
*/

$departmentLookupName1 = trim((string) $aiDepartment);
$departmentLookupName2 = $departmentLookupName1;
$departmentLookupName3 = $departmentLookupName1;

$departmentSql = '
    SELECT
        department_id,
        department_code,
        department_name,
        legacy_name
    FROM departments
    WHERE is_active = 1
      AND (
          BINARY LOWER(CONVERT(department_code USING utf8mb4)) =
              BINARY LOWER(CONVERT(? USING utf8mb4))
          OR BINARY LOWER(CONVERT(department_name USING utf8mb4)) =
              BINARY LOWER(CONVERT(? USING utf8mb4))
          OR BINARY LOWER(CONVERT(legacy_name USING utf8mb4)) =
              BINARY LOWER(CONVERT(? USING utf8mb4))
      )
    LIMIT 1
';

$departmentStmt = $conn->prepare($departmentSql);

if (!$departmentStmt) {
    stopRequest(
        "Routing database is not ready. Please run the CityGuardian routing migration.",
        500
    );
}

$departmentStmt->bind_param(
    "sss",
    $departmentLookupName1,
    $departmentLookupName2,
    $departmentLookupName3
);

if (!$departmentStmt->execute()) {
    $error = $departmentStmt->error;
    $departmentStmt->close();

    stopRequest(
        "Database error while resolving the AI department: " . $error,
        500
    );
}

$departmentResult = $departmentStmt->get_result();
$departmentRow = $departmentResult->fetch_assoc() ?: null;
$departmentStmt->close();

if (!$departmentRow) {
    stopRequest(
        "The AI department could not be matched to an active department.",
        500
    );
}

$aiDepartmentId = (int) $departmentRow["department_id"];
$assignedDepartmentId = $aiDepartmentId;
$departmentCode = trim((string) $departmentRow["department_code"]);

/* Keep the old text column synchronized with the normalized department. */
$aiDepartment = trim((string) ($departmentRow["legacy_name"] ?? ""));

if ($aiDepartment === "") {
    $aiDepartment = trim((string) $departmentRow["department_name"]);
}

$branchSql = '
    SELECT
        b.branch_id,
        b.branch_code,
        b.branch_name,
        b.branch_level,
        b.latitude,
        b.longitude
    FROM branches b
    JOIN branch_departments bd
      ON bd.branch_id = b.branch_id
     AND bd.department_id = ?
     AND bd.is_active = 1
    WHERE b.is_active = 1
    ORDER BY b.branch_id ASC
';

$branchStmt = $conn->prepare($branchSql);

if (!$branchStmt) {
    stopRequest(
        "Routing branches are not ready. Please run the CityGuardian routing migration.",
        500
    );
}

$branchStmt->bind_param("i", $assignedDepartmentId);

if (!$branchStmt->execute()) {
    $error = $branchStmt->error;
    $branchStmt->close();

    stopRequest(
        "Database error while finding the nearest branch: " . $error,
        500
    );
}

$branchResult = $branchStmt->get_result();
$nearestBranch = null;
$nearestDistanceMeters = null;
$nearestBranchRank = PHP_INT_MAX;

while ($branch = $branchResult->fetch_assoc()) {

    if (
        !is_numeric($branch["latitude"]) ||
        !is_numeric($branch["longitude"])
    ) {
        continue;
    }

    $distanceMeters = distanceInMeters(
        $latitude,
        $longitude,
        (float) $branch["latitude"],
        (float) $branch["longitude"]
    );

    $branchRankMap = [
        "district_branch" => 1,
        "local_branch" => 2,
        "state_hq" => 3,
        "national_hq" => 4
    ];

    $branchRank =
        $branchRankMap[$branch["branch_level"] ?? ""]
        ?? 5;

    $isCloser =
        $nearestDistanceMeters === null ||
        $distanceMeters < $nearestDistanceMeters - 0.001;

    $isEquivalentButPreferred =
        $nearestDistanceMeters !== null &&
        abs($distanceMeters - $nearestDistanceMeters) <= 0.001 &&
        $branchRank < $nearestBranchRank;

    if ($isCloser || $isEquivalentButPreferred) {
        $nearestBranch = $branch;
        $nearestDistanceMeters = $distanceMeters;
        $nearestBranchRank = $branchRank;
    }
}

$branchStmt->close();

if ($nearestBranch === null || $nearestDistanceMeters === null) {
    stopRequest(
        "No active branch serves the selected AI department.",
        500
    );
}

$assignedBranchId = (int) $nearestBranch["branch_id"];
$assignmentDistanceKm = round(
    $nearestDistanceMeters / 1000,
    3
);

/* Keep the case update and report/assignment history atomic. */
if (!$conn->begin_transaction()) {
    stopRequest(
        "Unable to start the report transaction.",
        500
    );
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
        longitude,
        ai_department_id,
        assigned_department_id,
        assigned_branch_id,
        assignment_distance_km,
        assigned_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
    "iisssssssssddiiid",
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
    $longitude,
    $aiDepartmentId,
    $assignedDepartmentId,
    $assignedBranchId,
    $assignmentDistanceKm
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

$reportId = (int) $conn->insert_id;
$stmt->close();


/*
|--------------------------------------------------------------------------
| Save the assignment history
|--------------------------------------------------------------------------
|
| This keeps an auditable record of which department/branch received the
| report and the distance used for the nearest-branch decision.
|
|--------------------------------------------------------------------------
*/

$historySql = "
    INSERT INTO report_assignment_history (
        report_id,
        department_id,
        branch_id,
        distance_km,
        assignment_method,
        assignment_note
    )
    VALUES (?, ?, ?, ?, ?, ?)
";

$historyStmt = $conn->prepare($historySql);

if (!$historyStmt) {
    stopRequest(
        "Database error while saving branch assignment history: " .
        $conn->error,
        500
    );
}

$assignmentMethod = "AI_NEAREST_BRANCH";
$assignmentNote =
    "Nearest active branch selected from the submitted GPS coordinates.";

$historyStmt->bind_param(
    "iiidss",
    $reportId,
    $assignedDepartmentId,
    $assignedBranchId,
    $assignmentDistanceKm,
    $assignmentMethod,
    $assignmentNote
);

if (!$historyStmt->execute()) {
    $error = $historyStmt->error;
    $historyStmt->close();

    stopRequest(
        "Database error while saving branch assignment history: " .
        $error,
        500
    );
}

$historyStmt->close();

if (!$conn->commit()) {
    stopRequest(
        "Unable to complete the report transaction.",
        500
    );
}


/*
|--------------------------------------------------------------------------
| Finish
|--------------------------------------------------------------------------
*/

$conn->close();

echo "Report submitted successfully!";
?>