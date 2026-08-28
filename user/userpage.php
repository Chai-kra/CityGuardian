<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['id'])) {
    header('Location: LogIn.php');
    exit();
}

if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: caseReview.php');
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

function statusLabel($status) {
    $group = statusGroup($status);

    if ($group === 'settled') {
        return 'Settled';
    }

    if ($group === 'underway') {
        return 'Underway';
    }

    return 'Action Needed';
}

function formatIssueType($issueType) {
    if (empty($issueType)) {
        return 'General Issue';
    }
    // Replace underscores with spaces, then capitalize the first letter of each word
    return ucwords(str_replace('_', ' ', (string)$issueType));
}

function reportDate($value) {
    if (empty($value)) {
        return 'Date unavailable';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('M d, Y', $timestamp) : 'Date unavailable';
}

$userId = (int)$_SESSION['id'];
$user = [];

$userStatement = $conn->prepare('SELECT * FROM users WHERE id = ?');
if ($userStatement) {
    $userStatement->bind_param('i', $userId);
    $userStatement->execute();
    $user = $userStatement->get_result()->fetch_assoc() ?: [];
    $userStatement->close();
}

$userName = trim((string)($user['name'] ?? $_SESSION['name'] ?? ''));
$userEmail = trim((string)($user['email'] ?? $_SESSION['email'] ?? ''));

if ($userName === '') {
    $emailName = explode('@', $userEmail !== '' ? $userEmail : 'User')[0];
    $userName = ucwords(str_replace(['.', '_', '-'], ' ', $emailName));
}

$userName = $userName !== '' ? $userName : 'User';
$userInitial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($userName, 0, 1));

$reportStatement = $conn->prepare(
    'SELECT r.*,
     latest_update.update_id AS latest_update_id,
     latest_update.new_status AS latest_update_status,
     latest_update.feedback AS latest_feedback,
     latest_update.created_at AS latest_update_at,
     latest_update.user_seen_at AS latest_update_seen_at
     FROM reports r
     LEFT JOIN report_updates latest_update
       ON latest_update.update_id = (
           SELECT MAX(update_id)
           FROM report_updates
           WHERE report_id = r.report_id
       )
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC, r.report_id DESC'
);

if (!$reportStatement) {
    exit('Unable to load your submissions.');
}

$reportStatement->bind_param('i', $userId);
$reportStatement->execute();
$reportResult = $reportStatement->get_result();
$allReports = [];

while ($row = $reportResult->fetch_assoc()) {
    $allReports[] = $row;
}

$reportStatement->close();
$conn->close();

$summary = [
    'total' => count($allReports),
    'action' => 0,
    'underway' => 0,
    'settled' => 0
];

foreach ($allReports as $report) {
    $summary[statusGroup($report['status'] ?? 'Pending')]++;
}

$unseenUpdateCount = 0;

foreach ($allReports as $report) {
    if (
        !empty($report['latest_update_id']) &&
        empty($report['latest_update_seen_at'])
    ) {
        $unseenUpdateCount++;
    }
}

$search = trim((string)($_GET['search'] ?? ''));
$statusFilter = strtolower(trim((string)($_GET['status'] ?? '')));
$priorityFilter = strtolower(trim((string)($_GET['priority'] ?? '')));

$allowedStatuses = ['', 'action', 'underway', 'settled'];
$allowedPriorities = ['', 'critical', 'high', 'medium', 'low'];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

if (!in_array($priorityFilter, $allowedPriorities, true)) {
    $priorityFilter = '';
}

$filteredReports = array_values(array_filter(
    $allReports,
    function ($report) use ($search, $statusFilter, $priorityFilter) {
        if (
            $statusFilter !== '' &&
            statusGroup($report['status'] ?? 'Pending') !== $statusFilter
        ) {
            return false;
        }

        $priority = strtolower(trim((string)($report['ai_priority'] ?? '')));
        if ($priorityFilter !== '' && $priority !== $priorityFilter) {
            return false;
        }

        if ($search === '') {
            return true;
        }

        $searchableText = implode(' ', [
            $report['report_id'] ?? '',
            $report['issue_type'] ?? '',
            $report['location'] ?? '',
            $report['ai_description'] ?? '',
            $report['extra_details'] ?? '',
            $report['ai_department'] ?? '',
            $report['latest_feedback'] ?? ''
        ]);

        return stripos($searchableText, $search) !== false;
    }
));

$filtersActive = $search !== '' || $statusFilter !== '' || $priorityFilter !== '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - AI City Guardian</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
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
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { color-scheme: dark; scroll-behavior: smooth; }
        body {
            min-width: 320px;
            min-height: 100vh;
            overflow-x: hidden;
            color: var(--text);
            background: radial-gradient(circle at 82% 0, rgba(51, 117, 245, .14), transparent 34rem), var(--navy-950);
            font-family: 'Poppins', Arial, sans-serif;
        }
        button, input, select { font: inherit; }
        button, a { -webkit-tap-highlight-color: transparent; }
        button { cursor: pointer; }
        a { color: inherit; text-decoration: none; }
        button:focus-visible, a:focus-visible, input:focus-visible, select:focus-visible {
            outline: 3px solid rgba(107, 154, 255, .58);
            outline-offset: 3px;
        }
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 50;
            display: flex;
            width: var(--sidebar);
            min-height: 100vh;
            flex-direction: column;
            padding: 27px 21px 21px;
            border-right: 1px solid rgba(151, 166, 210, .15);
            background: rgba(24, 34, 79, .98);
            transition: transform .22s ease;
        }
        .brand { display: flex; align-items: center; gap: 12px; padding: 3px 7px 30px; font-size: 20px; font-weight: 700; letter-spacing: -.04em; }
        .brand-mark {
            display: grid;
            width: 42px;
            height: 42px;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 13px;
            background: linear-gradient(145deg, var(--blue-light), var(--blue));
            box-shadow: 0 10px 24px rgba(51, 117, 245, .3);
            font-size: 22px;
        }
        .nav-label { padding: 8px 12px 10px; color: #7180aa; font-size: 10px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; }
        .sidebar-nav { display: grid; gap: 9px; }
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
        .nav-link i { width: 23px; font-size: 22px; text-align: center; }
        .nav-link:hover { color: #fff; border-color: rgba(255, 255, 255, .08); background: rgba(255, 255, 255, .05); transform: translateX(2px); }
        .nav-link.active { color: #fff; border-color: rgba(255, 255, 255, .36); background: linear-gradient(135deg, #397af8, #2863df); box-shadow: 0 12px 28px rgba(21, 84, 220, .27); }
        .sidebar-help { margin-top: 24px; padding: 15px; border: 1px solid var(--line); border-radius: 14px; background: rgba(255, 255, 255, .035); }
        .sidebar-help i { color: var(--amber); font-size: 20px; }
        .sidebar-help strong { display: block; margin: 7px 0 4px; font-size: 12px; }
        .sidebar-help p { color: #7e8bb1; font-size: 9px; line-height: 1.6; }
        .sidebar-footer { position: relative; margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(151, 166, 210, .14); }
        .profile-button {
            display: grid;
            width: 100%;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 10px;
            padding: 9px;
            border: 1px solid transparent;
            border-radius: 14px;
            color: #c0c9e3;
            background: transparent;
            text-align: left;
        }
        .profile-button:hover, .profile-button[aria-expanded='true'] { color: #fff; border-color: var(--line); background: rgba(255, 255, 255, .05); }
        .profile-avatar { display: grid; width: 38px; height: 38px; place-items: center; border: 1px solid rgba(107, 154, 255, .42); border-radius: 12px; color: #fff; background: rgba(51, 117, 245, .2); font-weight: 700; }
        .profile-copy { display: grid; min-width: 0; gap: 1px; }
        .profile-copy strong, .profile-copy small { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .profile-copy strong { font-size: 11px; }
        .profile-copy small { color: #7f8db4; font-size: 8px; }
        .profile-menu {
            position: absolute;
            right: 0;
            bottom: calc(100% + 9px);
            left: 0;
            display: none;
            padding: 7px;
            border: 1px solid var(--line-strong);
            border-radius: 13px;
            background: #111946;
            box-shadow: var(--shadow);
        }
        .profile-menu.open { display: block; }
        .profile-menu a { display: flex; align-items: center; gap: 9px; padding: 10px; border-radius: 9px; color: #f7b5bf; font-size: 10px; font-weight: 600; }
        .profile-menu a:hover { background: rgba(251, 113, 133, .1); }
        .sidebar-overlay { display: none; }
        .page-content { min-height: 100vh; margin-left: var(--sidebar); padding: 28px clamp(22px, 4vw, 58px); }
        .mobile-menu {
            display: none;
            width: 43px;
            height: 43px;
            place-items: center;
            border: 1px solid var(--line);
            border-radius: 12px;
            color: #fff;
            background: rgba(255, 255, 255, .04);
            font-size: 22px;
        }
        .page-header { display: flex; align-items: center; justify-content: space-between; gap: 22px; margin-bottom: 22px; }
        .header-copy { display: flex; align-items: center; gap: 14px; }
        .header-copy > div:last-child { min-width: 0; }
        .eyebrow { display: block; margin-bottom: 3px; color: var(--blue-light); font-size: 9px; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; }
        .page-header h1 { font-size: clamp(24px, 3vw, 35px); letter-spacing: -.045em; }
        .page-header p { margin-top: 4px; color: var(--muted); font-size: 11px; }
        .new-report-button {
            display: inline-flex;
            min-height: 45px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 16px;
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(135deg, #397af8, #2863df);
            box-shadow: 0 10px 24px rgba(21, 84, 220, .2);
            font-size: 11px;
            font-weight: 700;
            transition: .18s ease;
        }
        .new-report-button:hover { background: linear-gradient(135deg, #4d8aff, #3470ec); transform: translateY(-1px); }
        .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 13px; margin-bottom: 18px; }
        .summary-card {
            display: flex;
            min-height: 116px;
            align-items: center;
            gap: 13px;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: linear-gradient(145deg, rgba(21, 31, 80, .84), rgba(13, 20, 59, .84));
            box-shadow: var(--shadow);
            transition: .18s ease;
        }
        .summary-card:hover, .summary-card.active { border-color: rgba(107, 154, 255, .48); background: rgba(24, 38, 98, .86); transform: translateY(-2px); }
        .summary-icon { display: grid; width: 43px; height: 43px; flex: 0 0 auto; place-items: center; border-radius: 13px; color: var(--blue-light); background: rgba(51, 117, 245, .12); font-size: 21px; }
        .summary-card.action .summary-icon { color: #fda4af; background: rgba(251, 113, 133, .1); }
        .summary-card.underway .summary-icon { color: #fde68a; background: rgba(251, 191, 36, .1); }
        .summary-card.settled .summary-icon { color: #a7f3d0; background: rgba(52, 211, 153, .1); }
        .summary-copy { min-width: 0; }
        .summary-copy strong { display: block; font-size: 21px; letter-spacing: -.03em; }
        .summary-copy span { display: block; margin-top: 2px; color: #8996b9; font-size: 9px; }
        .filter-panel {
            margin-bottom: 20px;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: rgba(17, 24, 68, .74);
            box-shadow: var(--shadow);
        }
        .filter-form { display: grid; grid-template-columns: minmax(240px, 1fr) 170px 170px auto auto; gap: 9px; align-items: center; }
        .search-field { position: relative; }
        .search-field i { position: absolute; top: 50%; left: 13px; color: #7482aa; font-size: 18px; transform: translateY(-50%); }
        .filter-input, .filter-select {
            width: 100%;
            height: 43px;
            border: 1px solid var(--line);
            border-radius: 11px;
            outline: 0;
            color: #fff;
            background: rgba(8, 15, 53, .58);
            font-size: 10px;
        }
        .filter-input { padding: 0 13px 0 40px; }
        .filter-select { padding: 0 11px; cursor: pointer; }
        .filter-input::placeholder { color: #5f6c94; }
        .filter-input:focus, .filter-select:focus { border-color: var(--blue-light); box-shadow: 0 0 0 3px rgba(107, 154, 255, .09); }
        .filter-button, .clear-button { display: inline-flex; height: 43px; align-items: center; justify-content: center; gap: 7px; padding: 0 13px; border-radius: 11px; font-size: 10px; font-weight: 700; }
        .filter-button { border: 1px solid rgba(255, 255, 255, .12); color: #fff; background: var(--blue); }
        .clear-button { border: 1px solid var(--line); color: #aeb9d6; background: rgba(255, 255, 255, .035); }
        .results-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin: 0 2px 13px; }
        .results-heading h2 { font-size: 15px; }
        .results-heading p { margin-top: 3px; color: #7f8db4; font-size: 9px; }
        .result-count { color: #8190b6; font-size: 9px; }
        .submission-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); grid-auto-rows: 1fr; gap: 15px; align-items: stretch; }
        .submission-card {
            display: flex;
            min-width: 0;
            height: 100%;
            overflow: hidden;
            flex-direction: column;
            border: 1px solid var(--line);
            border-radius: 17px;
            background: linear-gradient(145deg, rgba(21, 31, 80, .88), rgba(13, 20, 59, .88));
            box-shadow: var(--shadow);
            transition: .18s ease;
        }
        .submission-card:hover { border-color: rgba(107, 154, 255, .46); transform: translateY(-3px); }
        .card-media { position: relative; height: 185px; flex: 0 0 auto; overflow: hidden; background: #080f35; }
        .card-media img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s ease; }
        .submission-card:hover .card-media img { transform: scale(1.025); }
        .empty-image { display: grid; width: 100%; height: 100%; place-items: center; color: #5d6b94; text-align: center; }
        .empty-image i { display: block; margin-bottom: 5px; font-size: 36px; }
        .empty-image span { font-size: 9px; }
        .card-status { position: absolute; top: 12px; left: 12px; display: inline-flex; align-items: center; gap: 6px; padding: 6px 9px; border: 1px solid rgba(255, 255, 255, .13); border-radius: 999px; backdrop-filter: blur(8px); font-size: 8px; font-weight: 700; }
        .card-status::before { width: 6px; height: 6px; border-radius: 50%; background: currentColor; content: ''; }
        .card-status.action { color: #fecdd3; background: rgba(88, 20, 38, .78); }
        .card-status.underway { color: #fde68a; background: rgba(76, 55, 12, .78); }
        .card-status.settled { color: #a7f3d0; background: rgba(11, 69, 54, .78); }
        .card-body { display: flex; flex: 1; flex-direction: column; padding: 17px; }
        .card-kicker { display: flex; align-items: center; justify-content: space-between; gap: 10px; color: #7080aa; font-size: 8px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .priority { padding: 4px 7px; border-radius: 999px; }
        .priority.critical { color: #fecdd3; background: rgba(244, 63, 94, .12); }
        .priority.high { color: #fed7aa; background: rgba(249, 115, 22, .12); }
        .priority.medium { color: #fde68a; background: rgba(245, 158, 11, .12); }
        .priority.low { color: #a7f3d0; background: rgba(16, 185, 129, .12); }
        .card-title { margin-top: 11px; font-size: 15px; line-height: 1.35; letter-spacing: -.02em; }
        .card-location { display: flex; min-height: 38px; align-items: flex-start; gap: 6px; margin-top: 8px; color: #94a1c3; font-size: 9px; line-height: 1.55; }
        .card-location i { flex: 0 0 auto; margin-top: 1px; color: var(--blue-light); font-size: 14px; }
        .card-description {
            display: -webkit-box;
            min-height: 54px;
            margin-top: 11px;
            overflow: hidden;
            color: #c9d1e7;
            font-size: 10px;
            line-height: 1.75;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
        }
        .extra-details { min-height: 54px; margin-top: 12px; padding: 10px; border: 1px solid rgba(107, 154, 255, .16); border-radius: 10px; background: rgba(51, 117, 245, .055); }
        .extra-details strong { display: block; color: #8495c3; font-size: 8px; text-transform: uppercase; }
        .extra-details p { display: -webkit-box; margin-top: 4px; overflow: hidden; color: #aeb9d6; font-size: 9px; line-height: 1.6; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .extra-details.empty p { color: #66749d; font-style: italic; }
        .card-footer { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: auto; padding-top: 15px; }
        .submitted-date { display: flex; align-items: center; gap: 5px; color: #7482a9; font-size: 8px; }
        .view-button { display: inline-flex; min-height: 37px; align-items: center; gap: 6px; padding: 0 11px; border: 1px solid rgba(107, 154, 255, .3); border-radius: 10px; color: #dce6ff; background: rgba(51, 117, 245, .12); font-size: 9px; font-weight: 700; }
        .view-button:hover { color: #fff; border-color: var(--blue-light); background: rgba(51, 117, 245, .23); }
        .empty-state { display: grid; min-height: 330px; place-items: center; padding: 35px; border: 1px dashed var(--line-strong); border-radius: 18px; background: rgba(17, 24, 68, .58); text-align: center; }
        .empty-state i { display: grid; width: 65px; height: 65px; margin: 0 auto 14px; place-items: center; border-radius: 20px; color: var(--blue-light); background: rgba(51, 117, 245, .11); font-size: 31px; }
        .empty-state h2 { font-size: 17px; }
        .empty-state p { max-width: 390px; margin: 6px auto 16px; color: #8390b4; font-size: 10px; line-height: 1.7; }
        .page-footer { margin-top: 38px; padding: 20px 2px 3px; border-top: 1px solid rgba(151, 166, 210, .11); color: #69779f; font-size: 9px; text-align: center; }
        .update-alert { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 18px; padding: 14px 16px; border: 1px solid rgba(107, 154, 255, .35); border-radius: 14px; background: linear-gradient(135deg, rgba(51, 117, 245, .13), rgba(51, 117, 245, .05)); box-shadow: var(--shadow); }
        .update-alert-copy { display: flex; align-items: center; gap: 11px; }
        .update-alert-copy i { display: grid; width: 39px; height: 39px; flex: 0 0 auto; place-items: center; border-radius: 12px; color: #fff; background: var(--blue); font-size: 20px; }
        .update-alert strong { display: block; font-size: 11px; }
        .update-alert p { margin-top: 2px; color: #93a2c8; font-size: 8px; }
        .update-alert span { color: #cfe0ff; font-size: 10px; font-weight: 700; white-space: nowrap; }
        .new-update-badge { position: absolute; top: 12px; right: 12px; display: inline-flex; align-items: center; gap: 5px; padding: 6px 9px; border: 1px solid rgba(107, 154, 255, .45); border-radius: 999px; color: #fff; background: rgba(37, 99, 235, .86); backdrop-filter: blur(8px); font-size: 8px; font-weight: 700; }
        .new-update-badge i { font-size: 12px; }
        .department-update { min-height: 88px; margin-top: 12px; padding: 10px; border: 1px solid rgba(52, 211, 153, .18); border-radius: 10px; background: rgba(52, 211, 153, .055); }
        .department-update.unread { border-color: rgba(107, 154, 255, .38); background: rgba(51, 117, 245, .09); }
        .department-update strong { display: flex; align-items: center; gap: 6px; color: #a7d8cb; font-size: 8px; text-transform: uppercase; }
        .department-update.unread strong { color: #b9d1ff; }
        .department-update p { display: -webkit-box; margin-top: 5px; overflow: hidden; color: #c5cee3; font-size: 9px; line-height: 1.6; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .department-update small { display: block; margin-top: 6px; color: #7080a8; font-size: 7px; }
        .department-update.empty { border-color: rgba(151, 166, 210, .13); background: rgba(255, 255, 255, .02); }
        .department-update.empty strong, .department-update.empty p { color: #66749d; }
        @media (max-width: 1180px) {
            .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .submission-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .filter-form { grid-template-columns: minmax(230px, 1fr) 150px 150px; }
            .filter-button, .clear-button { width: 100%; }
        }
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); }
            .nav-open .sidebar { transform: translateX(0); }
            .sidebar-overlay { position: fixed; inset: 0; z-index: 45; display: block; visibility: hidden; opacity: 0; background: rgba(3, 7, 24, .68); backdrop-filter: blur(3px); transition: .22s ease; }
            .nav-open .sidebar-overlay { visibility: visible; opacity: 1; }
            .page-content { margin-left: 0; }
            .mobile-menu { display: grid; }
        }
        @media (max-width: 680px) {
            .page-content { padding: 19px 13px; }
            .page-header { align-items: flex-start; flex-direction: column; }
            .header-copy { width: 100%; }
            .new-report-button { width: 100%; }
            .summary-grid, .submission-grid, .filter-form { grid-template-columns: 1fr; }
            .summary-card { min-height: 99px; }
            .filter-panel { padding: 13px; }
            .results-heading { align-items: flex-start; flex-direction: column; gap: 4px; }
            .card-media { height: 205px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" aria-label="Main navigation">
        <a class="brand" href="userpage.php">
            <span class="brand-mark"><i class="bx bxs-city"></i></span>
            <span>AI City Guardian</span>
        </a>

        <p class="nav-label">Citizen portal</p>
        <nav class="sidebar-nav">
            <a class="nav-link" href="uploadpage.php"><i class="bx bx-plus-circle"></i><span>New Report</span></a>
            <a class="nav-link active" href="userpage.php" aria-current="page"><i class="bx bx-file"></i><span>My Submissions</span></a>
        </nav>

        <div class="sidebar-help">
            <i class="bx bx-info-circle"></i>
            <strong>Track every report</strong>
            <p>Open a submission to view its full information and latest progress.</p>
        </div>

        <div class="sidebar-footer">
            <button class="profile-button" id="profileButton" type="button" aria-expanded="false">
                <span class="profile-avatar"><?php echo e($userInitial); ?></span>
                <span class="profile-copy">
                    <strong><?php echo e($userName); ?></strong>
                    <small><?php echo e($userEmail !== '' ? $userEmail : 'Citizen account'); ?></small>
                </span>
                <i class="bx bx-chevron-up"></i>
            </button>

            <div class="profile-menu" id="profileMenu">
                <a href="logout.php"><i class="bx bx-log-out"></i>Log out</a>
            </div>
        </div>
    </aside>

    <div class="page-content">
        <header class="page-header">
            <div class="header-copy">
                <button class="mobile-menu" id="mobileMenu" type="button" aria-label="Open navigation" aria-expanded="false">
                    <i class="bx bx-menu"></i>
                </button>
                <div>
                    <span class="eyebrow">Citizen dashboard</span>
                    <h1>My Submissions</h1>
                    <p>Review your reports and follow their latest progress.</p>
                </div>
            </div>

            <a class="new-report-button" href="uploadpage.php"><i class="bx bx-plus"></i>Submit new report</a>
        </header>

        <main>
            <section class="summary-grid" aria-label="Submission summary">
                <a class="summary-card<?php echo $statusFilter === '' ? ' active' : ''; ?>" href="userpage.php">
                    <span class="summary-icon"><i class="bx bx-folder"></i></span>
                    <span class="summary-copy"><strong><?php echo $summary['total']; ?></strong><span>Total submissions</span></span>
                </a>

                <a class="summary-card action<?php echo $statusFilter === 'action' ? ' active' : ''; ?>" href="userpage.php?status=action">
                    <span class="summary-icon"><i class="bx bx-error-circle"></i></span>
                    <span class="summary-copy"><strong><?php echo $summary['action']; ?></strong><span>Action needed</span></span>
                </a>

                <a class="summary-card underway<?php echo $statusFilter === 'underway' ? ' active' : ''; ?>" href="userpage.php?status=underway">
                    <span class="summary-icon"><i class="bx bx-loader-circle"></i></span>
                    <span class="summary-copy"><strong><?php echo $summary['underway']; ?></strong><span>Underway</span></span>
                </a>

                <a class="summary-card settled<?php echo $statusFilter === 'settled' ? ' active' : ''; ?>" href="userpage.php?status=settled">
                    <span class="summary-icon"><i class="bx bx-check-circle"></i></span>
                    <span class="summary-copy"><strong><?php echo $summary['settled']; ?></strong><span>Settled</span></span>
                </a>
            </section>

            <?php if ($unseenUpdateCount > 0): ?>
                <section class="update-alert" role="status">
                    <div class="update-alert-copy">
                        <i class="bx bx-bell"></i>
                        <div>
                            <strong>New department feedback available</strong>
                            <p>Open the marked reports to read the complete update history.</p>
                        </div>
                    </div>
                    <span><?php echo $unseenUpdateCount; ?> report<?php echo $unseenUpdateCount === 1 ? '' : 's'; ?> updated</span>
                </section>
            <?php endif; ?>

            <section class="filter-panel" aria-label="Submission filters">
                <form class="filter-form" method="get" action="userpage.php">
                    <div class="search-field">
                        <i class="bx bx-search"></i>
                        <input class="filter-input" type="search" name="search" value="<?php echo e($search); ?>" placeholder="Search case, issue or location...">
                    </div>

                    <select class="filter-select" name="status" aria-label="Filter by status">
                        <option value="">All statuses</option>
                        <option value="action" <?php echo $statusFilter === 'action' ? 'selected' : ''; ?>>To investigate</option>
                        <option value="underway" <?php echo $statusFilter === 'underway' ? 'selected' : ''; ?>>Underway</option>
                        <option value="settled" <?php echo $statusFilter === 'settled' ? 'selected' : ''; ?>>Settled</option>
                    </select>

                    <select class="filter-select" name="priority" aria-label="Filter by priority">
                        <option value="">All priorities</option>
                        <option value="critical" <?php echo $priorityFilter === 'critical' ? 'selected' : ''; ?>>Critical</option>
                        <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $priorityFilter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $priorityFilter === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>

                    <button class="filter-button" type="submit"><i class="bx bx-filter-alt"></i>Apply</button>
                    <?php if ($filtersActive): ?>
                        <a class="clear-button" href="userpage.php"><i class="bx bx-x"></i>Clear</a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                </form>
            </section>

            <div class="results-heading">
                <div>
                    <h2>Your reports</h2>
                    <p>Each card shows the most important information at a glance.</p>
                </div>
                <span class="result-count"><?php echo count($filteredReports); ?> result<?php echo count($filteredReports) === 1 ? '' : 's'; ?></span>
            </div>

            <?php if (!empty($filteredReports)): ?>
                <section class="submission-grid" aria-label="Your submitted reports">
                    <?php foreach ($filteredReports as $report): ?>
                        <?php
                        $reportId = (int)($report['report_id'] ?? 0);
                        $statusClass = statusGroup($report['status'] ?? 'Pending');
                        $priority = strtolower(trim((string)($report['ai_priority'] ?? '')));
                        $priorityClass = in_array($priority, ['critical', 'high', 'medium', 'low'], true) ? $priority : 'medium';
                        $priorityLabel = $priority !== '' ? ucfirst($priority) : 'Not analysed';
                        $imageName = basename((string)($report['image'] ?? ''));
                        $extraDetails = trim((string)($report['extra_details'] ?? ''));
                        $latestFeedback = trim((string)($report['latest_feedback'] ?? ''));
                        $latestUpdateIsUnread =
                            !empty($report['latest_update_id']) &&
                            empty($report['latest_update_seen_at']);
                        ?>
                        <article class="submission-card">
                            <div class="card-media">
                                <?php if ($imageName !== ''): ?>
                                    <img src="../report/uploads/<?php echo rawurlencode($imageName); ?>" alt="Evidence for report #<?php echo $reportId; ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="empty-image"><div><i class="bx bx-image-alt"></i><span>No photo attached</span></div></div>
                                <?php endif; ?>
                                <span class="card-status <?php echo e($statusClass); ?>"><?php echo e(statusLabel($report['status'] ?? 'Pending')); ?></span>
                                <?php if ($latestUpdateIsUnread): ?>
                                    <span class="new-update-badge"><i class="bx bx-bell"></i>New update</span>
                                <?php endif; ?>
                            </div>

                            <div class="card-body">
                                <div class="card-kicker">
                                    <span>Case #<?php echo $reportId; ?></span>
                                    <span class="priority <?php echo e($priorityClass); ?>"><?php echo e($priorityLabel); ?></span>
                                </div>

                                <h3 class="card-title"><?php echo e(formatIssueType($report['issue_type'] ?? '')); ?></h3>

                                <p class="card-location">
                                    <i class="bx bx-map"></i>
                                    <span><?php echo e($report['location'] ?: 'Location not available'); ?></span>
                                </p>

                                <p class="card-description"><?php echo e($report['ai_description'] ?: 'No description was provided.'); ?></p>

                                <div class="extra-details<?php echo $extraDetails === '' ? ' empty' : ''; ?>">
                                    <strong>Nearby facilities / extra details</strong>
                                    <p><?php echo e($extraDetails !== '' ? $extraDetails : 'No extra details provided.'); ?></p>
                                </div>

                                <div class="department-update<?php echo $latestFeedback === '' ? ' empty' : ($latestUpdateIsUnread ? ' unread' : ''); ?>">
                                    <strong><i class="bx bx-message-rounded-dots"></i>Latest department feedback</strong>
                                    <p><?php echo e($latestFeedback !== '' ? $latestFeedback : 'No department feedback yet.'); ?></p>
                                    <?php if (!empty($report['latest_update_at'])): ?>
                                        <small>
                                            <?php
                                            $latestUpdateTime = strtotime((string)$report['latest_update_at']);
                                            echo $latestUpdateTime
                                                ? e(date('M d, Y h:i A', $latestUpdateTime))
                                                : 'Date unavailable';
                                            ?>
                                        </small>
                                    <?php endif; ?>
                                </div>

                                <div class="card-footer">
                                    <span class="submitted-date"><i class="bx bx-calendar"></i><?php echo e(reportDate($report['created_at'] ?? '')); ?></span>
                                    <a class="view-button" href="../report/ReportPage.php?id=<?php echo $reportId; ?>">
                                        View details <i class="bx bx-right-arrow-alt"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php else: ?>
                <section class="empty-state">
                    <div>
                        <i class="bx bx-file-find"></i>
                        <h2><?php echo $filtersActive ? 'No matching submissions' : 'No reports yet'; ?></h2>
                        <p>
                            <?php echo $filtersActive
                                ? 'Try clearing the filters or searching with a different word.'
                                : 'Submit your first report to help improve your community.'; ?>
                        </p>
                        <?php if ($filtersActive): ?>
                            <a class="new-report-button" href="userpage.php"><i class="bx bx-x"></i>Clear filters</a>
                        <?php else: ?>
                            <a class="new-report-button" href="uploadpage.php"><i class="bx bx-plus"></i>Submit first report</a>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
        </main>

        <footer class="page-footer">&copy; <?php echo date('Y'); ?> AI City Guardian. Building cleaner, safer communities together.</footer>
    </div>

    <script>
        const body = document.body;
        const mobileMenu = document.getElementById('mobileMenu');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const profileButton = document.getElementById('profileButton');
        const profileMenu = document.getElementById('profileMenu');

        function closeNavigation() {
            body.classList.remove('nav-open');
            mobileMenu.setAttribute('aria-expanded', 'false');
        }

        mobileMenu.addEventListener('click', () => {
            const isOpen = !body.classList.contains('nav-open');
            body.classList.toggle('nav-open', isOpen);
            mobileMenu.setAttribute('aria-expanded', String(isOpen));
        });

        sidebarOverlay.addEventListener('click', closeNavigation);

        profileButton.addEventListener('click', event => {
            event.stopPropagation();
            const isOpen = !profileMenu.classList.contains('open');
            profileMenu.classList.toggle('open', isOpen);
            profileButton.setAttribute('aria-expanded', String(isOpen));
        });

        document.addEventListener('click', event => {
            if (!profileMenu.contains(event.target) && !profileButton.contains(event.target)) {
                profileMenu.classList.remove('open');
                profileButton.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeNavigation();
                profileMenu.classList.remove('open');
                profileButton.setAttribute('aria-expanded', 'false');
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 900) {
                closeNavigation();
            }
        });
    </script>
</body>
</html>