<?php
require_once __DIR__ . '/logs/logout_handler.php';
session_start();
if (!isset($_SESSION['agency_id'])) {
    header('Location: index.php');
    exit();
}
$agency_id = $_SESSION['agency_id'];
$agency_name = isset($_SESSION['agency_name']) ? $_SESSION['agency_name'] : 'Agency';
require_once 'database_connection/connection.php';

$active_page = 'agency_overview';

// Stat Card: Active Automations
$stmt = $pdo->prepare("SELECT COUNT(*) FROM automations WHERE agency_id = ? AND status = 'active'");
$stmt->execute([$agency_id]);
$active_automations = $stmt->fetchColumn();

// Fetch total automations (active or inactive) for the agency
$stmt = $pdo->prepare("SELECT COUNT(*) FROM automations WHERE agency_id = ?");
$stmt->execute([$agency_id]);
$total_automations = $stmt->fetchColumn();

// Recent Activity: 3 most recent events for this agency from activity_log
$stmt = $pdo->prepare("SELECT type, description, created_at FROM activity_log WHERE agency_id = ? ORDER BY created_at DESC LIMIT 3");
$stmt->execute([$agency_id]);
$recent_activity = $stmt->fetchAll();

// Top Error Causes: top 3 automation statuses that are not 'active' (simulate errors)
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM automations WHERE agency_id = ? AND status != 'active' GROUP BY status ORDER BY count DESC LIMIT 3");
$stmt->execute([$agency_id]);
$top_errors = $stmt->fetchAll();

// Most Used: automation assigned to the most users for this agency
$stmt = $pdo->prepare("SELECT a.name, COUNT(au.user_id) as user_count FROM automations a JOIN automation_users au ON a.id = au.automation_id WHERE a.agency_id = ? GROUP BY a.id ORDER BY user_count DESC LIMIT 1");
$stmt->execute([$agency_id]);
$most_used = $stmt->fetch();

// For Weekly Activity, check if there is any automation for this agency
$stmt = $pdo->prepare("SELECT COUNT(*) FROM automations WHERE agency_id = ?");
$stmt->execute([$agency_id]);
$automation_count = $stmt->fetchColumn();

// Time Saved (Hours): sum of time_saved_hours from automation_runs for this agency
$stmt = $pdo->prepare("SELECT COALESCE(SUM(time_saved_hours),0) FROM automation_runs WHERE agency_id = ?");
$stmt->execute([$agency_id]);
$time_saved = $stmt->fetchColumn();

// Time Saved This Week
$stmt = $pdo->prepare("SELECT COALESCE(SUM(time_saved_hours),0) FROM automation_runs WHERE agency_id = ? AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
$stmt->execute([$agency_id]);
$time_saved_this_week = $stmt->fetchColumn();

// Time Saved Last Week
$stmt = $pdo->prepare("SELECT COALESCE(SUM(time_saved_hours),0) FROM automation_runs WHERE agency_id = ? AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1)");
$stmt->execute([$agency_id]);
$time_saved_last_week = $stmt->fetchColumn();

$time_saved_diff = $time_saved_this_week - $time_saved_last_week;
$time_saved_diff_text = ($time_saved_diff >= 0 ? '↗ +' : '↓ ') . abs(round($time_saved_diff, 1)) . ' hours this week';

// Weekly Activity: time saved per day (Mon-Sun)
$stmt = $pdo->prepare("
    SELECT DAYOFWEEK(created_at) AS day_of_week, COALESCE(SUM(time_saved_hours),0) AS hours_saved
    FROM automation_runs
    WHERE agency_id = ? AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
    GROUP BY day_of_week
");
$stmt->execute([$agency_id]);
$weekly_activity = array_fill(1, 7, 0); // 1=Sunday, 7=Saturday
foreach ($stmt->fetchAll() as $row) {
    $weekly_activity[(int)$row['day_of_week']] = (float)$row['hours_saved'];
}
// Reorder to Mon-Sun (PHP: 1=Sunday, 2=Monday, ..., 7=Saturday)
$weekly_activity_ordered = [
    $weekly_activity[2] ?? 0, // Monday
    $weekly_activity[3] ?? 0, // Tuesday
    $weekly_activity[4] ?? 0, // Wednesday
    $weekly_activity[5] ?? 0, // Thursday
    $weekly_activity[6] ?? 0, // Friday
    $weekly_activity[7] ?? 0, // Saturday
    $weekly_activity[1] ?? 0, // Sunday
];

// Get agency's user count (signed clients)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE agency_id = ?");
$stmt->execute([$agency_id]);
$signed_clients = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Agency Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Montserrat:wght@600;800&display=swap" rel="stylesheet">
    <style>
        html, body {
            height: 100vh;
            overflow: hidden;
        }
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #000f1d;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }
        .layout { display: flex; min-height: 100vh; }
        .sidebar {
            background: #e5e2dd;
            color: #22325a;
            width: 240px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding: 0;
            min-height: 100vh;
        }
        .sidebar nav {
            width: 100%;
            margin-top: 40px;
        }
        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #22325a;
            text-decoration: none;
            font-weight: 700;
            padding: 14px 32px;
            border-left: 4px solid transparent;
            transition: background 0.2s, color 0.2s, border-color 0.2s;
            font-size: 1.08em;
            border-radius: 12px;
            background: transparent;
            margin-bottom: 8px;
        }
        .sidebar nav a.active, .sidebar nav a:hover {
            background: #178fff22;
            color: #178fff;
            border-left: 4px solid #1a8cff;
        }
        .sidebar nav a:not(.active):not(:hover) {
            background: #e5e2dd;
            color: #22325a;
            border-left: 4px solid transparent;
        }
        .sidebar .signout {
            margin-top: 16px;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #e3342f;
            font-weight: 800;
            font-size: 1.08em;
            cursor: pointer;
            border-radius: 10px;
            padding: 16px 0 16px 32px;
            transition: background 0.18s, color 0.18s, transform 0.18s;
            border: none;
            background: none;
        }
        .sidebar .signout:hover {
            background: #b22234;
            color: #fff;
            font-weight: 900;
        }
        .main-content {
            flex: 1;
            padding: 18px 18px 0 18px;
            background: #000f1d;
            min-width: 0;
            height: 100vh;
            overflow-y: auto;
            color: #fff;
            overflow: hidden !important;
        }
        .dashboard-cards {
            display: flex;
            gap: 18px;
            margin-bottom: 18px;
        }
        .dashboard-card {
            background: #101c2c;
            border-radius: 18px;
            box-shadow: 0 2px 12px #22325a22;
            padding: 18px 22px;
            min-width: 160px;
            flex: 1 1 120px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            position: relative;
            border: 1.5px solid #232f3e;
            margin-bottom: 0;
            transition: box-shadow 0.18s, border-color 0.18s;
        }
        .dashboard-card:hover {
            box-shadow: 0 4px 24px #178fff33;
            border-color: #178fff;
        }
        .dashboard-card .icon {
            position: absolute;
            top: 12px;
            right: 12px;
            font-size: 1.2em;
            color: #fff;
            background: #3864fa;
            padding: 5px 8px;
            border-radius: 8px;
            transition: background 0.18s, color 0.18s;
        }
        .dashboard-card:hover .icon {
            background: #178fff;
            color: #fff;
        }
        .dashboard-card .label {
            font-size: 0.98em;
            font-weight: 700;
            color: #b6c6d7;
            margin-bottom: 4px;
        }
        .dashboard-card .value {
            font-size: 1.5em;
            font-weight: 900;
            color: #fff;
            margin-bottom: 2px;
        }
        .dashboard-card .sub {
            font-size: 0.95em;
            color: #3ad1ff;
            font-weight: 600;
        }
        .dashboard-card .most-used {
            color: #fff;
            font-size: 1.3em;
            font-weight: 800;
            margin-top: 8px;
        }
        .cta-banner {
            background: linear-gradient(90deg, #0a1833 0%, #178fff 100%);
            color: #fff;
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            font-size: 0.98em;
            font-weight: 700;
            box-shadow: 0 0 24px 4px #178fff66;
        }
        .cta-banner .cta-btn {
            background: #fff;
            color: #178fff;
            font-weight: 800;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.18s, color 0.18s;
            box-shadow: 0 2px 8px #178fff22;
        }
        .cta-banner .cta-btn:hover {
            background: #14213d;
            color: #fff;
        }
        .main-content p {
            color: #22325a;
            margin-bottom: 32px;
        }
        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            margin-top: 32px;
        }
        .stat-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 12px #22325a11;
            padding: 36px 48px;
            min-width: 240px;
            flex: 1 1 220px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
        }
        .stat-card .stat-label {
            font-size: 1.18em;
            font-weight: 700;
            color: #14213d;
            margin-bottom: 12px;
        }
        .stat-card .stat-value {
            font-size: 2.5em;
            font-weight: 900;
            color: #14213d;
        }
        .sidebar nav a svg {
            stroke: #22325a;
            transition: stroke 0.2s, filter 0.2s;
        }
        .sidebar nav a.active svg, .sidebar nav a:hover svg {
            stroke: #178fff;
            filter: drop-shadow(0 0 6px #178fff88);
        }
        .dashboard-panel {
            background: rgba(255,255,255,0.07);
            border-radius: 14px;
            border: 1.5px solid #232f3e;
            box-shadow: 0 2px 12px #22325a11;
            color: #fff;
            padding: 10px 12px;
            margin-bottom: 0;
            transition: box-shadow 0.18s, border-color 0.18s, background 0.18s;
        }
        .dashboard-panel h3 {
            color: #fff;
            font-size: 0.92em;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .dashboard-panel ul li span,
        .dashboard-panel ul li {
            color: #fff;
        }
        .dashboard-panel ul li .error-count {
            background: #b22234;
            color: #fff;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1em;
            margin-left: auto;
        }
        .subtitle-agency {
            color: #22325a;
            font-size: 0.92em;
            margin-bottom: 10px;
            font-weight: 400;
            background: none;
            border-radius: 10px;
            padding: 0 0 0 2px;
            letter-spacing: 0.01em;
        }
        .cta-btn {
            background: linear-gradient(90deg, #178fff 0%, #0a1833 100%);
            color: #ffd166;
            font-family: 'Montserrat', 'Inter', Arial, sans-serif;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            padding: 12px 28px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            box-shadow: 0 2px 8px #178fff22;
        }
        .cta-btn:hover {
            background: #178fff;
            color: #fff;
            box-shadow: 0 0 16px 4px #178fff99;
        }
        .dashboard-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .dashboard-activity-grid {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 0;
        }
        .dashboard-activity-grid .hline {
            position: absolute;
            left: 0; right: 0;
            height: 1px;
            background: rgba(255,255,255,0.04);
        }
        .dashboard-activity-grid .vline {
            position: absolute;
            top: 0; bottom: 0;
            width: 1px;
            background: rgba(255,255,255,0.04);
        }
        .activity-list li {
            background: #232f3e;
            border-radius: 8px;
            padding: 8px 12px;
            margin-bottom: 7px;
            color: #fff;
            display: flex;
            flex-direction: column;
            gap: 2px;
            font-size: 0.98em;
        }
        .activity-list .activity-status {
            font-size: 0.98em;
        }
        .activity-list .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .activity-list .time {
            font-size: 0.85em;
            color: #b6c6d7;
            font-weight: 400;
        }
        .icon {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: 38px;
            width: 38px;
            padding: 0 !important;
            margin-left: auto;
            margin-right: 0;
        }
        .icon svg {
            display: block;
            margin: 0 auto;
        }
        .bell-notification {
            position: absolute;
            top: 32px;
            right: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #22325a;
            transition: background 0.18s;
        }
        .bell-notification svg {
            stroke: #7ecbff;
            transition: stroke 0.18s;
        }
        .bell-notification:hover {
            background: #fff;
        }
        .bell-notification:hover svg {
            stroke: #22325a;
        }
        .dashboard-panel.scrollable-list {
            max-height: 220px;
            overflow-y: auto;
        }
        .error-list, .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .error-list li, .activity-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #232f3e;
            border-radius: 8px;
            margin-bottom: 8px;
            padding: 12px 16px;
            transition: background 0.18s;
            cursor: pointer;
        }
        .error-list li:hover, .activity-list li:hover {
            background: #2d3a4d;
        }
        .error-list .error-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.08em;
            color: #fff;
        }
        .error-list .error-count {
            background: #b22234;
            color: #fff;
            font-weight: 800;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1em;
        }
        .activity-list li {
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
            font-size: 1em;
            color: #fff;
            background: #232f3e;
            border-radius: 8px;
            margin-bottom: 8px;
            padding: 12px 16px;
            transition: background 0.18s;
            cursor: default;
        }
        .activity-list li:hover {
            background: #2d3a4d;
        }
        .activity-list .activity-status {
            font-size: 1em;
            font-weight: 500;
        }
        .activity-list .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .activity-list .time {
            font-size: 0.92em;
            color: #b6c6d7;
            font-weight: 400;
        }
        .dashboard-panel.scrollable-list.activity-panel {
            max-height: none;
            height: 240px;
            overflow-y: visible;
        }
        .dashboard-panel .dashboard-activity-grid {
            position: relative;
            width: 100%;
            height: 90px;
            margin-bottom: 0;
            background: none;
        }
        .dashboard-panel.weekly-activity-panel {
            display: flex;
            flex-direction: column;
            position: relative;
            min-height: 180px;
            height: 220px;
            justify-content: flex-start;
        }
        .dashboard-panel.weekly-activity-panel .dashboard-activity-grid {
            position: absolute;
            top: 32px;
            left: 0;
            right: 0;
            bottom: 32px;
            width: 100%;
            height: auto;
            z-index: 0;
        }
        .dashboard-panel.weekly-activity-panel .days-row {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 32px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            color: #b6c6d7;
            font-size: 1em;
            z-index: 1;
            padding: 0 12px;
        }
    </style>
</head>
<body>
<div class="layout">
    <?php $active_page = 'agency_overview'; include 'agency_sidebar.php'; ?>
    <main class="main-content">
        <h1 style="font-size:2.2em;font-weight:900;color:#fff;margin-bottom:0;">Welcome to the Agency Panel</h1>
        <div class="welcome" style="font-size:1.08em;font-weight:700;margin-bottom:4px;margin-top:2px;color:#fff;">
            Logged in as <span style="color:#178fff;font-weight:900;"><?= htmlspecialchars($agency_name) ?></span>
            <span style="margin-left:6px;">      |      <span style="font-style:italic;">Agency Code:</span> <span style="font-weight:900;color:#7ecbff;font-style:italic;"><?= htmlspecialchars($agency_id) ?></span></span>
            <span style="margin-left:18px;opacity:0.7;font-weight:600;">Share Your Agency Code with New Clients when they Sign up.</span>
        </div>
        <div class="subtitle-agency" style="color:#4a5a6a;font-size:1.08em;font-weight:600;margin-bottom:18px;">This is the Agency Page where you can link your <b>automations</b>, manage your <b>clients</b>, and see <b>results</b>.</div>
        <div style="margin-left:2px;max-width:100vw;">
            <div class="bell-notification">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 16v-5a6 6 0 1 0-12 0v5l-1.5 2h15z"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </div>
            <div class="dashboard-cards" style="margin-bottom: 24px;">
                <div class="dashboard-card" style="border:1.5px solid #232f3e;">
                    <div class="label">Active Automations</div>
                    <div class="value" style="color:#fff;">
                        <?php echo $active_automations; ?>
                    </div>
                    <div class="sub" style="color:#3ad1ff;">Including inactive automations: <?php echo $total_automations; ?></div>
                    <div class="icon" style="background:#3864fa;padding:8px 12px;border-radius:8px;"><svg width="24" height="24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16M12 4v16"/></svg></div>
                </div>
                <div class="dashboard-card" style="border:1.5px solid #232f3e;">
                    <div class="label">Time Saved (Hours)</div>
                    <div class="value" style="color:#fff;">
                        <?php echo round($time_saved, 1); ?>
                    </div>
                    <div class="sub" style="color:#3ad1ff;">
                        <?php echo $time_saved_diff_text; ?>
                    </div>
                    <div class="icon" style="background:#1ec97e;padding:8px 12px;border-radius:8px;">
                        <svg width="24" height="24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    </div>
                </div>
                <div class="dashboard-card" style="border:1.5px solid #232f3e;">
                    <div class="label">Signed Clients</div>
                    <div class="value" style="color:#fff;">
                        <?php echo $signed_clients; ?>
                    </div>
                    <div class="sub" style="color:#3ad1ff;">Total users signed to your agency</div>
                    <div class="icon" style="background:#a259f7;padding:8px 12px;border-radius:8px;">
                        <svg width="24" height="24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 8-4 8-4s8 0 8 4"/></svg>
                    </div>
                </div>
                <div class="dashboard-card" style="border:1.5px solid #232f3e;">
                    <div class="label" style="color:#b6c6d7;">Most Used</div>
                    <div class="most-used" style="color:#fff;">
                        <?php echo $most_used ? htmlspecialchars($most_used['name']) : 'No automations made.'; ?>
                    </div>
                    <div class="icon" style="background:#16b1c9;padding:8px 12px;border-radius:8px;"><svg width="24" height="24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg></div>
                </div>
            </div>
            <div class="cta-banner" style="background: linear-gradient(90deg, #0a1833 0%, #178fff 100%); color:#fff; box-shadow: 0 0 24px 4px #178fff66;">
                <div>
                    <div style="font-size:1.25em;font-weight:800;">Ready to automate more?</div>
                    <div style="font-size:1em;font-weight:400;">Link a new automation to Put on your store and offer your clients!</div>
                </div>
                <button class="cta-btn" style="font-weight:800;border:none;border-radius:8px;padding:12px 28px;font-size:1.1em;cursor:pointer;transition:background 0.18s, color 0.18s;">+ Start New Automation</button>
            </div>
            <div class="dashboard-row">
                <div class="dashboard-panel weekly-activity-panel" style="flex:2;min-width:0;overflow:hidden;">
                    <h3>Weekly Activity</h3>
                    <div class="dashboard-activity-grid">
                        <div class="hline" style="top:12.5%;"></div>
                        <div class="hline" style="top:25%;"></div>
                        <div class="hline" style="top:37.5%;"></div>
                        <div class="hline" style="top:50%;"></div>
                        <div class="hline" style="top:62.5%;"></div>
                        <div class="hline" style="top:75%;"></div>
                        <div class="hline" style="top:87.5%;"></div>
                        <div class="vline" style="left:7.14%;"></div>
                        <div class="vline" style="left:14.28%;"></div>
                        <div class="vline" style="left:21.42%;"></div>
                        <div class="vline" style="left:28.56%;"></div>
                        <div class="vline" style="left:35.7%;"></div>
                        <div class="vline" style="left:42.84%;"></div>
                        <div class="vline" style="left:50%;"></div>
                        <div class="vline" style="left:57.14%;"></div>
                        <div class="vline" style="left:64.28%;"></div>
                        <div class="vline" style="left:71.42%;"></div>
                        <div class="vline" style="left:78.56%;"></div>
                        <div class="vline" style="left:85.7%;"></div>
                        <div class="vline" style="left:92.84%;"></div>
                        <?php
                        $max = max($weekly_activity_ordered);
                        if ($max == 0): ?>
                            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;opacity:0.5;font-size:1.2em;z-index:2;">No data</div>
                        <?php else:
                            foreach ($weekly_activity_ordered as $i => $val):
                                $height = $max > 0 ? (80 * $val / $max) : 0; // 80px max height
                        ?>
                            <div style="position:absolute;bottom:32px;left:calc(<?= $i ?>*14.28% + 7%);width:8%;height:<?= $height ?>px;background:#3ad1ff;border-radius:6px 6px 0 0;z-index:2;transition:height 0.2s;" title="<?= $val ?> hours"></div>
                        <?php endforeach; endif; ?>
                    </div>
                    <div class="days-row">
                        <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                    </div>
                </div>
                <div class="dashboard-panel scrollable-list" style="flex:1;max-width:340px;position:relative;">
                    <div style="display:flex;align-items:center;margin-bottom:6px;">
                        <h3 style="color:#fff;font-size:1em;font-weight:800;margin:0;">Top Error Causes</h3>
                        <span style="color:#fff;opacity:0.25;font-size:0.85em;margin-left:10px;">- Client issues</span>
                    </div>
                    <ul class="error-list">
                        <?php if (count($top_errors) === 0): ?>
                            <li style="opacity:0.5;text-align:center;width:100%;max-width:100%;box-sizing:border-box;white-space:normal;overflow-wrap:break-word;">No error causes to show.</li>
                        <?php else: ?>
                            <?php foreach ($top_errors as $err): ?>
                                <li><span class="error-label"><span style="color:#ffd166;font-size:1.2em;">&#9888;</span><?php echo htmlspecialchars($err['status']); ?></span><span class="error-count"><?php echo $err['count']; ?></span></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="dashboard-panel scrollable-list activity-panel" style="margin-bottom:10px;padding-bottom:8px;">
                <div style="display:flex;align-items:center;margin-bottom:6px;">
                    <h3 style="color:#fff;font-size:0.92em;font-weight:800;margin:0;">Recent Activity</h3>
                    <span style="color:#fff;opacity:0.25;font-size:0.98em;margin-left:12px;">- Most recent Client Activity and Automation updates and sendouts</span>
                </div>
                <ul class="activity-list">
                    <?php if (count($recent_activity) === 0): ?>
                        <li style="opacity:0.5;text-align:center;width:100%;max-width:100%;box-sizing:border-box;white-space:normal;overflow-wrap:break-word;">No recent activity to show.</li>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $row): ?>
                            <?php
                            // Color coding: green for user_assigned, automation_created; blue for user_edited, automation_updated; red for user_removed, error, automation_deleted
                            $type = $row['type'];
                            if ($type === 'error' || $type === 'user_removed' || $type === 'automation_deleted' || $type === 'automation_deactivated') {
                                $dot = '#e3342f'; // red
                            } elseif ($type === 'user_edited' || $type === 'automation_updated') {
                                $dot = '#178fff'; // blue
                            } elseif ($type === 'automation_activated' || $type === 'user_assigned' || $type === 'automation_created') {
                                $dot = '#1ec97e'; // green
                            } else {
                                $dot = '#ffd166'; // yellow/other
                            }
                            ?>
                            <li>
                                <span class="activity-status">
                                    <span class="dot" style="background:<?php echo $dot; ?>;"></span>
                                    <?php echo htmlspecialchars($row['description']); ?>
                                </span>
                                <span class="time"><?php echo date('M d, H:i', strtotime($row['created_at'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </main>
</div>
<!-- Logout Confirmation Modal and Form (same as admin panel) -->
<div id="logout-modal-bg" style="display:none;position:fixed;z-index:2000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.45);align-items:center;justify-content:center;">
  <div id="logout-modal" style="background:#fff;border-radius:16px;box-shadow:0 8px 32px #00132333;padding:32px 32px 24px 32px;min-width:320px;max-width:95vw;width:350px;display:flex;flex-direction:column;gap:18px;align-items:stretch;">
    <h1 style="font-size:1.5em;font-weight:900;margin-bottom:0;color:#e3342f;text-align:center;">Logout</h1>
    <h2 style="font-size:1.2em;font-weight:800;margin-bottom:8px;color:#e3342f;text-align:center;">Are you sure you want to log out?</h2>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px;">
      <button id="cancel-logout" type="button" style="background:#e3e8f0;color:#14213d;border:none;border-radius:8px;padding:8px 18px;font-weight:700;cursor:pointer;">Cancel</button>
      <button id="confirm-logout" type="submit" form="logout-form" name="logout" value="1" style="background:#b22234;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-weight:800;cursor:pointer;transition:background 0.18s, font-weight 0.18s;">Log Out</button>
    </div>
  </div>
</div>
<form id="logout-form" method="POST" style="display:none;"><input type="hidden" name="logout" value="1" /></form>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var sidebarSignoutBtn = document.getElementById('sidebar-signout-btn');
  var logoutModalBg = document.getElementById('logout-modal-bg');
  var cancelLogout = document.getElementById('cancel-logout');
  var confirmLogout = document.getElementById('confirm-logout');
  var logoutForm = document.getElementById('logout-form');
  if (sidebarSignoutBtn && logoutModalBg && cancelLogout && confirmLogout && logoutForm) {
    sidebarSignoutBtn.addEventListener('click', function(e) {
      e.preventDefault();
      logoutModalBg.style.display = 'flex';
    });
    cancelLogout.addEventListener('click', function() {
      logoutModalBg.style.display = 'none';
    });
    logoutModalBg.addEventListener('click', function(e) {
      if (e.target === logoutModalBg) {
        logoutModalBg.style.display = 'none';
      }
    });
    confirmLogout.addEventListener('click', function(e) {
      e.preventDefault();
      logoutForm.submit();
    });
  }
});
</script>
</body>
</html> 