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

function validDate($date) {
    if ($date === '') {
        return true;
    }

    $parsed = DateTime::createFromFormat('Y-m-d', $date);

    return $parsed && $parsed->format('Y-m-d') === $date;
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

$fromDate = (string)($_GET['from'] ?? '');
$toDate = (string)($_GET['to'] ?? '');
$dateError = '';

if (!validDate($fromDate)) {
    $fromDate = '';
}

if (!validDate($toDate)) {
    $toDate = '';
}

if (
    $fromDate !== '' &&
    $toDate !== '' &&
    $fromDate > $toDate
) {
    $dateError =
        'The From date cannot be later than the To date.';
}

$result = $conn->query(
    "SELECT ai_department, status, created_at
     FROM reports
     ORDER BY ai_department ASC"
);

if (!$result) {
    error_log('Statistics error: ' . $conn->error);
    exit('Unable to load statistics.');
}

$reports = [];

while ($row = $result->fetch_assoc()) {
    if ($dateError !== '') {
        continue;
    }

    $timestamp = strtotime(
        (string)($row['created_at'] ?? '')
    );

    $reportDate = $timestamp
        ? date('Y-m-d', $timestamp)
        : '';

    if (
        $fromDate !== '' &&
        ($reportDate === '' || $reportDate < $fromDate)
    ) {
        continue;
    }

    if (
        $toDate !== '' &&
        ($reportDate === '' || $reportDate > $toDate)
    ) {
        continue;
    }

    $reports[] = $row;
}

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

    $departmentName = trim(
        (string)($report['ai_department'] ?? '')
    );

    if ($departmentName === '') {
        $departmentName = 'Unassigned Department';
    }

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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Statistics Overview - AI City Guardian</title>

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

        .statistics-form {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .statistics-date {
            height: 54px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 40px;
            color: #fff;
            color-scheme: dark;
            background: transparent;
            padding: 0 18px;
            outline: none;
        }

        .apply-btn {
            height: 54px;
            border: 2px solid #2563eb;
            border-radius: 40px;
            padding: 0 20px;
            color: #fff;
            background: #2563eb;
            font-weight: 600;
            cursor: pointer;
        }

        .refresh-link {
            text-decoration: none;
        }

        .summary-card-link {
            color: #fff;
            display: block;
        }

        .date-error,
        .empty-statistics {
            width: 100%;
            margin: 20px 0;
            padding: 18px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.8);
            text-align: center;
        }

        .date-error {
            color: #fecaca;
            border-color: rgba(239, 68, 68, 0.5);
            background: rgba(239, 68, 68, 0.1);
        }

        .main-footer {
            justify-content: center;
        }

        @media (max-width: 1050px) {
            .main-header {
                align-items: flex-start;
                flex-direction: column;
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

            .statistics-form {
                width: 100%;
            }

            .statistics-date {
                flex: 1;
                min-width: 145px;
            }

            .dept-card-body {
                flex-direction: column;
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

        <li class="sidebar-item">
            <a href="caseReview.php" class="sidebar-link">
                <i class="bx bxs-dashboard"></i>
                <span>Case Review</span>
            </a>
        </li>

        <li class="sidebar-item active">
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
            <h1>Statistics Overview</h1>
            <p>
                Analyze city maintenance reports and performance metrics
            </p>
        </div>

        <form class="header-actions statistics-form"
              method="get"
              action="adminStatistics.php">

            <input class="statistics-date"
                   type="date"
                   name="from"
                   aria-label="From date"
                   value="<?php echo e($fromDate); ?>">

            <input class="statistics-date"
                   type="date"
                   name="to"
                   aria-label="To date"
                   value="<?php echo e($toDate); ?>">

            <button class="apply-btn" type="submit">
                Apply
            </button>

            <a class="refresh-btn refresh-link"
               href="adminStatistics.php"
               aria-label="Clear dates"
               title="Clear dates">

                <i class="bx bx-refresh"
                   style="font-size:24px;"></i>

            </a>

        </form>

    </header>

    <main>

        <?php if ($dateError !== ''): ?>

            <p class="date-error">
                <?php echo e($dateError); ?>
            </p>

        <?php endif; ?>

        <div class="card-container"
             style="margin-bottom:30px;">

            <a class="card summary-card-link"
               href="caseReview.php?open=all">

                <div class="card-content">
                    <h3>Total Cases</h3>
                    <p><?php echo $summary['total']; ?> cases</p>
                </div>

            </a>

            <a class="card summary-card-link"
               href="caseReview.php?open=action">

                <div class="card-content">
                    <h3>Action Needed</h3>
                    <p><?php echo $summary['action']; ?> cases</p>
                </div>

            </a>

            <a class="card summary-card-link"
               href="caseReview.php?open=underway">

                <div class="card-content">
                    <h3>Underway</h3>
                    <p><?php echo $summary['underway']; ?> cases</p>
                </div>

            </a>

            <a class="card summary-card-link"
               href="caseReview.php?open=settled">

                <div class="card-content">
                    <h3>Settled</h3>
                    <p><?php echo $summary['settled']; ?> cases</p>
                </div>

            </a>

        </div>

        <?php if ($departments === []): ?>

            <div class="empty-statistics">
                No reports were found for the selected date range.
            </div>

        <?php else: ?>

            <div class="department-grid">

                <?php foreach ($departments as $department): ?>

                    <?php
                    $total = max(
                        1,
                        (int)$department['total']
                    );

                    $settledPercent = round(
                        ($department['settled'] / $total) * 100,
                        2
                    );

                    $underwayStop = round(
                        (
                            (
                                $department['settled'] +
                                $department['underway']
                            ) / $total
                        ) * 100,
                        2
                    );

                    $gradient =
                        "conic-gradient(" .
                        "#10b981 0% {$settledPercent}%, " .
                        "#fbbf24 {$settledPercent}% " .
                        "{$underwayStop}%, " .
                        "#ef4444 {$underwayStop}% 100%)";
                    ?>

                    <div class="dept-card">

                        <h3>
                            <?php echo e($department['name']); ?>
                        </h3>

                        <div class="dept-card-body">

                            <div class="donut-chart"
                                 style="background:<?php echo e($gradient); ?>;">

                                <div class="donut-inner">

                                    <span class="donut-number">
                                        <?php echo $department['total']; ?>
                                    </span>

                                </div>

                            </div>

                            <ul class="dept-legend">

                                <li class="legend-item">

                                    <div class="legend-label">

                                        <span class="legend-dot settled"></span>
                                        Settled

                                    </div>

                                    <span class="legend-value">
                                        <?php echo $department['settled']; ?>
                                    </span>

                                </li>

                                <li class="legend-item">

                                    <div class="legend-label">

                                        <span class="legend-dot underway"></span>
                                        Underway

                                    </div>

                                    <span class="legend-value">
                                        <?php echo $department['underway']; ?>
                                    </span>

                                </li>

                                <li class="legend-item">

                                    <div class="legend-label">

                                        <span class="legend-dot action-needed"></span>
                                        Action Needed

                                    </div>

                                    <span class="legend-value">
                                        <?php echo $department['action']; ?>
                                    </span>

                                </li>

                            </ul>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </main>

    <footer class="main-footer">
        <p>
            &copy; <?php echo date('Y'); ?>
            AI City Guardian. All rights reserved.
        </p>
    </footer>

</div>

<script>
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