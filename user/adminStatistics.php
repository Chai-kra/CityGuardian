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

function validDate($date) {
    if ($date === '') {
        return true;
    }

    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date;
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

$adminName = trim((string)($_SESSION['name'] ?? 'Admin'));
$adminName = $adminName !== '' ? $adminName : 'Admin';
$adminDepartment = trim((string)($_SESSION['department'] ?? 'City administration'));
$adminDepartment = $adminDepartment !== '' ? $adminDepartment : 'City administration';
$adminInitial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($adminName, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($adminName, 0, 1));

$fromDate = (string)($_GET['from'] ?? '');
$toDate = (string)($_GET['to'] ?? '');
$dateError = '';

if (!validDate($fromDate)) {
    $fromDate = '';
}

if (!validDate($toDate)) {
    $toDate = '';
}

if ($fromDate !== '' && $toDate !== '' && $fromDate > $toDate) {
    $dateError = 'The From date cannot be later than the To date.';
}

$result = $conn->query(
    'SELECT ai_department, status, created_at FROM reports ORDER BY ai_department ASC'
);

if (!$result) {
    exit('Unable to load statistics.');
}

$reports = [];

while ($row = $result->fetch_assoc()) {
    if ($dateError !== '') {
        continue;
    }

    $timestamp = strtotime((string)($row['created_at'] ?? ''));
    $reportDate = $timestamp ? date('Y-m-d', $timestamp) : '';

    if ($fromDate !== '' && ($reportDate === '' || $reportDate < $fromDate)) {
        continue;
    }

    if ($toDate !== '' && ($reportDate === '' || $reportDate > $toDate)) {
        continue;
    }

    $reports[] = $row;
}

$conn->close();

$summary = [
    'total' => count($reports),
    'action' => 0,
    'underway' => 0,
    'settled' => 0
];

$departments = [];

foreach ($reports as $report) {
    $group = statusGroup($report['status'] ?? 'Pending');
    $summary[$group]++;

    $departmentName = trim((string)($report['ai_department'] ?? ''));
    $departmentName = $departmentName !== '' ? $departmentName : 'Unassigned Department';

    if (!isset($departments[$departmentName])) {
        $departments[$departmentName] = [
            'name' => $departmentName,
            'total' => 0,
            'settled' => 0,
            'underway' => 0,
            'action' => 0
        ];
    }

    $departments[$departmentName]['total']++;
    $departments[$departmentName][$group]++;
}

uasort($departments, function ($left, $right) {
    return $right['total'] <=> $left['total'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - AI City Guardian</title>
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
        .sidebar-note { margin-top: 24px; padding: 15px; border: 1px solid var(--line); border-radius: 14px; background: rgba(255, 255, 255, .035); }
        .sidebar-note i { color: var(--amber); font-size: 20px; }
        .sidebar-note strong { display: block; margin: 7px 0 4px; font-size: 12px; }
        .sidebar-note p { color: #7e8bb1; font-size: 9px; line-height: 1.6; }
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
        .page-header { display: flex; align-items: center; justify-content: space-between; gap: 22px; margin-bottom: 21px; }
        .header-copy { display: flex; align-items: center; gap: 14px; }
        .eyebrow { display: block; margin-bottom: 3px; color: var(--blue-light); font-size: 9px; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; }
        .page-header h1 { font-size: clamp(24px, 3vw, 35px); letter-spacing: -.045em; }
        .page-header p { margin-top: 4px; color: var(--muted); font-size: 11px; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 13px; margin-bottom: 18px; }
        .summary-card {
            display: flex;
            width: 100%;
            min-height: 116px;
            align-items: center;
            gap: 13px;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 16px;
            color: #fff;
            background: linear-gradient(145deg, rgba(21, 31, 80, .84), rgba(13, 20, 59, .84));
            box-shadow: var(--shadow);
            text-align: left;
            cursor: default;
        }
        .summary-icon { display: grid; width: 43px; height: 43px; flex: 0 0 auto; place-items: center; border-radius: 13px; color: var(--blue-light); background: rgba(51, 117, 245, .12); font-size: 21px; }
        .summary-card.action .summary-icon { color: #fda4af; background: rgba(251, 113, 133, .1); }
        .summary-card.underway .summary-icon { color: #fde68a; background: rgba(251, 191, 36, .1); }
        .summary-card.settled .summary-icon { color: #a7f3d0; background: rgba(52, 211, 153, .1); }
        .summary-copy { min-width: 0; }
        .summary-copy strong { display: block; font-size: 21px; letter-spacing: -.03em; }
        .summary-copy span { display: block; margin-top: 2px; color: #8996b9; font-size: 9px; }
        .filter-panel { margin-bottom: 18px; padding: 15px; border: 1px solid var(--line); border-radius: 16px; background: rgba(17, 24, 68, .74); box-shadow: var(--shadow); }
        .filter-form { display: flex; align-items: center; gap: 9px; }
        .search-field { position: relative; min-width: 230px; flex: 1; }
        .search-field i { position: absolute; top: 50%; left: 13px; color: #7482aa; font-size: 18px; transform: translateY(-50%); }
        .filter-input, .filter-select {
            width: 100%;
            height: 43px;
            border: 1px solid var(--line);
            border-radius: 11px;
            outline: 0;
            color: #fff;
            background: rgba(8, 15, 53, .58);
            color-scheme: dark;
            font-size: 10px;
        }
        .filter-input { padding: 0 13px 0 40px; }
        .filter-select { min-width: 145px; padding: 0 11px; cursor: pointer; }
        .filter-input::placeholder { color: #5f6c94; }
        .filter-input:focus, .filter-select:focus { border-color: var(--blue-light); box-shadow: 0 0 0 3px rgba(107, 154, 255, .09); }
        .filter-button, .clear-button {
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
        .filter-button { border: 1px solid rgba(255, 255, 255, .12); color: #fff; background: var(--blue); }
        .filter-button:hover { background: #4380f6; }
        .clear-button { border: 1px solid var(--line); color: #aeb9d6; background: rgba(255, 255, 255, .035); }
        .section-shell { margin-top: 18px; padding: 21px; border: 1px solid var(--line); border-radius: 18px; background: linear-gradient(145deg, rgba(21, 31, 80, .82), rgba(13, 20, 59, .82)); box-shadow: var(--shadow); }
        .section-heading { display: flex; align-items: center; justify-content: space-between; gap: 15px; margin-bottom: 16px; }
        .section-heading h2 { font-size: 15px; }
        .section-heading p { margin-top: 3px; color: #7f8db4; font-size: 9px; }
        .result-count { color: #8190b6; font-size: 9px; }
        .empty-state { display: grid; min-height: 270px; place-items: center; padding: 30px; border: 1px dashed var(--line-strong); border-radius: 15px; color: #7f8db4; background: rgba(8, 15, 53, .35); text-align: center; }
        .empty-state i { display: block; margin-bottom: 8px; color: #60709c; font-size: 39px; }
        .empty-state strong { display: block; color: #aab5d2; font-size: 13px; }
        .empty-state p { margin-top: 5px; font-size: 9px; }
        .page-footer { margin-top: 38px; padding: 20px 2px 3px; border-top: 1px solid rgba(151, 166, 210, .11); color: #69779f; font-size: 9px; text-align: center; }
        @media (max-width: 1120px) {
            .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .page-header { align-items: flex-start; flex-direction: column; }
            .filter-form { width: 100%; flex-wrap: wrap; }
            .search-field { min-width: min(100%, 300px); }
        }
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); }
            .nav-open .sidebar { transform: translateX(0); }
            .sidebar-overlay { position: fixed; inset: 0; z-index: 45; display: block; visibility: hidden; opacity: 0; background: rgba(3, 7, 24, .68); backdrop-filter: blur(3px); transition: .22s ease; }
            .nav-open .sidebar-overlay { visibility: visible; opacity: 1; }
            .page-content { margin-left: 0; }
            .mobile-menu { display: grid; }
        }
        @media (max-width: 650px) {
            .page-content { padding: 19px 13px; }
            .summary-grid { grid-template-columns: 1fr; }
            .summary-card { min-height: 99px; }
            .filter-form { align-items: stretch; flex-direction: column; }
            .search-field, .filter-select, .filter-button, .clear-button { width: 100%; min-width: 0; }
            .section-shell { padding: 15px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; }
        }
        .date-error { display: flex; align-items: center; gap: 9px; margin-bottom: 18px; padding: 13px 15px; border: 1px solid rgba(251, 113, 133, .38); border-radius: 13px; color: #fecdd3; background: rgba(251, 113, 133, .09); font-size: 10px; }
        .department-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); grid-auto-rows: 1fr; gap: 14px; align-items: stretch; }
        .department-card { display: flex; min-width: 0; height: 100%; flex-direction: column; padding: 18px; border: 1px solid var(--line); border-radius: 15px; background: rgba(8, 15, 53, .42); }
        .department-card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; min-height: 45px; }
        .department-card-header h3 { font-size: 12px; line-height: 1.5; }
        .department-total { padding: 5px 8px; border-radius: 999px; color: #b8c8ef; background: rgba(51, 117, 245, .11); font-size: 8px; font-weight: 700; white-space: nowrap; }
        .department-body { display: flex; flex: 1; align-items: center; justify-content: center; gap: 25px; padding-top: 18px; }
        .donut-chart { position: relative; display: grid; width: 150px; height: 150px; flex: 0 0 auto; place-items: center; border-radius: 50%; }
        .donut-inner { display: grid; width: 103px; height: 103px; place-items: center; border: 1px solid var(--line); border-radius: 50%; background: #101741; box-shadow: inset 0 0 22px rgba(2, 6, 23, .3); text-align: center; }
        .donut-inner strong { display: block; font-size: 20px; }
        .donut-inner span { display: block; margin-top: 1px; color: #7281aa; font-size: 7px; text-transform: uppercase; }
        .department-legend { display: grid; min-width: 160px; gap: 9px; list-style: none; }
        .legend-item { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 9px 10px; border: 1px solid rgba(151, 166, 210, .14); border-radius: 9px; background: rgba(255, 255, 255, .025); }
        .legend-label { display: flex; align-items: center; gap: 7px; color: #9aa6c5; font-size: 8px; }
        .legend-dot { width: 8px; height: 8px; border-radius: 50%; }
        .legend-dot.action { background: #ef4444; }
        .legend-dot.underway { background: #fbbf24; }
        .legend-dot.settled { background: #10b981; }
        .legend-value { color: #fff; font-size: 10px; font-weight: 700; }
        @media (max-width: 1120px) { .department-body { gap: 16px; } }
        @media (max-width: 1000px) { .department-grid { grid-template-columns: 1fr; } }
        @media (max-width: 650px) {
            .department-body { flex-direction: column; }
            .department-legend { width: 100%; }
            .donut-chart { width: 140px; height: 140px; }
            .donut-inner { width: 96px; height: 96px; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar">
        <a class="brand" href="caseReview.php">
            <span class="brand-mark"><i class="bx bxs-city"></i></span>
            <span>AI City Guardian</span>
        </a>

        <p class="nav-label">Admin portal</p>
        <nav class="sidebar-nav">
            <a class="nav-link" href="caseReview.php"><i class="bx bxs-dashboard"></i><span>Case Review</span></a>
            <a class="nav-link active" href="adminStatistics.php" aria-current="page"><i class="bx bx-bar-chart-alt-2"></i><span>Statistics</span></a>
        </nav>

        <div class="sidebar-note">
            <i class="bx bx-line-chart"></i>
            <strong>Performance overview</strong>
            <p>Use the date range to compare case activity across departments.</p>
        </div>

        <div class="sidebar-footer">
            <button class="profile-button" id="profileButton" type="button" aria-expanded="false">
                <span class="profile-avatar"><?php echo e($adminInitial); ?></span>
                <span class="profile-copy"><strong><?php echo e($adminName); ?></strong><small><?php echo e($adminDepartment); ?></small></span>
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
                <button class="mobile-menu" id="mobileMenu" type="button" aria-label="Open navigation" aria-expanded="false"><i class="bx bx-menu"></i></button>
                <div>
                    <span class="eyebrow">Admin analytics</span>
                    <h1>Statistics Overview</h1>
                    <p>Compare city maintenance workloads and resolution progress.</p>
                </div>
            </div>
        </header>

        <main>
            <section class="filter-panel" aria-label="Statistics date filters">
                <form class="filter-form" method="get" action="adminStatistics.php">
                    <input class="filter-select" type="date" name="from" value="<?php echo e($fromDate); ?>" aria-label="From date">
                    <input class="filter-select" type="date" name="to" value="<?php echo e($toDate); ?>" aria-label="To date">
                    <button class="filter-button" type="submit"><i class="bx bx-filter-alt"></i>Apply dates</button>
                    <?php if ($fromDate !== '' || $toDate !== ''): ?><a class="clear-button" href="adminStatistics.php"><i class="bx bx-refresh"></i>Reset</a><?php endif; ?>
                </form>
            </section>

            <?php if ($dateError !== ''): ?>
                <div class="date-error"><i class="bx bx-error-circle"></i><?php echo e($dateError); ?></div>
            <?php endif; ?>

            <section class="summary-grid" aria-label="Statistics summary">
                <article class="summary-card">
                    <span class="summary-icon"><i class="bx bx-folder"></i></span>
                    <span class="summary-copy"><strong><?php echo $summary['total']; ?></strong><span>Total cases</span></span>
                </article>
                <article class="summary-card action">
                    <span class="summary-icon"><i class="bx bx-error-circle"></i></span>
                    <span class="summary-copy"><strong><?php echo $summary['action']; ?></strong><span>Action needed</span></span>
                </article>
                <article class="summary-card underway">
                    <span class="summary-icon"><i class="bx bx-loader-circle"></i></span>
                    <span class="summary-copy"><strong><?php echo $summary['underway']; ?></strong><span>Underway</span></span>
                </article>
                <article class="summary-card settled">
                    <span class="summary-icon"><i class="bx bx-check-circle"></i></span>
                    <span class="summary-copy"><strong><?php echo $summary['settled']; ?></strong><span>Settled</span></span>
                </article>
            </section>

            <section class="section-shell">
                <div class="section-heading">
                    <div>
                        <h2>Department performance</h2>
                        <p>Case distribution for the selected date range.</p>
                    </div>
                    <span class="result-count"><?php echo count($departments); ?> department<?php echo count($departments) === 1 ? '' : 's'; ?></span>
                </div>

                <?php if (empty($departments)): ?>
                    <div class="empty-state"><div><i class="bx bx-bar-chart-square"></i><strong>No statistics available</strong><p>No reports were found for the selected date range.</p></div></div>
                <?php else: ?>
                    <div class="department-grid">
                        <?php foreach ($departments as $department): ?>
                            <?php
                            $total = max(1, (int)$department['total']);
                            $settledPercent = round(($department['settled'] / $total) * 100, 2);
                            $underwayStop = round((($department['settled'] + $department['underway']) / $total) * 100, 2);
                            $gradient = "conic-gradient(#10b981 0% {$settledPercent}%, #fbbf24 {$settledPercent}% {$underwayStop}%, #ef4444 {$underwayStop}% 100%)";
                            ?>
                            <article class="department-card">
                                <div class="department-card-header">
                                    <h3><?php echo e($department['name']); ?></h3>
                                    <span class="department-total"><?php echo $department['total']; ?> cases</span>
                                </div>
                                <div class="department-body">
                                    <div class="donut-chart" style="background: <?php echo e($gradient); ?>;">
                                        <div class="donut-inner"><div><strong><?php echo $department['total']; ?></strong><span>Total cases</span></div></div>
                                    </div>
                                    <ul class="department-legend">
                                        <li class="legend-item"><span class="legend-label"><span class="legend-dot action"></span>Action Needed</span><span class="legend-value"><?php echo $department['action']; ?></span></li>
                                        <li class="legend-item"><span class="legend-label"><span class="legend-dot underway"></span>Underway</span><span class="legend-value"><?php echo $department['underway']; ?></span></li>
                                        <li class="legend-item"><span class="legend-label"><span class="legend-dot settled"></span>Settled</span><span class="legend-value"><?php echo $department['settled']; ?></span></li>
                                    </ul>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <footer class="page-footer">&copy; <?php echo date('Y'); ?> AI City Guardian. Authorized analytics dashboard.</footer>
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
            const open = !body.classList.contains('nav-open');
            body.classList.toggle('nav-open', open);
            mobileMenu.setAttribute('aria-expanded', String(open));
        });

        sidebarOverlay.addEventListener('click', closeNavigation);

        profileButton.addEventListener('click', event => {
            event.stopPropagation();
            const open = !profileMenu.classList.contains('open');
            profileMenu.classList.toggle('open', open);
            profileButton.setAttribute('aria-expanded', String(open));
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
            if (window.innerWidth > 900) closeNavigation();
        });
    </script>
</body>
</html>