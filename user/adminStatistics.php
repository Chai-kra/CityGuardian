<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Review</title>
    <link rel="stylesheet" href="/css/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <header>
        <nav class="navbar">
            <a href="#" class="nav-logo">
                <h2 class="logo-text">AI City Guardian</h2>
            </a>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="caseReview.php" class="nav-link">Case Review</a>
                </li>
                <li class="nav-item">
                    <a href="adminStatistics.html" class="nav-link">Statistics</a>
                </li>
                <li class="nav-item admin-menu-item">
                    <a href="#" class="nav-link">Admin</a>
                    <div class="logout-dropdown">
                        <a href="login.php" class="logout-btn">
                            <i class='bx bx-log-out'></i> Logout
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <h2 class="category">Statistics</h2>
        <figure class="charts">
            <div class="pie"></div>
            <div class="pie"></div>
            <div class="pie"></div> 
            <div class="pie"></div>
            <div class="pie"></div>
            <div class="pie"></div>

            <figcaption class="legends">
                <span>Action needed</span>
                <span>Underway</span>
                <span>Settled</span>
            </figcaption>
        </figure>

        <div role="figure" aria-labelledby="caption">
            <img src="" alt="">
            <p id="caption">The caption</p>
        </div>
    </main>
</body>

</html>