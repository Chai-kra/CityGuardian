<?php
session_start();

if (!isset($_SESSION['id'])) {
    header('Location: LogIn.php');
    exit();
}

if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: caseReview.php');
    exit();
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$userName = trim((string) ($_SESSION['name'] ?? ''));
if ($userName === '') {
    $emailName = explode('@', (string) ($_SESSION['email'] ?? 'User'))[0];
    $userName = ucwords(str_replace(['.', '_', '-'], ' ', $emailName));
}
$userName = $userName ?: 'User';
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Report - AI City Guardian</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <style>
        :root {
            --navy-950: #090f32;
            --navy-900: #101741;
            --navy-850: #141d4d;
            --navy-800: #1a2454;
            --blue: #3375f5;
            --blue-light: #6b9aff;
            --text: #f8faff;
            --muted: #9ca8ca;
            --line: rgba(151, 166, 210, .22);
            --line-strong: rgba(151, 166, 210, .38);
            --green: #34d399;
            --red: #fb7185;
            --amber: #fbbf24;
            --sidebar: 278px;
            --shadow: 0 20px 55px rgba(2, 6, 23, .24);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { color-scheme: dark; scroll-behavior: smooth; }
        body {
            min-width: 320px;
            min-height: 100vh;
            overflow-x: hidden;
            color: var(--text);
            background: radial-gradient(circle at 82% 0, rgba(51, 117, 245, .14), transparent 34rem), var(--navy-950);
            font-family: 'Poppins', Arial, sans-serif;
        }
        button, input, textarea { font: inherit; }
        button, a, label { -webkit-tap-highlight-color: transparent; }
        button { cursor: pointer; }
        a { color: inherit; text-decoration: none; }
        button:focus-visible, a:focus-visible, input:focus-visible, textarea:focus-visible {
            outline: 3px solid rgba(107, 154, 255, .58);
            outline-offset: 3px;
        }
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 50;
            display: flex;
            width: var(--sidebar);
            min-height: 100vh;
            flex-direction: column;
            padding: 27px 21px 21px;
            border-right: 1px solid rgba(151, 166, 210, .15);
            background: rgba(24, 34, 79, .98);
            transition: transform .22s ease;
        }
        .brand { display: flex; align-items: center; gap: 12px; padding: 3px 7px 30px; font-size: 20px; font-weight: 700; letter-spacing: -.04em; }
        .brand-mark {
            display: grid;
            width: 42px;
            height: 42px;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 13px;
            background: linear-gradient(145deg, var(--blue-light), var(--blue));
            box-shadow: 0 10px 24px rgba(51, 117, 245, .3);
            font-size: 22px;
        }
        .nav-label { padding: 8px 12px 10px; color: #7180aa; font-size: 10px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; }
        .sidebar-nav { display: grid; gap: 9px; }
        .nav-link {
            display: flex;
            min-height: 55px;
            align-items: center;
            gap: 14px;
            padding: 0 16px;
            border: 1px solid transparent;
            border-radius: 14px;
            color: #b5bfdc;
            font-size: 14px;
            font-weight: 600;
            transition: .18s ease;
        }
        .nav-link i { width: 23px; font-size: 22px; text-align: center; }
        .nav-link:hover { color: #fff; border-color: rgba(255, 255, 255, .08); background: rgba(255, 255, 255, .05); transform: translateX(2px); }
        .nav-link.active { color: #fff; border-color: rgba(255, 255, 255, .36); background: linear-gradient(135deg, #397af8, #2863df); box-shadow: 0 12px 28px rgba(21, 84, 220, .27); }
        .sidebar-help { margin-top: 24px; padding: 15px; border: 1px solid var(--line); border-radius: 14px; background: rgba(255, 255, 255, .035); }
        .sidebar-help i { color: var(--amber); font-size: 20px; }
        .sidebar-help strong { display: block; margin: 7px 0 4px; font-size: 12px; }
        .sidebar-help p { color: #7e8bb1; font-size: 9px; line-height: 1.6; }
        .sidebar-footer { position: relative; margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(151, 166, 210, .14); }
        .profile-button {
            display: grid;
            width: 100%;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 10px;
            padding: 9px;
            border: 1px solid transparent;
            border-radius: 14px;
            color: #c0c9e3;
            background: transparent;
            text-align: left;
            transition: .18s ease;
        }
        .profile-button:hover, .profile-button[aria-expanded='true'] { color: #fff; border-color: var(--line); background: rgba(255, 255, 255, .05); }
        .profile-avatar { display: grid; width: 38px; height: 38px; place-items: center; border: 1px solid rgba(107, 154, 255, .42); border-radius: 12px; color: #fff; background: rgba(51, 117, 245, .2); font-weight: 700; }
        .profile-copy { display: grid; min-width: 0; gap: 1px; }
        .profile-copy strong { overflow: hidden; font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
        .profile-copy small { color: #7f8db4; font-size: 9px; }
        .profile-button > i { font-size: 18px; transition: transform .18s ease; }
        .profile-button[aria-expanded='true'] > i { transform: rotate(180deg); }
        .profile-menu {
            position: absolute;
            right: 0;
            bottom: 68px;
            left: 0;
            visibility: hidden;
            padding: 7px;
            border: 1px solid var(--line-strong);
            border-radius: 13px;
            opacity: 0;
            background: #101842;
            box-shadow: var(--shadow);
            transform: translateY(8px);
            transition: .18s ease;
        }
        .profile-menu.open { visibility: visible; opacity: 1; transform: translateY(0); }
        .profile-menu a { display: flex; align-items: center; gap: 9px; padding: 10px 11px; border-radius: 9px; color: #ffc3cb; font-size: 12px; font-weight: 600; }
        .profile-menu a:hover { background: rgba(251, 113, 133, .11); }
        .mobile-menu, .sidebar-overlay { display: none; }
        .app-shell { min-height: 100vh; margin-left: var(--sidebar); padding: 36px 42px 24px; }
        .page-header, .page-main, .page-footer { width: min(100%, 1380px); margin-inline: auto; }
        .page-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; margin-bottom: 26px; }
        .eyebrow { margin-bottom: 7px; color: var(--blue-light); font-size: 10px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; }
        .page-title h1 { font-size: clamp(28px, 3vw, 39px); line-height: 1.15; letter-spacing: -.045em; }
        .page-title > p:last-child { margin-top: 8px; color: var(--muted); font-size: 13px; }
        .header-link { display: inline-flex; min-height: 44px; align-items: center; gap: 8px; padding: 0 15px; border: 1px solid var(--line); border-radius: 12px; color: #c7d0e7; background: rgba(17, 24, 68, .65); font-size: 11px; font-weight: 600; transition: .18s ease; }
        .header-link:hover { color: #fff; border-color: var(--blue-light); background: rgba(51, 117, 245, .12); }
        .progress-card {
            margin-bottom: 18px;
            padding: 19px 22px;
            border: 1px solid var(--line);
            border-radius: 17px;
            background: linear-gradient(145deg, rgba(22, 33, 82, .9), rgba(14, 21, 62, .9));
            box-shadow: 0 14px 40px rgba(2, 6, 23, .14);
        }
        .progress-top { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 14px; }
        .progress-top strong { font-size: 12px; }
        .progress-top span { color: #8e9abe; font-size: 10px; }
        .progress-track { height: 6px; overflow: hidden; border-radius: 999px; background: rgba(255, 255, 255, .07); }
        .progress-fill { display: block; width: 0; height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--blue), #61a0ff); transition: width .25s ease; }
        .step-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 15px; }
        .step { display: flex; align-items: center; gap: 8px; color: #7180aa; font-size: 9px; font-weight: 600; }
        .step-number { display: grid; width: 23px; height: 23px; flex: 0 0 auto; place-items: center; border: 1px solid var(--line); border-radius: 50%; background: rgba(255, 255, 255, .03); }
        .step.complete { color: #c7d4f1; }
        .step.complete .step-number { color: #fff; border-color: var(--green); background: rgba(52, 211, 153, .14); }
        .report-grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(340px, .85fr); gap: 18px; }
        .form-card {
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: 19px;
            background: linear-gradient(145deg, rgba(21, 31, 80, .88), rgba(13, 20, 59, .88));
            box-shadow: var(--shadow);
        }
        .description-card { grid-column: 1 / -1; }
        .card-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 15px; margin-bottom: 18px; }
        .heading-main { display: flex; align-items: center; gap: 12px; }
        .heading-icon { display: grid; width: 42px; height: 42px; flex: 0 0 auto; place-items: center; border: 1px solid rgba(107, 154, 255, .32); border-radius: 13px; color: var(--blue-light); background: rgba(51, 117, 245, .11); font-size: 20px; }
        .card-heading h2 { font-size: 15px; letter-spacing: -.02em; }
        .card-heading p { margin-top: 3px; color: #8491b6; font-size: 9px; line-height: 1.5; }
        .requirement { padding: 5px 8px; border-radius: 999px; color: #aebae0; background: rgba(255, 255, 255, .05); font-size: 8px; font-weight: 700; text-transform: uppercase; }
        .requirement.optional { color: #93a5d7; }
        .field-label { display: block; margin-bottom: 7px; color: #cbd3e8; font-size: 10px; font-weight: 600; }
        .input-wrap { position: relative; }
        .input-wrap > i { position: absolute; top: 50%; left: 14px; color: #7282ad; font-size: 18px; transform: translateY(-50%); }
        .text-input, .description-input {
            width: 100%;
            border: 1px solid var(--line);
            outline: 0;
            color: #fff;
            background: rgba(8, 15, 53, .54);
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }
        .text-input { height: 50px; padding: 0 14px 0 42px; border-radius: 13px; font-size: 11px; }
        .description-input { min-height: 145px; padding: 14px; resize: vertical; border-radius: 13px; font-size: 11px; line-height: 1.7; }
        .text-input::placeholder, .description-input::placeholder { color: #5f6c94; }
        .text-input:focus, .description-input:focus { border-color: var(--blue-light); background: rgba(11, 19, 61, .85); box-shadow: 0 0 0 3px rgba(107, 154, 255, .09); }
        .field-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .small-button {
            display: inline-flex;
            min-height: 36px;
            align-items: center;
            gap: 7px;
            padding: 0 12px;
            border: 1px solid var(--line);
            border-radius: 10px;
            color: #c5cee6;
            background: rgba(255, 255, 255, .035);
            font-size: 9px;
            font-weight: 600;
            transition: .18s ease;
        }
        .small-button:hover { color: #fff; border-color: var(--blue-light); background: rgba(51, 117, 245, .12); }
        .small-button.primary { color: #fff; border-color: rgba(107, 154, 255, .42); background: rgba(51, 117, 245, .17); }
        .status-message { min-height: 18px; margin-top: 8px; color: #8491b5; font-size: 9px; line-height: 1.5; }
        .status-message.success { color: var(--green); }
        .status-message.error { color: var(--red); }
        #locationMap { width: 100%; height: 285px; margin-top: 10px; overflow: hidden; border: 1px solid var(--line); border-radius: 14px; background: #101741; }
        .map-help { display: flex; align-items: center; gap: 6px; margin-top: 8px; color: #6e7ba3; font-size: 8px; }
        .upload-zone {
            position: relative;
            display: grid;
            min-height: 285px;
            overflow: hidden;
            place-items: center;
            border: 2px dashed rgba(151, 166, 210, .3);
            border-radius: 15px;
            background: rgba(8, 15, 53, .38);
            text-align: center;
            cursor: pointer;
            transition: .18s ease;
        }
        .upload-zone:hover, .upload-zone.dragging { border-color: var(--blue-light); background: rgba(51, 117, 245, .07); }
        .upload-empty { padding: 28px; }
        .upload-icon { display: grid; width: 58px; height: 58px; margin: 0 auto 13px; place-items: center; border: 1px solid rgba(107, 154, 255, .3); border-radius: 17px; color: var(--blue-light); background: rgba(51, 117, 245, .11); font-size: 28px; }
        .upload-empty strong { display: block; font-size: 12px; }
        .upload-empty p { margin-top: 5px; color: #7f8db4; font-size: 9px; line-height: 1.6; }
        .upload-empty span { display: inline-block; margin-top: 10px; padding: 5px 8px; border-radius: 999px; color: #7685ad; background: rgba(255, 255, 255, .04); font-size: 8px; }
        .preview-wrap { position: absolute; inset: 0; display: none; background: #080e30; }
        .preview-wrap.visible { display: block; }
        .preview-image { width: 100%; height: 100%; object-fit: contain; }
        .preview-overlay { position: absolute; right: 0; bottom: 0; left: 0; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 32px 13px 12px; background: linear-gradient(transparent, rgba(3, 7, 27, .92)); }
        .file-info { min-width: 0; text-align: left; }
        .file-info strong, .file-info span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .file-info strong { font-size: 9px; }
        .file-info span { margin-top: 2px; color: #a3aecb; font-size: 8px; }
        .remove-photo { display: grid; width: 34px; height: 34px; flex: 0 0 auto; place-items: center; border: 1px solid rgba(251, 113, 133, .36); border-radius: 10px; color: #ffc4cc; background: rgba(251, 113, 133, .12); font-size: 17px; }
        .ai-button {
            display: flex;
            width: 100%;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 11px;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(135deg, #3779f7, #2863df);
            box-shadow: 0 10px 24px rgba(21, 84, 220, .18);
            font-size: 10px;
            font-weight: 700;
            transition: .18s ease;
        }
        .ai-button:hover:not(:disabled) { background: linear-gradient(135deg, #4d8aff, #3470ec); transform: translateY(-1px); }
        .ai-button:disabled { color: #6f7da5; border-color: var(--line); background: rgba(255, 255, 255, .04); box-shadow: none; cursor: not-allowed; }
        .description-footer { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-top: 7px; color: #6e7ba3; font-size: 8px; }
        .description-footer span.valid { color: var(--green); }
        .submit-panel { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-top: 18px; padding: 17px 19px; border: 1px solid var(--line); border-radius: 16px; background: rgba(17, 24, 68, .75); }
        .submission-message { min-height: 18px; color: #8e9abc; font-size: 9px; line-height: 1.5; }
        .submission-message.success { color: var(--green); }
        .submission-message.error { color: var(--red); }
        .submit-actions { display: flex; align-items: center; gap: 9px; flex: 0 0 auto; }
        .cancel-button, .submit-button { display: inline-flex; min-height: 43px; align-items: center; justify-content: center; gap: 7px; padding: 0 16px; border-radius: 11px; font-size: 10px; font-weight: 700; transition: .18s ease; }
        .cancel-button { border: 1px solid var(--line); color: #b9c3dd; background: rgba(255, 255, 255, .035); }
        .cancel-button:hover { color: #fff; border-color: var(--line-strong); }
        .submit-button { border: 1px solid rgba(255, 255, 255, .14); color: #fff; background: linear-gradient(135deg, #397af8, #2863df); box-shadow: 0 10px 24px rgba(21, 84, 220, .2); }
        .submit-button:hover:not(:disabled) { background: linear-gradient(135deg, #4d8aff, #3470ec); transform: translateY(-1px); }
        .submit-button:disabled { color: #7582a7; border-color: var(--line); background: rgba(255, 255, 255, .05); box-shadow: none; cursor: not-allowed; }
        .page-footer { margin-top: 38px; padding: 20px 2px 3px; border-top: 1px solid rgba(151, 166, 210, .11); color: #69779f; font-size: 9px; text-align: center; }
        .toast {
            position: fixed;
            top: 22px;
            right: 22px;
            z-index: 1000;
            display: flex;
            visibility: hidden;
            max-width: 350px;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 15px;
            border: 1px solid var(--line-strong);
            border-radius: 13px;
            opacity: 0;
            background: #121a49;
            box-shadow: var(--shadow);
            transform: translateY(-8px);
            transition: .2s ease;
        }
        .toast.show { visibility: visible; opacity: 1; transform: translateY(0); }
        .toast.success { border-color: rgba(52, 211, 153, .4); }
        .toast.error { border-color: rgba(251, 113, 133, .4); }
        .toast i { margin-top: 1px; font-size: 20px; }
        .toast.success i { color: var(--green); }
        .toast.error i { color: var(--red); }
        .toast strong { display: block; font-size: 10px; }
        .toast p { margin-top: 3px; color: #9ca8c8; font-size: 8px; line-height: 1.5; }
        @media (max-width: 1080px) {
            .report-grid { grid-template-columns: 1fr; }
            .description-card { grid-column: auto; }
        }
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); }
            .nav-open .sidebar { transform: translateX(0); }
            .sidebar-overlay { position: fixed; inset: 0; z-index: 45; display: block; visibility: hidden; opacity: 0; background: rgba(3, 7, 24, .68); backdrop-filter: blur(3px); transition: .22s ease; }
            .nav-open .sidebar-overlay { visibility: visible; opacity: 1; }
            .mobile-menu { position: fixed; top: 17px; left: 17px; z-index: 55; display: grid; width: 44px; height: 44px; place-items: center; border: 1px solid var(--line-strong); border-radius: 13px; color: #fff; background: rgba(17, 24, 68, .94); box-shadow: var(--shadow); font-size: 22px; }
            .app-shell { margin-left: 0; padding: 80px 24px 22px; }
        }
        @media (max-width: 650px) {
            .app-shell { padding-inline: 14px; }
            .page-header { align-items: flex-start; flex-direction: column; }
            .header-link { width: 100%; justify-content: center; }
            .step-row { grid-template-columns: 1fr; }
            .form-card { padding: 17px; }
            .card-heading { align-items: flex-start; }
            .submit-panel { align-items: stretch; flex-direction: column; }
            .submit-actions { display: grid; grid-template-columns: 1fr 1fr; }
            .cancel-button, .submit-button { width: 100%; }
            #locationMap, .upload-zone { min-height: 250px; height: 250px; }
            .toast { right: 14px; left: 14px; max-width: none; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu" id="mobileMenu" type="button" aria-label="Open navigation" aria-expanded="false"><i class="bx bx-menu"></i></button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar">
        <a class="brand" href="userpage.php">
            <span class="brand-mark"><i class="bx bxs-city"></i></span>
            <span>AI City Guardian</span>
        </a>
        <p class="nav-label">Citizen portal</p>
        <nav class="sidebar-nav" aria-label="User navigation">
            <a class="nav-link active" href="uploadpage.php" aria-current="page"><i class="bx bx-plus-circle"></i><span>New Report</span></a>
            <a class="nav-link" href="userpage.php"><i class="bx bx-file"></i><span>My Submissions</span></a>
        </nav>
        <div class="sidebar-help">
            <i class="bx bx-bulb"></i>
            <strong>Quick reporting tip</strong>
            <p>Add a clear photo and exact location so the correct department can respond faster.</p>
        </div>
        <div class="sidebar-footer">
            <button class="profile-button" id="profileButton" type="button" aria-expanded="false">
                <span class="profile-avatar"><?php echo e($userInitial); ?></span>
                <span class="profile-copy"><strong><?php echo e($userName); ?></strong><small>Citizen account</small></span>
                <i class="bx bx-chevron-up"></i>
            </button>
            <div class="profile-menu" id="profileMenu"><a href="logout.php"><i class="bx bx-log-out"></i>Log out</a></div>
        </div>
    </aside>

    <div class="app-shell">
        <header class="page-header">
            <div class="page-title">
                <p class="eyebrow">Smart civic reporting</p>
                <h1>Submit a New Issue</h1>
                <p>Pin the location, add evidence and describe what happened.</p>
            </div>
            <a class="header-link" href="userpage.php"><i class="bx bx-list-ul"></i>View my submissions</a>
        </header>

        <main class="page-main">
            <form id="reportForm" action="../report/upload.php" method="post" enctype="multipart/form-data" novalidate>
                <section class="progress-card" aria-label="Report completion">
                    <div class="progress-top">
                        <strong>Report completion</strong>
                        <span id="progressText">0 of 3 steps ready</span>
                    </div>
                    <div class="progress-track"><span class="progress-fill" id="progressFill"></span></div>
                    <div class="step-row">
                        <div class="step" id="locationStep"><span class="step-number">1</span><span>Choose location</span></div>
                        <div class="step" id="photoStep"><span class="step-number">2</span><span>Add photo (recommended)</span></div>
                        <div class="step" id="descriptionStep"><span class="step-number">3</span><span>Review description</span></div>
                    </div>
                </section>

                <div class="report-grid">
                    <section class="form-card">
                        <div class="card-heading">
                            <div class="heading-main">
                                <span class="heading-icon"><i class="bx bx-map"></i></span>
                                <div><h2>Issue Location</h2><p>Enter an address or click directly on the map.</p></div>
                            </div>
                            <span class="requirement">Required</span>
                        </div>
                        <label class="field-label" for="location">Address or landmark</label>
                        <div class="input-wrap">
                            <i class="bx bx-search"></i>
                            <input class="text-input" type="text" id="location" name="location" placeholder="Example: Jalan Ampang, Kuala Lumpur" autocomplete="street-address" required>
                        </div>
                        <div class="field-actions">
                            <button class="small-button primary" type="button" id="autoLocateBtn"><i class="bx bx-current-location"></i>Use my location</button>
                            <button class="small-button" type="button" id="mapButton"><i class="bx bxl-google"></i>Open Google Maps</button>
                        </div>
                        <p class="status-message" id="mapMessage" aria-live="polite"></p>
                        <div id="locationMap" aria-label="Select issue location on map"></div>
                        <p class="map-help"><i class="bx bx-mouse"></i>Click anywhere on the map to move the location pin.</p>
                        <input type="hidden" id="latitude" name="latitude">
                        <input type="hidden" id="longitude" name="longitude">
                    </section>

                    <section class="form-card">
                        <div class="card-heading">
                            <div class="heading-main">
                                <span class="heading-icon"><i class="bx bx-image-add"></i></span>
                                <div><h2>Photo Evidence</h2><p>A clear photo helps AI understand the issue.</p></div>
                            </div>
                            <span class="requirement optional">Recommended</span>
                        </div>
                        <label class="upload-zone" id="dropArea" for="inputFile">
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" id="inputFile" hidden>
                            <span class="upload-empty" id="uploadEmpty">
                                <span class="upload-icon"><i class="bx bx-cloud-upload"></i></span>
                                <strong>Drop a photo here</strong>
                                <p>or click to browse from your device</p>
                                <span>JPG, PNG or WEBP · Maximum 8 MB</span>
                            </span>
                            <span class="preview-wrap" id="previewWrap">
                                <img class="preview-image" id="previewImage" alt="Selected issue preview">
                                <span class="preview-overlay">
                                    <span class="file-info"><strong id="fileName"></strong><span id="fileSize"></span></span>
                                    <button class="remove-photo" id="removePhoto" type="button" aria-label="Remove selected photo"><i class="bx bx-trash"></i></button>
                                </span>
                            </span>
                        </label>
                        <button class="ai-button" type="button" id="analyzeButton" disabled><i class="bx bxs-magic-wand"></i>Analyze photo with AI</button>
                        <p class="status-message" id="analyzeMessage" aria-live="polite">Upload a photo to enable AI analysis.</p>
                    </section>

                    <section class="form-card description-card">
                        <div class="card-heading">
                            <div class="heading-main">
                                <span class="heading-icon"><i class="bx bx-edit-alt"></i></span>
                                <div><h2>Issue Description</h2><p>Use AI as a starting point, then check the details yourself.</p></div>
                            </div>
                            <span class="requirement">Required</span>
                        </div>
                        <label class="field-label" for="description">What problem did you notice?</label>
                        <textarea class="description-input" name="ai_description" id="description" minlength="10" maxlength="1200" placeholder="Describe the issue, nearby landmarks and anything that may be dangerous..." required></textarea>
                        <div class="description-footer">
                            <span id="descriptionHint">Enter at least 10 characters.</span>
                            <span id="characterCount">0 / 1200</span>
                        </div>
                    </section>
                </div>

                <section class="submit-panel">
                    <p class="submission-message" id="message" aria-live="polite">Your report will be routed to the appropriate department.</p>
                    <div class="submit-actions">
                        <a class="cancel-button" href="userpage.php"><i class="bx bx-x"></i>Cancel</a>
                        <button class="submit-button" id="submitButton" type="submit"><i class="bx bx-send"></i>Submit report</button>
                    </div>
                </section>
            </form>
        </main>
        <footer class="page-footer">&copy; <?php echo date('Y'); ?> AI City Guardian. Building cleaner, safer communities together.</footer>
    </div>

    <div class="toast" id="toast" role="status" aria-live="polite">
        <i class="bx" id="toastIcon"></i>
        <div><strong id="toastTitle"></strong><p id="toastMessage"></p></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const body = document.body;
        const mobileMenu = document.getElementById('mobileMenu');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const profileButton = document.getElementById('profileButton');
        const profileMenu = document.getElementById('profileMenu');
        const reportForm = document.getElementById('reportForm');
        const locationInput = document.getElementById('location');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const mapMessage = document.getElementById('mapMessage');
        const autoLocateBtn = document.getElementById('autoLocateBtn');
        const mapButton = document.getElementById('mapButton');
        const inputFile = document.getElementById('inputFile');
        const dropArea = document.getElementById('dropArea');
        const previewWrap = document.getElementById('previewWrap');
        const previewImage = document.getElementById('previewImage');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const removePhoto = document.getElementById('removePhoto');
        const analyzeButton = document.getElementById('analyzeButton');
        const analyzeMessage = document.getElementById('analyzeMessage');
        const descriptionBox = document.getElementById('description');
        const descriptionHint = document.getElementById('descriptionHint');
        const characterCount = document.getElementById('characterCount');
        const message = document.getElementById('message');
        const submitButton = document.getElementById('submitButton');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        const toast = document.getElementById('toast');
        const toastIcon = document.getElementById('toastIcon');
        const toastTitle = document.getElementById('toastTitle');
        const toastMessage = document.getElementById('toastMessage');
        let map = null;
        let marker = null;
        let previewUrl = '';
        let locationTimer = null;
        let toastTimer = null;

        function setLiveMessage(element, text, type = '') {
            element.textContent = text;
            element.className = 'status-message' + (type ? ' ' + type : '');
        }

        function showToast(type, title, text) {
            clearTimeout(toastTimer);
            toast.className = 'toast show ' + type;
            toastIcon.className = 'bx ' + (type === 'success' ? 'bx-check-circle' : 'bx-error-circle');
            toastTitle.textContent = title;
            toastMessage.textContent = text;
            toastTimer = setTimeout(() => toast.classList.remove('show'), 4200);
        }

        function closeNavigation() {
            body.classList.remove('nav-open');
            mobileMenu.setAttribute('aria-expanded', 'false');
        }

        mobileMenu.addEventListener('click', () => {
            const open = !body.classList.contains('nav-open');
            body.classList.toggle('nav-open', open);
            mobileMenu.setAttribute('aria-expanded', String(open));
        });
        sidebarOverlay.addEventListener('click', closeNavigation);
        profileButton.addEventListener('click', event => {
            event.stopPropagation();
            const open = !profileMenu.classList.contains('open');
            profileMenu.classList.toggle('open', open);
            profileButton.setAttribute('aria-expanded', String(open));
        });
        document.addEventListener('click', event => {
            if (!profileMenu.contains(event.target) && !profileButton.contains(event.target)) {
                profileMenu.classList.remove('open');
                profileButton.setAttribute('aria-expanded', 'false');
            }
        });

        function updateProgress() {
            const states = [
                locationInput.value.trim().length >= 3,
                Boolean(inputFile.files[0]),
                descriptionBox.value.trim().length >= 10
            ];
            const stepIds = ['locationStep', 'photoStep', 'descriptionStep'];
            states.forEach((complete, index) => {
                const step = document.getElementById(stepIds[index]);
                step.classList.toggle('complete', complete);
                step.querySelector('.step-number').innerHTML = complete ? '<i class="bx bx-check"></i>' : String(index + 1);
            });
            const completeCount = states.filter(Boolean).length;
            progressFill.style.width = (completeCount / 3 * 100) + '%';
            progressText.textContent = completeCount + ' of 3 steps ready';
        }

        function initMap() {
            if (typeof L === 'undefined') {
                setLiveMessage(mapMessage, 'Map could not load. You can still enter the address manually.', 'error');
                return;
            }
            map = L.map('locationMap', { zoomControl: true }).setView([3.1390, 101.6869], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            map.on('click', event => selectCoordinates(event.latlng.lat, event.latlng.lng, true));
            setTimeout(() => map.invalidateSize(), 250);
        }

        function moveMarker(lat, lng, zoom = 16) {
            if (!map) return;
            map.setView([lat, lng], zoom);
            if (!marker) marker = L.marker([lat, lng]).addTo(map);
            else marker.setLatLng([lat, lng]);
        }

        async function selectCoordinates(lat, lng, lookupAddress) {
            latitudeInput.value = lat.toFixed(7);
            longitudeInput.value = lng.toFixed(7);
            moveMarker(lat, lng);
            setLiveMessage(mapMessage, lookupAddress ? 'Finding the nearest address...' : 'Location selected.', '');
            if (lookupAddress) await reverseGeocode(lat, lng);
            updateProgress();
        }

        async function reverseGeocode(lat, lng) {
            try {
                const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`;
                const response = await fetch(url, { headers: { 'Accept-Language': 'en' } });
                if (!response.ok) throw new Error('Address lookup failed');
                const data = await response.json();
                if (data.display_name) {
                    locationInput.value = data.display_name;
                    marker?.bindPopup(data.display_name).openPopup();
                    setLiveMessage(mapMessage, 'Location detected successfully.', 'success');
                } else {
                    setLiveMessage(mapMessage, 'Coordinates saved. Please enter a nearby landmark.', '');
                }
            } catch (error) {
                setLiveMessage(mapMessage, 'Coordinates saved, but the address could not be found.', 'error');
                console.error(error);
            }
            updateProgress();
        }

        async function searchLocation(query) {
            if (!map || query.length < 3) return;
            setLiveMessage(mapMessage, 'Matching the address on the map...', '');
            try {
                const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=1&countrycodes=my`;
                const response = await fetch(url, { headers: { 'Accept-Language': 'en' } });
                if (!response.ok) throw new Error('Location lookup failed');
                const results = await response.json();
                if (!results.length) {
                    setLiveMessage(mapMessage, 'No matching Malaysian location found. Try a nearby landmark.', 'error');
                    return;
                }
                const lat = Number(results[0].lat);
                const lng = Number(results[0].lon);
                latitudeInput.value = lat.toFixed(7);
                longitudeInput.value = lng.toFixed(7);
                moveMarker(lat, lng);
                marker.bindPopup(results[0].display_name).openPopup();
                setLiveMessage(mapMessage, 'Address matched on the map.', 'success');
            } catch (error) {
                setLiveMessage(mapMessage, 'Location lookup is temporarily unavailable.', 'error');
                console.error(error);
            }
        }

        locationInput.addEventListener('input', () => {
            clearTimeout(locationTimer);
            updateProgress();
            const query = locationInput.value.trim();
            if (query.length < 3) {
                setLiveMessage(mapMessage, '', '');
                return;
            }
            locationTimer = setTimeout(() => searchLocation(query), 900);
        });

        autoLocateBtn.addEventListener('click', () => {
            if (!navigator.geolocation) {
                setLiveMessage(mapMessage, 'Your browser does not support location detection.', 'error');
                return;
            }
            autoLocateBtn.disabled = true;
            autoLocateBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i>Locating...';
            setLiveMessage(mapMessage, 'Requesting your current location...', '');
            navigator.geolocation.getCurrentPosition(
                async position => {
                    await selectCoordinates(position.coords.latitude, position.coords.longitude, true);
                    autoLocateBtn.disabled = false;
                    autoLocateBtn.innerHTML = '<i class="bx bx-current-location"></i>Use my location';
                },
                error => {
                    setLiveMessage(mapMessage, 'Location permission failed: ' + error.message, 'error');
                    autoLocateBtn.disabled = false;
                    autoLocateBtn.innerHTML = '<i class="bx bx-current-location"></i>Use my location';
                },
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 }
            );
        });

        mapButton.addEventListener('click', () => {
            const query = locationInput.value.trim();
            if (!query) {
                setLiveMessage(mapMessage, 'Enter an address before opening Google Maps.', 'error');
                locationInput.focus();
                return;
            }
            window.open('https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(query), '_blank', 'noopener');
        });

        function formatBytes(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1024 / 1024).toFixed(1) + ' MB';
        }

        function clearPhoto() {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = '';
            inputFile.value = '';
            previewImage.removeAttribute('src');
            previewWrap.classList.remove('visible');
            analyzeButton.disabled = true;
            setLiveMessage(analyzeMessage, 'Upload a photo to enable AI analysis.', '');
            updateProgress();
        }

        function usePhoto(file) {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                clearPhoto();
                showToast('error', 'Unsupported image', 'Please choose a JPG, PNG or WEBP image.');
                return;
            }
            if (file.size > 8 * 1024 * 1024) {
                clearPhoto();
                showToast('error', 'Image is too large', 'Please choose an image smaller than 8 MB.');
                return;
            }
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = URL.createObjectURL(file);
            previewImage.src = previewUrl;
            fileName.textContent = file.name;
            fileSize.textContent = formatBytes(file.size) + ' · Click image to change';
            previewWrap.classList.add('visible');
            analyzeButton.disabled = false;
            setLiveMessage(analyzeMessage, 'Photo ready. You can now generate an AI description.', 'success');
            updateProgress();
        }

        inputFile.addEventListener('change', () => {
            if (inputFile.files[0]) usePhoto(inputFile.files[0]);
        });
        dropArea.addEventListener('dragover', event => {
            event.preventDefault();
            dropArea.classList.add('dragging');
        });
        dropArea.addEventListener('dragleave', () => dropArea.classList.remove('dragging'));
        dropArea.addEventListener('drop', event => {
            event.preventDefault();
            dropArea.classList.remove('dragging');
            const file = event.dataTransfer.files[0];
            if (!file) return;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            inputFile.files = transfer.files;
            usePhoto(file);
        });
        removePhoto.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            clearPhoto();
        });

        analyzeButton.addEventListener('click', async () => {
            const file = inputFile.files[0];
            if (!file) {
                showToast('error', 'Photo required', 'Upload a photo before using AI analysis.');
                return;
            }
            analyzeButton.disabled = true;
            analyzeButton.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i>Analyzing photo...';
            descriptionBox.disabled = true;
            setLiveMessage(analyzeMessage, 'AI is checking the image. This may take a few seconds...', '');
            const formData = new FormData();
            formData.append('image', file);
            formData.append('location', locationInput.value.trim());
            try {
                const response = await fetch('../report/analyze.php', { method: 'POST', body: formData });
                if (!response.ok) throw new Error('Server returned ' + response.status);
                const result = await response.json();
                if (!result.success) throw new Error(result.error || 'AI analysis failed');
                descriptionBox.value = result.description || '';
                setLiveMessage(analyzeMessage, 'AI description generated. Please review it before submitting.', 'success');
                showToast('success', 'AI analysis complete', 'Review the generated description and edit anything inaccurate.');
            } catch (error) {
                setLiveMessage(analyzeMessage, 'AI could not analyze the photo. Please describe the issue manually.', 'error');
                showToast('error', 'AI analysis unavailable', 'You can still type the issue description manually.');
                console.error(error);
            } finally {
                descriptionBox.disabled = false;
                analyzeButton.disabled = !inputFile.files[0];
                analyzeButton.innerHTML = '<i class="bx bxs-magic-wand"></i>Analyze photo with AI';
                descriptionBox.dispatchEvent(new Event('input'));
                descriptionBox.focus();
            }
        });

        descriptionBox.addEventListener('input', () => {
            const length = descriptionBox.value.length;
            characterCount.textContent = length + ' / 1200';
            const valid = descriptionBox.value.trim().length >= 10;
            descriptionHint.textContent = valid ? 'Description is ready.' : 'Enter at least 10 characters.';
            descriptionHint.classList.toggle('valid', valid);
            updateProgress();
        });

        reportForm.addEventListener('keydown', event => {
            if (event.key === 'Enter' && event.target.tagName === 'INPUT') event.preventDefault();
        });

        reportForm.addEventListener('submit', async event => {
            event.preventDefault();
            if (!reportForm.checkValidity() || descriptionBox.value.trim().length < 10) {
                reportForm.reportValidity();
                message.className = 'submission-message error';
                message.textContent = 'Please complete the required location and description fields.';
                return;
            }
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i>Submitting...';
            message.className = 'submission-message';
            message.textContent = 'Sending your report securely...';
            try {
                const response = await fetch('../report/upload.php', { method: 'POST', body: new FormData(reportForm) });
                const data = await response.text();
                if (!response.ok || !data.toLowerCase().includes('successfully')) throw new Error(data || 'Submission failed');
                message.className = 'submission-message success';
                message.textContent = 'Report submitted successfully. Opening your submissions...';
                showToast('success', 'Report submitted', 'Thank you for helping improve your community.');
                setTimeout(() => { window.location.href = 'userpage.php'; }, 1300);
            } catch (error) {
                message.className = 'submission-message error';
                message.textContent = error.message || 'The report could not be submitted. Please try again.';
                showToast('error', 'Submission failed', 'Please check your connection and try again.');
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="bx bx-send"></i>Submit report';
                console.error(error);
            }
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeNavigation();
                profileMenu.classList.remove('open');
                profileButton.setAttribute('aria-expanded', 'false');
            }
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth > 900) closeNavigation();
            map?.invalidateSize();
        });
        window.addEventListener('beforeunload', () => {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
        });
        window.addEventListener('load', () => {
            initMap();
            updateProgress();
        });
    </script>
</body>
</html>