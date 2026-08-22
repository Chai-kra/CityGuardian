<?php
session_start();
include "../db.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/LogIn.php");
    exit();
}

$department = $_SESSION['department'];

$sql = "SELECT * FROM reports WHERE ai_department = ? ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();

$reports = [];

while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

$totalCases = count($reports);
$actionNeededCases = 0;
$underwayCases = 0;
$settledCases = 0;

foreach ($reports as $report) {
    if ($report['status'] === 'Pending') {
        $actionNeededCases++;
    }

    if ($report['status'] === 'Assigned' || $report['status'] === 'In Progress') {
        $underwayCases++;
    }

    if ($report['status'] === 'Resolved') {
        $settledCases++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Case Review</title>
<link rel="stylesheet" href="/css/style.css">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=search">
</head>

<body>
    <!-- =======================
         SIDEBAR (LEFT COLUMN)
    ======================== -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="#" class="nav-logo">
                <h2 class="logo-text">AI City Guardian</h2>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item active">
                <a href="caseReview.php" class="sidebar-link">
                    <i class='bx bxs-dashboard'></i>
                    <span>Case Review</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="adminStatistics.php" class="sidebar-link">
                    <i class='bx bx-bar-chart-alt-2'></i>
                    <span>Statistics</span>
                </a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <ul class="sidebar-menu">
                <li class="sidebar-item admin-menu-item">
                     <a href="#" class="sidebar-link">
                        <i class='bx bxs-user-circle'></i>
                        <span>Admin</span>
                    </a>
                    <div class="logout-dropdown">
                        <a href="../user/logout.php" class="logout-btn">
                            <i class='bx bx-log-out'></i>
                            Logout
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </aside>

<!-- =======================
         MAIN CONTENT (RIGHT COLUMN)
    ======================== -->
    <div class="main-content">
        <header class="main-header">
            <div class="header-title">
                <h1>Dashboard Overview</h1>
                <p>Manage civic issues and track city maintenance</p>
            </div>
            <div class="header-actions">
                 <div class="search">
                    <span class="search-icon material-symbols-outlined">search</span>
                    <input class="search-input" type="search" name="search" placeholder="Search issues, locations..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                 <div class="dropdown">
                    <div class="select">
                        <span class="selected">Priority</span>
                        <div class="caret"></div>
                    </div>
                    <ul class="menu">
                        <li class="active">Priority</li>
                        <li>Low</li>
                        <li>Medium</li>
                        <li>High</li>
                        <li>Critical</li>
                    </ul>
                </div>
                <div class="date-picker-container">
                    <div class="select date-select">
                        <span class="selected" id="date-label">Choose Date</span>
                        <div class="caret"></div>
                        <input type="date" id="case-date-picker" class="native-date-input" onchange="updateDateLabel(this)">
                    </div>
                </div>
            </div>
        </header>

        <main>
            <!-- Summary Cards -->
            <div class="card-container">
                <!-- Your 4 PHP summary cards go here -->
                 <div class="card">
                    <div class="card-content">
                        <h3>Total</h3>
                        <p><?php echo $totalCases; ?> cases</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3>Action Needed</h3>
                        <p><?php echo $actionNeededCases; ?> cases</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3>Underway</h3>
                        <p><?php echo $underwayCases; ?> cases</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3>Settled</h3>
                        <p><?php echo $settledCases; ?> cases</p>
                    </div>
                </div>
            </div>

            <!-- Case Review Box -->
            <div class="caseReviewBox">
                <div class="sub-category">
                    
                    <!-- 1. Action Needed -->
                    <button class="collapsible">
                        <span>Action needed</span>
                        <span class="case-count"><?php echo $actionNeededCases; ?></span>
                    </button>
                    <div class="content">
                        <div class="inner-content report-list">
                            <?php
                            $hasPending = false;
                            foreach ($reports as $report):
                                if ($report['status'] !== 'Pending') {
                                    continue;
                                }
                                $hasPending = true;
                            ?>
                            <div class="report-card">
                                <div class="report-info">
                                    <h4>Case #<?php echo htmlspecialchars($report['report_id']); ?>: <?php echo htmlspecialchars($report['issue_type']); ?></h4>
                                    <p>Location: <?php echo htmlspecialchars($report['location']); ?></p>
                                </div>
                                <div class="report-meta">
                                    <span class="badge <?php echo strtolower($report['ai_priority']); ?>"><?php echo htmlspecialchars($report['ai_priority']); ?></span>
                                    <span class="date"><?php echo date("M d, Y", strtotime($report['created_at'])); ?></span>
                                </div>
                                <button class="view-btn" type="button" onclick="viewReport(<?php echo $report['report_id']; ?>)">Review</button>
                            </div>
                            <?php endforeach; ?>

                            <?php if (!$hasPending): ?>
                            <p class="no-reports">No pending reports.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 2. Underway -->
                    <button class="collapsible">
                        <span>Underway</span>
                        <span class="case-count"><?php echo $underwayCases; ?></span>
                    </button>
                    <div class="content">
                        <div class="inner-content report-list">
                            <?php
                            $hasUnderway = false;
                            foreach ($reports as $report):
                                if ($report['status'] !== 'Assigned' && $report['status'] !== 'In Progress') {
                                    continue;
                                }
                                $hasUnderway = true;
                            ?>
                            <div class="report-card">
                                <div class="report-info">
                                    <h4>Case #<?php echo htmlspecialchars($report['report_id']); ?>: <?php echo htmlspecialchars($report['issue_type']); ?></h4>
                                    <p>Location: <?php echo htmlspecialchars($report['location']); ?></p>
                                </div>
                                <div class="report-meta">
                                    <span class="badge <?php echo strtolower($report['ai_priority']); ?>"><?php echo htmlspecialchars($report['ai_priority']); ?></span>
                                    <span class="date"><?php echo date("M d, Y", strtotime($report['created_at'])); ?></span>
                                </div>
                                <button class="view-btn" type="button" onclick="viewReport(<?php echo $report['report_id']; ?>)">Review</button>
                            </div>
                            <?php endforeach; ?>

                            <?php if (!$hasUnderway): ?>
                            <p class="no-reports">No reports currently underway.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 3. Settled -->
                    <button class="collapsible">
                        <span>Settled</span>
                        <span class="case-count"><?php echo $settledCases; ?></span>
                    </button>
                    <div class="content">
                        <div class="inner-content report-list">
                            <?php
                            $hasSettled = false;
                            foreach ($reports as $report):
                                if ($report['status'] !== 'Resolved') {
                                    continue;
                                }
                                $hasSettled = true;
                            ?>
                            <div class="report-card">
                                <div class="report-info">
                                    <h4>Case #<?php echo htmlspecialchars($report['report_id']); ?>: <?php echo htmlspecialchars($report['issue_type']); ?></h4>
                                    <p>Location: <?php echo htmlspecialchars($report['location']); ?></p>
                                </div>
                                <div class="report-meta">
                                    <span class="badge <?php echo strtolower($report['ai_priority']); ?>"><?php echo htmlspecialchars($report['ai_priority']); ?></span>
                                    <span class="date"><?php echo date("M d, Y", strtotime($report['created_at'])); ?></span>
                                </div>
                                <button class="view-btn" type="button" onclick="viewReport(<?php echo $report['report_id']; ?>)">Review</button>
                            </div>
                            <?php endforeach; ?>

                            <?php if (!$hasSettled): ?>
                            <p class="no-reports">No resolved reports.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

<script>
var coll = document.getElementsByClassName("collapsible");
var i;

for (i = 0; i < coll.length; i++) {
    coll[i].addEventListener("click", function() {
        this.classList.toggle("active");
        var content = this.nextElementSibling;

        if (content.style.maxHeight) {
            content.style.overflow = "hidden";
            content.style.maxHeight = null;
        } else {
            content.style.maxHeight = content.scrollHeight + "px";

            setTimeout(() => {
                if (content.style.maxHeight) {
                    content.style.overflow = "visible";
                }
            }, 200);
        }
    });
}

const dropdowns = document.querySelectorAll(".dropdown");

dropdowns.forEach(dropdown => {
    const select = dropdown.querySelector(".select");
    const caret = dropdown.querySelector(".caret");
    const menu = dropdown.querySelector(".menu");
    const options = dropdown.querySelectorAll(".menu li");
    const selected = dropdown.querySelector(".selected");

    select.addEventListener("click", (e) => {
        e.stopPropagation();
        toggleMenu();
    });

    options.forEach(option => {
        option.addEventListener("click", () => {
            selected.innerText = option.innerText;

            options.forEach(opt => opt.classList.remove("active"));

            option.classList.add("active");
            closeMenu();
        });
    });

    const toggleMenu = () => {
        const isOpen = menu.classList.contains("menu-open");

        if (isOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    };

    const openMenu = () => {
        select.classList.add("select-clicked");
        caret.classList.add("caret-rotate");
        menu.classList.add("menu-open");

        document.addEventListener("click", handleClickOutside);
    };

    const closeMenu = () => {
        select.classList.remove("select-clicked");
        caret.classList.remove("caret-rotate");
        menu.classList.remove("menu-open");

        document.removeEventListener("click", handleClickOutside);
    };

    const handleClickOutside = (e) => {
        if (!dropdown.contains(e.target)) {
            closeMenu();
        }
    };
});

function updateDateLabel(input) {
    const label = document.getElementById("date-label");

    if (input.value) {
        const [year, month, day] = input.value.split('-');
        const formattedDate = `${day}/${month}/${year}`;
        label.innerText = formattedDate;
    } else {
        label.innerText = "Choose Date";
    }
}

function viewReport(reportId) {
    window.location.href = "../report/ReportPage.php?id=" + reportId;
}
</script>
</body>
</html>