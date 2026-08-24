<?php
include "../db.php";
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../user/LogIn.php");
    exit();
}

$user_id = $_SESSION['id'];

$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    exit("User not found.");
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function priorityClass($priority) {
    $priority = strtolower($priority);
    $allowed = ['critical', 'high', 'medium', 'low'];

    return in_array($priority, $allowed, true) ? $priority : 'medium';
}

$sql = "SELECT * FROM reports WHERE user_id = ? ORDER BY report_id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reports_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Page</title>

    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">

    <style>
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

        .header-actions .form-btn {
            flex: none;
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

        .report-image {
            max-width: 280px;
            border-radius: 10px;
            margin-top: 12px;
            display: block;
        }

        .form-card + .form-card {
            margin-top: 25px;
        }

        .report-detail-row {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 8px;
        }

        .report-detail-row strong {
            color: #fff;
        }

        /* ---- Fix: email text overflowing the sidebar box ---- */
        .admin-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0; /* allow flex children to shrink below content size */
        }

        .admin-toggle span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
            flex: 1;
        }

        .admin-menu-item {
            min-width: 0;
        }
    </style>
    
</head>

<body>

<aside class="sidebar">

    <div>
        <div class="sidebar-header">
            <a href="../user/userpage.php" class="nav-logo">
                <h2 class="logo-text">AI City Guardian</h2>
            </a>
        </div>

        <ul class="sidebar-menu">

            <li class="sidebar-item active" id="menu-report">
                <a href="#" class="sidebar-link" onclick="switchPage('report'); return false;">
                    <i class="bx bxs-report"></i>
                    <span>My Reports</span>
                </a>
            </li>

            <li class="sidebar-item" id="menu-profile">
                <a href="#" class="sidebar-link" onclick="switchPage('profile'); return false;">
                    <i class="bx bxs-user"></i>
                    <span>Profile</span>
                </a>
            </li>

            <li class="sidebar-item" id="menu-settings">
                <a href="#" class="sidebar-link" onclick="switchPage('settings'); return false;">
                    <i class="bx bxs-cog"></i>
                    <span>Settings</span>
                </a>
            </li>

        </ul>
    </div>

    <div class="sidebar-footer">

        <ul class="sidebar-menu">

            <li class="sidebar-item admin-menu-item" id="admin-menu-item">

                <button type="button" class="sidebar-link admin-toggle" id="admin-toggle" aria-expanded="false">
                    <i class="bx bxs-user-circle"></i>
                    <span><?php echo e($user['email'] ?? 'Account'); ?></span>
                </button>

                <div class="logout-dropdown">
                    <a href="../user/logout.php" class="logout-btn">
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
            <h1 id="page-heading">My Reports</h1>
            <p id="page-subheading">Track the civic issues you've submitted</p>
        </div>

        <div class="header-actions">
            <a href="../user/uploadpage.php" class="form-btn primary">
                <i class="bx bx-plus"></i>
                Submit New Report
            </a>
        </div>

    </header>

    <main id="page-content">

        <?php if ($reports_result->num_rows > 0): ?>

            <?php $report_number = 1; ?>

            <?php while ($report = $reports_result->fetch_assoc()): ?>

                <?php $displayPriority = $report['ai_priority'] ?: 'Not analysed'; ?>

                <div class="form-card">

                    <div class="form-card-title">
                        Report <?php echo $report_number; ?>
                        <?php if ($report['ai_priority']): ?>
                            <span class="badge <?php echo e(priorityClass($displayPriority)); ?>">
                                <?php echo e($displayPriority); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="report-detail-row">
                        <strong>Issue Type:</strong> <?php echo e($report['issue_type'] ?? 'Not provided'); ?>
                    </p>

                    <p class="report-detail-row">
                        <strong>Location:</strong> <?php echo e($report['location'] ?? 'Not provided'); ?>
                    </p>

                    <p class="report-detail-row">
                        <strong>Description:</strong> <?php echo e($report['description'] ?? 'Not provided'); ?>
                    </p>

                    <?php if (!empty($report['image'])): ?>
                        <p class="report-detail-row"><strong>Report Image:</strong></p>
                        <img class="report-image" src="../user/uploadpage/<?php echo e($report['image']); ?>">
                    <?php endif; ?>

                    <p class="report-detail-row">
                        <strong>Status:</strong> <?php echo e($report['status'] ?? 'Pending'); ?>
                    </p>

                    <p class="report-detail-row">
                        <strong>Submitted:</strong> <?php echo e($report['created_at'] ?? ''); ?>
                    </p>

                </div>

                <?php $report_number++; ?>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="form-card">
                <div class="form-card-title">No Reports Yet</div>
                <p class="no-reports">You have not uploaded any reports.</p>
            </div>

        <?php endif; ?>

    </main>

</div>

<script>
const userEmail = <?php echo json_encode($user['email'] ?? ''); ?>;

const pageHeading = document.getElementById('page-heading');
const pageSubheading = document.getElementById('page-subheading');

// Text shown in the header for each page
const pageText = {
    report: {
        title: 'My Reports',
        subtitle: "Track the civic issues you've submitted"
    },
    profile: {
        title: 'Profile',
        subtitle: 'View your account information'
    },
    settings: {
        title: 'Settings',
        subtitle: 'Manage your account settings'
    }
};

function setActiveMenu(page) {
    document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('active'));
    const active = document.getElementById('menu-' + page);
    if (active) active.classList.add('active');
}

function switchPage(page) {
    const pageContent = document.getElementById('page-content');

    // Update the header title + subtitle for every page switch
    const text = pageText[page];
    if (text) {
        pageHeading.textContent = text.title;
        pageSubheading.textContent = text.subtitle;
    }

    if (page === 'report') {
        setActiveMenu('report');
        location.reload();
        return;
    }

    if (page === 'profile') {
        setActiveMenu('profile');
        pageContent.innerHTML = `
            <div class="form-card">
                <div class="form-card-title">Profile Information</div>
                <p class="report-detail-row"><strong>Email:</strong> ${userEmail}</p>
            </div>
        `;
        return;
    }

    if (page === 'settings') {
        setActiveMenu('settings');
        pageContent.innerHTML = `
            <div class="form-card">
                <div class="form-card-title">Settings</div>
                <p class="report-detail-row">Account settings will appear here.</p>
            </div>
        `;
        return;
    }
}

const adminItem = document.getElementById('admin-menu-item');
const adminToggle = document.getElementById('admin-toggle');

adminToggle.addEventListener('click', event => {
    event.stopPropagation();
    const isOpen = adminItem.classList.toggle('menu-open');
    adminToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
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