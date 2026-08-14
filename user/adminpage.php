<?php
session_start();
include "db.php";
//this is a comment
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: LogIn.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: UserPage.php");
    exit();
}

$totalQuery = "SELECT COUNT(*) AS total FROM reports";
$totalResult = $conn->query($totalQuery);
$total = $totalResult->fetch_assoc()['total'];

$actionQuery = "SELECT COUNT(*) AS total FROM reports WHERE status = 'Action Needed'";
$actionResult = $conn->query($actionQuery);
$actionNeeded = $actionResult->fetch_assoc()['total'];

$underwayQuery = "SELECT COUNT(*) AS total FROM reports WHERE status = 'Underway'";
$underwayResult = $conn->query($underwayQuery);
$underway = $underwayResult->fetch_assoc()['total'];

$settledQuery = "SELECT COUNT(*) AS total FROM reports WHERE status = 'Settled'";
$settledResult = $conn->query($settledQuery);
$settled = $settledResult->fetch_assoc()['total'];

$actionPercent = $total > 0 ? ($actionNeeded / $total) * 100 : 0;
$underwayPercent = $total > 0 ? ($underway / $total) * 100 : 0;
$settledPercent = $total > 0 ? ($settled / $total) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #e0e0e0;
        }

        .container {
            min-height: 100vh;
        }

        .navbar {
            background-color: #1a237e;
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            font-size: 16px;
            font-weight: bold;
        }

        .nav-links button {
            background: none;
            border: none;
            color: #b0bec5;
            font-size: 14px;
            margin-left: 20px;
            cursor: pointer;
            padding: 5px 0;
        }

        .nav-links button.active {
            color: white;
            border-bottom: 2px solid white;
        }

        .main-container {
            padding: 20px;
        }

        .map-container {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        iframe {
            width: 80%;
            height: 500px;
            border: none;
            border-radius: 8px;
        }

        .report-container {
            margin: 10px;
            padding: 10px;
            background-color: #a7c6ff;
        }

        .chart-title {
            margin-top: 40px;
            margin-bottom: 20px;
            margin-left: 20px;
            font-size: 20px;
            font-weight: 600;
        }

        .chart-container {
            display: flex;
            align-items: center;
            gap: 40px;
            margin-left: 20px;
        }

        .chart {
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: conic-gradient(
                #71ff71 0% <?php echo $settledPercent; ?>%,
                #7b7bff <?php echo $settledPercent; ?>% <?php echo $settledPercent + $underwayPercent; ?>%,
                #ff8080 <?php echo $settledPercent + $underwayPercent; ?>% 100%
            );
        }

        .legend {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .legend-box {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        .settled {
            background-color: #71ff71;
        }

        .underway {
            background-color: #7b7bff;
        }

        .action {
            background-color: #ff8080;
        }
    </style>

</head>

<body>

<div class="container">

    <header class="navbar">

        <div class="brand">
            AI City Guardian
        </div>

        <nav class="nav-links">

            <button id="btn-review"
                    class="active"
                    onclick="switchPage('case-review')">
                Case Review
            </button>

            <button id="btn-stats"
                    onclick="switchPage('statistics')">
                Statistics
            </button>

            <button onclick="window.location.href='logout.php'">
                Log Out
            </button>

        </nav>

    </header>


    <main class="main-container">

        <div class="map-container">

            <h1>Location Map</h1>

            <iframe
                src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d7967.530022559729!2d101.71401304999999!3d3.156548150000005!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1szh-CN!2smy!4v1783431995204!5m2!1szh-CN!2smy"
                width="600"
                height="450"
                allowfullscreen
                loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin">
            </iframe>

        </div>

        <div class="report-container">
            <h2>Total Reports: <?php echo $total; ?></h2>
        </div>

        <div class="report-container">
            <h2>Action Needed: <?php echo $actionNeeded; ?></h2>
        </div>

        <div class="report-container">
            <h2>Underway: <?php echo $underway; ?></h2>
        </div>

        <div class="report-container">
            <h2>Settled: <?php echo $settled; ?></h2>
        </div>

    </main>

</div>


<script>

function switchPage(page) {

    const mainContainer = document.querySelector('.main-container');

    const btnReview = document.getElementById('btn-review');

    const btnStats = document.getElementById('btn-stats');

    if (page === 'case-review') {

        btnReview.classList.add('active');
        btnStats.classList.remove('active');

        mainContainer.innerHTML = `
            <div class="map-container">

                <h1>Location Map</h1>

                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d7967.530022559729!2d101.71401304999999!3d3.156548150000005!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1szh-CN!2smy!4v1783431995204!5m2!1szh-CN!2smy"
                    width="600"
                    height="450"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="strict-origin-when-cross-origin">
                </iframe>

            </div>

            <div class="report-container">
                <h2>Total Reports: <?php echo $total; ?></h2>
            </div>

            <div class="report-container">
                <h2>Action Needed: <?php echo $actionNeeded; ?></h2>
            </div>

            <div class="report-container">
                <h2>Underway: <?php echo $underway; ?></h2>
            </div>

            <div class="report-container">
                <h2>Settled: <?php echo $settled; ?></h2>
            </div>
        `;

    } else if (page === 'statistics') {

        btnStats.classList.add('active');
        btnReview.classList.remove('active');

        mainContainer.innerHTML = `
            <p class="chart-title">Statistics Pie Chart</p>

            <div class="chart-container">

                <div class="chart"></div>

                <div class="legend">

                    <div class="legend-item">
                        <div class="legend-box settled"></div>
                        <span>Settled: <?php echo $settled; ?></span>
                    </div>

                    <div class="legend-item">
                        <div class="legend-box underway"></div>
                        <span>Underway: <?php echo $underway; ?></span>
                    </div>

                    <div class="legend-item">
                        <div class="legend-box action"></div>
                        <span>Action Needed: <?php echo $actionNeeded; ?></span>
                    </div>

                </div>

            </div>
        `;
    }
}

</script>

</body>
</html>