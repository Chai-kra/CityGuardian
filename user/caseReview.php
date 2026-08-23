<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../user/LogIn.php");
    exit();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function statusGroup($status) {
    if ($status === 'Resolved') {
        return 'settled';
    }

    if ($status === 'Assigned' || $status === 'In Progress') {
        return 'underway';
    }

    return 'action';
}

function priorityClass($priority) {
    $priority = strtolower((string)$priority);
    $allowed = ['critical', 'high', 'medium', 'low'];

    return in_array($priority, $allowed, true)
        ? $priority
        : 'medium';
}

function validDate($date) {
    if ($date === '') {
        return true;
    }

    $parsed = DateTime::createFromFormat('Y-m-d', $date);

    return $parsed && $parsed->format('Y-m-d') === $date;
}

$department = trim((string)($_SESSION['department'] ?? ''));

if ($department === '') {
    exit('Your admin account has no department.');
}

$search = trim((string)($_GET['search'] ?? ''));
$priority = (string)($_GET['priority'] ?? 'All');
$selectedDate = (string)($_GET['date'] ?? '');
$openSection = (string)($_GET['open'] ?? '');

$allowedPriorities = ['All', 'Critical', 'High', 'Medium', 'Low'];
$allowedSections = ['', 'all', 'action', 'underway', 'settled'];

if (!in_array($priority, $allowedPriorities, true)) {
    $priority = 'All';
}

if (!validDate($selectedDate)) {
    $selectedDate = '';
}

if (!in_array($openSection, $allowedSections, true)) {
    $openSection = '';
}

$sql = "SELECT * FROM reports
        WHERE ai_department = ?
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log('Case Review error: ' . $conn->error);
    exit('Unable to load reports.');
}

$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();

$allReports = [];

while ($row = $result->fetch_assoc()) {
    $allReports[] = $row;
}

$stmt->close();

$summary = [
    'total' => count($allReports),
    'action' => 0,
    'underway' => 0,
    'settled' => 0
];

foreach ($allReports as $report) {
    $group = statusGroup($report['status'] ?? 'Pending');
    $summary[$group]++;
}

$filteredReports = array_values(array_filter(
    $allReports,
    function ($report) use ($search, $priority, $selectedDate) {
        if (
            $priority !== 'All' &&
            strcasecmp(
                (string)($report['ai_priority'] ?? ''),
                $priority
            ) !== 0
        ) {
            return false;
        }

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

        if ($search !== '') {
            $searchText = implode(' ', [
                $report['report_id'] ?? '',
                $report['issue_type'] ?? '',
                $report['location'] ?? '',
                $report['description'] ?? '',
                $report['ai_description'] ?? ''
            ]);

            if (stripos($searchText, $search) === false) {
                return false;
            }
        }

        return true;
    }
));

$groupedReports = [
    'action' => [],
    'underway' => [],
    'settled' => []
];

foreach ($filteredReports as $report) {
    $group = statusGroup($report['status'] ?? 'Pending');
    $groupedReports[$group][] = $report;
}

$hasFilters =
    $search !== '' ||
    $priority !== 'All' ||
    $selectedDate !== '';

$sections = [
    'action' => [
        'title' => 'Action Needed',
        'empty' => 'No pending reports match the filters.'
    ],
    'underway' => [
        'title' => 'Underway',
        'empty' => 'No underway reports match the filters.'
    ],
    'settled' => [
        'title' => 'Settled',
        'empty' => 'No settled reports match the filters.'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Case Review - AI City Guardian</title>

    <link rel="stylesheet" href="../css/style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
          rel="stylesheet">

    <style>
        header.main-header {
            display: flex;
        }

        .nav-logo {
            color: #fff;
        }

        .admin-toggle {
            width: 100%;
            border: 0;
            background: transparent;
            cursor: pointer;
            text-align: left;
        }

        .admin-menu-item.menu-open .logout-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        .dashboard-filters {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .search-submit {
            border: 0;
            background: transparent;
            color: rgba(255, 255, 255, 0.65);
            font-size: 21px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .search-input {
            margin-left: 8px;
        }

        .filter-select,
        .filter-date {
            height: 54px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 40px;
            color: #fff;
            background: transparent;
            padding: 0 18px;
            outline: none;
            cursor: pointer;
        }

        .filter-select option {
            color: #fff;
            background: #111844;
        }

        .filter-date {
            color-scheme: dark;
        }

        .clear-filter {
            color: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 30px;
            padding: 9px 15px;
        }

        .summary-card-button {
            color: #fff;
            cursor: pointer;
            font: inherit;
        }

        .filter-result {
            margin: 22px 0 0;
            color: rgba(255, 255, 255, 0.65);
            font-size: 14px;
        }

        .badge.high {
            background: rgba(255, 128, 64, 0.2);
            color: #ff9b69;
            border: 1px solid #ff9b69;
        }

        .badge.low {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid #34d399;
        }

        .report-card .view-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .main-footer {
            justify-content: center;
        }

        @media (max-width: 1100px) {
            .main-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
            }
        }

        @media (max-width: 760px) {
            body {
                display: block;
            }

            .sidebar {
                width: 100%;
                min-height: auto;
            }

            .main-content {
                padding: 24px 16px;
            }

            .dashboard-filters,
            .search {
                width: 100%;
            }

            .search-input {
                width: 100%;
            }

            .report-card {
                align-items: flex-start;
                flex-direction: column;
                gap: 14px;
            }

            .report-meta {
                align-items: flex-start;
                margin: 0;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">

    <div class="sidebar-header">
        <a href="caseReview.php" class="nav-logo">
            <h2 class="logo-text">AI City Guardian</h2>
        </a>
    </div>

    <ul class="sidebar-menu">

        <li class="sidebar-item active">
            <a href="caseReview.php" class="sidebar-link">
                <i class="bx bxs-dashboard"></i>
                <span>Case Review</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a href="adminStatistics.php" class="sidebar-link">
                <i class="bx bx-bar-chart-alt-2"></i>
                <span>Statistics</span>
            </a>
        </li>

    </ul>

    <div class="sidebar-footer">

        <ul class="sidebar-menu">

            <li class="sidebar-item admin-menu-item"
                id="admin-menu-item">

                <button type="button"
                        class="sidebar-link admin-toggle"
                        id="admin-toggle"
                        aria-expanded="false">

                    <i class="bx bxs-user-circle"></i>
                    <span>Admin</span>

                </button>

                <div class="logout-dropdown">
                    <a href="../user/logout.php"
                       class="logout-btn">

                        <i class="bx bx-log-out"></i>
                        Logout

                    </a>
                </div>

            </li>

        </ul>

    </div>

</aside>

<div class="main-content">

    <header class="main-header">

        <div class="header-title">
            <h1>Dashboard Overview</h1>
            <p>Manage civic issues and track city maintenance</p>
        </div>

        <form class="header-actions dashboard-filters"
              method="get"
              action="caseReview.php">

            <div class="search">

                <button class="search-submit"
                        type="submit"
                        aria-label="Search">

                    <i class="bx bx-search"></i>

                </button>

                <input class="search-input"
                       type="search"
                       name="search"
                       placeholder="Search issues, locations..."
                       value="<?php echo e($search); ?>">

            </div>

            <select class="filter-select"
                    name="priority"
                    aria-label="Priority"
                    onchange="this.form.submit()">

                <?php foreach ($allowedPriorities as $option): ?>

                    <option value="<?php echo e($option); ?>"
                        <?php echo $priority === $option ? 'selected' : ''; ?>>

                        <?php
                        echo $option === 'All'
                            ? 'All priorities'
                            : e($option);
                        ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <input class="filter-date"
                   type="date"
                   name="date"
                   aria-label="Report date"
                   value="<?php echo e($selectedDate); ?>"
                   onchange="this.form.submit()">

            <?php if ($hasFilters): ?>

                <a class="clear-filter" href="caseReview.php">
                    Clear
                </a>

            <?php endif; ?>

        </form>

    </header>

    <main>

        <div class="card-container">

            <button class="card summary-card-button"
                    type="button"
                    data-summary-section="all">

                <div class="card-content">
                    <h3>Total</h3>
                    <p><?php echo $summary['total']; ?> cases</p>
                </div>

            </button>

            <button class="card summary-card-button"
                    type="button"
                    data-summary-section="action">

                <div class="card-content">
                    <h3>Action Needed</h3>
                    <p><?php echo $summary['action']; ?> cases</p>
                </div>

            </button>

            <button class="card summary-card-button"
                    type="button"
                    data-summary-section="underway">

                <div class="card-content">
                    <h3>Underway</h3>
                    <p><?php echo $summary['underway']; ?> cases</p>
                </div>

            </button>

            <button class="card summary-card-button"
                    type="button"
                    data-summary-section="settled">

                <div class="card-content">
                    <h3>Settled</h3>
                    <p><?php echo $summary['settled']; ?> cases</p>
                </div>

            </button>

        </div>

        <?php if ($hasFilters): ?>

            <p class="filter-result">
                Showing <?php echo count($filteredReports); ?>
                of <?php echo count($allReports); ?> cases.
            </p>

        <?php endif; ?>

        <div class="caseReviewBox">

            <p style="text-align:center; margin-bottom:30px; color:rgba(255,255,255,.7);">

                Department:

                <strong style="color:#fff;">
                    <?php echo e($department); ?>
                </strong>

            </p>

            <div class="sub-category">

                <?php foreach ($sections as $sectionKey => $section): ?>

                    <section id="section-<?php echo e($sectionKey); ?>">

                        <button class="collapsible"
                                type="button"
                                data-section="<?php echo e($sectionKey); ?>"
                                aria-expanded="false">

                            <span><?php echo e($section['title']); ?></span>

                            <span class="case-count">
                                <?php echo count($groupedReports[$sectionKey]); ?>
                            </span>

                        </button>

                        <div class="content">

                            <div class="inner-content report-list">

                                <?php if ($groupedReports[$sectionKey] === []): ?>

                                    <p class="no-reports">
                                        <?php echo e($section['empty']); ?>
                                    </p>

                                <?php else: ?>

                                    <?php foreach ($groupedReports[$sectionKey] as $report): ?>

                                        <?php
                                        $timestamp = strtotime(
                                            (string)($report['created_at'] ?? '')
                                        );

                                        $displayDate = $timestamp
                                            ? date('M d, Y', $timestamp)
                                            : 'No date';

                                        $displayPriority =
                                            $report['ai_priority']
                                            ?: 'Medium';
                                        ?>

                                        <div class="report-card">

                                            <div class="report-info">

                                                <h4>
                                                    Case #<?php echo e($report['report_id']); ?>:
                                                    <?php echo e($report['issue_type'] ?: 'General issue'); ?>
                                                </h4>

                                                <p>
                                                    Location:
                                                    <?php echo e($report['location'] ?: 'Not provided'); ?>
                                                </p>

                                            </div>

                                            <div class="report-meta">

                                                <span class="badge <?php echo e(priorityClass($displayPriority)); ?>">
                                                    <?php echo e($displayPriority); ?>
                                                </span>

                                                <span class="date">
                                                    <?php echo e($displayDate); ?>
                                                </span>

                                            </div>

                                            <a class="view-btn"
                                               href="../report/ReportPage.php?id=<?php echo (int)$report['report_id']; ?>">

                                                Review

                                            </a>

                                        </div>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </div>

                        </div>

                    </section>

                <?php endforeach; ?>

            </div>

        </div>

    </main>

    <footer class="main-footer">
        <p>
            &copy; <?php echo date('Y'); ?>
            AI City Guardian. All rights reserved.
        </p>
    </footer>

</div>

<script>
const collapsibleButtons = document.querySelectorAll('.collapsible');

function setSection(button, shouldOpen) {
    const content = button.nextElementSibling;

    button.classList.toggle('active', shouldOpen);
    button.setAttribute(
        'aria-expanded',
        shouldOpen ? 'true' : 'false'
    );

    if (shouldOpen) {
        content.style.maxHeight = content.scrollHeight + 'px';

        setTimeout(() => {
            if (button.getAttribute('aria-expanded') === 'true') {
                content.style.overflow = 'visible';
            }
        }, 220);
    } else {
        content.style.overflow = 'hidden';
        content.style.maxHeight = null;
    }
}

collapsibleButtons.forEach(button => {
    button.addEventListener('click', () => {
        const shouldOpen =
            button.getAttribute('aria-expanded') !== 'true';

        setSection(button, shouldOpen);
    });
});

function openSummarySection(section) {
    collapsibleButtons.forEach(button => {
        const shouldOpen =
            section === 'all' ||
            button.dataset.section === section;

        setSection(button, shouldOpen);
    });

    if (section !== 'all') {
        document
            .getElementById('section-' + section)
            ?.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
    }
}

document
    .querySelectorAll('[data-summary-section]')
    .forEach(card => {
        card.addEventListener('click', () => {
            openSummarySection(card.dataset.summarySection);
        });
    });

const requestedSection =
    <?php echo json_encode($openSection); ?>;

if (requestedSection) {
    window.addEventListener('load', () => {
        openSummarySection(requestedSection);
    });
}

const adminItem =
    document.getElementById('admin-menu-item');

const adminToggle =
    document.getElementById('admin-toggle');

adminToggle.addEventListener('click', event => {
    event.stopPropagation();

    const isOpen =
        adminItem.classList.toggle('menu-open');

    adminToggle.setAttribute(
        'aria-expanded',
        isOpen ? 'true' : 'false'
    );
});

document.addEventListener('click', event => {
    if (!adminItem.contains(event.target)) {
        adminItem.classList.remove('menu-open');
        adminToggle.setAttribute('aria-expanded', 'false');
    }
});
</script>

</body>
</html>