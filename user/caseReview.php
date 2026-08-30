<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: LogIn.php');
    exit();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function statusGroup($status) {
    $status = strtolower(trim((string)$status));

    if (in_array($status, ['resolved', 'settled'], true)) {
        return 'settled';
    }

    if (in_array($status, ['assigned', 'in progress', 'underway'], true)) {
        return 'underway';
    }

    return 'action';
}

function priorityClass($priority) {
    $priority = strtolower(trim((string)$priority));

    return in_array(
        $priority,
        ['critical', 'high', 'medium', 'low'],
        true
    ) ? $priority : 'medium';
}

function validDate($date) {
    if ($date === '') {
        return true;
    }

    $parsed = DateTime::createFromFormat('Y-m-d', $date);

    return $parsed &&
        $parsed->format('Y-m-d') === $date;
}

function formatIssueType($issueType) {
    if (empty($issueType)) {
        return 'General Issue';
    }

    return ucwords(
        str_replace('_', ' ', (string)$issueType)
    );
}

function displayDate($value) {
    $timestamp = strtotime((string)$value);

    return $timestamp
        ? date('M d, Y', $timestamp)
        : 'Date unavailable';
}

/*
|--------------------------------------------------------------------------
| ADMIN INFORMATION
|--------------------------------------------------------------------------
*/

$adminUserId = (int)($_SESSION['id'] ?? 0);
$departmentId = (int)($_SESSION['department_id'] ?? 0);
$department = trim((string)($_SESSION['department'] ?? ''));
$departmentCode = '';
$departmentLegacyName = $department;

$accessScope = 'department';
$isNationalAdmin = false;

$departmentStatement = $conn->prepare(
    'SELECT
        u.department_id,
        u.access_scope,
        d.department_code,
        d.department_name,
        d.legacy_name
     FROM users u
     LEFT JOIN departments d
       ON d.department_id = u.department_id
      AND d.is_active = 1
     WHERE u.id = ?
     LIMIT 1'
);

if (!$departmentStatement) {
    exit('Unable to resolve your account.');
}

$departmentStatement->bind_param('i', $adminUserId);
$departmentStatement->execute();

$departmentResult = $departmentStatement->get_result();
$departmentRow = $departmentResult->fetch_assoc() ?: null;

$departmentStatement->close();

if ($departmentRow) {
    $accessScope = strtolower(
        trim((string)($departmentRow['access_scope'] ?? 'department'))
    );

    $isNationalAdmin = $accessScope === 'national';
}

if ($departmentRow && !empty($departmentRow['department_id'])) {
    $departmentId = (int)$departmentRow['department_id'];
}

if (!$isNationalAdmin && $departmentId <= 0 && $department !== '') {
    $lookupStatement = $conn->prepare(
        'SELECT department_id, department_code, department_name, legacy_name
         FROM departments
         WHERE department_code = ?
            OR department_name = ?
            OR legacy_name = ?
         LIMIT 1'
    );

    if ($lookupStatement) {
        $lookupStatement->bind_param(
            'sss',
            $department,
            $department,
            $department
        );

        $lookupStatement->execute();

        $lookupResult = $lookupStatement->get_result();
        $departmentRow = $lookupResult->fetch_assoc() ?: null;

        $lookupStatement->close();

        if ($departmentRow) {
            $departmentId = (int)$departmentRow['department_id'];
        }
    }
}

if (!$isNationalAdmin && ($departmentId <= 0 || !$departmentRow)) {
    exit('Your admin account has no valid department assigned.');
}

if ($isNationalAdmin) {
    $departmentCode = 'NATIONAL';
    $department = 'Malaysia-wide Administration';
    $departmentLegacyName = '';
} else {
    $departmentCode = trim(
        (string)($departmentRow['department_code'] ?? '')
    );

    $department = trim(
        (string)($departmentRow['department_name'] ?? $department)
    );

    $departmentLegacyName = trim(
        (string)($departmentRow['legacy_name'] ?? $department)
    );
}

$adminName = trim(
    (string)($_SESSION['name'] ?? 'Admin')
);

$adminName = $adminName !== ''
    ? $adminName
    : 'Admin';

$adminInitial = function_exists('mb_substr')
    ? mb_strtoupper(
        mb_substr($adminName, 0, 1, 'UTF-8'),
        'UTF-8'
    )
    : strtoupper(
        substr($adminName, 0, 1)
    );

/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$search = trim(
    (string)($_GET['search'] ?? '')
);

$priority = strtolower(
    trim((string)($_GET['priority'] ?? ''))
);

$selectedDate = (string)(
    $_GET['date'] ?? ''
);

$selectedBranchId = (int)(
    $_GET['branch_id'] ?? 0
);

if ($selectedBranchId < 0) {
    $selectedBranchId = 0;
}

$openSection = strtolower(
    trim((string)($_GET['open'] ?? 'action'))
);

$allowedPriorities = [
    '',
    'critical',
    'high',
    'medium',
    'low'
];

$allowedSections = [
    'all',
    'action',
    'underway',
    'settled'
];

if (!in_array(
    $priority,
    $allowedPriorities,
    true
)) {
    $priority = '';
}

if (!validDate($selectedDate)) {
    $selectedDate = '';
}

if (!in_array(
    $openSection,
    $allowedSections,
    true
)) {
    $openSection = 'action';
}

/*
|--------------------------------------------------------------------------
| LOAD BRANCHES FOR THIS DEPARTMENT
|--------------------------------------------------------------------------
|
| A department admin can only choose branches linked to the department.
| The selected branch ID is validated against this list before reports load.
|
*/

$branchSql = $isNationalAdmin
    ? 'SELECT
            b.branch_id,
            b.branch_code,
            b.branch_name,
            b.state_name,
            b.district_name
       FROM branches b
       WHERE b.is_active = 1
       ORDER BY b.state_name, b.district_name, b.branch_name'
    : 'SELECT
            b.branch_id,
            b.branch_code,
            b.branch_name,
            b.state_name,
            b.district_name
       FROM branches b
       JOIN branch_departments bd
         ON bd.branch_id = b.branch_id
        AND bd.department_id = ?
        AND bd.is_active = 1
       WHERE b.is_active = 1
       ORDER BY b.state_name, b.district_name, b.branch_name';

$branchStatement = $conn->prepare($branchSql);

if (!$branchStatement) {
    exit('Unable to load branches.');
}

if (!$isNationalAdmin) {
    $branchStatement->bind_param('i', $departmentId);
}

$branchStatement->execute();
$branchResult = $branchStatement->get_result();
$branches = [];
$allowedBranchIds = [];

while ($branch = $branchResult->fetch_assoc()) {
    $branch['branch_id'] = (int)$branch['branch_id'];
    $branches[] = $branch;
    $allowedBranchIds[$branch['branch_id']] = true;
}

$branchStatement->close();

if (
    $selectedBranchId > 0 &&
    !isset($allowedBranchIds[$selectedBranchId])
) {
    $selectedBranchId = 0;
}

/*
|--------------------------------------------------------------------------
| LOAD REPORTS
|--------------------------------------------------------------------------
|
| IMPORTANT:
| The normalized assigned_department_id is authoritative. The legacy text
| fallback only keeps older reports visible until all old records are
| migrated; it cannot grant access to another department.
|
*/

$reportSql = $isNationalAdmin
    ? 'SELECT
            r.*,
            b.branch_code,
            b.branch_name,
            b.state_name AS branch_state,
            b.district_name AS branch_district
       FROM reports r
       LEFT JOIN branches b
         ON b.branch_id = r.assigned_branch_id
       WHERE 1 = 1'
    : 'SELECT
            r.*,
            b.branch_code,
            b.branch_name,
            b.state_name AS branch_state,
            b.district_name AS branch_district
       FROM reports r
       LEFT JOIN branches b
         ON b.branch_id = r.assigned_branch_id
       WHERE (
            r.assigned_department_id = ?
            OR (
                r.assigned_department_id IS NULL
                AND LOWER(r.ai_department) = LOWER(?)
            )
       )';

if ($selectedBranchId > 0) {
    $reportSql .= ' AND r.assigned_branch_id = ?';
}

$reportSql .= ' ORDER BY r.created_at DESC, r.report_id DESC';

$statement = $conn->prepare($reportSql);

if (!$statement) {
    exit('Unable to load reports.');
}

if ($isNationalAdmin && $selectedBranchId > 0) {
    $statement->bind_param('i', $selectedBranchId);
} elseif (!$isNationalAdmin && $selectedBranchId > 0) {
    $statement->bind_param(
        'isi',
        $departmentId,
        $departmentLegacyName,
        $selectedBranchId
    );
} elseif (!$isNationalAdmin) {
    $statement->bind_param(
        'is',
        $departmentId,
        $departmentLegacyName
    );
}

$statement->execute();

$result = $statement->get_result();

$allReports = [];

while ($row = $result->fetch_assoc()) {
    $allReports[] = $row;
}

$statement->close();

/*
|--------------------------------------------------------------------------
| FILTER INDIVIDUAL SUBMISSIONS
|--------------------------------------------------------------------------
*/

$filteredReports = array_values(
    array_filter(
        $allReports,
        function ($report) use (
            $search,
            $priority,
            $selectedDate
        ) {

            /*
             * Priority filter
             */
            if (
                $priority !== '' &&
                strtolower(
                    (string)($report['ai_priority'] ?? '')
                ) !== $priority
            ) {
                return false;
            }

            /*
             * Date filter
             */
            if ($selectedDate !== '') {

                $timestamp = strtotime(
                    (string)($report['created_at'] ?? '')
                );

                $reportDate = $timestamp
                    ? date('Y-m-d', $timestamp)
                    : '';

                if ($reportDate !== $selectedDate) {
                    return false;
                }
            }

            /*
             * Search filter
             */
            if ($search === '') {
                return true;
            }

            $searchableText = implode(
                ' ',
                [
                    $report['report_id'] ?? '',
                    $report['issue_type'] ?? '',
                    $report['location'] ?? '',
                    $report['extra_details'] ?? '',
                    $report['ai_description'] ?? ''
                ]
            );

            return stripos(
                $searchableText,
                $search
            ) !== false;
        }
    )
);

/*
|--------------------------------------------------------------------------
| GROUP REPORTS INTO CASES
|--------------------------------------------------------------------------
|
| Every report with the same case_group_id
| becomes one case.
|
*/

$cases = [];

foreach ($filteredReports as $report) {

    /*
     * Fallback to report_id if an old report somehow
     * does not have a case_group_id.
     */
    $caseGroupId = !empty($report['case_group_id'])
        ? (int)$report['case_group_id']
        : (int)$report['report_id'];

    if (!isset($cases[$caseGroupId])) {

        $cases[$caseGroupId] = [
            'case_group_id' => $caseGroupId,
            'submissions' => [],
            'latest_report' => $report
        ];
    }

    $cases[$caseGroupId]['submissions'][] = $report;

    /*
     * Keep the newest report as the representative
     * report for the case card.
     */
    if (
        strtotime(
            (string)($report['created_at'] ?? '')
        ) >
        strtotime(
            (string)($cases[$caseGroupId]['latest_report']['created_at'] ?? '')
        )
    ) {
        $cases[$caseGroupId]['latest_report'] = $report;
    }
}

/*
|--------------------------------------------------------------------------
| SORT CASES BY LATEST SUBMISSION
|--------------------------------------------------------------------------
*/

usort(
    $cases,
    function ($a, $b) {

        $aTime = strtotime(
            (string)(
                $a['latest_report']['created_at'] ?? ''
            )
        );

        $bTime = strtotime(
            (string)(
                $b['latest_report']['created_at'] ?? ''
            )
        );

        return $bTime <=> $aTime;
    }
);

/*
|--------------------------------------------------------------------------
| GROUP CASES BY STATUS
|--------------------------------------------------------------------------
*/

$groupedCases = [
    'action' => [],
    'underway' => [],
    'settled' => []
];

foreach ($cases as $case) {

    $latestReport = $case['latest_report'];

    $statusSection = statusGroup(
        $latestReport['status'] ?? 'Pending'
    );

    $groupedCases[$statusSection][] = $case;
}

/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Summary now counts CASES, not individual submissions.
|
*/

$summary = [
    'total' => count($cases),
    'action' => count($groupedCases['action']),
    'underway' => count($groupedCases['underway']),
    'settled' => count($groupedCases['settled'])
];

$hasFilters =
    $search !== '' ||
    $priority !== '' ||
    $selectedDate !== '' ||
    $selectedBranchId > 0;

/*
|--------------------------------------------------------------------------
| SECTION INFORMATION
|--------------------------------------------------------------------------
*/

$sections = [
    'action' => [
        'title' => 'Action Needed',
        'subtitle' => 'New cases waiting for department action.',
        'empty' => 'No action-needed cases match the filters.'
    ],

    'underway' => [
        'title' => 'Underway',
        'subtitle' => 'Cases currently being handled.',
        'empty' => 'No underway cases match the filters.'
    ],

    'settled' => [
        'title' => 'Settled',
        'subtitle' => 'Cases that have been resolved.',
        'empty' => 'No settled cases match the filters.'
    ]
];

/*
|--------------------------------------------------------------------------
| MAP DATA
|--------------------------------------------------------------------------
|
| Use the latest submission from each case so the map
| also shows one marker per case.
|
*/

$mapReports = [];

foreach ($cases as $case) {

    $report = $case['latest_report'];

    if (
        isset(
            $report['latitude'],
            $report['longitude']
        ) &&
        is_numeric($report['latitude']) &&
        is_numeric($report['longitude'])
    ) {

        $mapReports[] = [
            'case_id' => (int)$case['case_group_id'],

            'id' => (int)(
                $report['report_id'] ?? 0
            ),

            'latitude' => (float)$report['latitude'],

            'longitude' => (float)$report['longitude'],

            'issue' => formatIssueType(
                $report['issue_type'] ?? ''
            ),

            'priority' =>
                $report['ai_priority'] ??
                'Not analysed',

            'status' =>
                $report['status'] ??
                'Pending',

            'location' =>
                $report['location'] ??
                'Location unavailable',

            'branch' =>
                $report['branch_name'] ??
                'Branch not assigned',

            'submissions' =>
                count($case['submissions'])
        ];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Case Review - AI City Guardian</title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
>

<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>

<link
    href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
    rel="stylesheet"
>

<style>

:root {
    --navy-950: #090f32;
    --navy-900: #101741;
    --navy-850: #141d4d;
    --navy-800: #1a2454;
    --blue: #3375f5;
    --blue-light: #6b9aff;
    --text: #f8faff;
    --muted: #9ca8ca;
    --line: rgba(151, 166, 210, .22);
    --line-strong: rgba(151, 166, 210, .38);
    --green: #34d399;
    --red: #fb7185;
    --amber: #fbbf24;
    --sidebar: 278px;
    --shadow: 0 20px 55px rgba(2, 6, 23, .24);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html {
    color-scheme: dark;
    scroll-behavior: smooth;
}

body {
    min-width: 320px;
    min-height: 100vh;
    overflow-x: hidden;
    color: var(--text);
    background:
        radial-gradient(
            circle at 82% 0,
            rgba(51,117,245,.14),
            transparent 34rem
        ),
        var(--navy-950);
    font-family: 'Poppins', Arial, sans-serif;
}

button,
input,
select {
    font: inherit;
}

button {
    cursor: pointer;
}

a {
    color: inherit;
    text-decoration: none;
}

button:focus-visible,
a:focus-visible,
input:focus-visible,
select:focus-visible {
    outline: 3px solid rgba(107,154,255,.58);
    outline-offset: 3px;
}

/* SIDEBAR */

.sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    z-index: 50;

    display: flex;

    width: var(--sidebar);
    min-height: 100vh;

    flex-direction: column;

    padding: 27px 21px 21px;

    border-right:
        1px solid rgba(151,166,210,.15);

    background:
        rgba(24,34,79,.98);

    transition: transform .22s ease;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;

    padding: 3px 7px 30px;

    font-size: 20px;
    font-weight: 700;
    letter-spacing: -.04em;
}

.brand-mark {
    display: grid;

    width: 42px;
    height: 42px;

    flex: 0 0 auto;

    place-items: center;

    border-radius: 13px;

    background:
        linear-gradient(
            145deg,
            var(--blue-light),
            var(--blue)
        );

    box-shadow:
        0 10px 24px rgba(51,117,245,.3);

    font-size: 22px;
}

.nav-label {
    padding: 8px 12px 10px;

    color: #7180aa;

    font-size: 10px;
    font-weight: 700;

    letter-spacing: .16em;

    text-transform: uppercase;
}

.sidebar-nav {
    display: grid;
    gap: 9px;
}

.nav-link {
    display: flex;

    min-height: 55px;

    align-items: center;
    gap: 14px;

    padding: 0 16px;

    border: 1px solid transparent;
    border-radius: 14px;

    color: #b5bfdc;

    font-size: 14px;
    font-weight: 600;

    transition: .18s ease;
}

.nav-link i {
    width: 23px;
    font-size: 22px;
    text-align: center;
}

.nav-link:hover {
    color: #fff;

    border-color:
        rgba(255,255,255,.08);

    background:
        rgba(255,255,255,.05);

    transform: translateX(2px);
}

.nav-link.active {
    color: #fff;

    border-color:
        rgba(255,255,255,.36);

    background:
        linear-gradient(
            135deg,
            #397af8,
            #2863df
        );

    box-shadow:
        0 12px 28px rgba(21,84,220,.27);
}

.sidebar-note {
    margin-top: 24px;

    padding: 15px;

    border: 1px solid var(--line);
    border-radius: 14px;

    background:
        rgba(255,255,255,.035);
}

.sidebar-note i {
    color: var(--amber);
    font-size: 20px;
}

.sidebar-note strong {
    display: block;

    margin: 7px 0 4px;

    font-size: 12px;
}

.sidebar-note p {
    color: #7e8bb1;

    font-size: 9px;
    line-height: 1.6;
}

.sidebar-footer {
    position: relative;

    margin-top: auto;

    padding-top: 20px;

    border-top:
        1px solid rgba(151,166,210,.14);
}

.profile-button {
    display: grid;

    width: 100%;

    grid-template-columns:
        auto 1fr auto;

    align-items: center;

    gap: 10px;

    padding: 9px;

    border: 1px solid transparent;
    border-radius: 14px;

    color: #c0c9e3;

    background: transparent;

    text-align: left;
}

.profile-button:hover,
.profile-button[aria-expanded='true'] {
    color: #fff;

    border-color: var(--line);

    background:
        rgba(255,255,255,.05);
}

.profile-avatar {
    display: grid;

    width: 38px;
    height: 38px;

    place-items: center;

    border:
        1px solid rgba(107,154,255,.42);

    border-radius: 12px;

    color: #fff;

    background:
        rgba(51,117,245,.2);

    font-weight: 700;
}

.profile-copy {
    display: grid;

    min-width: 0;

    gap: 1px;
}

.profile-copy strong,
.profile-copy small {
    overflow: hidden;

    text-overflow: ellipsis;
    white-space: nowrap;
}

.profile-copy strong {
    font-size: 11px;
}

.profile-copy small {
    color: #7f8db4;
    font-size: 8px;
}

.profile-menu {
    position: absolute;

    right: 0;
    bottom: calc(100% + 9px);
    left: 0;

    display: none;

    padding: 7px;

    border:
        1px solid var(--line-strong);

    border-radius: 13px;

    background: #111946;

    box-shadow: var(--shadow);
}

.profile-menu.open {
    display: block;
}

.profile-menu a {
    display: flex;

    align-items: center;

    gap: 9px;

    padding: 10px;

    border-radius: 9px;

    color: #f7b5bf;

    font-size: 10px;
    font-weight: 600;
}

.profile-menu a:hover {
    background:
        rgba(251,113,133,.1);
}

.sidebar-overlay {
    display: none;
}

/* PAGE */

.page-content {
    min-height: 100vh;

    margin-left: var(--sidebar);

    padding:
        28px clamp(22px,4vw,58px);
}

.mobile-menu {
    display: none;

    width: 43px;
    height: 43px;

    place-items: center;

    border:
        1px solid var(--line);

    border-radius: 12px;

    color: #fff;

    background:
        rgba(255,255,255,.04);

    font-size: 22px;
}

.page-header {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 22px;

    margin-bottom: 21px;
}

.header-copy {
    display: flex;

    align-items: center;

    gap: 14px;
}

.eyebrow {
    display: block;

    margin-bottom: 3px;

    color: var(--blue-light);

    font-size: 9px;
    font-weight: 700;

    letter-spacing: .15em;

    text-transform: uppercase;
}

.page-header h1 {
    font-size: clamp(24px,3vw,35px);

    letter-spacing: -.045em;
}

.page-header p {
    margin-top: 4px;

    color: var(--muted);

    font-size: 11px;
}

/* SUMMARY */

.summary-grid {
    display: grid;

    grid-template-columns:
        repeat(4,minmax(0,1fr));

    gap: 13px;

    margin-bottom: 18px;
}

.summary-card {
    display: flex;

    width: 100%;
    min-height: 116px;

    align-items: center;

    gap: 13px;

    padding: 16px;

    border:
        1px solid var(--line);

    border-radius: 16px;

    color: #fff;

    background:
        linear-gradient(
            145deg,
            rgba(21,31,80,.84),
            rgba(13,20,59,.84)
        );

    box-shadow: var(--shadow);

    text-align: left;

    transition: .18s ease;
}

.summary-card:hover,
.summary-card.active {
    border-color:
        rgba(107,154,255,.48);

    background:
        rgba(24,38,98,.86);

    transform: translateY(-2px);
}

.summary-icon {
    display: grid;

    width: 43px;
    height: 43px;

    flex: 0 0 auto;

    place-items: center;

    border-radius: 13px;

    color: var(--blue-light);

    background:
        rgba(51,117,245,.12);

    font-size: 21px;
}

.summary-card.action .summary-icon {
    color: #fda4af;
    background: rgba(251,113,133,.1);
}

.summary-card.underway .summary-icon {
    color: #fde68a;
    background: rgba(251,191,36,.1);
}

.summary-card.settled .summary-icon {
    color: #a7f3d0;
    background: rgba(52,211,153,.1);
}

.summary-copy {
    min-width: 0;
}

.summary-copy strong {
    display: block;

    font-size: 21px;

    letter-spacing: -.03em;
}

.summary-copy span {
    display: block;

    margin-top: 2px;

    color: #8996b9;

    font-size: 9px;
}

/* FILTER */

.filter-panel {
    margin-bottom: 18px;

    padding: 15px;

    border:
        1px solid var(--line);

    border-radius: 16px;

    background:
        rgba(17,24,68,.74);

    box-shadow: var(--shadow);
}

.filter-form {
    display: flex;

    align-items: center;

    gap: 9px;
}

.search-field {
    position: relative;

    min-width: 230px;

    flex: 1;
}

.search-field i {
    position: absolute;

    top: 50%;
    left: 13px;

    color: #7482aa;

    font-size: 18px;

    transform: translateY(-50%);
}

.filter-input,
.filter-select {
    width: 100%;
    height: 43px;

    border:
        1px solid var(--line);

    border-radius: 11px;

    outline: 0;

    color: #fff;

    background:
        rgba(8,15,53,.58);

    color-scheme: dark;

    font-size: 10px;
}

.filter-input {
    padding: 0 13px 0 40px;
}

.filter-select {
    min-width: 145px;

    padding: 0 11px;

    cursor: pointer;
}

.filter-input::placeholder {
    color: #5f6c94;
}

.filter-input:focus,
.filter-select:focus {
    border-color: var(--blue-light);

    box-shadow:
        0 0 0 3px rgba(107,154,255,.09);
}

.filter-button,
.clear-button {
    display: inline-flex;

    height: 43px;

    flex: 0 0 auto;

    align-items: center;
    justify-content: center;

    gap: 7px;

    padding: 0 13px;

    border-radius: 11px;

    font-size: 10px;
    font-weight: 700;
}

.filter-button {
    border:
        1px solid rgba(255,255,255,.12);

    color: #fff;

    background: var(--blue);
}

.filter-button:hover {
    background: #4380f6;
}

.clear-button {
    border:
        1px solid var(--line);

    color: #aeb9d6;

    background:
        rgba(255,255,255,.035);
}

/* DEPARTMENT */

.department-banner {
    display: flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 18px;

    padding: 14px 16px;

    border:
        1px solid rgba(107,154,255,.28);

    border-radius: 14px;

    background:
        rgba(51,117,245,.075);
}

.department-banner i {
    display: grid;

    width: 38px;
    height: 38px;

    flex: 0 0 auto;

    place-items: center;

    border-radius: 11px;

    color: var(--blue-light);

    background:
        rgba(51,117,245,.12);

    font-size: 19px;
}

.department-banner span {
    display: block;

    color: #8090ba;

    font-size: 8px;
    font-weight: 700;

    text-transform: uppercase;
}

.department-banner strong {
    display: block;

    margin-top: 2px;

    font-size: 11px;
}

.department-scope {
    display: block;

    margin-top: 4px;

    color: #7180a9;

    font-size: 8px;
}

/* MAP */

.map-panel {
    margin-bottom: 18px;

    padding: 21px;

    border:
        1px solid var(--line);

    border-radius: 18px;

    background:
        linear-gradient(
            145deg,
            rgba(21,31,80,.82),
            rgba(13,20,59,.82)
        );

    box-shadow: var(--shadow);
}

.map-heading {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 15px;

    margin-bottom: 15px;
}

.map-heading h2 {
    font-size: 15px;
}

.map-heading p {
    margin-top: 3px;

    color: #7f8db4;

    font-size: 9px;
}

.map-count {
    color: #8190b6;

    font-size: 9px;
}

#reportsMap {
    width: 100%;
    height: 430px;

    overflow: hidden;

    border:
        1px solid var(--line);

    border-radius: 14px;
}

/* CASE SECTION */

.section-shell {
    margin-top: 18px;

    padding: 21px;

    border:
        1px solid var(--line);

    border-radius: 18px;

    background:
        linear-gradient(
            145deg,
            rgba(21,31,80,.82),
            rgba(13,20,59,.82)
        );

    box-shadow: var(--shadow);
}

.section-heading {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 15px;

    margin-bottom: 16px;
}

.section-heading h2 {
    font-size: 15px;
}

.section-heading p {
    margin-top: 3px;

    color: #7f8db4;

    font-size: 9px;
}

.result-count {
    color: #8190b6;

    font-size: 9px;
}

.case-sections {
    display: grid;

    gap: 13px;
}

.case-section {
    overflow: hidden;

    border:
        1px solid var(--line);

    border-radius: 15px;

    background:
        rgba(8,15,53,.4);
}

.section-toggle {
    display: grid;

    width: 100%;

    grid-template-columns:
        auto 1fr auto auto;

    align-items: center;

    gap: 12px;

    padding: 15px 16px;

    border: 0;

    color: #fff;

    background: transparent;

    text-align: left;
}

.section-toggle:hover,
.section-toggle[aria-expanded='true'] {
    background:
        rgba(51,117,245,.075);
}

.section-toggle-icon {
    display: grid;

    width: 38px;
    height: 38px;

    place-items: center;

    border-radius: 11px;

    color: var(--blue-light);

    background:
        rgba(51,117,245,.1);

    font-size: 19px;
}

.section-toggle-copy strong {
    display: block;

    font-size: 12px;
}

.section-toggle-copy small {
    display: block;

    margin-top: 2px;

    color: #7685ad;

    font-size: 8px;
}

.case-count {
    display: grid;

    min-width: 34px;
    height: 29px;

    place-items: center;

    padding: 0 8px;

    border:
        1px solid var(--line);

    border-radius: 999px;

    color: #dbe4fa;

    background:
        rgba(255,255,255,.04);

    font-size: 9px;
    font-weight: 700;
}

.section-toggle > i {
    color: #7e8cb2;

    font-size: 20px;

    transition: transform .18s ease;
}

.section-toggle[aria-expanded='true'] > i {
    transform: rotate(180deg);
}

.accordion-content {
    padding: 0 14px 14px;

    border-top:
        1px solid var(--line);
}

.accordion-content[hidden] {
    display: none;
}

/* CASE GRID */

.case-grid {
    display: grid;

    grid-template-columns:
        repeat(2,minmax(0,1fr));

    gap: 12px;

    padding-top: 14px;
}

.case-card {
    display: flex;

    min-width: 0;

    overflow: hidden;

    border:
        1px solid var(--line);

    border-radius: 13px;

    background:
        rgba(14,23,67,.75);
}

.case-image {
    display: grid;

    width: 128px;
    min-height: 225px;

    flex: 0 0 auto;

    overflow: hidden;

    place-items: center;

    color: #596990;

    background: #080f35;

    font-size: 31px;
}

.case-image img {
    width: 100%;
    height: 100%;

    object-fit: cover;
}

.case-body {
    display: flex;

    min-width: 0;

    flex: 1;

    flex-direction: column;

    padding: 14px;
}

.case-top {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 9px;

    color: #7180a9;

    font-size: 8px;
    font-weight: 700;

    text-transform: uppercase;
}

.case-title {
    margin-top: 9px;

    font-size: 12px;

    line-height: 1.45;
}

.case-location {
    display: flex;

    min-height: 35px;

    align-items: flex-start;

    gap: 5px;

    margin-top: 7px;

    color: #98a4c3;

    font-size: 8px;

    line-height: 1.5;
}

.case-location i {
    flex: 0 0 auto;

    color: var(--blue-light);

    font-size: 13px;
}

.case-branch {
    display: flex;

    min-height: 25px;

    align-items: flex-start;
    gap: 5px;

    margin-top: 6px;

    color: #9eb7f1;

    font-size: 8px;

    line-height: 1.5;
}

.case-branch i {
    flex: 0 0 auto;

    color: var(--green);

    font-size: 13px;
}

.case-description {
    display: -webkit-box;

    min-height: 43px;

    margin-top: 8px;

    overflow: hidden;

    color: #b6c0da;

    font-size: 8px;

    line-height: 1.65;

    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
}

.case-extra {
    display: -webkit-box;

    min-height: 28px;

    margin-top: 7px;

    overflow: hidden;

    color: #7483ab;

    font-size: 8px;

    line-height: 1.55;

    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.case-actions {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 10px;

    margin-top: auto;

    padding-top: 11px;
}

.priority-badge {
    padding: 5px 8px;

    border-radius: 999px;

    font-size: 8px;
    font-weight: 700;

    text-transform: capitalize;
}

.priority-badge.critical {
    color: #fecdd3;
    background: rgba(244,63,94,.13);
}

.priority-badge.high {
    color: #fed7aa;
    background: rgba(249,115,22,.13);
}

.priority-badge.medium {
    color: #fde68a;
    background: rgba(245,158,11,.13);
}

.priority-badge.low {
    color: #a7f3d0;
    background: rgba(16,185,129,.13);
}

.review-button {
    display: inline-flex;

    min-height: 34px;

    align-items: center;

    gap: 5px;

    padding: 0 10px;

    border:
        1px solid rgba(107,154,255,.3);

    border-radius: 9px;

    color: #dce6ff;

    background:
        rgba(51,117,245,.12);

    font-size: 8px;
    font-weight: 700;
}

.review-button:hover {
    color: #fff;

    border-color:
        var(--blue-light);

    background:
        rgba(51,117,245,.22);
}

.submission-count {
    display: inline-flex;

    align-items: center;

    gap: 4px;

    padding: 5px 8px;

    border-radius: 999px;

    color: #bcd0ff;

    background:
        rgba(51,117,245,.12);

    font-size: 8px;
    font-weight: 700;
}

.no-reports {
    padding: 28px 15px 14px;

    color: #7482a8;

    font-size: 9px;

    text-align: center;
}

/* MODAL */

.modal-overlay {
    position: fixed;

    inset: 0;

    z-index: 100;

    display: none;

    align-items: center;
    justify-content: center;

    padding: 20px;

    background:
        rgba(3,7,24,.78);

    backdrop-filter: blur(7px);
}

.modal-overlay.open {
    display: flex;
}

.case-modal {
    width: min(760px,100%);

    max-height: 88vh;

    overflow: hidden;

    border:
        1px solid var(--line-strong);

    border-radius: 20px;

    background:
        linear-gradient(
            145deg,
            #151f50,
            #0d143b
        );

    box-shadow:
        0 30px 90px rgba(0,0,0,.5);
}

.modal-header {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 15px;

    padding: 20px;

    border-bottom:
        1px solid var(--line);
}

.modal-header h2 {
    font-size: 17px;
}

.modal-header p {
    margin-top: 3px;

    color: #7f8db4;

    font-size: 9px;
}

.modal-close {
    display: grid;

    width: 38px;
    height: 38px;

    place-items: center;

    border:
        1px solid var(--line);

    border-radius: 10px;

    color: #b8c4df;

    background:
        rgba(255,255,255,.04);

    font-size: 20px;
}

.modal-close:hover {
    color: #fff;

    background:
        rgba(255,255,255,.08);
}

.submission-list {
    max-height: calc(88vh - 100px);

    overflow-y: auto;

    padding: 15px 20px 20px;
}

.submission-item {
    display: grid;

    grid-template-columns:
        72px 1fr auto;

    align-items: center;

    gap: 13px;

    padding: 12px;

    margin-bottom: 9px;

    border:
        1px solid var(--line);

    border-radius: 13px;

    background:
        rgba(8,15,53,.45);
}

.submission-item:last-child {
    margin-bottom: 0;
}

.submission-image {
    width: 72px;
    height: 72px;

    overflow: hidden;

    border-radius: 10px;

    background: #080f35;
}

.submission-image img {
    width: 100%;
    height: 100%;

    object-fit: cover;
}

.submission-image i {
    display: grid;

    width: 100%;
    height: 100%;

    place-items: center;

    color: #596990;

    font-size: 24px;
}

.submission-info {
    min-width: 0;
}

.submission-info strong {
    display: block;

    font-size: 11px;
}

.submission-info span {
    display: block;

    margin-top: 4px;

    color: #8996b9;

    font-size: 8px;
}

.submission-info p {
    display: -webkit-box;

    margin-top: 5px;

    overflow: hidden;

    color: #b7c2dd;

    font-size: 8px;

    line-height: 1.5;

    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.submission-actions {
    display: flex;

    flex-direction: column;

    gap: 6px;
}

.view-submission,
.move-submission {
    display: inline-flex;

    min-height: 32px;

    align-items: center;
    justify-content: center;

    gap: 5px;

    padding: 0 10px;

    border-radius: 8px;

    font-size: 8px;
    font-weight: 700;

    white-space: nowrap;
}

.view-submission {
    border:
        1px solid rgba(107,154,255,.3);

    color: #dce6ff;

    background:
        rgba(51,117,245,.12);
}

.view-submission:hover {
    background:
        rgba(51,117,245,.22);
}

.move-submission {
    border:
        1px solid rgba(251,113,133,.22);

    color: #fda4af;

    background:
        rgba(251,113,133,.07);
}

.move-submission:hover {
    background:
        rgba(251,113,133,.14);
}

/* FOOTER */

.page-footer {
    margin-top: 38px;

    padding: 20px 2px 3px;

    border-top:
        1px solid rgba(151,166,210,.11);

    color: #69779f;

    font-size: 9px;

    text-align: center;
}

/* RESPONSIVE */

@media (max-width:1180px) {

    .summary-grid {
        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }

    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .filter-form {
        width: 100%;

        flex-wrap: wrap;
    }

    .search-field {
        min-width: min(100%,300px);
    }

    .case-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width:900px) {

    .sidebar {
        transform: translateX(-100%);
    }

    .nav-open .sidebar {
        transform: translateX(0);
    }

    .sidebar-overlay {
        position: fixed;

        inset: 0;

        z-index: 45;

        display: block;

        visibility: hidden;

        opacity: 0;

        background:
            rgba(3,7,24,.68);

        backdrop-filter: blur(3px);

        transition: .22s ease;
    }

    .nav-open .sidebar-overlay {
        visibility: visible;
        opacity: 1;
    }

    .page-content {
        margin-left: 0;
    }

    .mobile-menu {
        display: grid;
    }
}

@media (max-width:650px) {

    .page-content {
        padding: 19px 13px;
    }

    .summary-grid {
        grid-template-columns: 1fr;
    }

    .summary-card {
        min-height: 99px;
    }

    .filter-form {
        align-items: stretch;
        flex-direction: column;
    }

    .search-field,
    .filter-select,
    .filter-button,
    .clear-button {
        width: 100%;
        min-width: 0;
    }

    .section-shell {
        padding: 15px;
    }

    .section-toggle {
        grid-template-columns:
            auto 1fr auto;
    }

    .section-toggle-copy small {
        display: none;
    }

    .section-toggle > i {
        display: none;
    }

    .case-card {
        flex-direction: column;
    }

    .case-image {
        width: 100%;
        height: 180px;
        min-height: 180px;
    }

    .map-panel {
        padding: 15px;
    }

    #reportsMap {
        height: 350px;
    }

    .map-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .submission-item {
        grid-template-columns: 58px 1fr;
    }

    .submission-image {
        width: 58px;
        height: 58px;
    }

    .submission-actions {
        grid-column: 1 / -1;

        flex-direction: row;
    }

    .view-submission,
    .move-submission {
        flex: 1;
    }
}

@media (prefers-reduced-motion:reduce) {

    *,
    *::before {
        scroll-behavior: auto !important;
        transition: none !important;
    }
}

</style>

</head>

<body>

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>

<aside class="sidebar">

    <a
        class="brand"
        href="caseReview.php"
    >
        <span class="brand-mark">
            <i class="bx bxs-city"></i>
        </span>

        <span>AI City Guardian</span>
    </a>

    <p class="nav-label">
        Admin portal
    </p>

    <nav class="sidebar-nav">

        <a
            class="nav-link active"
            href="caseReview.php"
            aria-current="page"
        >
            <i class="bx bxs-dashboard"></i>
            <span>Case Review</span>
        </a>

        <a
            class="nav-link"
            href="adminStatistics.php"
        >
            <i class="bx bx-bar-chart-alt-2"></i>
            <span>Statistics</span>
        </a>

    </nav>

    <div class="sidebar-note">

        <i class="bx bx-buildings"></i>

        <strong>
            Your department queue
        </strong>

        <p>
            Only reports assigned to
            <?php echo e($department); ?>
            are shown here.
        </p>

    </div>

    <div class="sidebar-footer">

        <button
            class="profile-button"
            id="profileButton"
            type="button"
            aria-expanded="false"
        >

            <span class="profile-avatar">
                <?php echo e($adminInitial); ?>
            </span>

            <span class="profile-copy">

                <strong>
                    <?php echo e($adminName); ?>
                </strong>

                <small>
                    <?php echo e($department); ?>
                </small>

            </span>

            <i class="bx bx-chevron-up"></i>

        </button>

        <div
            class="profile-menu"
            id="profileMenu"
        >

            <a href="logout.php">
                <i class="bx bx-log-out"></i>
                Log out
            </a>

        </div>

    </div>

</aside>

<div class="page-content">

<header class="page-header">

    <div class="header-copy">

        <button
            class="mobile-menu"
            id="mobileMenu"
            type="button"
            aria-label="Open navigation"
            aria-expanded="false"
        >
            <i class="bx bx-menu"></i>
        </button>

        <div>

            <span class="eyebrow">
                Department dashboard
            </span>

            <h1>
                Case Review
            </h1>

            <p>
                Prioritize reports, inspect evidence and update case progress.
            </p>

        </div>

    </div>

</header>

<main>

<!-- FILTERS -->

<section
    class="filter-panel"
    aria-label="Case filters"
>

<form
    class="filter-form"
    method="get"
    action="caseReview.php"
>

    <div class="search-field">

        <i class="bx bx-search"></i>

        <input
            class="filter-input"
            type="search"
            name="search"
            value="<?php echo e($search); ?>"
            placeholder="Search case, issue or location..."
        >

    </div>

    <select
        class="filter-select branch-filter"
        name="branch_id"
        aria-label="Filter by branch"
    >

        <option value="0">
            All branches
        </option>

        <?php foreach ($branches as $branch): ?>

            <option
                value="<?php echo (int)$branch['branch_id']; ?>"
                <?php echo
                    $selectedBranchId === (int)$branch['branch_id']
                    ? 'selected'
                    : '';
                ?>
            >
                <?php echo e($branch['branch_name']); ?>
                <?php if (!empty($branch['district_name'])): ?>
                    — <?php echo e($branch['district_name']); ?>
                <?php endif; ?>
            </option>

        <?php endforeach; ?>

    </select>

    <select
        class="filter-select"
        name="priority"
        aria-label="Filter by priority"
    >

        <option value="">
            All priorities
        </option>

        <?php foreach (
            ['critical','high','medium','low']
            as $option
        ): ?>

            <option
                value="<?php echo e($option); ?>"
                <?php echo
                    $priority === $option
                    ? 'selected'
                    : '';
                ?>
            >
                <?php echo e(ucfirst($option)); ?>
            </option>

        <?php endforeach; ?>

    </select>

    <input
        class="filter-select"
        type="date"
        name="date"
        value="<?php echo e($selectedDate); ?>"
        aria-label="Filter by report date"
    >

    <button
        class="filter-button"
        type="submit"
    >
        <i class="bx bx-filter-alt"></i>
        Apply
    </button>

    <?php if ($hasFilters): ?>

        <a
            class="clear-button"
            href="caseReview.php"
        >
            <i class="bx bx-x"></i>
            Clear
        </a>

    <?php endif; ?>

</form>

</section>

<!-- SUMMARY -->

<section
    class="summary-grid"
    aria-label="Case summary"
>

    <button
        class="summary-card"
        type="button"
        data-summary-section="all"
    >

        <span class="summary-icon">
            <i class="bx bx-folder"></i>
        </span>

        <span class="summary-copy">

            <strong>
                <?php echo $summary['total']; ?>
            </strong>

            <span>
                Total cases
            </span>

        </span>

    </button>

    <button
        class="summary-card action"
        type="button"
        data-summary-section="action"
    >

        <span class="summary-icon">
            <i class="bx bx-error-circle"></i>
        </span>

        <span class="summary-copy">

            <strong>
                <?php echo $summary['action']; ?>
            </strong>

            <span>
                Action needed
            </span>

        </span>

    </button>

    <button
        class="summary-card underway"
        type="button"
        data-summary-section="underway"
    >

        <span class="summary-icon">
            <i class="bx bx-loader-circle"></i>
        </span>

        <span class="summary-copy">

            <strong>
                <?php echo $summary['underway']; ?>
            </strong>

            <span>
                Underway
            </span>

        </span>

    </button>

    <button
        class="summary-card settled"
        type="button"
        data-summary-section="settled"
    >

        <span class="summary-icon">
            <i class="bx bx-check-circle"></i>
        </span>

        <span class="summary-copy">

            <strong>
                <?php echo $summary['settled']; ?>
            </strong>

            <span>
                Settled
            </span>

        </span>

    </button>

</section>

<!-- DEPARTMENT -->

<div class="department-banner">

    <i class="bx bx-buildings"></i>

    <div>

        <span>
            Assigned department
        </span>

        <strong>
            <?php echo e($department); ?>
        </strong>

        <small class="department-scope">
            <?php echo count($branches); ?>
            eligible branch<?php echo count($branches) === 1 ? '' : 'es'; ?>
            available for this department
        </small>

    </div>

</div>

<!-- MAP -->

<section class="map-panel">

    <div class="map-heading">

        <div>

            <span class="eyebrow">
                Issue locations
            </span>

            <h2>
                Reports Map
            </h2>

            <p>
                One marker is shown for each case.
            </p>

        </div>

        <span class="map-count">

            <?php echo count($cases); ?>
            cases

        </span>

    </div>

    <div id="reportsMap"></div>

</section>

<!-- CASES -->

<section class="section-shell">

    <div class="section-heading">

        <div>

            <h2>
                Department cases
            </h2>

            <p>
                Multiple submissions belonging to the same case are grouped together.
            </p>

        </div>

        <span class="result-count">

            Showing
            <?php echo count($cases); ?>
            cases from
            <?php echo count($filteredReports); ?>
            submissions

        </span>

    </div>

    <div class="case-sections">

        <?php foreach (
            $sections as $sectionKey => $section
        ): ?>

            <section
                class="case-section"
                id="section-<?php echo e($sectionKey); ?>"
            >

                <button
                    class="section-toggle"
                    type="button"
                    data-section="<?php echo e($sectionKey); ?>"
                    aria-expanded="false"
                >

                    <span class="section-toggle-icon">

                        <i class="bx
                            <?php
                            echo
                                $sectionKey === 'action'
                                ? 'bx-error-circle'
                                :
                                (
                                    $sectionKey === 'underway'
                                    ? 'bx-loader-circle'
                                    : 'bx-check-circle'
                                );
                            ?>
                        "></i>

                    </span>

                    <span class="section-toggle-copy">

                        <strong>
                            <?php echo e($section['title']); ?>
                        </strong>

                        <small>
                            <?php echo e($section['subtitle']); ?>
                        </small>

                    </span>

                    <span class="case-count">

                        <?php echo count(
                            $groupedCases[$sectionKey]
                        ); ?>

                    </span>

                    <i class="bx bx-chevron-down"></i>

                </button>

                <div
                    class="accordion-content"
                    hidden
                >

                <?php if (
                    empty(
                        $groupedCases[$sectionKey]
                    )
                ): ?>

                    <p class="no-reports">
                        <?php echo e(
                            $section['empty']
                        ); ?>
                    </p>

                <?php else: ?>

                    <div class="case-grid">

                    <?php foreach (
                        $groupedCases[$sectionKey]
                        as $case
                    ): ?>

                        <?php

                        $report =
                            $case['latest_report'];

                        $reportId =
                            (int)(
                                $report['report_id'] ?? 0
                            );

                        $imageName =
                            basename(
                                (string)(
                                    $report['image'] ?? ''
                                )
                            );

                        $displayPriority =
                            trim(
                                (string)(
                                    $report['ai_priority']
                                    ?? ''
                                )
                            );

                        $displayPriority =
                            $displayPriority !== ''
                                ? $displayPriority
                                : 'Not analysed';

                        $extraDetails =
                            trim(
                                (string)(
                                    $report['extra_details']
                                    ?? ''
                                )
                            );

                        $displayBranch = trim(
                            (string)(
                                $report['branch_name']
                                ?? ''
                            )
                        );

                        if ($displayBranch === '') {
                            $displayBranch = 'Branch not assigned';
                        }

                        $branchDistrict = trim(
                            (string)(
                                $report['branch_district']
                                ?? ''
                            )
                        );

                        if ($branchDistrict !== '' &&
                            $displayBranch !== 'Branch not assigned') {
                            $displayBranch .= ' · ' . $branchDistrict;
                        }

                        $submissionCount =
                            count(
                                $case['submissions']
                            );

                        $caseJson = [];

                        foreach (
                            $case['submissions']
                            as $submission
                        ) {

                            $caseJson[] = [

                                'id' =>
                                    (int)(
                                        $submission['report_id']
                                        ?? 0
                                    ),

                                'image' =>
                                    basename(
                                        (string)(
                                            $submission['image']
                                            ?? ''
                                        )
                                    ),

                                'issue' =>
                                    formatIssueType(
                                        $submission['issue_type']
                                        ?? ''
                                    ),

                                'location' =>
                                    $submission['location']
                                    ?? '',

                                'description' =>
                                    $submission['ai_description']
                                    ?? '',

                                'branch' =>
                                    $submission['branch_name']
                                    ?? 'Branch not assigned',

                                'date' =>
                                    displayDate(
                                        $submission['created_at']
                                        ?? ''
                                    )
                            ];
                        }

                        ?>

                        <article class="case-card">

                            <div class="case-image">

                                <?php if (
                                    $imageName !== ''
                                ): ?>

                                    <img
                                        src="../report/uploads/<?php echo rawurlencode($imageName); ?>"
                                        alt="Evidence for case #<?php echo $case['case_group_id']; ?>"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <i class="bx bx-image-alt"></i>

                                <?php endif; ?>

                            </div>

                            <div class="case-body">

                                <div class="case-top">

                                    <span>
                                        Case #<?php echo $case['case_group_id']; ?>
                                    </span>

                                    <span>
                                        <?php echo displayDate(
                                            $report['created_at'] ?? ''
                                        ); ?>
                                    </span>

                                </div>

                                <h3 class="case-title">

                                    <?php echo e(
                                        formatIssueType(
                                            $report['issue_type'] ?? ''
                                        )
                                    ); ?>

                                </h3>

                                <p class="case-location">

                                    <i class="bx bx-map"></i>

                                    <span>
                                        <?php echo e(
                                            $report['location']
                                            ?: 'Location not provided'
                                        ); ?>
                                    </span>

                                </p>

                                <p class="case-branch">

                                    <i class="bx bx-git-branch"></i>

                                    <span>
                                        <?php echo e($displayBranch); ?>
                                    </span>

                                </p>

                                <p class="case-description">

                                    <?php echo e(
                                        $report['ai_description']
                                        ?: 'No description provided.'
                                    ); ?>

                                </p>

                                <p class="case-extra">

                                    <?php echo e(
                                        $extraDetails !== ''
                                        ? 'Nearby: ' . $extraDetails
                                        : 'No nearby facilities provided.'
                                    ); ?>

                                </p>

                                <div class="case-actions">

                                    <div>

                                        <span
                                            class="priority-badge <?php echo e(
                                                priorityClass(
                                                    $displayPriority
                                                )
                                            ); ?>"
                                        >
                                            <?php echo e(
                                                $displayPriority
                                            ); ?>
                                        </span>

                                        <?php if (
                                            $submissionCount > 1
                                        ): ?>

                                            <span class="submission-count">

                                                <i class="bx bx-copy-alt"></i>

                                                <?php echo $submissionCount; ?>

                                                submissions

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                    <button
                                        class="review-button review-case-button"
                                        type="button"
                                        data-case-id="<?php echo $case['case_group_id']; ?>"
                                        data-case-title="<?php echo e(
                                            formatIssueType(
                                                $report['issue_type'] ?? ''
                                            )
                                        ); ?>"
                                        data-submissions="<?php echo e(
                                            json_encode(
                                                $caseJson,
                                                JSON_UNESCAPED_SLASHES |
                                                JSON_UNESCAPED_UNICODE
                                            )
                                        ); ?>"
                                    >

                                        Review

                                        <i class="bx bx-right-arrow-alt"></i>

                                    </button>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                    </div>

                <?php endif; ?>

                </div>

            </section>

        <?php endforeach; ?>

    </div>

</section>

</main>

<footer class="page-footer">

    &copy;
    <?php echo date('Y'); ?>
    AI City Guardian.
    Authorized department dashboard.

</footer>

</div>

<!-- CASE MODAL -->

<div
    class="modal-overlay"
    id="caseModal"
    aria-hidden="true"
>

    <div
        class="case-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modalCaseTitle"
    >

        <div class="modal-header">

            <div>

                <span class="eyebrow">
                    Case submissions
                </span>

                <h2 id="modalCaseTitle">
                    Case
                </h2>

                <p id="modalCaseSubtitle">
                    Review all submissions belonging to this case.
                </p>

            </div>

            <button
                class="modal-close"
                id="modalClose"
                type="button"
                aria-label="Close"
            >

                <i class="bx bx-x"></i>

            </button>

        </div>

        <div
            class="submission-list"
            id="submissionList"
        ></div>

    </div>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

/*
|--------------------------------------------------------------------------
| NAVIGATION
|--------------------------------------------------------------------------
*/

const body =
    document.body;

const mobileMenu =
    document.getElementById(
        'mobileMenu'
    );

const sidebarOverlay =
    document.getElementById(
        'sidebarOverlay'
    );

const profileButton =
    document.getElementById(
        'profileButton'
    );

const profileMenu =
    document.getElementById(
        'profileMenu'
    );

function closeNavigation() {

    body.classList.remove(
        'nav-open'
    );

    mobileMenu.setAttribute(
        'aria-expanded',
        'false'
    );
}

mobileMenu.addEventListener(
    'click',
    () => {

        const open =
            !body.classList.contains(
                'nav-open'
            );

        body.classList.toggle(
            'nav-open',
            open
        );

        mobileMenu.setAttribute(
            'aria-expanded',
            String(open)
        );
    }
);

sidebarOverlay.addEventListener(
    'click',
    closeNavigation
);

profileButton.addEventListener(
    'click',
    event => {

        event.stopPropagation();

        const open =
            !profileMenu.classList.contains(
                'open'
            );

        profileMenu.classList.toggle(
            'open',
            open
        );

        profileButton.setAttribute(
            'aria-expanded',
            String(open)
        );
    }
);

document.addEventListener(
    'click',
    event => {

        if (
            !profileMenu.contains(
                event.target
            ) &&
            !profileButton.contains(
                event.target
            )
        ) {

            profileMenu.classList.remove(
                'open'
            );

            profileButton.setAttribute(
                'aria-expanded',
                'false'
            );
        }
    }
);

/*
|--------------------------------------------------------------------------
| ACCORDIONS
|--------------------------------------------------------------------------
*/

const sectionButtons =
    document.querySelectorAll(
        '.section-toggle'
    );

const summaryButtons =
    document.querySelectorAll(
        '[data-summary-section]'
    );

function setSection(
    button,
    open
) {

    button.setAttribute(
        'aria-expanded',
        String(open)
    );

    button.nextElementSibling.hidden =
        !open;
}

function openSummarySection(
    section
) {

    sectionButtons.forEach(
        button => {

            setSection(
                button,
                section === 'all' ||
                button.dataset.section === section
            );
        }
    );

    summaryButtons.forEach(
        button => {

            button.classList.toggle(
                'active',
                button.dataset.summarySection === section
            );
        }
    );

    if (section !== 'all') {

        document
            .getElementById(
                'section-' + section
            )
            ?.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
    }
}

sectionButtons.forEach(
    button => {

        button.addEventListener(
            'click',
            () => {

                setSection(
                    button,
                    button.getAttribute(
                        'aria-expanded'
                    ) !== 'true'
                );
            }
        );
    }
);

summaryButtons.forEach(
    button => {

        button.addEventListener(
            'click',
            () => {

                openSummarySection(
                    button.dataset.summarySection
                );
            }
        );
    }
);

window.addEventListener(
    'load',
    () => {

        openSummarySection(
            <?php echo json_encode(
                $openSection
            ); ?>
        );
    }
);

/*
|--------------------------------------------------------------------------
| CASE MODAL
|--------------------------------------------------------------------------
*/

const caseModal =
    document.getElementById(
        'caseModal'
    );

const modalClose =
    document.getElementById(
        'modalClose'
    );

const modalCaseTitle =
    document.getElementById(
        'modalCaseTitle'
    );

const modalCaseSubtitle =
    document.getElementById(
        'modalCaseSubtitle'
    );

const submissionList =
    document.getElementById(
        'submissionList'
    );

function escapeHtml(
    value
) {

    return String(value ?? '')
        .replace(
            /&/g,
            '&amp;'
        )
        .replace(
            /</g,
            '&lt;'
        )
        .replace(
            />/g,
            '&gt;'
        )
        .replace(
            /"/g,
            '&quot;'
        )
        .replace(
            /'/g,
            '&#039;'
        );
}

function openCaseModal(
    button
) {

    let submissions = [];

    try {

        submissions =
            JSON.parse(
                button.dataset.submissions
            );

    } catch (error) {

        console.error(
            'Unable to read case submissions.',
            error
        );

        return;
    }

    const caseId =
        button.dataset.caseId;

    const caseTitle =
        button.dataset.caseTitle ||
        'Case';

    modalCaseTitle.textContent =
        'Case #' + caseId +
        ' — ' +
        caseTitle;

    modalCaseSubtitle.textContent =
        submissions.length +
        ' submission' +
        (
            submissions.length === 1
                ? ''
                : 's'
        ) +
        ' currently belong to this case.';

    submissionList.innerHTML = '';

    submissions.forEach(
        submission => {

            const imageHtml =
                submission.image
                ? `
                    <img
                        src="../report/uploads/${encodeURIComponent(submission.image)}"
                        alt="Submission #${submission.id}"
                    >
                  `
                : `
                    <i class="bx bx-image-alt"></i>
                  `;

            const item =
                document.createElement(
                    'div'
                );

            item.className =
                'submission-item';

            item.innerHTML = `

                <div class="submission-image">
                    ${imageHtml}
                </div>

                <div class="submission-info">

                    <strong>
                        Submission #${escapeHtml(submission.id)}
                    </strong>

                    <span>
                        ${escapeHtml(submission.date)}
                    </span>

                    <span>
                        ${escapeHtml(submission.location)}
                    </span>

                    <span>
                        Branch:
                        ${escapeHtml(
                            submission.branch ||
                            'Branch not assigned'
                        )}
                    </span>

                    <p>
                        ${escapeHtml(
                            submission.description ||
                            'No description provided.'
                        )}
                    </p>

                </div>

                <div class="submission-actions">

                    <a
                        class="view-submission"
                        href="../report/ReportPage.php?id=${encodeURIComponent(submission.id)}"
                    >
                        <i class="bx bx-show"></i>
                        View
                    </a>

                    ${
                        submissions.length > 1
                        ? `
                            <button
                                class="move-submission"
                                type="button"
                                data-report-id="${escapeHtml(submission.id)}"
                                data-case-id="${escapeHtml(caseId)}"
                            >
                                <i class="bx bx-log-out"></i>
                                Move out
                            </button>
                          `
                        : ''
                    }

                </div>

            `;

            submissionList.appendChild(
                item
            );
        }
    );

    caseModal.classList.add(
        'open'
    );

    caseModal.setAttribute(
        'aria-hidden',
        'false'
    );
}

function closeCaseModal() {

    caseModal.classList.remove(
        'open'
    );

    caseModal.setAttribute(
        'aria-hidden',
        'true'
    );
}

document
    .querySelectorAll(
        '.review-case-button'
    )
    .forEach(
        button => {

            button.addEventListener(
                'click',
                () => {

                    openCaseModal(
                        button
                    );
                }
            );
        }
    );

modalClose.addEventListener(
    'click',
    closeCaseModal
);

caseModal.addEventListener(
    'click',
    event => {

        if (
            event.target === caseModal
        ) {
            closeCaseModal();
        }
    }
);

/*
|--------------------------------------------------------------------------
| MOVE SUBMISSION OUT OF CASE
|--------------------------------------------------------------------------
*/

submissionList.addEventListener(
    'click',
    async event => {

        const button =
            event.target.closest(
                '.move-submission'
            );

        if (!button) {
            return;
        }

        const reportId =
            button.dataset.reportId;

        const caseId =
            button.dataset.caseId;

        const confirmed =
            confirm(
                'Move submission #' +
                reportId +
                ' out of Case #' +
                caseId +
                '?\n\n' +
                'This submission will become a new case.'
            );

        if (!confirmed) {
            return;
        }

        button.disabled = true;

        const originalText =
            button.innerHTML;

        button.innerHTML =
            '<i class="bx bx-loader-alt bx-spin"></i> Moving...';

        try {

            const formData =
                new FormData();

            formData.append(
                'report_id',
                reportId
            );

            formData.append(
                'case_group_id',
                caseId
            );

            const response =
                await fetch(
                    'moveSubmission.php',
                    {
                        method: 'POST',
                        body: formData
                    }
                );

            const text =
                await response.text();

            let result;

            try {

                result =
                    JSON.parse(text);

            } catch (error) {

                throw new Error(
                    text ||
                    'Unexpected server response.'
                );
            }

            if (!response.ok || !result.success) {

                throw new Error(
                    result.message ||
                    'Failed to move submission.'
                );
            }

            alert(
                'Submission #' +
                reportId +
                ' has been moved into a new case.'
            );

            window.location.reload();

        } catch (error) {

            alert(
                error.message ||
                'Unable to move submission.'
            );

            button.disabled = false;

            button.innerHTML =
                originalText;
        }
    }
);

/*
|--------------------------------------------------------------------------
| ESCAPE KEY
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'keydown',
    event => {

        if (event.key === 'Escape') {

            closeNavigation();

            profileMenu.classList.remove(
                'open'
            );

            profileButton.setAttribute(
                'aria-expanded',
                'false'
            );

            closeCaseModal();
        }
    }
);

window.addEventListener(
    'resize',
    () => {

        if (
            window.innerWidth > 900
        ) {
            closeNavigation();
        }
    }
);

/*
|--------------------------------------------------------------------------
| MAP
|--------------------------------------------------------------------------
*/

const mapReports =
    <?php echo json_encode(
        $mapReports,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    ); ?>;

const reportsMap =
    L.map(
        'reportsMap'
    ).setView(
        [3.1390, 101.6869],
        12
    );

L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
        maxZoom: 19,
        attribution:
            '&copy; OpenStreetMap contributors'
    }
).addTo(
    reportsMap
);

const mapBounds = [];

mapReports.forEach(
    report => {

        const lat =
            Number(report.latitude);

        const lng =
            Number(report.longitude);

        if (
            !Number.isFinite(lat) ||
            !Number.isFinite(lng)
        ) {
            return;
        }

        const marker =
            L.marker([
                lat,
                lng
            ]).addTo(
                reportsMap
            );

        marker.bindPopup(`

            <strong>
                Case #${report.case_id}
            </strong>

            <br>

            ${escapeHtml(report.issue)}

            <br>

            <small>
                ${escapeHtml(report.location)}
            </small>

            <br>

            <small>
                Branch:
                ${escapeHtml(
                    report.branch ||
                    'Branch not assigned'
                )}
            </small>

            <br>

            <small>
                Priority:
                ${escapeHtml(report.priority)}
            </small>

            <br>

            <small>
                Status:
                ${escapeHtml(report.status)}
            </small>

            <br>

            <small>
                ${report.submissions}
                submission(s)
            </small>

        `);

        mapBounds.push([
            lat,
            lng
        ]);
    }
);

if (mapBounds.length > 0) {

    reportsMap.fitBounds(
        mapBounds,
        {
            padding: [30,30]
        }
    );
}

</script>

</body>

</html>
