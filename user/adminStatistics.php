<?php
session_start();
include "../db.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/LogIn.php");
    exit();
}

// dummy data
$departments = [
    ["name" => "DBKL Engineering", "total" => 120, "settled" => 70, "underway" => 30, "action" => 20],
    ["name" => "Health & Environment", "total" => 95, "settled" => 55, "underway" => 22, "action" => 18],
    ["name" => "Landscape & Recreation", "total" => 88, "settled" => 60, "underway" => 20, "action" => 8],
    ["name" => "JKR", "total" => 102, "settled" => 65, "underway" => 15, "action" => 22],
    ["name" => "JPS", "total" => 76, "settled" => 48, "underway" => 20, "action" => 8],
    ["name" => "Solid Waste Management", "total" => 90, "settled" => 38, "underway" => 30, "action" => 22],
];

// Calculate overall summary stats from all departments
$totalCases = array_sum(array_column($departments, 'total'));
$settledCases = array_sum(array_column($departments, 'settled'));
$underwayCases = array_sum(array_column($departments, 'underway'));
$actionNeededCases = array_sum(array_column($departments, 'action'));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics Overview - AI City Guardian</title>
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <!-- THIS IS THE CORRECTED LINE -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="#" class="nav-logo">
                <h2 class="logo-text">AI City Guardian</h2>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="caseReview.php" class="sidebar-link">
                    <i class='bx bxs-dashboard'></i>
                    <span>Case Review</span>
                </a>
            </li>
            <li class="sidebar-item active">
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


    <div class="main-content">
        <header class="main-header">
            <div class="header-title">
                <h1>Statistics Overview</h1>
                <p>Analyze city maintenance reports and performance metrics</p>
            </div>
            
            <div class="header-actions">
                <!-- From Date -->
                <div class="date-picker-container">
                    <div class="select date-select" style="min-width: 140px;">
                        <span class="selected" id="from-date-label">From</span>
                        <div class="caret"></div>
                        <input type="date" class="native-date-input" onchange="updateDateLabel(this, 'from-date-label', 'From')">
                    </div>
                </div>

                <!-- To Date -->
                <div class="date-picker-container">
                    <div class="select date-select" style="min-width: 140px;">
                        <span class="selected" id="to-date-label">To</span>
                        <div class="caret"></div>
                        <input type="date" class="native-date-input" onchange="updateDateLabel(this, 'to-date-label', 'To')">
                    </div>
                </div>

                <!-- Refresh Button -->
                <button class="refresh-btn" onclick="location.reload();">
                    <span class="material-symbols-outlined">refresh</span>
                </button>
            </div>
        </header>

        <main>
            <!-- TOP 4 SUMMARY CARDS -->
            <div class="card-container" style="margin-bottom: 30px;">
                <div class="card">
                    <div class="card-content">
                        <h3>Total Cases</h3>
                        <p><?php echo $totalCases; ?> cases</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3>Settled</h3>
                        <p><?php echo $settledCases; ?> cases</p>
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
                        <h3>Action Needed</h3>
                        <p><?php echo $actionNeededCases; ?> cases</p>
                    </div>
                </div>
            </div>

            <!-- DEPARTMENT GRID CARDS -->
            <div class="department-grid">
                <?php foreach ($departments as $dept): 
                    // Calculate percentages for the CSS Donut Chart
                    $total = $dept['total'];
                    $settledPct = ($total > 0) ? round(($dept['settled'] / $total) * 100) : 0;
                    $underwayPct = ($total > 0) ? round(($dept['underway'] / $total) * 100) : 0;
                    $actionPct = 100 - $settledPct - $underwayPct;

                    // Calculate degree breakpoints for conic-gradient
                    $stop1 = $settledPct;
                    $stop2 = $settledPct + $underwayPct;
                    
                    $gradient = "conic-gradient(#10b981 0% {$stop1}%, #fbbf24 {$stop1}% {$stop2}%, #ef4444 {$stop2}% 100%)";
                ?>
                <div class="dept-card">
                    <h3><?php echo htmlspecialchars($dept['name']); ?></h3>
                    
                    <div class="dept-card-body">
                        <!-- Donut Chart -->
                        <div class="donut-chart" style="background: <?php echo $gradient; ?>;">
                            <div class="donut-inner">
                                <span class="donut-number"><?php echo $dept['total']; ?></span>
                            </div>
                        </div>

                        <!-- Legend -->
                        <ul class="dept-legend">
                            <li class="legend-item">
                                <div class="legend-label"><span class="legend-dot settled"></span>Settled</div>
                                <span class="legend-value"><?php echo $dept['settled']; ?></span>
                            </li>
                            <li class="legend-item">
                                <div class="legend-label"><span class="legend-dot underway"></span>Underway</div>
                                <span class="legend-value"><?php echo $dept['underway']; ?></span>
                            </li>
                            <li class="legend-item">
                                <div class="legend-label"><span class="legend-dot action-needed"></span>Action Needed</div>
                                <span class="legend-value"><?php echo $dept['action']; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>

        <footer class="main-footer">
            <p>&copy; <?php echo date("Y"); ?> AI City Guardian. All rights reserved.</p>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Help & Support</a>
            </div>
        </footer>
    </div>

    <!-- Script to handle dynamic Date Picker labels -->
    <script>
        function updateDateLabel(input, labelId, defaultText) {
            const label = document.getElementById(labelId);
            if (input.value) {
                const [year, month, day] = input.value.split('-');
                label.innerText = `${day}/${month}/${year}`;
            } else {
                label.innerText = defaultText;
            }
        }
    </script>

</body>

</html>