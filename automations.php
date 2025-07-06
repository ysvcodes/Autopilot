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

// Fetch automations for this agency
$automations = [];
try {
    $stmt = $pdo->prepare('SELECT id, name, description, automation_notes, type, pricing, pricing_model, is_trial_available, run_limit, scheduling, status, created_at FROM automations WHERE agency_id = ? ORDER BY created_at DESC');
    $stmt->execute([$agency_id]);
    while ($row = $stmt->fetch()) {
        $automations[] = $row;
    }
} catch (Exception $e) {
    $automations = [];
}

// Fetch agency clients for multi-select
$agency_clients = [];
try {
    $stmt = $pdo->prepare('SELECT id, first_name, last_name, email FROM users WHERE agency_id = ?');
    $stmt->execute([$agency_id]);
    while ($row = $stmt->fetch()) {
        $agency_clients[] = $row;
    }
} catch (Exception $e) {
    $agency_clients = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Automations</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        html, body { height: 100vh; overflow: hidden; }
        body { font-family: 'Inter', Arial, sans-serif; background: #000f1d; margin: 0; padding: 0; height: 100vh; overflow: hidden; }
        .layout { display: flex; min-height: 100vh; }
        .sidebar { background: #e5e2dd; color: #22325a; width: 240px; display: flex; flex-direction: column; align-items: flex-start; padding: 0; min-height: 100vh; }
        .sidebar nav { width: 100%; margin-top: 40px; }
        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #22325a;
            text-decoration: none;
            font-weight: 700;
            padding: 14px 32px;
            border-left: 4px solid transparent;
            transition: background 0.2s, color 0.2s, border-color 0.2s, font-weight 0.18s;
            font-size: 1.08em;
            border-radius: 12px;
            background: transparent;
            margin-bottom: 8px;
        }
        .sidebar nav a svg {
            stroke: #22325a !important;
            transition: stroke 0.2s, filter 0.2s;
        }
        .sidebar nav a.active, .sidebar nav a:hover {
            background: #178fff22;
            color: #178fff;
            border-left: 4px solid #1a8cff;
            font-weight: 900;
        }
        .sidebar nav a.active svg, .sidebar nav a:hover svg {
            stroke: #178fff !important;
            filter: drop-shadow(0 0 6px #178fff88);
        }
        .sidebar nav a:not(.active):not(:hover) {
            background: #e5e2dd;
            color: #22325a;
            border-left: 4px solid transparent;
            font-weight: 700;
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
            transition: background 0.18s, color 0.18s, font-weight 0.18s;
            border: none;
            background: none;
        }
        .sidebar .signout:hover {
            background: #b22234;
            color: #fff;
            font-weight: 900;
        }
        .main-content { flex: 1; padding: 18px 18px 0 18px; background: #000f1d; min-width: 0; height: 100vh; overflow-y: auto; color: #fff; }
        .automations-title { font-size: 2.2em; font-weight: 900; color: #fff; margin-bottom: 6px; }
        .automations-subtitle { font-size: 1.1em; color: #4a5a6a; margin-bottom: 24px; }
        .automations-table { width: 100%; border-collapse: collapse; background: #101c2c; border-radius: 16px; overflow: hidden; margin-bottom: 32px; }
        .automations-table th, .automations-table td { padding: 14px 18px; text-align: left; }
        .automations-table th { color: #7ecbff; font-weight: 800; background: #14213d; }
        .automations-table tr { border-top: 1px solid #232f3e; transition: background 0.15s; }
        .automations-table tr:hover { background: #14213d; }
        .automations-table td { color: #fff; font-weight: 600; }
        .automations-table td.status-active { color: #1ec97e; }
        .automations-table td.status-inactive { color: #e3342f; }
        .automations-table td.status-other { color: #ffd166; }
        .automation-filters .automation-filter-btn:hover {
            background: #178fff !important;
            color: #fff !important;
        }
        .switch {
          position: relative;
          display: inline-block;
          width: 38px;
          height: 22px;
        }
        .switch input {display:none;}
        .slider {
          position: absolute;
          cursor: pointer;
          top: 0; left: 0; right: 0; bottom: 0;
          background-color: #b6c6d7;
          transition: .3s;
          border-radius: 22px;
        }
        .slider:before {
          position: absolute;
          content: "";
          height: 16px;
          width: 16px;
          left: 3px;
          bottom: 3px;
          background-color: white;
          transition: .3s;
          border-radius: 50%;
        }
        input:checked + .slider {
          background-color: #178fff;
        }
        input:checked + .slider:before {
          transform: translateX(16px);
        }
        .automation-card {
            cursor: default;
            transition: all 0.2s ease-in-out;
            overflow: visible !important;
        }
        .automation-card:hover {
            box-shadow: 0 0 0 4px #178fff55, 0 4px 24px #178fff33 !important;
            border-color: #22325a !important;
            background: #1f2a3a !important;
        }
        .automation-card span.automation-tag {
            transition: box-shadow 0.18s, background 0.18s, color 0.18s;
            cursor: default;
        }
        .automation-card:hover span.automation-tag {
            box-shadow: 0 0 8px 2px #178fff88;
            background: #178fff;
            color: #fff;
        }
        .edit-btn:hover {
            background: #ffb347 !important;
            color: #fff !important;
        }
        .delete-btn:hover {
            background: #e3342f !important;
            color: #fff !important;
        }
        .next-run:hover {
            box-shadow: 0 0 8px 1px #178fff44;
            background: #19253a;
            border-radius: 8px;
            transition: box-shadow 0.18s, background 0.18s;
        }
        .play-pause-btn.playing { background: #1ec97e !important; color: #fff !important; }
        .play-pause-btn.paused { background: #e3342f !important; color: #fff !important; }
        .play-pause-btn .play-icon { display: none; }
        .play-pause-btn.paused .play-icon { display: inline; }
        .play-pause-btn.paused .pause-icon { display: none; }
        .play-pause-btn.playing .pause-icon { display: inline; }
        .play-pause-btn.playing .play-icon { display: none; }
        .active-inactive-btn.active { background: linear-gradient(90deg,#178fff 0%,#7ecbff 100%) !important; color: #fff !important; border: none !important; opacity: 1 !important; }
        .active-inactive-btn.inactive { background: linear-gradient(90deg,#178fff 0%,#7ecbff 100%) !important; color: #fff !important; border: none !important; opacity: 0.7 !important; }
        .active-inactive-btn.active .pause-icon { display: inline; }
        .active-inactive-btn.active .play-icon { display: none; }
        .active-inactive-btn.inactive .pause-icon { display: none; }
        .active-inactive-btn.inactive .play-icon { display: inline; }
        .create-automation-panel {
            width: 800px;
            min-width: 600px;
            max-width: 95vw;
            background: #14213d;
            border-radius: 24px;
            box-shadow: 0 8px 32px #00132333, 0 2px 8px #178fff11;
            padding: 20px 24px 32px 24px;
            display: flex;
            flex-direction: column;
            gap: 22px;
            align-items: stretch;
            margin-top: 130px;
            margin-bottom: 32px;
            margin-left: 0;
        }
        .create-automation-panel .panel-header {
            display: block;
            margin-bottom: 2px;
        }
        .create-automation-panel .panel-title {
            font-size: 1.45em;
            font-weight: 900;
            color: #fff;
            font-family: inherit;
            margin-bottom: 0;
        }
        .create-automation-panel .panel-subtitle {
            color: #b6c6d7;
            font-size: 0.85em;
            font-weight: 400;
            margin-bottom: 2px;
            margin-top: 2px;
        }
        .create-automation-panel .toggle-switch {
            position: relative;
            width: 44px;
            height: 24px;
            display: inline-block;
        }
        .create-automation-panel .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .create-automation-panel .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #22325a;
            transition: .3s;
            border-radius: 24px;
        }
        .create-automation-panel .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: #fff;
            transition: .3s;
            border-radius: 50%;
        }
        .create-automation-panel .toggle-switch input:checked + .slider {
            background-color: #178fff;
        }
        .create-automation-panel .toggle-switch input:checked + .slider:before {
            transform: translateX(20px);
        }
        .create-automation-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 34px;
            align-items: flex-start;
        }
        .create-automation-form label {
            color: #7ecbff;
            font-weight: 700;
            font-size: 0.92em;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .create-automation-form input,
        .create-automation-form textarea,
        .create-automation-form select {
            width: 100%;
            margin-top: 0;
            padding: 9px 10px;
            border-radius: 14px;
            border: 1.5px solid #22325a;
            background: #19253a;
            color: #fff;
            font-size: 0.92em;
            outline: none;
            transition: box-shadow 0.18s, border 0.18s;
            box-shadow: 0 1px 2px #0002;
        }
        .create-automation-form input::placeholder,
        .create-automation-form textarea::placeholder {
            font-family: Consolas, 'Liberation Mono', Menlo, Courier, monospace;
            color: #7ecbff99;
            opacity: 1;
            font-size: 0.9em;
        }
        .create-automation-form input:focus,
        .create-automation-form textarea:focus,
        .create-automation-form select:focus {
            box-shadow: 0 0 0 3px #178fff55;
            border-color: #178fff;
        }
        .create-automation-form textarea {
            min-height: 60px;
            resize: vertical;
        }
        .create-automation-form .tag-list {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
            margin-bottom: 6px;
            margin-top: 8px;
            min-height: 32px;
            max-height: 32px;
            align-items: center;
            overflow-x: auto;
        }
        .create-automation-form .tag-pill {
            background: #22325a;
            color: #7ecbff;
            border-radius: 16px;
            padding: 3px 10px 3px 14px;
            font-size: 0.9em;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            margin-right: 2px;
            margin-bottom: 2px;
            transition: background 0.18s, color 0.18s;
            cursor: pointer;
            position: relative;
        }
        .create-automation-form .tag-pill .remove-tag {
            display: none;
            margin-left: 8px;
            color: #e3342f;
            font-size: 1.1em;
            font-weight: 900;
            cursor: pointer;
            transition: color 0.18s;
        }
        .create-automation-form .tag-pill:hover .remove-tag {
            display: inline;
        }
        .create-automation-form .full-width {
            grid-column: 1 / -1;
        }
        .create-automation-form .submit-btn {
            margin-top: 18px;
            background: linear-gradient(90deg,#178fff 0%,#7ecbff 100%);
            color: #fff;
            font-weight: 900;
            border: none;
            border-radius: 14px;
            padding: 12px 0;
            font-size: 0.95em;
            cursor: pointer;
            transition: background 0.18s, color 0.18s;
            grid-column: 1 / -1;
            box-shadow: 0 2px 8px #178fff22;
        }
        .create-automation-form select[multiple] {
            min-height: 90px;
            background: #19253a;
            color: #fff;
            border-radius: 14px;
            border: 1.5px solid #22325a;
            font-size: 1em;
            padding: 13px 14px;
        }
        .create-automation-form select[multiple] option {
            background: #22325a;
            color: #7ecbff;
            border-radius: 10px;
            margin: 2px 0;
            padding: 6px 10px;
        }
        .create-automation-form label.full-width:first-of-type {
            margin-top: 0;
        }
        .assign-clients-btn {
            background: #19253a;
            color: #7ecbff;
            font-family: Consolas, 'Liberation Mono', Menlo, Courier, monospace;
            font-size: 0.92em;
            border: 1.5px solid #22325a;
            border-radius: 10px;
            padding: 10px 18px;
            cursor: pointer;
            margin-top: 0;
            margin-bottom: 0;
            transition: background 0.18s, color 0.18s, border 0.18s;
            width: 100%;
            text-align: left;
        }
        .assign-clients-btn:hover {
            background: #22325a;
            color: #fff;
            border-color: #178fff;
        }
        #assign-clients-modal-bg {
            display: none;
            position: fixed;
            z-index: 3000;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(10,20,40,0);
            align-items: center;
            justify-content: center;
            transition: background 0.4s ease;
        }
        #assign-clients-modal-bg.show {
            background: rgba(10,20,40,0.45);
        }
        #assign-clients-modal {
            background: #101c2c;
            border-radius: 18px;
            box-shadow: 0 8px 32px #00132333;
            padding: 32px 32px 24px 32px;
            min-width: 320px;
            max-width: 95vw;
            width: 400px;
            display: flex;
            flex-direction: column;
            gap: 18px;
            align-items: stretch;
            transform: translateY(30px);
            opacity: 0;
            transition: transform 0.4s ease, opacity 0.4s ease;
        }
        #assign-clients-modal.show {
            transform: translateY(0);
            opacity: 1;
        }
        #assign-clients-modal h2 {
            font-size: 1.15em;
            font-weight: 900;
            color: #7ecbff;
            margin-bottom: 0;
            text-align: center;
        }
        .assign-clients-list {
            max-height: 260px;
            overflow-y: auto;
            margin-bottom: 10px;
            font-family: Consolas, 'Liberation Mono', Menlo, Courier, monospace;
            font-size: 0.92em;
        }
        .assign-clients-list label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: #7ecbff;
            cursor: pointer;
        }
        .assign-clients-list input[type=checkbox] {
            accent-color: #178fff;
            width: 16px;
            height: 16px;
        }
        .selected-clients-list {
            margin-top: 6px;
            font-family: Consolas, 'Liberation Mono', Menlo, Courier, monospace;
            font-size: 0.92em;
            color: #7ecbff;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .selected-client-pill {
            background: #22325a;
            color: #7ecbff;
            border-radius: 14px;
            padding: 3px 10px;
            font-size: 0.9em;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 2px;
        }
        .create-automation-form input[type=number]::-webkit-inner-spin-button,
        .create-automation-form input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .create-automation-form input[type=number] {
            -moz-appearance: textfield;
        }
        .create-automation-form .pricing-note {
            color: #7ecbff99;
            font-size: 0.85em;
            margin-top: 2px;
            margin-bottom: 0;
            font-family: Consolas, 'Liberation Mono', Menlo, Courier, monospace;
        }
        .create-automation-btn {
            background: linear-gradient(90deg,#2541b2 0%,#3a5ba0 100%);
            color: #fff;
            font-weight: 900;
            border: none;
            border-radius: 14px;
            padding: 12px 32px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            box-shadow: 0 2px 8px #2541b222;
            margin-left: 24px;
        }
        .create-automation-btn:hover {
            background: linear-gradient(90deg,#3a5ba0 0%,#5c7cff 100%);
            color: #fff;
            box-shadow: 0 4px 16px #3a5ba055;
        }
        #edit-automation-modal-bg[fade-up] > #edit-automation-modal { animation: fadeUp 0.45s cubic-bezier(.4,1.4,.6,1) forwards; }
        #edit-automation-modal-bg[fade-out] > #edit-automation-modal { animation: fadeOut 0.45s cubic-bezier(.4,1.4,.6,1) forwards; }
        @keyframes fadeUp {
          0% { opacity: 0; transform: translateY(60px) scale(0.98); }
          100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes fadeOut {
          0% { opacity: 1; transform: translateY(0) scale(1); }
          100% { opacity: 0; transform: translateY(60px) scale(0.98); }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <a href="agencyspanel.php" class="logo" style="display: flex; align-items: center; justify-content: center; width: 100%; margin-bottom: 0; margin-top:0; padding-top:0;">
            <img src="assets/logo-light.png" alt="Logo" style="width: 235px; height: 235px; object-fit: contain; display: block; margin-top:0; padding-top:0;" />
        </a>
        <nav>
            <a href="agencyspanel.php"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="4" fill="none"/><path d="M9 9h6v6H9z"/></svg>Agency Overview</a>
            <a href="automations.php" class="active"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="7" width="16" height="10" rx="4"/><circle cx="8.5" cy="12" r="1"/><circle cx="15.5" cy="12" r="1"/><path d="M10 16h4"/><line x1="12" y1="3" x2="12" y2="7"/></svg>Automations</a>
            <a href="store.php"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1.5"/><circle cx="20" cy="21" r="1.5"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>My Store</a>
            <a href="clients.php"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 8-4 8-4s8 0 8 4"/></svg>Clients</a>
            <a href="logs.php"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 8h8M8 12h8M8 16h4"/></svg>Logs and Errors</a>
            <a href="inbox.php"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Inbox</a>
            <button class="signout" id="sidebar-signout-btn">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#e3342f" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Sign Out
            </button>
        </nav>
    </aside>
    <main class="main-content" style="display:flex;gap:96px;align-items:flex-start;">
        <div style="flex:1;min-width:0;">
            <h1 style="font-size:2.2em;font-weight:900;color:#fff;margin-bottom:0;">Automations</h1>
            <div class="welcome" style="font-size:1.15em;font-weight:700;margin-bottom:2px;color:#fff;">Logged in as <span style="color:#178fff;font-weight:900;"><?= htmlspecialchars($agency_name) ?></span></div>
            <div class="subtitle-agency" style="color:#4a5a6a;font-size:1.08em;font-weight:600;margin-bottom:24px;">This is you will manage, handle and assigned your <b>automations</b>.</div>
            <div style="background:#101c2c;border-radius:16px;padding:8px 16px 9px 16px;margin-bottom:32px;max-width:38vw;width:38vw;display:flex;flex-direction:column;align-items:flex-start;gap:7px;min-height:782px;">
                <h1 style="font-size:1.45em;font-weight:900;color:#fff;margin-bottom:0;">My Automations</h1>
                <div style="font-size:0.85em;color:#b6c6d7;margin-bottom:4px;">Manage and monitor your automation workflows</div>
                <div class="automation-filters" style="display:flex;gap:5px;">
                    <button class="automation-filter-btn active" data-filter="all" style="background:#1ec6ff;color:#fff;font-weight:800;padding:4px 11px;border:none;border-radius:8px;font-size:0.85em;cursor:pointer;transition:background 0.18s, color 0.18s;">All</button>
                    <button class="automation-filter-btn" data-filter="active" style="background:#19253a;color:#7ecbff;font-weight:700;padding:4px 11px;border:none;border-radius:8px;font-size:0.85em;cursor:pointer;transition:background 0.18s, color 0.18s;">Active</button>
                    <button class="automation-filter-btn" data-filter="inactive" style="background:#19253a;color:#7ecbff;font-weight:700;padding:4px 11px;border:none;border-radius:8px;font-size:0.85em;cursor:pointer;transition:background 0.18s, color 0.18s;">Inactive</button>
                </div>
                <div class="automation-search-bar" style="margin:14px 0 0 0; width:93%; position:relative;">
                    <input type="text" placeholder="Search automations..." style="width:100%;padding:10px 42px 10px 14px;border-radius:10px;border:1.5px solid #22325a;background:#19253a;color:#fff;font-size:1em;transition:box-shadow 0.18s, border 0.18s;outline:none;" onfocus="this.style.boxShadow='0 0 0 3px #178fff55';this.style.borderColor='#178fff';" onblur="this.style.boxShadow='none';this.style.borderColor='#22325a';">
                    <svg style="position:absolute;right:16px;top:50%;transform:translateY(-50%);pointer-events:none;z-index:2;background:none;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <div style="background:#1a2332;border-radius:14px;padding:0 0 0 0;margin-top:12px;width:100%;box-shadow:0 2px 8px #0002;border:1.5px solid #22325a;max-height:682px;min-height:622px;overflow-y:auto;display:flex;flex-direction:column;gap:0;align-items:center;justify-content:center;">
                    <?php if (empty($automations)): ?>
                        <div style="color:#4a5a6a;font-size:1.08em;font-weight:600;min-height:180px;display:flex;align-items:center;justify-content:center;">No Automations</div>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:8px;width:100%;padding:16px;">
                            <?php foreach ($automations as $automation): ?>
                                <div class="automation-card" data-status="<?= htmlspecialchars($automation['status']) ?>" style="background:#19253a;border:1.5px solid #22325a;border-radius:12px;padding:16px;display:flex;flex-direction:column;gap:8px;transition:all 0.2s ease-in-out;">
                                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                                        <div style="flex:1;">
                                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                                                <h3 style="font-size:1.1em;font-weight:800;color:#fff;margin:0;"><?= htmlspecialchars($automation['name']) ?></h3>
                                                <span class="automation-tag" style="background:#22325a;color:#7ecbff;padding:2px 8px;border-radius:8px;font-size:0.8em;font-weight:700;"><?= htmlspecialchars($automation['type']) ?></span>
                                                <span class="automation-tag" style="background:#1ec97e;color:#fff;padding:2px 8px;border-radius:8px;font-size:0.8em;font-weight:700;">
                                                    <?php 
                                                    $pricing_text = '';
                                                    switch($automation['pricing_model']) {
                                                        case 'free_trial':
                                                            $pricing_text = 'Free Trial';
                                                            break;
                                                        case 'first_run_free':
                                                            $pricing_text = 'First Run Free';
                                                            break;
                                                        case 'per_run':
                                                            $pricing_text = '£' . number_format($automation['pricing'], 2) . ' Per Run';
                                                            break;
                                                        default:
                                                            $pricing_text = '£' . number_format($automation['pricing'], 2) . ' ' . ucfirst(str_replace('_', ' ', $automation['pricing_model']));
                                                    }
                                                    echo $pricing_text;
                                                    ?>
                                                </span>
                                                <?php if ($automation['is_trial_available']): ?>
                                                    <span class="automation-tag" style="background:#ffd166;color:#000;padding:2px 8px;border-radius:8px;font-size:0.8em;font-weight:700;">Trial Available</span>
                                                <?php endif; ?>
                                            </div>
                                            <p style="color:#b6c6d7;font-size:0.9em;margin:0;line-height:1.4;"><?= htmlspecialchars($automation['description']) ?></p>
                                            <?php if (!empty($automation['automation_notes'])): ?>
                                                <div style="background:#22325a;border-left:3px solid #178fff;padding:8px 12px;border-radius:6px;margin-top:6px;">
                                                    <div style="color:#7ecbff;font-size:0.8em;font-weight:700;margin-bottom:4px;font-family:Consolas, 'Liberation Mono', Menlo, Courier, monospace;">📝 Private Notes:</div>
                                                    <div style="color:#b6c6d7;font-size:0.85em;line-height:1.4;font-style:italic;"><?= htmlspecialchars($automation['automation_notes']) ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div style="display:flex;gap:6px;">
                                            <button class="edit-btn" style="background:#19253a;color:#ffb347;border:1.5px solid #ffb347;border-radius:8px;padding:6px 12px;font-size:0.8em;font-weight:700;cursor:pointer;transition:background 0.18s, color 0.18s;">Edit</button>
                                            <button class="delete-btn" style="background:#19253a;color:#e3342f;border:1.5px solid #e3342f;border-radius:8px;padding:6px 12px;font-size:0.8em;font-weight:700;cursor:pointer;transition:background 0.18s, color 0.18s;">Delete</button>
                                        </div>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px;">
                                        <div style="display:flex;align-items:center;gap:12px;">
                                            <div style="color:#7ecbff;font-size:0.85em;font-weight:600;">Created: <?= date('M j, Y', strtotime($automation['created_at'])) ?></div>
                                            <div style="color:#ffd166;font-size:0.85em;font-weight:600;">Schedule: <?= ucfirst($automation['scheduling']) ?></div>
                                            <button class="active-inactive-btn <?= $automation['status'] === 'active' ? 'active' : 'inactive' ?>" style="background:linear-gradient(90deg,#178fff 0%,#7ecbff 100%);color:#fff;border:none;border-radius:8px;padding:4px 10px;font-size:0.8em;font-weight:700;cursor:pointer;transition:opacity 0.18s;">
                                                <span class="active-inactive-label"><?= ucfirst($automation['status']) ?></span>
                                                <svg class="play-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5,3 19,12 5,21"/></svg>
                                                <svg class="pause-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="create-automation-panel">
            <div class="panel-header" style="display:block;">
                <span class="panel-title">Create Automation</span>
                <div class="panel-subtitle">Create your automation. Ensure to fill in all the Fields.</div>
            </div>
            <?php
            $create_errors = [];
            $create_success = false;
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_automation'])) {
                $name = trim($_POST['name'] ?? '');
                $api = trim($_POST['api'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $automation_notes = trim($_POST['automation_notes'] ?? '');
                $type = trim($_POST['type'] ?? '');
                $pricing = trim($_POST['pricing'] ?? '');
                $pricing_model = trim($_POST['pricing_model'] ?? 'one_time');
                $is_trial_available = isset($_POST['is_trial_available']) ? 1 : 0;
                $run_limit = !empty($_POST['run_limit']) ? intval($_POST['run_limit']) : null;
                $scheduling = trim($_POST['scheduling'] ?? 'manual');
                $tags = trim($_POST['tags'] ?? '');
                $status = isset($_POST['status']) ? 'active' : 'inactive';
                $assigned_clients = $_POST['assigned_clients'] ?? [];
                if ($name === '') $create_errors[] = 'Name is required.';
                if ($api === '') $create_errors[] = 'API is required.';
                if ($pricing === '' || !is_numeric(str_replace([',','£','$'], '', $pricing))) $create_errors[] = 'Pricing must be a number.';
                if (empty($assigned_clients)) $create_errors[] = 'Please assign at least one client.';
                if (empty($create_errors)) {
                    $pricing_num = floatval(str_replace([',','£','$'], '', $pricing));
                    try {
                        $stmt = $pdo->prepare('INSERT INTO automations (agency_id, admin_id, name, api, description, automation_notes, type, pricing, pricing_model, is_trial_available, run_limit, tags, scheduling, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$agency_id, $_SESSION['admin_id'] ?? 1, $name, $api, $description, $automation_notes, $type, $pricing_num, $pricing_model, $is_trial_available, $run_limit, $tags, $scheduling, $status]);
                        $automation_id = $pdo->lastInsertId();
                        foreach ($assigned_clients as $client_id) {
                            $stmt2 = $pdo->prepare('INSERT INTO automation_users (automation_id, user_id) VALUES (?, ?)');
                            $stmt2->execute([$automation_id, $client_id]);
                        }
                        $create_success = true;
                    } catch (Exception $e) {
                        $create_errors[] = 'Error creating automation: ' . $e->getMessage();
                    }
                }
            }
            if ($create_success) {
                echo '<div style="color:#1ec97e;font-weight:800;text-align:center;margin-bottom:8px;">Automation created successfully!</div>';
            } elseif (!empty($create_errors)) {
                echo '<div style="color:#e3342f;font-weight:700;text-align:center;margin-bottom:8px;">'.implode('<br>', array_map('htmlspecialchars', $create_errors)).'</div>';
            }
            ?>
            <form method="POST" class="create-automation-form" id="create-automation-form">
                <input type="hidden" name="create_automation" value="1" />
                <label class="full-width">Name
                    <input type="text" name="name" required placeholder="Enter automation name" />
                </label>
                <label class="full-width">API
                    <input type="text" name="api" required placeholder="Place your n8n and make API here" />
                </label>
                <label>Description
                    <textarea name="description" required placeholder="Enter automation description"></textarea>
                </label>
                <label>Automation Notes
                    <textarea name="automation_notes" placeholder="Private notes (only visible to you)"></textarea>
                    <div style="color: #7ecbff99; font-size: 0.85em; margin-top: 2px; font-family: Consolas, 'Liberation Mono', Menlo, Courier, monospace;">These notes are private and only visible to you as the creator</div>
                </label>  
                <label>Automation Type
                    <select name="type" required style="font-family: Consolas, 'Liberation Mono', Menlo, Courier, monospace;">
                        <option value="Lead Gen">Lead Gen</option>
                        <option value="Scraper">Scraper</option>
                        <option value="Cold Email">Cold Email</option>
                        <option value="Data Enrichment">Data Enrichment</option>
                        <option value="CRM Sync">CRM Sync</option>
                        <option value="Social Media">Social Media</option>
                        <option value="Outreach">Outreach</option>
                        <option value="Form Filler">Form Filler</option>
                        <option value="Email Verifier">Email Verifier</option>
                        <option value="Other" selected>Other</option>
                    </select>
                </label>
                <label>Pricing (£)
                    <input type="number" name="pricing" required min="0" step="0.01" placeholder="e.g. 1200.00" id="pricing-input" />
                    <div class="pricing-note">Enter price such as (1200.00)</div>
                </label>
                <label>Pricing Model
                    <select name="pricing_model" required style="font-family: Consolas, 'Liberation Mono', Menlo, Courier, monospace;">
                        <option value="one_time" selected>One Time</option>
                        <option value="monthly">Monthly</option>
                        <option value="free_trial">Free Trial</option>
                        <option value="first_run_free">First Run Free</option>
                        <option value="per_run">Per Run</option>
                    </select>
                </label>
                <label>Trial Available
                    <div class="toggle-switch">
                        <input type="checkbox" name="is_trial_available" id="trial-toggle" />
                        <span class="slider"></span>
                    </div>
                    <div style="color: #7ecbff99; font-size: 0.85em; margin-top: 4px;">Enable trial for this automation</div>
                </label>
                <label>Run Limit
                    <input type="number" name="run_limit" min="0" placeholder="e.g. 100" />
                    <div style="color: #7ecbff99; font-size: 0.85em; margin-top: 2px; font-family: Consolas, 'Liberation Mono', Menlo, Courier, monospace;">Maximum number of runs (leave empty for unlimited)</div>
                </label>
                <label>Scheduling
                    <select name="scheduling" required style="font-family: Consolas, 'Liberation Mono', Menlo, Courier, monospace;">
                        <option value="manual" selected>Manual</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </label>
                <label>Tags
                    <div class="tags-input-container">
                        <input type="text" id="tag-input" placeholder="Add tags (max 3)" />
                        <div id="tags-display"></div>
                    </div>
                </label>
                <label>Assigned Clients
                    <button type="button" id="open-assign-clients-modal" class="assign-clients-btn">Select Clients</button>
                    <div id="selected-clients-list" class="selected-clients-list"></div>
                </label>
                <label class="full-width">Status
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div class="toggle-switch">
                                <input type="checkbox" name="status" id="status-toggle" />
                                <span class="slider"></span>
                            </div>
                            <div style="color: #7ecbff99; font-size: 0.85em; margin-top: 4px;">Toggle to activate automation</div>
                        </div>
                        <button type="submit" class="create-automation-btn">Create Automation</button>
                    </div>
                </label>
            </form>
            <script>
            // Tag input to pill display with add/remove, max 3 tags, pills below input and '×' on hover
            const tagsInput = document.getElementById('tags-input');
            const tagList = document.getElementById('tag-list');
            let tagsArr = [];
            tagsInput.addEventListener('keydown', function(e) {
                if ((e.key === 'Enter' || e.key === ',') && tagsArr.length < 3) {
                    e.preventDefault();
                    let val = tagsInput.value.trim();
                    if (val && !tagsArr.includes(val)) {
                        tagsArr.push(val);
                        renderTags();
                        tagsInput.value = '';
                    }
                } else if ((e.key === 'Enter' || e.key === ',') && tagsArr.length >= 3) {
                    e.preventDefault();
                }
            });
            function renderTags() {
                tagList.innerHTML = '';
                tagsArr.forEach((tag, idx) => {
                    let pill = document.createElement('span');
                    pill.className = 'tag-pill';
                    pill.textContent = tag;
                    // Add remove icon
                    let remove = document.createElement('span');
                    remove.className = 'remove-tag';
                    remove.textContent = '×';
                    remove.onclick = function(e) {
                        e.stopPropagation();
                        tagsArr.splice(idx, 1);
                        renderTags();
                    };
                    pill.appendChild(remove);
                    tagList.appendChild(pill);
                });
                document.getElementById('tags-input').value = tagsArr.join(', ');
                // Disable input if 3 tags
                tagsInput.disabled = tagsArr.length >= 3;
            }
            // Format pricing input as 1200.00 on blur
            const pricingInput = document.getElementById('pricing-input');
            if (pricingInput) {
                pricingInput.addEventListener('blur', function() {
                    let val = pricingInput.value;
                    if (val && !isNaN(val)) {
                        pricingInput.value = parseFloat(val).toFixed(2);
                    }
                });
            }
            </script>
        </div>
    </main>
</div>
<!-- Logout Confirmation Modal and Form (same as agency panel) -->
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
<div id="delete-modal-bg" style="display:none;position:fixed;z-index:3000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.45);align-items:center;justify-content:center;">
  <div id="delete-modal" style="background:#fff;border-radius:16px;box-shadow:0 8px 32px #00132333;padding:32px 32px 24px 32px;min-width:320px;max-width:95vw;width:370px;display:flex;flex-direction:column;gap:18px;align-items:stretch;">
    <h1 style="font-size:1.5em;font-weight:900;margin-bottom:0;color:#e3342f;text-align:center;">Delete Automation</h1>
    <h2 style="font-size:1.1em;font-weight:800;margin-bottom:8px;color:#22325a;text-align:center;">Are you sure you want to delete <span id='delete-automation-name' style='color:#178fff;'>Lead Generation Bot</span>?</h2>
    <div style="color:#e3342f;font-size:1em;font-weight:700;text-align:center;margin-bottom:8px;">This will permanently delete this automation for the following users:</div>
    <div id="delete-assigned-users" style="color:#22325a;font-size:1em;font-weight:700;text-align:center;margin-bottom:8px;">John Doe, Jane Smith</div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px;">
      <button id="cancel-delete" type="button" style="background:#e3e8f0;color:#14213d;border:none;border-radius:8px;padding:8px 18px;font-weight:700;cursor:pointer;">Cancel</button>
      <button id="confirm-delete" type="button" style="background:#e3342f;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-weight:800;cursor:pointer;transition:background 0.18s, font-weight 0.18s;">Delete</button>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var deleteBtns = document.querySelectorAll('.delete-btn');
  var deleteModalBg = document.getElementById('delete-modal-bg');
  var cancelDelete = document.getElementById('cancel-delete');
  var confirmDelete = document.getElementById('confirm-delete');
  var deleteAutomationName = document.getElementById('delete-automation-name');
  var deleteAssignedUsers = document.getElementById('delete-assigned-users');
  deleteBtns.forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      // For now, use static values. Replace with dynamic values as needed.
      deleteAutomationName.textContent = 'Lead Generation Bot';
      deleteAssignedUsers.textContent = 'John Doe, Jane Smith';
      deleteModalBg.style.display = 'flex';
    });
  });
  cancelDelete.addEventListener('click', function() {
    deleteModalBg.style.display = 'none';
  });
  deleteModalBg.addEventListener('click', function(e) {
    if (e.target === deleteModalBg) {
      deleteModalBg.style.display = 'none';
    }
  });
  confirmDelete.addEventListener('click', function(e) {
    // Add deletion logic here
    deleteModalBg.style.display = 'none';
  });
});
</script>
<div id="deactivate-modal-bg" style="display:none;position:fixed;z-index:3000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.45);align-items:center;justify-content:center;">
  <div id="deactivate-modal" style="background:#fff;border-radius:16px;box-shadow:0 8px 32px #00132333;padding:32px 32px 24px 32px;min-width:320px;max-width:95vw;width:370px;display:flex;flex-direction:column;gap:18px;align-items:stretch;">
    <h1 style="font-size:1.5em;font-weight:900;margin-bottom:0;color:#178fff;text-align:center;">Deactivate Automation</h1>
    <h2 style="font-size:1.1em;font-weight:800;margin-bottom:8px;color:#22325a;text-align:center;">Are you sure you want to deactivate <span id='deactivate-automation-name' style='color:#178fff;'>Lead Generation Bot</span>?</h2>
    <div style="color:#e3342f;font-size:1em;font-weight:700;text-align:center;margin-bottom:8px;">There are clients assigned to this automation.</div>
    <div id="deactivate-assigned-users" style="color:#22325a;font-size:1em;font-weight:700;text-align:center;margin-bottom:8px;">John Doe, Jane Smith</div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px;">
      <button id="cancel-deactivate" type="button" style="background:#e3e8f0;color:#14213d;border:none;border-radius:8px;padding:8px 18px;font-weight:700;cursor:pointer;">Cancel</button>
      <button id="confirm-deactivate" type="button" style="background:#178fff;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-weight:800;cursor:pointer;transition:background 0.18s, font-weight 0.18s;">Deactivate</button>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var activeInactiveBtns = document.querySelectorAll('.active-inactive-btn');
  var deactivateModalBg = document.getElementById('deactivate-modal-bg');
  var cancelDeactivate = document.getElementById('cancel-deactivate');
  var confirmDeactivate = document.getElementById('confirm-deactivate');
  var deactivateAutomationName = document.getElementById('deactivate-automation-name');
  var deactivateAssignedUsers = document.getElementById('deactivate-assigned-users');
  activeInactiveBtns.forEach(function(btn) {
          btn.addEventListener('click', function(e) {
        if (btn.classList.contains('active')) {
          // About to deactivate
          // Check if there are assigned users (you can replace this with actual data)
          var hasAssignedUsers = false; // Set to false for demo - no users assigned
          if (hasAssignedUsers) {
            e.preventDefault();
            deactivateAutomationName.textContent = 'Lead Generation Bot';
            deactivateAssignedUsers.textContent = 'John Doe, Jane Smith';
            deactivateModalBg.style.display = 'flex';
            // Store the button for later
            deactivateModalBg._targetBtn = btn;
            return;
          }
        }
        // Otherwise, toggle as normal
        if (btn.classList.contains('active')) {
          btn.classList.remove('active');
          btn.classList.add('inactive');
          btn.querySelector('.active-inactive-label').textContent = 'Inactive';
          // Update the card's data-status attribute
          var card = btn.closest('.automation-card');
          if (card) {
            card.setAttribute('data-status', 'inactive');
          }
        } else {
          btn.classList.remove('inactive');
          btn.classList.add('active');
          btn.querySelector('.active-inactive-label').textContent = 'Active';
          // Update the card's data-status attribute
          var card = btn.closest('.automation-card');
          if (card) {
            card.setAttribute('data-status', 'active');
          }
        }
      });
  });
  cancelDeactivate.addEventListener('click', function() {
    deactivateModalBg.style.display = 'none';
  });
  deactivateModalBg.addEventListener('click', function(e) {
    if (e.target === deactivateModalBg) {
      deactivateModalBg.style.display = 'none';
    }
  });
  confirmDeactivate.addEventListener('click', function() {
    // Actually deactivate the automation
    var btn = deactivateModalBg._targetBtn;
    if (btn) {
      btn.classList.remove('active');
      btn.classList.add('inactive');
      btn.querySelector('.active-inactive-label').textContent = 'Inactive';
      // Update the card's data-status attribute
      var card = btn.closest('.automation-card');
      if (card) {
        card.setAttribute('data-status', 'inactive');
      }
    }
    deactivateModalBg.style.display = 'none';
  });
  
  // Filter functionality
  var filterBtns = document.querySelectorAll('.automation-filter-btn');
  var automationCards = document.querySelectorAll('.automation-card');
  
  filterBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      // Remove active class from all filter buttons
      filterBtns.forEach(function(b) {
        b.classList.remove('active');
        b.style.background = '#19253a';
        b.style.color = '#7ecbff';
        b.style.fontWeight = '700';
      });
      
      // Add active class to clicked button
      btn.classList.add('active');
      btn.style.background = '#1ec6ff';
      btn.style.color = '#fff';
      btn.style.fontWeight = '800';
      
      // Get the filter value
      var filter = btn.getAttribute('data-filter');
      
      // Show/hide automation cards based on filter
      automationCards.forEach(function(card) {
        var status = card.getAttribute('data-status');
        
        if (filter === 'all' || filter === status) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
});
</script>
<div id="assign-clients-modal-bg">
    <div id="assign-clients-modal">
        <h2>Select Clients</h2>
        <div class="assign-clients-list">
            <?php foreach ($agency_clients as $client): ?>
                <label><input type="checkbox" class="assign-client-checkbox" value="<?= htmlspecialchars($client['id']) ?>"> <?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name'] . ' (' . $client['email'] . ')') ?></label>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px;">
            <button type="button" id="cancel-assign-clients" style="background:#e3e8f0;color:#14213d;border:none;border-radius:8px;padding:8px 18px;font-weight:700;cursor:pointer;">Cancel</button>
            <button type="button" id="confirm-assign-clients" style="background:#178fff;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-weight:800;cursor:pointer;transition:background 0.18s, font-weight 0.18s;">Confirm</button>
        </div>
    </div>
</div>
<script>
// Modal logic for assigning clients
const openAssignClientsBtn = document.getElementById('open-assign-clients-modal');
const assignClientsModalBg = document.getElementById('assign-clients-modal-bg');
const cancelAssignClients = document.getElementById('cancel-assign-clients');
const confirmAssignClients = document.getElementById('confirm-assign-clients');
const assignClientCheckboxes = document.querySelectorAll('.assign-client-checkbox');
const selectedClientsList = document.getElementById('selected-clients-list');
let selectedClients = [];
openAssignClientsBtn.addEventListener('click', function() {
    assignClientsModalBg.style.display = 'flex';
    // Trigger fade in animation
    setTimeout(() => {
        assignClientsModalBg.classList.add('show');
        assignClientsModalBg.querySelector('#assign-clients-modal').classList.add('show');
    }, 10);
    // Set checked state for checkboxes
    assignClientCheckboxes.forEach(cb => {
        cb.checked = selectedClients.includes(cb.value);
    });
});
cancelAssignClients.addEventListener('click', function() {
    // Trigger fade out animation
    assignClientsModalBg.classList.remove('show');
    assignClientsModalBg.querySelector('#assign-clients-modal').classList.remove('show');
    setTimeout(() => {
        assignClientsModalBg.style.display = 'none';
    }, 400);
});
assignClientsModalBg.addEventListener('click', function(e) {
    if (e.target === assignClientsModalBg) {
        // Trigger fade out animation
        assignClientsModalBg.classList.remove('show');
        assignClientsModalBg.querySelector('#assign-clients-modal').classList.remove('show');
        setTimeout(() => {
            assignClientsModalBg.style.display = 'none';
        }, 400);
    }
});
confirmAssignClients.addEventListener('click', function() {
    selectedClients = Array.from(assignClientCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
    renderSelectedClients();
    // Trigger fade out animation
    assignClientsModalBg.classList.remove('show');
    assignClientsModalBg.querySelector('#assign-clients-modal').classList.remove('show');
    setTimeout(() => {
        assignClientsModalBg.style.display = 'none';
    }, 400);
});
function renderSelectedClients() {
    selectedClientsList.innerHTML = '';
    assignClientCheckboxes.forEach(cb => {
        if (selectedClients.includes(cb.value)) {
            let pill = document.createElement('span');
            pill.className = 'selected-client-pill';
            pill.textContent = cb.parentElement.textContent.trim();
            selectedClientsList.appendChild(pill);
        }
    });
}
// On form submit, add selectedClients to a hidden input
const createAutomationForm = document.getElementById('create-automation-form');
if (createAutomationForm) {
    createAutomationForm.addEventListener('submit', function() {
        let hidden = document.getElementById('assigned-clients-hidden');
        if (hidden) hidden.remove();
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'assigned_clients[]';
        hidden.id = 'assigned-clients-hidden';
        selectedClients.forEach(val => {
            let clone = hidden.cloneNode();
            clone.value = val;
            createAutomationForm.appendChild(clone);
        });
    });
}
// Client-side validation for required fields
const requiredFields = [
    { name: 'name', selector: 'input[name="name"]' },
    { name: 'api', selector: 'input[name="api"]' },
    { name: 'description', selector: 'textarea[name="description"]' },
    { name: 'pricing', selector: 'input[name="pricing"]' },
    { name: 'assigned_clients', selector: '#selected-clients-list' }
];

function highlightField(field, error) {
    if (!field) return;
    if (error) {
        field.style.boxShadow = '0 0 0 3px #e3342f88';
        field.style.borderColor = '#e3342f';
    } else {
        field.style.boxShadow = '';
        field.style.borderColor = '';
    }
}

if (createAutomationForm) {
    createAutomationForm.addEventListener('submit', function(e) {
        let valid = true;
        // Remove previous highlights
        requiredFields.forEach(f => {
            const el = document.querySelector(f.selector);
            highlightField(el, false);
        });
        // Validate required fields
        requiredFields.forEach(f => {
            const el = document.querySelector(f.selector);
            if (f.name === 'assigned_clients') {
                if (!document.querySelectorAll('#selected-clients-list .selected-client-pill').length) {
                    highlightField(document.querySelector('#open-assign-clients-modal'), true);
                    valid = false;
                } else {
                    highlightField(document.querySelector('#open-assign-clients-modal'), false);
                }
            } else if (el && !el.value.trim()) {
                highlightField(el, true);
                valid = false;
            }
        });
        if (!valid) {
            e.preventDefault();
            // Optionally, scroll to first error
            const firstError = document.querySelector('input[style*="e3342f"], textarea[style*="e3342f"], #open-assign-clients-modal[style*="e3342f"]');
            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
    });
}
// Success animation
function showSuccessAnimation() {
    const panel = document.querySelector('.create-automation-panel');
    if (!panel) return;
    const anim = document.createElement('div');
    anim.innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;position:absolute;top:0;left:0;width:100%;height:100%;z-index:999;background:rgba(20,33,61,0.96);border-radius:24px;"><svg width="80" height="80" viewBox="0 0 80 80"><circle cx="40" cy="40" r="36" fill="#1ec97e" opacity="0.15"/><circle cx="40" cy="40" r="32" fill="#1ec97e" opacity="0.25"/><polyline points="26,42 38,54 56,30" style="fill:none;stroke:#1ec97e;stroke-width:7;stroke-linecap:round;stroke-linejoin:round;"/></svg><div style="color:#1ec97e;font-size:1.5em;font-weight:900;margin-top:18px;">Automation Created!</div></div>';
    anim.style.position = 'absolute';
    anim.style.top = '0';
    anim.style.left = '0';
    anim.style.width = '100%';
    anim.style.height = '100%';
    anim.style.zIndex = '999';
    panel.style.position = 'relative';
    panel.appendChild(anim);
    setTimeout(() => {
        anim.remove();
    }, 1800);
}
// Show animation if PHP success message is present
window.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('div[style*="color:#1ec97e"]') && document.querySelector('div[style*="Automation created successfully!"]')) {
        showSuccessAnimation();
    }
});
</script>
<!-- Edit Automation Modal -->
<div id="edit-automation-modal-bg" style="display:none;position:fixed;z-index:3000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.45);align-items:center;justify-content:center;">
  <div id="edit-automation-modal" style="background:#14213d;padding:32px 32px 24px 32px;border-radius:20px;box-shadow:0 2px 16px #22325a33;max-width:95vw;width:520px;text-align:left;">
    <div style="font-size:1.25em;font-weight:800;color:#7ecbff;margin-bottom:12px;">Edit Automation</div>
    <form id="edit-automation-form" autocomplete="off">
      <input type="hidden" name="automation_id" id="edit-automation-id">
      <label>Name
        <input type="text" name="name" id="edit-automation-name" required style="margin-top:4px;padding:10px 14px;border-radius:8px;border:1.5px solid #22325a;background:#19253a;color:#fff;width:100%;font-size:1.08em;" />
      </label>
      <label>Description
        <textarea name="description" id="edit-automation-description" required style="margin-top:4px;padding:10px 14px;border-radius:8px;border:1.5px solid #22325a;background:#19253a;color:#fff;width:100%;font-size:1.08em;"></textarea>
      </label>
      <label>Tags
        <input type="text" name="tags" id="edit-automation-tags" style="margin-top:4px;padding:10px 14px;border-radius:8px;border:1.5px solid #22325a;background:#19253a;color:#fff;width:100%;font-size:1.08em;" />
      </label>
      <label>Pricing (£)
        <input type="number" name="pricing" id="edit-automation-pricing" required min="0" step="0.01" style="margin-top:4px;padding:10px 14px;border-radius:8px;border:1.5px solid #22325a;background:#19253a;color:#fff;width:100%;font-size:1.08em;" />
      </label>
      <label>Pricing Model
        <select name="pricing_model" id="edit-automation-pricing-model" required style="margin-top:4px;padding:10px 14px;border-radius:8px;border:1.5px solid #22325a;background:#19253a;color:#fff;width:100%;font-size:1.08em;">
          <option value="one_time">One Time</option>
          <option value="monthly">Monthly</option>
          <option value="free_trial">Free Trial</option>
          <option value="first_run_free">First Run Free</option>
          <option value="per_run">Per Run</option>
        </select>
      </label>
      <label>Status
        <select name="status" id="edit-automation-status" required style="margin-top:4px;padding:10px 14px;border-radius:8px;border:1.5px solid #22325a;background:#19253a;color:#fff;width:100%;font-size:1.08em;">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </label>
      <div style="display:flex;gap:10px;margin-top:18px;">
        <button type="button" id="cancel-edit-automation-btn" style="background:#22325a;color:#fff;font-weight:700;font-size:1.08em;padding:10px 28px;border:none;border-radius:8px;cursor:pointer;">Cancel</button>
        <button type="submit" id="save-edit-automation-btn" style="background:#178fff;color:#fff;font-weight:700;font-size:1.08em;padding:10px 28px;border:none;border-radius:8px;cursor:pointer;">Save</button>
      </div>
      <div id="edit-automation-error" style="display:none;color:#e3342f;background:#f8d7da;padding:10px;border-radius:6px;text-align:center;font-weight:700;margin-top:12px;"></div>
      <div id="edit-automation-success" style="display:none;color:#4bb543;background:#d4edda;padding:10px;border-radius:6px;text-align:center;font-weight:700;margin-top:12px;"></div>
    </form>
  </div>
</div>
<script>
// --- Edit Modal Logic ---
let editAutomationModalBg = document.getElementById('edit-automation-modal-bg');
let editAutomationForm = document.getElementById('edit-automation-form');
let editAutomationError = document.getElementById('edit-automation-error');
let editAutomationSuccess = document.getElementById('edit-automation-success');
let saveEditAutomationBtn = document.getElementById('save-edit-automation-btn');
let cancelEditAutomationBtn = document.getElementById('cancel-edit-automation-btn');
document.querySelectorAll('.edit-btn').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    const card = btn.closest('.automation-card');
    document.getElementById('edit-automation-id').value = card.getAttribute('data-id');
    document.getElementById('edit-automation-name').value = card.querySelector('h3').textContent.trim();
    document.getElementById('edit-automation-description').value = card.querySelector('p').textContent.trim();
    document.getElementById('edit-automation-tags').value = Array.from(card.querySelectorAll('.automation-tag')).map(t=>t.textContent.trim()).join(', ');
    document.getElementById('edit-automation-pricing').value = card.getAttribute('data-pricing');
    document.getElementById('edit-automation-pricing-model').value = card.getAttribute('data-pricing-model');
    document.getElementById('edit-automation-status').value = card.getAttribute('data-status');
    editAutomationError.style.display = 'none';
    editAutomationSuccess.style.display = 'none';
    editAutomationModalBg.style.display = 'flex';
    editAutomationModalBg.removeAttribute('fade-out');
    editAutomationModalBg.setAttribute('fade-up', '');
  });
});
cancelEditAutomationBtn.addEventListener('click', function() {
  editAutomationModalBg.removeAttribute('fade-up');
  editAutomationModalBg.setAttribute('fade-out', '');
  setTimeout(function() {
    editAutomationModalBg.style.display = 'none';
    editAutomationModalBg.removeAttribute('fade-out');
  }, 450);
});
editAutomationModalBg.addEventListener('click', function(e) {
  if (e.target === editAutomationModalBg) {
    editAutomationModalBg.removeAttribute('fade-up');
    editAutomationModalBg.setAttribute('fade-out', '');
    setTimeout(function() {
      editAutomationModalBg.style.display = 'none';
      editAutomationModalBg.removeAttribute('fade-out');
    }, 450);
  }
});
editAutomationForm.addEventListener('submit', function(e) {
  e.preventDefault();
  saveEditAutomationBtn.disabled = true;
  editAutomationError.style.display = 'none';
  editAutomationSuccess.style.display = 'none';
  const formData = new FormData(editAutomationForm);
  fetch('agency_handler/edit_automation.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    saveEditAutomationBtn.disabled = false;
    if (data.success) {
      editAutomationSuccess.textContent = 'Automation updated successfully!';
      editAutomationSuccess.style.display = 'block';
      setTimeout(() => {
        editAutomationModalBg.removeAttribute('fade-up');
        editAutomationModalBg.setAttribute('fade-out', '');
        setTimeout(function() {
          editAutomationModalBg.style.display = 'none';
          editAutomationModalBg.removeAttribute('fade-out');
          location.reload();
        }, 450);
      }, 900);
    } else {
      editAutomationError.textContent = data.message || 'Failed to update automation.';
      editAutomationError.style.display = 'block';
    }
  })
  .catch(() => {
    saveEditAutomationBtn.disabled = false;
    editAutomationError.textContent = 'Server error. Please try again.';
    editAutomationError.style.display = 'block';
  });
});
// Make My Automations non-horizontally-scrollable
const myAutomationsBox = document.querySelector('[style*="My Automations"]');
if (myAutomationsBox) {
  myAutomationsBox.style.overflowX = 'visible';
  myAutomationsBox.style.flexWrap = 'wrap';
</body>
</html> 