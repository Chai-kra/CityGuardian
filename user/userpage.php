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

    <style>
        body {
            margin: 0;
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

        .nav-links button.active,
        .nav-links button:hover {
            color: white;
        }

        .nav-links button.active {
            border-bottom: 2px solid white;
        }

        .main-container {
            padding: 20px;
        }

        .report-container {
            margin: 10px;
            padding: 20px;
            background-color: #a7c6ff;
            border-radius: 8px;
        }

        .report-container img {
            max-width: 300px;
            border-radius: 5px;
        }

        .submit-button {
            display: inline-block;
            margin: 10px;
            padding: 10px 18px;
            background-color: #1a237e;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>

<body>

<div class="container">

    <header class="navbar">
        <div class="brand">
            <i class="bx bxs-user"></i>
            User Page
        </div>

        <div class="nav-links">
            <button class="active" id="btn-report" onclick="switchPage('report')">Reports</button>
            <button id="btn-profile" onclick="switchPage('profile')">Profile</button>
            <button id="btn-settings" onclick="switchPage('settings')">Settings</button>
            <button id="btn-logout" onclick="switchPage('logout')">Logout</button>
        </div>
    </header>

    <main class="main-container">

        <?php if ($reports_result->num_rows > 0): ?>

            <?php $report_number = 1; ?>

            <?php while ($report = $reports_result->fetch_assoc()): ?>

                <div class="report-container">

                    <h2>Report <?php echo $report_number; ?></h2>

                    <p>
                        <strong>Issue Type:</strong>
                        <?php echo htmlspecialchars($report['issue_type'] ?? 'Not provided'); ?>
                    </p>

                    <p>
                        <strong>Location:</strong>
                        <?php echo htmlspecialchars($report['location'] ?? 'Not provided'); ?>
                    </p>

                    <p>
                        <strong>Description:</strong>
                        <?php echo htmlspecialchars($report['description'] ?? 'Not provided'); ?>
                    </p>

                    <?php if (!empty($report['image'])): ?>
                        <p><strong>Report Image:</strong></p>
                        <img src="../uploads/<?php echo htmlspecialchars($report['image']); ?>">
                    <?php endif; ?>

                    <p>
                        <strong>AI Priority:</strong>
                        <?php echo htmlspecialchars($report['ai_priority'] ?? 'Not analysed'); ?>
                    </p>

                    <p>
                        <strong>Status:</strong>
                        <?php echo htmlspecialchars($report['status'] ?? 'Pending'); ?>
                    </p>

                    <p>
                        <strong>Submitted:</strong>
                        <?php echo htmlspecialchars($report['created_at'] ?? ''); ?>
                    </p>

                </div>

                <?php $report_number++; ?>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="report-container">
                <h2>No Reports Yet</h2>
                <p>You have not uploaded any reports.</p>
            </div>

        <?php endif; ?>

        <a href="../report/ReportPage.php" class="submit-button">
            <i class="bx bx-plus"></i>
            Submit New Report
        </a>

    </main>

</div>

<script>
function switchPage(page) {

    const mainContainer = document.querySelector('.main-container');

    const btnReport = document.getElementById('btn-report');
    const btnProfile = document.getElementById('btn-profile');
    const btnSettings = document.getElementById('btn-settings');
    const btnLogout = document.getElementById('btn-logout');

    btnReport.classList.remove('active');
    btnProfile.classList.remove('active');
    btnSettings.classList.remove('active');
    btnLogout.classList.remove('active');

    if (page === 'report') {

        btnReport.classList.add('active');
        location.reload();

    } else if (page === 'profile') {

        btnProfile.classList.add('active');

        mainContainer.innerHTML = `
            <div class="report-container">
                <h2>Profile Information</h2>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        `;

    } else if (page === 'settings') {

        btnSettings.classList.add('active');

        mainContainer.innerHTML = `
            <div class="report-container">
                <h2>Settings</h2>
                <p>Account settings will appear here.</p>
            </div>
        `;

    } else if (page === 'logout') {

        window.location.href = "../user/logout.php";

    }
}
</script>

</body>
</html>