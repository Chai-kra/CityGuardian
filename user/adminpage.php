<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: LogIn.php");
    exit();
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: userpage.php");
    exit();
}

// Use caseReview.php as the only admin dashboard.
header("Location: caseReview.php");
exit();