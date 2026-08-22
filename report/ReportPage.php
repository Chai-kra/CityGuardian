<?php
session_start();
include "../db.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../user/LogIn.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid report ID.");
}

$reportId = intval($_GET['id']);

$sql = "SELECT * FROM reports WHERE report_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reportId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Report not found.");
}

$report = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report Details</title>
<link rel="stylesheet" href="/css/style.css">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f6fa;
}

.container {
    width: 90%;
    max-width: 900px;
    margin: 40px auto;
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

h1 {
    margin-bottom: 25px;
}

.detail {
    margin-bottom: 18px;
}

.detail strong {
    display: block;
    margin-bottom: 5px;
    color: #333;
}

.detail p {
    margin: 0;
    color: #555;
}

.report-image {
    max-width: 100%;
    max-height: 400px;
    border-radius: 10px;
    margin-top: 10px;
}

.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
}

.critical {
    background: #ffcccc;
    color: #b30000;
}

.high {
    background: #ffd6cc;
    color: #cc3300;
}

.medium {
    background: #fff0b3;
    color: #996600;
}

.low {
    background: #d9f2d9;
    color: #267326;
}

.back-btn {
    display: inline-block;
    margin-bottom: 20px;
    padding: 8px 16px;
    background: #5555ff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}

.back-btn:hover {
    background: #4444dd;
}
</style>
</head>

<body>

<div class="container">

<a href="../admin/caseReview.php" class="back-btn">← Back to Case Review</a>

<h1>Report Details</h1>

<div class="detail">
<strong>Case ID</strong>
<p>#<?php echo htmlspecialchars($report['report_id']); ?></p>
</div>

<div class="detail">
<strong>Issue Type</strong>
<p><?php echo htmlspecialchars($report['issue_type'] ?? 'Not available'); ?></p>
</div>

<div class="detail">
<strong>Location</strong>
<p><?php echo htmlspecialchars($report['location'] ?? 'Not available'); ?></p>
</div>

<div class="detail">
<strong>Description</strong>
<p><?php echo nl2br(htmlspecialchars($report['description'] ?? 'Not available')); ?></p>
</div>

<div class="detail">
<strong>AI Description</strong>
<p><?php echo nl2br(htmlspecialchars($report['ai_description'] ?? 'Not available')); ?></p>
</div>

<div class="detail">
<strong>AI Priority</strong>
<p>
<?php if (!empty($report['ai_priority'])): ?>
<span class="badge <?php echo strtolower(htmlspecialchars($report['ai_priority'])); ?>">
<?php echo htmlspecialchars($report['ai_priority']); ?>
</span>
<?php else: ?>
Not available
<?php endif; ?>
</p>
</div>

<div class="detail">
<strong>AI Department</strong>
<p><?php echo htmlspecialchars($report['ai_department'] ?? 'Not assigned'); ?></p>
</div>

<div class="detail">
<strong>AI Confidence</strong>
<p>
<?php
if ($report['ai_confidence'] !== null) {
    echo htmlspecialchars($report['ai_confidence']) . "%";
} else {
    echo "Not available";
}
?>
</p>
</div>

<div class="detail">
<strong>Status</strong>
<p><?php echo htmlspecialchars($report['status'] ?? 'Pending'); ?></p>
</div>

<div class="detail">
<strong>Submitted On</strong>
<p>
<?php
echo !empty($report['created_at'])
    ? date("M d, Y h:i A", strtotime($report['created_at']))
    : "Not available";
?>
</p>
</div>

<?php if (!empty($report['image'])): ?>
<div class="detail">
<strong>Image</strong>
<img
    class="report-image"
    src="../<?php echo htmlspecialchars($report['image']); ?>"
    alt="Report Image"
>
</div>
<?php endif; ?>

</div>

</body>
</html>