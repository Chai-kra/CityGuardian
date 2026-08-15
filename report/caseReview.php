<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/LogIn.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/userpage.php");
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

$sql = "SELECT * FROM reports ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Review</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=search">
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
                <a href="statistics.php" class="nav-link">Statistics</a>
            </li>

            <li class="nav-item">
                <a href="../user/logout.php" class="nav-link">Log Out</a>
            </li>
        </ul>
    </nav>
</header>

<main>

    <div class="summary">
        <h2 class="title">Case Review</h2>

        <div class="card-container">

            <div class="card">
                <div class="card-content">
                    <h3>Total</h3>
                    <p><?php echo $total; ?> cases</p>
                    <a href="#allCases" class="read-more">Read more</a>
                </div>
            </div>

            <div class="card">
                <div class="card-content">
                    <h3>Action Needed</h3>
                    <p><?php echo $actionNeeded; ?> cases</p>
                    <a href="#actionNeeded" class="read-more">Read more</a>
                </div>
            </div>

            <div class="card">
                <div class="card-content">
                    <h3>Underway</h3>
                    <p><?php echo $underway; ?> cases</p>
                    <a href="#underway" class="read-more">Read more</a>
                </div>
            </div>

            <div class="card">
                <div class="card-content">
                    <h3>Settled</h3>
                    <p><?php echo $settled; ?> cases</p>
                    <a href="#settled" class="read-more">Read more</a>
                </div>
            </div>

        </div>
    </div>

    <div class="filter-container">

        <form method="GET" action="caseReview.php">
            <div class="search">
                <span class="search-icon material-symbols-outlined">search</span>
                <input
                    class="search-input"
                    type="search"
                    name="search"
                    placeholder="Search case..."
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                >
            </div>
        </form>

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

        <div class="dropdown">
            <div class="select">
                <span class="selected">Status</span>
                <div class="caret"></div>
            </div>

            <ul class="menu">
                <li class="active">Status</li>
                <li>Action Needed</li>
                <li>Underway</li>
                <li>Settled</li>
            </ul>
        </div>

    </div>

    <div class="caseReviewBox">

        <div class="sub-category">

            <button class="collapsible" id="actionNeeded">
                Action Needed
            </button>

            <div class="content">
                <div class="inner-content">

                    <?php
                    if ($result && $result->num_rows > 0) {
                        $result->data_seek(0);
                        $found = false;

                        while ($row = $result->fetch_assoc()) {
                            if (strcasecmp($row['status'], 'Action Needed') == 0) {
                                $found = true;
                                ?>

                                <div class="report-card">
                                    <h3>Report #<?php echo $row['id']; ?></h3>

                                    <p>
                                        <strong>Issue:</strong>
                                        <?php echo htmlspecialchars($row['issue_type']); ?>
                                    </p>

                                    <p>
                                        <strong>Location:</strong>
                                        <?php echo htmlspecialchars($row['location']); ?>
                                    </p>

                                    <p>
                                        <strong>Description:</strong>
                                        <?php echo htmlspecialchars($row['description']); ?>
                                    </p>

                                    <p>
                                        <strong>Priority:</strong>
                                        <?php echo htmlspecialchars($row['priority']); ?>
                                    </p>
                                </div>

                                <?php
                            }
                        }

                        if (!$found) {
                            echo "<p>No Action Needed cases.</p>";
                        }
                    }
                    ?>

                </div>
            </div>

            <button class="collapsible" id="underway">
                Underway
            </button>

            <div class="content">
                <div class="inner-content">

                    <?php
                    if ($result && $result->num_rows > 0) {
                        $result->data_seek(0);
                        $found = false;

                        while ($row = $result->fetch_assoc()) {
                            if (strcasecmp($row['status'], 'Underway') == 0) {
                                $found = true;
                                ?>

                                <div class="report-card">
                                    <h3>Report #<?php echo $row['id']; ?></h3>

                                    <p>
                                        <strong>Issue:</strong>
                                        <?php echo htmlspecialchars($row['issue_type']); ?>
                                    </p>

                                    <p>
                                        <strong>Location:</strong>
                                        <?php echo htmlspecialchars($row['location']); ?>
                                    </p>

                                    <p>
                                        <strong>Description:</strong>
                                        <?php echo htmlspecialchars($row['description']); ?>
                                    </p>

                                    <p>
                                        <strong>Priority:</strong>
                                        <?php echo htmlspecialchars($row['priority']); ?>
                                    </p>
                                </div>

                                <?php
                            }
                        }

                        if (!$found) {
                            echo "<p>No Underway cases.</p>";
                        }
                    }
                    ?>

                </div>
            </div>

            <button class="collapsible" id="settled">
                Settled
            </button>

            <div class="content">
                <div class="inner-content">

                    <?php
                    if ($result && $result->num_rows > 0) {
                        $result->data_seek(0);
                        $found = false;

                        while ($row = $result->fetch_assoc()) {
                            if (strcasecmp($row['status'], 'Settled') == 0) {
                                $found = true;
                                ?>

                                <div class="report-card">
                                    <h3>Report #<?php echo $row['id']; ?></h3>

                                    <p>
                                        <strong>Issue:</strong>
                                        <?php echo htmlspecialchars($row['issue_type']); ?>
                                    </p>

                                    <p>
                                        <strong>Location:</strong>
                                        <?php echo htmlspecialchars($row['location']); ?>
                                    </p>

                                    <p>
                                        <strong>Description:</strong>
                                        <?php echo htmlspecialchars($row['description']); ?>
                                    </p>

                                    <p>
                                        <strong>Priority:</strong>
                                        <?php echo htmlspecialchars($row['priority']); ?>
                                    </p>
                                </div>

                                <?php
                            }
                        }

                        if (!$found) {
                            echo "<p>No Settled cases.</p>";
                        }
                    }
                    ?>

                </div>
            </div>

        </div>
    </div>

</main>

<script>
var coll = document.getElementsByClassName("collapsible");

for (var i = 0; i < coll.length; i++) {
    coll[i].addEventListener("click", function() {
        this.classList.toggle("active");

        var content = this.nextElementSibling;

        if (content.style.maxHeight) {
            content.style.maxHeight = null;
        } else {
            content.style.maxHeight = content.scrollHeight + "px";
        }
    });
}
</script>

</body>
</html>