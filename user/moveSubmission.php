<?php

session_start();

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

function response($success, $message, $status = 200) {

    http_response_code($status);

    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| ADMIN AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    response(
        false,
        'Unauthorized.',
        401
    );
}


/*
|--------------------------------------------------------------------------
| INPUT
|--------------------------------------------------------------------------
*/

$reportId = isset($_POST['report_id'])
    ? (int)$_POST['report_id']
    : 0;

$oldCaseId = isset($_POST['case_group_id'])
    ? (int)$_POST['case_group_id']
    : 0;

if ($reportId <= 0) {
    response(
        false,
        'Invalid report ID.'
    );
}


/*
|--------------------------------------------------------------------------
| ADMIN DEPARTMENT
|--------------------------------------------------------------------------
*/

$adminUserId = (int)($_SESSION['id'] ?? 0);
$departmentId = (int)($_SESSION['department_id'] ?? 0);
$department = trim((string)($_SESSION['department'] ?? ''));
$departmentLegacy = $department;
$accessScope = 'department';
$isNationalAdmin = false;

$adminStmt = $conn->prepare(
    'SELECT
        u.department_id,
        u.department,
        u.access_scope,
        d.department_name,
        d.legacy_name
     FROM users u
     LEFT JOIN departments d
       ON d.department_id = u.department_id
      AND d.is_active = 1
     WHERE u.id = ?
     LIMIT 1'
);

if (!$adminStmt) {
    response(false, 'Database error.', 500);
}

$adminStmt->bind_param('i', $adminUserId);
$adminStmt->execute();
$adminResult = $adminStmt->get_result();
$adminRow = $adminResult->fetch_assoc() ?: null;
$adminStmt->close();

if ($adminRow) {
    $accessScope = strtolower(trim((string)($adminRow['access_scope'] ?? 'department')));
    $isNationalAdmin = $accessScope === 'national';

    if (!empty($adminRow['department_id'])) {
        $departmentId = (int)$adminRow['department_id'];
    }

    $department = trim((string)($adminRow['department_name'] ?? $department));
    $departmentLegacy = trim((string)($adminRow['legacy_name'] ?? $departmentLegacy));
}

if ($departmentLegacy === '') {
    $departmentLegacy = $department;
}


/*
|--------------------------------------------------------------------------
| VERIFY REPORT
|--------------------------------------------------------------------------
|
| Make sure this report actually belongs to
| the department of the logged-in admin.
|
*/

$stmt = $conn->prepare(
    'SELECT
        report_id,
        case_group_id,
        ai_department,
        ai_department_id,
        assigned_department_id
     FROM reports
     WHERE report_id = ?
     LIMIT 1'
);

if (!$stmt) {
    response(
        false,
        'Database error.',
        500
    );
}

$stmt->bind_param(
    'i',
    $reportId
);

$stmt->execute();

$result =
    $stmt->get_result();

$report =
    $result->fetch_assoc();

$stmt->close();

if (!$report) {
    response(
        false,
        'Report not found.',
        404
    );
}

if (!$isNationalAdmin) {
    $reportDepartmentId = (int)($report['assigned_department_id'] ?? 0);
    $reportAiDepartmentId = (int)($report['ai_department_id'] ?? 0);
    $matchesDepartment = $reportDepartmentId === $departmentId
        || (
            $reportDepartmentId === 0
            && (
                $reportAiDepartmentId === $departmentId
                || strtolower(trim((string)$report['ai_department']))
                    === strtolower(trim($departmentLegacy))
            )
        );

    if (!$matchesDepartment) {
        response(
            false,
            'You cannot modify a report assigned to another department.',
            403
        );
    }
}


/*
|--------------------------------------------------------------------------
| VERIFY CURRENT GROUP
|--------------------------------------------------------------------------
*/

$currentCaseId =
    (int)($report['case_group_id'] ?? 0);

if (
    $oldCaseId > 0 &&
    $currentCaseId !== $oldCaseId
) {
    response(
        false,
        'This submission is no longer in the selected case.'
    );
}


/*
|--------------------------------------------------------------------------
| PREVENT MOVING A SINGLE-SUBMISSION CASE
|--------------------------------------------------------------------------
*/

$countStmt = $conn->prepare(
    'SELECT COUNT(*) AS total
     FROM reports
     WHERE case_group_id = ?'
);

if (!$countStmt) {
    response(
        false,
        'Database error.',
        500
    );
}

$countStmt->bind_param(
    'i',
    $currentCaseId
);

$countStmt->execute();

$countResult =
    $countStmt->get_result();

$countRow =
    $countResult->fetch_assoc();

$countStmt->close();

$totalInCase =
    (int)($countRow['total'] ?? 0);

if ($totalInCase <= 1) {
    response(
        false,
        'A case must contain at least one submission.'
    );
}


/*
|--------------------------------------------------------------------------
| CREATE NEW CASE
|--------------------------------------------------------------------------
|
| We use this report's own report_id as its new
| case_group_id.
|
| Example:
|
| Case #25
|   Report 25
|   Report 31
|   Report 44
|
| Move Report 31 out:
|
| Case #25
|   Report 25
|   Report 44
|
| Case #31
|   Report 31
|
*/

$newCaseId = $reportId;


/*
|--------------------------------------------------------------------------
| MOVE REPORT
|--------------------------------------------------------------------------
*/

$updateSql = $isNationalAdmin
    ? 'UPDATE reports
       SET case_group_id = ?
       WHERE report_id = ?'
    : 'UPDATE reports
       SET case_group_id = ?
       WHERE report_id = ?
         AND (
             assigned_department_id = ?
             OR (
                 assigned_department_id IS NULL
                 AND (
                     ai_department_id = ?
                     OR (
                         LOWER(CONVERT(ai_department USING utf8mb4)) COLLATE utf8mb4_unicode_ci =
                         LOWER(CONVERT(? USING utf8mb4)) COLLATE utf8mb4_unicode_ci
                     )
                 )
             )
         )';

$updateStmt = $conn->prepare($updateSql);

if (!$updateStmt) {
    response(
        false,
        'Database error.',
        500
    );
}

if ($isNationalAdmin) {
    $updateStmt->bind_param('ii', $newCaseId, $reportId);
} else {
    $updateStmt->bind_param(
        'iiiis',
        $newCaseId,
        $reportId,
        $departmentId,
        $departmentId,
        $departmentLegacy
    );
}

if (!$updateStmt->execute()) {

    $updateStmt->close();

    response(
        false,
        'Failed to move submission.',
        500
    );
}

$updateStmt->close();

$conn->close();


response(
    true,
    'Submission moved successfully.'
);
