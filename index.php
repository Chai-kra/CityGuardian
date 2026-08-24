<?php
session_start();

if (isset($_SESSION['id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header("Location: user/caseReview.php");
    } else {
        header("Location: user/uploadpage.php");
    }
} else {
    header("Location: user/LogIn.php");
}

exit();
?>