<?php
include "../db.php";


session_start();

// Make sure user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: ../user/LogIn.php");
    exit();
}

// Get current user's information
$user_id = $_SESSION['id'];

$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "User not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>User Page</title>

    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

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

        .report-container {
            margin: 10px;
            padding: 10px;
            background-color: #a7c6ff;
        }
    </style>
</head>

<body>

<div class="container">

    <header class="navbar">

        <div class="brand">
            <i class='bx bxs-user'></i>
            User Page
        </div>

        <div class="nav-links">
            <button class="active" id="btn-home" onclick="switchPage('home')">
                Home
            </button>

            <button id="btn-profile" onclick="switchPage('profile')">
                Profile
            </button>

            <button id="btn-settings" onclick="switchPage('settings')">
                Settings
            </button>

            <button id="btn-logout" onclick="switchPage('logout')">
                Logout
            </button>
        </div>

    </header>

    <main class="main-container">

        <div class="report-container">
            <h2>Welcome, <?php echo htmlspecialchars($user['email']); ?></h2>
        </div>

        <div class="report-container">
            <h2>Report 1</h2>
        </div>

        <div class="report-container">
            <h2>Report 2</h2>
        </div>

        <div class="report-container">
            <h2>Report 3</h2>
        </div>

    </main>

</div>


<script>

function switchPage(page) {

    const mainContainer = document.querySelector('.main-container');

    const btnHome = document.getElementById('btn-home');
    const btnProfile = document.getElementById('btn-profile');
    const btnSettings = document.getElementById('btn-settings');
    const btnLogout = document.getElementById('btn-logout');

    btnHome.classList.remove('active');
    btnProfile.classList.remove('active');
    btnSettings.classList.remove('active');
    btnLogout.classList.remove('active');

    if (page === 'home') {

        btnHome.classList.add('active');

        mainContainer.innerHTML = `
            <div class="report-container">
                <h2>Report 1</h2>
            </div>

            <div class="report-container">
                <h2>Report 2</h2>
            </div>

            <div class="report-container">
                <h2>Report 3</h2>
            </div>
        `;

    } else if (page === 'profile') {

        btnProfile.classList.add('active');

        mainContainer.innerHTML = `
            <div class="report-container">
                <h2>Profile Information</h2>
                <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        `;

    } else if (page === 'settings') {

        btnSettings.classList.add('active');

        mainContainer.innerHTML = `
            <div class="report-container">
                <h2>Settings</h2>
            </div>
        `;

    } else if (page === 'logout') {

        window.location.href = "../user/logout.php";

    }
}

</script>

</body>
</html>