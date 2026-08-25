<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../user/LogIn.php');
    exit();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function displayStatus($status) {
    $status = strtolower(trim((string)$status));

    if (in_array($status, ['resolved', 'settled'], true)) {
        return 'Settled';
    }

    if (in_array($status, ['assigned', 'in progress', 'underway'], true)) {
        return 'Underway';
    }

    return 'Action Needed';
}

function textLength($value) {
    return function_exists('mb_strlen')
        ? mb_strlen($value, 'UTF-8')
        : strlen($value);
}

if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    http_response_code(400);
    exit('Invalid report ID.');
}

$reportId = (int)$_GET['id'];
$userId = (int)$_SESSION['id'];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$adminDepartment = trim((string)($_SESSION['department'] ?? ''));
$formError = '';
$feedbackValue = '';

if (empty($_SESSION['report_csrf_token'])) {
    $_SESSION['report_csrf_token'] = bin2hex(random_bytes(32));
}

if ($isAdmin) {
    $sql = 'SELECT * FROM reports WHERE report_id = ? AND ai_department = ?';
    $statement = $conn->prepare($sql);

    if (!$statement) {
        exit('Unable to load this report.');
    }

    $statement->bind_param('is', $reportId, $adminDepartment);
} else {
    $sql = 'SELECT * FROM reports WHERE report_id = ? AND user_id = ?';
    $statement = $conn->prepare($sql);

    if (!$statement) {
        exit('Unable to load this report.');
    }

    $statement->bind_param('ii', $reportId, $userId);
}

$statement->execute();
$result = $statement->get_result();

if ($result->num_rows !== 1) {
    http_response_code(404);
    exit('Report not found or you do not have permission.');
}

$report = $result->fetch_assoc();
$statement->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) {
        http_response_code(403);
        exit('Only an administrator can update the status.');
    }

    $submittedToken = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['report_csrf_token'], $submittedToken)) {
        http_response_code(403);
        exit('Invalid form token.');
    }

    $newStatus = trim((string)($_POST['status'] ?? ''));
    $feedbackValue = trim((string)($_POST['feedback'] ?? ''));
    $allowedStatuses = ['Pending', 'In Progress', 'Resolved'];
    $oldStatus = (string)($report['status'] ?? 'Pending');
    $comparableOldStatus = $oldStatus === 'Assigned' ? 'In Progress' : $oldStatus;

    if (!in_array($newStatus, $allowedStatuses, true)) {
        $formError = 'Select a valid status.';
    } elseif ($newStatus === $comparableOldStatus) {
        $formError = 'Select a different status before sending an update.';
    } elseif (textLength($feedbackValue) < 5) {
        $formError = 'Feedback must contain at least 5 characters.';
    } elseif (textLength($feedbackValue) > 1000) {
        $formError = 'Feedback cannot exceed 1000 characters.';
    }

    if ($formError === '') {
        $conn->begin_transaction();

        try {
            $updateStatement = $conn->prepare(
                'UPDATE reports SET status = ? WHERE report_id = ? AND ai_department = ?'
            );

            if (!$updateStatement) {
                throw new Exception('Status statement could not be prepared.');
            }

            $updateStatement->bind_param('sis', $newStatus, $reportId, $adminDepartment);

            if (!$updateStatement->execute() || $updateStatement->affected_rows !== 1) {
                throw new Exception('Status could not be updated.');
            }

            $updateStatement->close();

            $historyStatement = $conn->prepare(
                'INSERT INTO report_updates
                (report_id, admin_id, old_status, new_status, feedback)
                VALUES (?, ?, ?, ?, ?)'
            );

            if (!$historyStatement) {
                throw new Exception('Feedback statement could not be prepared.');
            }

            $historyStatement->bind_param(
                'iisss',
                $reportId,
                $userId,
                $oldStatus,
                $newStatus,
                $feedbackValue
            );

            if (!$historyStatement->execute()) {
                throw new Exception('Feedback could not be saved.');
            }

            $historyStatement->close();
            $conn->commit();

            header('Location: ReportPage.php?id=' . $reportId . '&updated=1');
            exit();
        } catch (Throwable $error) {
            $conn->rollback();
            error_log('Report update error: ' . $error->getMessage());
            $formError = 'The status and feedback could not be saved. Please try again.';
        }
    }
}

$historyStatement = $conn->prepare(
    'SELECT update_id, old_status, new_status, feedback, user_seen_at, created_at
     FROM report_updates
     WHERE report_id = ?
     ORDER BY created_at DESC, update_id DESC'
);

if (!$historyStatement) {
    exit('The report update table is missing. Run add_report_updates.sql first.');
}

$historyStatement->bind_param('i', $reportId);
$historyStatement->execute();
$historyResult = $historyStatement->get_result();
$statusUpdates = [];

while ($update = $historyResult->fetch_assoc()) {
    $statusUpdates[] = $update;
}

$historyStatement->close();

if (!$isAdmin && !empty($statusUpdates)) {
    $seenStatement = $conn->prepare(
        'UPDATE report_updates
         SET user_seen_at = NOW()
         WHERE report_id = ? AND user_seen_at IS NULL'
    );

    if ($seenStatement) {
        $seenStatement->bind_param('i', $reportId);
        $seenStatement->execute();
        $seenStatement->close();
    }
}

$databaseStatus = (string)($report['status'] ?? 'Pending');
$formStatus = $databaseStatus === 'Assigned' ? 'In Progress' : $databaseStatus;
$backPage = $isAdmin ? '../user/caseReview.php' : '../user/userpage.php';
$imageName = basename((string)($report['image'] ?? ''));
$priority = strtolower((string)($report['ai_priority'] ?: 'medium'));
$allowedPriorityClasses = ['critical', 'high', 'medium', 'low'];
$priorityClass = in_array($priority, $allowedPriorityClasses, true) ? $priority : 'medium';
$extraDetails = trim((string)($report['extra_details'] ?? ''));

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case #<?php echo e($reportId); ?> - AI City Guardian</title>
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
            --orange: #fb923c;
            --shadow: 0 24px 65px rgba(2, 6, 23, .32);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { color-scheme: dark; scroll-behavior: smooth; }
        body.report-details-page {
            min-width: 320px;
            min-height: 100vh;
            padding: 34px 18px;
            color: var(--text);
            background:
                radial-gradient(circle at 82% 0, rgba(51, 117, 245, .16), transparent 34rem),
                radial-gradient(circle at 0 100%, rgba(107, 154, 255, .08), transparent 28rem),
                var(--navy-950);
            font-family: "Poppins", Arial, sans-serif;
        }
        button, select { font: inherit; }
        button, a, select { -webkit-tap-highlight-color: transparent; }
        a { color: inherit; text-decoration: none; }
        button { cursor: pointer; }
        button:focus-visible, a:focus-visible, select:focus-visible {
            outline: 3px solid rgba(107, 154, 255, .55);
            outline-offset: 3px;
        }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
        }
        .report-page-shell {
            width: min(1180px, 100%);
            margin: 0 auto;
        }
        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 18px;
            padding: 23px 25px;
            border: 1px solid var(--line);
            border-radius: 20px;
            background: linear-gradient(145deg, rgba(21, 31, 80, .92), rgba(13, 20, 59, .92));
            box-shadow: var(--shadow);
        }
        .header-left { display: flex; min-width: 0; align-items: center; gap: 17px; }
        .back-button {
            display: grid;
            width: 45px;
            height: 45px;
            flex: 0 0 auto;
            place-items: center;
            border: 1px solid var(--line);
            border-radius: 13px;
            color: #c9d2ea;
            background: rgba(255, 255, 255, .04);
            font-size: 23px;
            transition: .18s ease;
        }
        .back-button:hover {
            color: #fff;
            border-color: var(--blue-light);
            background: rgba(51, 117, 245, .12);
            transform: translateX(-2px);
        }
        .eyebrow {
            display: block;
            margin-bottom: 4px;
            color: var(--blue-light);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .15em;
            text-transform: uppercase;
        }
        .report-header h1 { font-size: clamp(22px, 3vw, 31px); letter-spacing: -.04em; }
        .report-header p { margin-top: 4px; color: var(--muted); font-size: 11px; line-height: 1.6; }
        .header-status {
            display: flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 9px;
            padding: 10px 13px;
            border: 1px solid var(--line);
            border-radius: 13px;
            background: rgba(255, 255, 255, .035);
        }
        .header-status > span:first-child { color: #7f8db4; font-size: 9px; font-weight: 600; text-transform: uppercase; }
        .success-message {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
            padding: 13px 16px;
            border: 1px solid rgba(52, 211, 153, .38);
            border-radius: 13px;
            color: #b8f7dd;
            background: rgba(52, 211, 153, .1);
            font-size: 11px;
        }
        .success-message i { color: var(--green); font-size: 20px; }
        .content-section {
            margin-top: 18px;
            padding: 23px;
            border: 1px solid var(--line);
            border-radius: 20px;
            background: linear-gradient(145deg, rgba(21, 31, 80, .86), rgba(13, 20, 59, .86));
            box-shadow: var(--shadow);
        }
        .section-heading {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 17px;
        }
        .section-icon {
            display: grid;
            width: 40px;
            height: 40px;
            flex: 0 0 auto;
            place-items: center;
            border: 1px solid rgba(107, 154, 255, .32);
            border-radius: 12px;
            color: var(--blue-light);
            background: rgba(51, 117, 245, .11);
            font-size: 20px;
        }
        .section-heading h2 { font-size: 15px; letter-spacing: -.02em; }
        .section-heading p { margin-top: 3px; color: #8491b6; font-size: 9px; line-height: 1.5; }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            grid-auto-rows: 1fr;
            gap: 12px;
            align-items: stretch;
        }
        .info-card {
            display: flex;
            min-width: 0;
            min-height: 132px;
            height: 100%;
            flex-direction: column;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: rgba(8, 15, 53, .44);
            transition: border-color .18s ease, transform .18s ease, background .18s ease;
        }
        .info-card:hover {
            border-color: rgba(107, 154, 255, .42);
            background: rgba(13, 23, 70, .68);
            transform: translateY(-2px);
        }
        .card-label {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #7f8db4;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .card-label i { color: var(--blue-light); font-size: 16px; }
        .card-value {
            display: flex;
            flex: 1;
            align-items: flex-end;
            margin-top: 14px;
            color: #f8faff;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.55;
            overflow-wrap: anywhere;
        }
        .card-value.large { font-size: 21px; letter-spacing: -.03em; }
        .report-status-badge, .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
        }
        .report-status-badge::before {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
            content: "";
            box-shadow: 0 0 0 4px rgba(107, 154, 255, .08);
        }
        .report-status-badge {
            color: #b9d1ff;
            background: rgba(51, 117, 245, .17);
        }
        .priority-badge.critical { color: #fecdd3; background: rgba(244, 63, 94, .16); }
        .priority-badge.high { color: #fed7aa; background: rgba(249, 115, 22, .16); }
        .priority-badge.medium { color: #fde68a; background: rgba(245, 158, 11, .16); }
        .priority-badge.low { color: #a7f3d0; background: rgba(16, 185, 129, .16); }
        .narrative-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            grid-auto-rows: 1fr;
            gap: 12px;
            align-items: stretch;
        }
        .narrative-card {
            display: flex;
            min-width: 0;
            min-height: 225px;
            height: 100%;
            flex-direction: column;
            padding: 17px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: rgba(8, 15, 53, .44);
        }
        .narrative-card .card-label { margin-bottom: 13px; }
        .narrative-text {
            flex: 1;
            color: #d7def0;
            font-size: 11px;
            line-height: 1.8;
            overflow-wrap: anywhere;
            white-space: normal;
        }
        .empty-text { color: #6f7ca3; font-style: italic; }
        .evidence-card {
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 15px;
            background: rgba(8, 15, 53, .44);
        }
        .evidence-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--line);
        }
        .evidence-header strong { font-size: 11px; }
        .evidence-header span { color: #7785aa; font-size: 9px; }
        .evidence-preview {
            position: relative;
            display: grid;
            min-height: 390px;
            place-items: center;
            padding: 18px;
            background:
                linear-gradient(45deg, rgba(255, 255, 255, .015) 25%, transparent 25%),
                linear-gradient(-45deg, rgba(255, 255, 255, .015) 25%, transparent 25%),
                #080f35;
            background-size: 28px 28px;
        }
        .report-image {
            display: block;
            width: 100%;
            max-height: 520px;
            border-radius: 11px;
            object-fit: contain;
        }
        .open-image {
            position: absolute;
            right: 30px;
            bottom: 30px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 11px;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 10px;
            color: #fff;
            background: rgba(5, 10, 35, .78);
            backdrop-filter: blur(7px);
            font-size: 9px;
            font-weight: 600;
        }
        .empty-evidence {
            display: grid;
            min-height: 250px;
            place-items: center;
            padding: 30px;
            color: #6f7ca3;
            text-align: center;
        }
        .empty-evidence i { display: block; margin-bottom: 9px; color: #52618d; font-size: 42px; }
        .empty-evidence strong { display: block; color: #8390b5; font-size: 11px; }
        .empty-evidence p { margin-top: 4px; font-size: 9px; }
        .status-update-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 20px;
            border: 1px solid rgba(107, 154, 255, .3);
            border-radius: 15px;
            background: linear-gradient(135deg, rgba(51, 117, 245, .11), rgba(51, 117, 245, .04));
        }
        .status-copy { display: flex; min-width: 0; align-items: center; gap: 13px; }
        .status-copy i {
            display: grid;
            width: 43px;
            height: 43px;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 13px;
            color: #fff;
            background: linear-gradient(145deg, var(--blue-light), var(--blue));
            box-shadow: 0 10px 24px rgba(51, 117, 245, .25);
            font-size: 21px;
        }
        .status-copy h2 { font-size: 14px; }
        .status-copy p { margin-top: 3px; color: #8491b6; font-size: 9px; line-height: 1.5; }
        .status-form { display: flex; flex: 0 0 auto; align-items: center; gap: 9px; }
        .status-select {
            min-width: 170px;
            height: 44px;
            padding: 0 35px 0 12px;
            border: 1px solid var(--line-strong);
            border-radius: 11px;
            outline: 0;
            color: #fff;
            background: var(--navy-900);
            font-size: 10px;
            cursor: pointer;
        }
        .update-status-button {
            display: inline-flex;
            height: 44px;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 0 15px;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 11px;
            color: #fff;
            background: linear-gradient(135deg, #397af8, #2863df);
            box-shadow: 0 10px 24px rgba(21, 84, 220, .2);
            font-size: 10px;
            font-weight: 700;
            transition: .18s ease;
        }
        .update-status-button:hover { background: linear-gradient(135deg, #4d8aff, #3470ec); transform: translateY(-1px); }
        .page-footer {
            margin-top: 22px;
            padding: 18px 2px 2px;
            color: #69779f;
            font-size: 9px;
            text-align: center;
        }
        .update-timeline { display: grid; gap: 12px; }
        .timeline-item { position: relative; display: grid; grid-template-columns: 38px 1fr; gap: 13px; }
        .timeline-item:not(:last-child)::before { position: absolute; top: 38px; bottom: -12px; left: 18px; width: 2px; background: rgba(107, 154, 255, .18); content: ''; }
        .timeline-marker { display: grid; width: 38px; height: 38px; z-index: 1; place-items: center; border: 1px solid rgba(107, 154, 255, .32); border-radius: 12px; color: var(--blue-light); background: #111946; font-size: 18px; }
        .timeline-card { padding: 15px; border: 1px solid var(--line); border-radius: 13px; background: rgba(8, 15, 53, .42); }
        .timeline-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; }
        .timeline-change { display: flex; flex-wrap: wrap; align-items: center; gap: 7px; color: #dbe4f8; font-size: 10px; font-weight: 700; }
        .timeline-change i { color: #7182b2; font-size: 17px; }
        .timeline-date { color: #7180a8; font-size: 8px; white-space: nowrap; }
        .timeline-feedback { margin-top: 10px; color: #b8c2da; font-size: 10px; line-height: 1.75; overflow-wrap: anywhere; }
        .timeline-empty { padding: 24px; border: 1px dashed var(--line-strong); border-radius: 13px; color: #7381a8; background: rgba(8, 15, 53, .3); font-size: 10px; text-align: center; }
        .status-update-card { align-items: flex-start; }
        .status-copy { max-width: 370px; align-items: flex-start; }
        .status-form { display: grid; width: min(570px, 100%); grid-template-columns: minmax(170px, 1fr) auto; gap: 9px; }
        .feedback-field { grid-column: 1 / -1; }
        .feedback-label { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 7px; color: #cbd3e8; font-size: 9px; font-weight: 600; }
        .feedback-label span { color: #7280a8; font-size: 8px; font-weight: 400; }
        .feedback-textarea { width: 100%; min-height: 112px; padding: 12px; resize: vertical; border: 1px solid var(--line-strong); border-radius: 11px; outline: 0; color: #fff; background: rgba(8, 15, 53, .68); font-size: 10px; line-height: 1.7; }
        .feedback-textarea::placeholder { color: #5f6c94; }
        .feedback-textarea:focus { border-color: var(--blue-light); box-shadow: 0 0 0 3px rgba(107, 154, 255, .09); }
        .form-error { grid-column: 1 / -1; padding: 10px 12px; border: 1px solid rgba(251, 113, 133, .35); border-radius: 10px; color: #fecdd3; background: rgba(251, 113, 133, .08); font-size: 9px; }
        @media (max-width: 980px) {
            .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .narrative-grid { grid-template-columns: 1fr; grid-auto-rows: auto; }
            .narrative-card { min-height: 170px; }
            .status-update-card { align-items: flex-start; flex-direction: column; }
            .status-form { width: 100%; }
            .status-select { flex: 1; }
        }
        @media (max-width: 620px) {
            body.report-details-page { padding: 15px 10px; }
            .report-header, .content-section { padding: 17px; border-radius: 16px; }
            .report-header { align-items: flex-start; flex-direction: column; }
            .header-left { align-items: flex-start; }
            .header-status { margin-left: 62px; }
            .summary-grid { grid-template-columns: 1fr; grid-auto-rows: auto; }
            .info-card { min-height: 112px; }
            .evidence-preview { min-height: 270px; padding: 10px; }
            .open-image { right: 18px; bottom: 18px; }
            .status-form { grid-template-columns: 1fr; }
            .status-select, .update-status-button { width: 100%; }
            .timeline-top { flex-direction: column; gap: 6px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; }
        }
    </style>
</head>
<body class="report-details-page">
    <main class="report-page-shell">
        <header class="report-header">
            <div class="header-left">
                <a class="back-button" href="<?php echo e($backPage); ?>" aria-label="Return to reports">
                    <i class="bx bx-left-arrow-alt"></i>
                </a>
                <div>
                    <span class="eyebrow">Case overview</span>
                    <h1>Report #<?php echo e($reportId); ?></h1>
                    <p>Review the submitted information, AI assessment and current progress.</p>
                </div>
            </div>
            <div class="header-status">
                <span>Current status</span>
                <span class="report-status-badge"><?php echo e(displayStatus($databaseStatus)); ?></span>
            </div>
        </header>

        <?php if (isset($_GET['updated'])): ?>
            <div class="success-message" role="status">
                <i class="bx bx-check-circle"></i>
                <span>Status updated and feedback sent to the user successfully.</span>
            </div>
        <?php endif; ?>

        <section class="content-section" aria-labelledby="overview-title">
            <div class="section-heading">
                <span class="section-icon"><i class="bx bx-grid-alt"></i></span>
                <div>
                    <h2 id="overview-title">Report overview</h2>
                    <p>All important case information is arranged in equal cards.</p>
                </div>
            </div>

            <div class="summary-grid">
                <article class="info-card">
                    <span class="card-label"><i class="bx bx-hash"></i>Case ID</span>
                    <div class="card-value large">#<?php echo e($report['report_id']); ?></div>
                </article>

                <article class="info-card">
                    <span class="card-label"><i class="bx bx-loader-circle"></i>Status</span>
                    <div class="card-value">
                        <span class="report-status-badge"><?php echo e(displayStatus($databaseStatus)); ?></span>
                    </div>
                </article>

                <article class="info-card">
                    <span class="card-label"><i class="bx bx-category"></i>Issue Type</span>
                    <div class="card-value"><?php echo e($report['issue_type'] ?: 'Not available'); ?></div>
                </article>

                <article class="info-card">
                    <span class="card-label"><i class="bx bx-error-circle"></i>AI Priority</span>
                    <div class="card-value">
                        <span class="priority-badge <?php echo e($priorityClass); ?>">
                            <?php echo e($report['ai_priority'] ?: 'Not analysed'); ?>
                        </span>
                    </div>
                </article>

                <article class="info-card">
                    <span class="card-label"><i class="bx bx-map"></i>Location</span>
                    <div class="card-value"><?php echo e($report['location'] ?: 'Not available'); ?></div>
                </article>

                <article class="info-card">
                    <span class="card-label"><i class="bx bx-buildings"></i>Assigned Department</span>
                    <div class="card-value"><?php echo e($report['ai_department'] ?: 'Not assigned'); ?></div>
                </article>

                <article class="info-card">
                    <span class="card-label"><i class="bx bx-line-chart"></i>AI Confidence</span>
                    <div class="card-value">
                        <?php echo $report['ai_confidence'] !== null ? e($report['ai_confidence']) . '%' : 'Not available'; ?>
                    </div>
                </article>

                <article class="info-card">
                    <span class="card-label"><i class="bx bx-calendar"></i>Submitted On</span>
                    <div class="card-value">
                        <?php
                        echo !empty($report['created_at'])
                            ? e(date('M d, Y h:i A', strtotime($report['created_at'])))
                            : 'Not available';
                        ?>
                    </div>
                </article>
            </div>
        </section>

        <section class="content-section" aria-labelledby="description-title">
            <div class="section-heading">
                <span class="section-icon"><i class="bx bx-message-square-detail"></i></span>
                <div>
                    <h2 id="description-title">Descriptions and nearby facilities</h2>
                    <p>Compare the citizen’s information with the AI-generated assessment.</p>
                </div>
            </div>

            <div class="narrative-grid">
                <article class="narrative-card">
                    <span class="card-label"><i class="bx bx-map-pin"></i>Nearby Facilities / Extra Details</span>
                    <p class="narrative-text<?php echo $extraDetails === '' ? ' empty-text' : ''; ?>">
                        <?php
                        echo $extraDetails !== ''
                            ? nl2br(e($extraDetails))
                            : 'No nearby facilities or extra details were provided.';
                        ?>
                    </p>
                </article>

                <article class="narrative-card">
                    <span class="card-label"><i class="bx bx-bot"></i>AI Description</span>
                    <p class="narrative-text">
                        <?php echo nl2br(e($report['ai_description'] ?: 'Not available')); ?>
                    </p>
                </article>
            </div>
        </section>

        <section class="content-section" aria-labelledby="updates-title">
            <div class="section-heading">
                <span class="section-icon"><i class="bx bx-message-rounded-dots"></i></span>
                <div>
                    <h2 id="updates-title">Department updates and feedback</h2>
                    <p>Every status change and its feedback are kept here for the user.</p>
                </div>
            </div>

            <?php if (empty($statusUpdates)): ?>
                <div class="timeline-empty">No department status updates have been posted yet.</div>
            <?php else: ?>
                <div class="update-timeline">
                    <?php foreach ($statusUpdates as $update): ?>
                        <article class="timeline-item">
                            <span class="timeline-marker"><i class="bx bx-message-check"></i></span>
                            <div class="timeline-card">
                                <div class="timeline-top">
                                    <div class="timeline-change">
                                        <span><?php echo e(displayStatus($update['old_status'])); ?></span>
                                        <i class="bx bx-right-arrow-alt"></i>
                                        <span><?php echo e(displayStatus($update['new_status'])); ?></span>
                                    </div>
                                    <time class="timeline-date">
                                        <?php
                                        $updateTime = strtotime((string)$update['created_at']);
                                        echo $updateTime ? e(date('M d, Y h:i A', $updateTime)) : 'Date unavailable';
                                        ?>
                                    </time>
                                </div>
                                <p class="timeline-feedback"><?php echo nl2br(e($update['feedback'])); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="content-section" aria-labelledby="evidence-title">
            <div class="section-heading">
                <span class="section-icon"><i class="bx bx-image"></i></span>
                <div>
                    <h2 id="evidence-title">Photo evidence</h2>
                    <p>Open the submitted image at full size when closer inspection is needed.</p>
                </div>
            </div>

            <div class="evidence-card">
                <div class="evidence-header">
                    <strong>Submitted evidence</strong>
                    <span><?php echo $imageName !== '' ? 'Image available' : 'No image attached'; ?></span>
                </div>

                <?php if ($imageName !== ''): ?>
                    <div class="evidence-preview">
                        <img class="report-image"
                             src="uploads/<?php echo rawurlencode($imageName); ?>"
                             alt="Evidence for case #<?php echo e($reportId); ?>">
                        <a class="open-image"
                           href="uploads/<?php echo rawurlencode($imageName); ?>"
                           target="_blank"
                           rel="noopener">
                            <i class="bx bx-expand-alt"></i>
                            Open full image
                        </a>
                    </div>
                <?php else: ?>
                    <div class="empty-evidence">
                        <div>
                            <i class="bx bx-image-alt"></i>
                            <strong>No photo was submitted</strong>
                            <p>The report can still be reviewed using its written information.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($isAdmin): ?>
            <section class="content-section" aria-labelledby="status-title">
                <div class="status-update-card">
                    <div class="status-copy">
                        <i class="bx bx-message-square-edit"></i>
                        <div>
                            <h2 id="status-title">Update status and notify user</h2>
                            <p>Select a new status and explain what was done or what happens next. Feedback is required for every change.</p>
                        </div>
                    </div>

                    <form class="status-form" method="post" action="ReportPage.php?id=<?php echo $reportId; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['report_csrf_token']); ?>">

                        <div>
                            <label for="status" class="feedback-label">New case status</label>
                            <select class="status-select" id="status" name="status" required>
                                <option value="Pending" <?php echo $formStatus === 'Pending' ? 'selected' : ''; ?>>Action Needed</option>
                                <option value="In Progress" <?php echo $formStatus === 'In Progress' ? 'selected' : ''; ?>>Underway</option>
                                <option value="Resolved" <?php echo $formStatus === 'Resolved' ? 'selected' : ''; ?>>Settled</option>
                            </select>
                        </div>

                        <button class="update-status-button" type="submit">
                            <i class="bx bx-send"></i>
                            Save & notify user
                        </button>

                        <div class="feedback-field">
                            <label class="feedback-label" for="feedback">
                                Feedback for user
                                <span>Required · 5–1000 characters</span>
                            </label>
                            <textarea class="feedback-textarea"
                                      id="feedback"
                                      name="feedback"
                                      minlength="5"
                                      maxlength="1000"
                                      placeholder="Example: Our maintenance team inspected the pothole today. Repair work is scheduled for tomorrow morning."
                                      required><?php echo e($feedbackValue); ?></textarea>
                        </div>

                        <?php if ($formError !== ''): ?>
                            <p class="form-error" role="alert"><?php echo e($formError); ?></p>
                        <?php endif; ?>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <footer class="page-footer">
            &copy; <?php echo date('Y'); ?> AI City Guardian. Case information is visible only to authorized users.
        </footer>
    </main>
</body>
</html>