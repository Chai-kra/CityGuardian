<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../user/LogIn.php");
    exit();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function displayStatus($status) {
    if ($status === 'Resolved') {
        return 'Settled';
    }

    if ($status === 'Assigned' || $status === 'In Progress') {
        return 'Underway';
    }

    return 'Action Needed';
}

if (
    !isset($_GET['id']) ||
    !ctype_digit((string)$_GET['id'])
) {
    http_response_code(400);
    exit('Invalid report ID.');
}

$reportId = (int)$_GET['id'];
$userId = (int)$_SESSION['id'];

$isAdmin =
    ($_SESSION['role'] ?? '') === 'admin';

$adminDepartment = trim(
    (string)($_SESSION['department'] ?? '')
);

if (empty($_SESSION['report_csrf_token'])) {
    $_SESSION['report_csrf_token'] =
        bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) {
        http_response_code(403);
        exit('Only an administrator can update the status.');
    }

    $submittedToken =
        (string)($_POST['csrf_token'] ?? '');

    if (
        !hash_equals(
            $_SESSION['report_csrf_token'],
            $submittedToken
        )
    ) {
        http_response_code(403);
        exit('Invalid form token.');
    }

    $newStatus =
        (string)($_POST['status'] ?? '');

    $allowedStatuses = [
        'Pending',
        'In Progress',
        'Resolved'
    ];

    if (!in_array($newStatus, $allowedStatuses, true)) {
        http_response_code(400);
        exit('Invalid status selected.');
    }

    $updateSql = "UPDATE reports
                  SET status = ?
                  WHERE report_id = ?
                  AND ai_department = ?";

    $updateStmt = $conn->prepare($updateSql);

    if (!$updateStmt) {
        error_log('Status update error: ' . $conn->error);
        exit('Unable to update report.');
    }

    $updateStmt->bind_param(
        'sis',
        $newStatus,
        $reportId,
        $adminDepartment
    );

    if (!$updateStmt->execute()) {
        error_log(
            'Status update error: ' .
            $updateStmt->error
        );

        $updateStmt->close();
        exit('Unable to update report.');
    }

    $updateStmt->close();

    header(
        'Location: ReportPage.php?id=' .
        $reportId .
        '&updated=1'
    );

    exit();
}

if ($isAdmin) {
    $sql = "SELECT *
            FROM reports
            WHERE report_id = ?
            AND ai_department = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        'is',
        $reportId,
        $adminDepartment
    );
} else {
    $sql = "SELECT *
            FROM reports
            WHERE report_id = ?
            AND user_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        'ii',
        $reportId,
        $userId
    );
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    http_response_code(404);

    exit(
        'Report not found or you do not have permission.'
    );
}

$report = $result->fetch_assoc();
$stmt->close();

$databaseStatus =
    (string)($report['status'] ?? 'Pending');

$formStatus =
    $databaseStatus === 'Assigned'
        ? 'In Progress'
        : $databaseStatus;

$backPage =
    $isAdmin
        ? '../user/caseReview.php'
        : '../user/userpage.php';

$imageName = basename(
    (string)($report['image'] ?? '')
);

$priority = strtolower(
    (string)($report['ai_priority'] ?: 'medium')
);

$allowedPriorityClasses = [
    'critical',
    'high',
    'medium',
    'low'
];

$priorityClass = in_array(
    $priority,
    $allowedPriorityClasses,
    true
)
    ? $priority
    : 'medium';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Case #<?php echo e($reportId); ?> - AI City Guardian
    </title>

    <link rel="stylesheet" href="../css/style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
          rel="stylesheet">

    <style>
        body.report-details-page {
            display: block;
            min-height: 100vh;
            margin: 0;
            padding: 38px 16px;
            color: #fff;
            background: #111844;
        }

        .report-details-container {
            width: min(920px, 100%);
            margin: 0 auto;
            padding: 30px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            background: #151e51;
            box-shadow: 0 18px 55px rgba(0, 0, 0, 0.35);
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
        }

        .details-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 26px;
        }

        .details-topbar h1 {
            margin: 0;
            font-size: 28px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 30px;
            color: #fff;
            text-decoration: none;
        }

        .back-btn:hover {
            border-color: #fff;
            background: rgba(255, 255, 255, 0.08);
        }

        .success-message {
            margin-bottom: 22px;
            padding: 13px 16px;
            border: 1px solid rgba(16, 185, 129, 0.55);
            border-radius: 10px;
            color: #a7f3d0;
            background: rgba(16, 185, 129, 0.12);
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .detail {
            min-width: 0;
            padding: 17px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.035);
        }

        .detail.full-width {
            grid-column: 1 / -1;
        }

        .detail strong {
            display: block;
            margin-bottom: 7px;
            color: rgba(255, 255, 255, 0.55);
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .detail p {
            margin: 0;
            color: #fff;
            line-height: 1.65;
            overflow-wrap: anywhere;
        }

        .report-image {
            display: block;
            max-width: 100%;
            max-height: 480px;
            margin-top: 10px;
            border-radius: 10px;
            object-fit: contain;
        }

        .priority-badge,
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
        }

        .priority-badge.critical {
            color: #fecaca;
            background: rgba(239, 68, 68, 0.18);
        }

        .priority-badge.high {
            color: #fed7aa;
            background: rgba(249, 115, 22, 0.18);
        }

        .priority-badge.medium {
            color: #fde68a;
            background: rgba(245, 158, 11, 0.18);
        }

        .priority-badge.low {
            color: #a7f3d0;
            background: rgba(16, 185, 129, 0.18);
        }

        .status-badge {
            color: #bfdbfe;
            background: rgba(37, 99, 235, 0.2);
        }

        .status-update-box {
            margin-top: 24px;
            padding: 22px;
            border: 1px solid rgba(37, 99, 235, 0.55);
            border-radius: 12px;
            background: rgba(37, 99, 235, 0.08);
        }

        .status-update-box h2 {
            margin: 0 0 6px;
            font-size: 20px;
        }

        .status-update-box p {
            margin: 0 0 15px;
            color: rgba(255, 255, 255, 0.65);
        }

        .status-form {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .status-select {
            min-width: 210px;
            height: 46px;
            padding: 0 14px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 9px;
            color: #fff;
            background: #111844;
        }

        .update-status-btn {
            height: 46px;
            padding: 0 18px;
            border: 0;
            border-radius: 9px;
            color: #fff;
            background: #2563eb;
            font-weight: 700;
            cursor: pointer;
        }

        .update-status-btn:hover {
            background: #1d4ed8;
        }

        @media (max-width: 650px) {
            body.report-details-page {
                padding: 16px 10px;
            }

            .report-details-container {
                padding: 20px 15px;
            }

            .details-topbar {
                align-items: flex-start;
                flex-direction: column-reverse;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .detail.full-width {
                grid-column: auto;
            }

            .status-select,
            .update-status-btn {
                width: 100%;
            }
        }
    </style>
</head>

<body class="report-details-page">

<div class="report-details-container">

    <div class="details-topbar">

        <h1>Report Details</h1>

        <a href="<?php echo e($backPage); ?>"
           class="back-btn">

            <i class="bx bx-left-arrow-alt"></i>
            Back

        </a>

    </div>

    <?php if (isset($_GET['updated'])): ?>

        <p class="success-message">
            Case status updated successfully.
        </p>

    <?php endif; ?>

    <div class="details-grid">

        <div class="detail">
            <strong>Case ID</strong>
            <p>#<?php echo e($report['report_id']); ?></p>
        </div>

        <div class="detail">
            <strong>Status</strong>

            <p>
                <span class="status-badge">
                    <?php echo e(displayStatus($databaseStatus)); ?>
                </span>
            </p>
        </div>

        <div class="detail">
            <strong>Issue Type</strong>
            <p>
                <?php echo e($report['issue_type'] ?: 'Not available'); ?>
            </p>
        </div>

        <div class="detail">
            <strong>Location</strong>
            <p>
                <?php echo e($report['location'] ?: 'Not available'); ?>
            </p>
        </div>

        <div class="detail full-width">
            <strong>User Description</strong>
            <p>
                <?php
                echo nl2br(
                    e($report['description'] ?: 'Not available')
                );
                ?>
            </p>
        </div>

        <div class="detail full-width">
            <strong>AI Description</strong>
            <p>
                <?php
                echo nl2br(
                    e($report['ai_description'] ?: 'Not available')
                );
                ?>
            </p>
        </div>

        <div class="detail">
            <strong>AI Priority</strong>

            <p>
                <span class="priority-badge <?php echo e($priorityClass); ?>">
                    <?php echo e($report['ai_priority'] ?: 'Not available'); ?>
                </span>
            </p>
        </div>

        <div class="detail">
            <strong>AI Department</strong>
            <p>
                <?php echo e($report['ai_department'] ?: 'Not assigned'); ?>
            </p>
        </div>

        <div class="detail">
            <strong>AI Confidence</strong>

            <p>
                <?php
                echo $report['ai_confidence'] !== null
                    ? e($report['ai_confidence']) . '%'
                    : 'Not available';
                ?>
            </p>
        </div>

        <div class="detail">
            <strong>Submitted On</strong>

            <p>
                <?php
                echo !empty($report['created_at'])
                    ? e(
                        date(
                            'M d, Y h:i A',
                            strtotime($report['created_at'])
                        )
                    )
                    : 'Not available';
                ?>
            </p>
        </div>

        <?php if ($imageName !== ''): ?>

            <div class="detail full-width">

                <strong>Image</strong>

                <img class="report-image"
                     src="uploads/<?php echo rawurlencode($imageName); ?>"
                     alt="Image for case #<?php echo e($reportId); ?>">

            </div>

        <?php endif; ?>

    </div>

    <?php if ($isAdmin): ?>

        <div class="status-update-box">

            <h2>Update Case Status</h2>

            <p>
                Move the case between Action Needed,
                Underway and Settled.
            </p>

            <form class="status-form"
                  method="post"
                  action="ReportPage.php?id=<?php echo $reportId; ?>">

                <input type="hidden"
                       name="csrf_token"
                       value="<?php echo e($_SESSION['report_csrf_token']); ?>">

                <label for="status" class="sr-only">
                    New case status
                </label>

                <select class="status-select"
                        id="status"
                        name="status"
                        required>

                    <option value="Pending"
                        <?php echo $formStatus === 'Pending' ? 'selected' : ''; ?>>

                        Action Needed

                    </option>

                    <option value="In Progress"
                        <?php echo $formStatus === 'In Progress' ? 'selected' : ''; ?>>

                        Underway

                    </option>

                    <option value="Resolved"
                        <?php echo $formStatus === 'Resolved' ? 'selected' : ''; ?>>

                        Settled

                    </option>

                </select>

                <button class="update-status-btn"
                        type="submit">

                    Update Status

                </button>

            </form>

        </div>

    <?php endif; ?>

</div>

</body>
</html>