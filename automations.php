<?php
$active_page = 'automations';
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
    $stmt = $pdo->prepare('SELECT id, name, description, automation_notes, type, pricing, pricing_model, is_trial_available, run_limit, scheduling, status, created_at, tags, api FROM automations WHERE agency_id = ? ORDER BY created_at DESC');
    $stmt->execute([$agency_id]);
    while ($row = $stmt->fetch()) {
        $automations[] = $row;
    }
} catch (Exception $e) {
    $automations = [];
}

// Fetch 
// lients for multi-select
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
            width: 680px !important; /* reduced width */
            min-height: 200px !important; /* increased height */
            height: 100% !important;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
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
            gap: 10px 18px;
            column-gap: 28px;
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
        .create-automation-form .tags-input-container {
            position: relative;
        }
        .create-automation-form .tag-list {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 6px;
            margin-top: 8px;
            min-height: 32px;
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
        @keyframes fadeInUp {
          from { opacity: 0; transform: translateY(40px); }
          to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeOutUp {
          from { opacity: 1; transform: translateY(0); }
          to { opacity: 0; transform: translateY(-40px); }
        }
        #edit-modal {
          opacity: 0;
          transform: translateY(40px);
          transition: none;
        }
        #edit-modal.show {
          animation: fadeInUp 0.35s cubic-bezier(.4,1.7,.7,1) forwards;
        }
        #edit-modal.hide {
          animation: fadeOutUp 0.35s cubic-bezier(.4,1.7,.7,1) forwards;
        }
        .users-btn { background:#178fff !important; color:#fff !important; border:1.5px solid #178fff !important; }
        .users-btn:hover { background:#7ecbff !important; color:#14213d !important; border-color:#7ecbff !important; }
        @keyframes fadeInUpModal {
          from { opacity: 0; transform: translateY(40px); }
          to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeOutUpModal {
          from { opacity: 1; transform: translateY(0); }
          to { opacity: 0; transform: translateY(-40px); }
        }
        #assign-users-modal.fade-in {
          animation: fadeInUpModal 0.35s cubic-bezier(0.4,1.4,0.6,1) forwards;
        }
        #assign-users-modal.fade-out {
          animation: fadeOutUpModal 0.35s cubic-bezier(0.4,1.4,0.6,1) forwards;
        }
        .automation-tag-bubble:hover {
          background: #178fff !important;
          color: #fff !important;
        }
        .automation-cards-list, #automation-cards-list, .main-content, body {
          overflow-x: hidden !important;
        }
    </style>
</head>
<body>
<div class="layout">
    <?php $active_page = 'automations'; include 'agency_sidebar.php'; ?>
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
                <div style="background:#1a2332;border-radius:14px;padding:0 0 0 0;margin-top:12px;width:100%;box-shadow:0 2px 8px #0002;border:1.5px solid #22325a;max-height:682px;min-height:622px;overflow-y:auto;display:flex;flex-direction:column;gap:0;align-items:center;justify-content:flex-start;">
                    <?php if (empty($automations)): ?>
                        <div style="color:#4a5a6a;font-size:1.08em;font-weight:600;min-height:180px;display:flex;align-items:center;justify-content:center;">No Automations</div>
                    <?php else: ?>
                        <div id="automation-cards-list" style="display:flex;flex-direction:column;gap:8px;width:100%;padding:16px;align-items:center;">
                            <?php foreach ($automations as $automation): ?>
                                <div class="automation-card" data-id="<?= htmlspecialchars($automation['id']) ?>" data-status="<?= htmlspecialchars($automation['status']) ?>" data-pricing="<?= htmlspecialchars($automation['pricing']) ?>" data-pricing-model="<?= htmlspecialchars($automation['pricing_model']) ?>" data-run-limit="<?= htmlspecialchars($automation['run_limit']) ?>" data-api="<?= htmlspecialchars($automation['api'] ?? '') ?>" style="background:#19253a;border:1.5px solid #22325a;border-radius:12px;padding:16px;display:flex;flex-direction:column;gap:8px;transition:all 0.2s ease-in-out;">
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
                                            <?php if (!empty($automation['tags'])): ?>
                                                <div style="color:#7ecbff;font-weight:700;font-size:0.98em;margin:10px 0 2px 0;">Automation Tags:</div>
                                                <div class="automation-tags-list" style="margin:0 0 0 0;display:flex;gap:8px;flex-wrap:wrap;">
                                                    <?php foreach (explode(',', $automation['tags']) as $tag): ?>
                                                        <span class="automation-tag-bubble" style="background:#19253a;color:#7ecbff;padding:2px 10px;border-radius:12px;font-size:0.95em;font-weight:700;display:inline-block;transition:background 0.18s, color 0.18s;cursor:pointer;border:1.5px solid #7ecbff;">#<?= htmlspecialchars(trim($tag)) ?></span>
                                                    <?php endforeach; ?>
                                        </div>
                                            <?php endif; ?>
                                        </div>
                                        <div style="display:flex;gap:6px;flex-direction:column;align-items:flex-end;">
                                        <div style="display:flex;gap:6px;">
                                            <button class="edit-btn" style="background:#19253a;color:#ffb347;border:1.5px solid #ffb347;border-radius:8px;padding:6px 12px;font-size:0.8em;font-weight:700;cursor:pointer;transition:background 0.18s, color 0.18s;">Edit</button>
                                            <button class="delete-btn" style="background:#19253a;color:#e3342f;border:1.5px solid #e3342f;border-radius:8px;padding:6px 12px;font-size:0.8em;font-weight:700;cursor:pointer;transition:background 0.18s, color 0.18s;">Delete</button>
                                          </div>
                                          <button class="users-btn" style="background:#178fff;color:#fff;border:1.5px solid #178fff;border-radius:8px;padding:6px 12px;font-size:0.8em;font-weight:700;cursor:pointer;transition:background 0.18s, color 0.18s;margin-top:6px;width:100%;max-width:100%;display:block;">Users</button>
                                        </div>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px;">
                                        <div style="display:flex;align-items:center;gap:12px;">
                                            <div style="color:#7ecbff;font-size:0.85em;font-weight:600;">Created: <?= date('M j, Y', strtotime($automation['created_at'])) ?></div>
                                            <div style="color:#ffd166;font-size:0.85em;font-weight:600;">Schedule: <?= ucfirst($automation['scheduling']) ?></div>
                                            <button class="active-inactive-btn <?= $automation['status'] === 'active' ? 'active' : 'inactive' ?>" style="background:linear-gradient(90deg,#178fff 0%,#7ecbff 100%);color:#fff;border:none;border-radius:8px;padding:4px 10px;font-size:0.8em;font-weight:700;cursor:pointer;transition:opacity 0.18s;display:flex;align-items:center;gap:6px;">
                                                <span class="active-inactive-label"><?= ucfirst($automation['status']) ?></span>
                                                <svg class="play-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:<?= $automation['status'] === 'active' ? 'inline' : 'none' ?>;">
                                                    <polygon points="5,3 19,12 5,21" fill="#fff" />
                                                </svg>
                                                <svg class="pause-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:<?= $automation['status'] === 'inactive' ? 'inline' : 'none' ?>;">
                                                    <rect x="6" y="4" width="4" height="16" rx="2" fill="#fff"/>
                                                    <rect x="14" y="4" width="4" height="16" rx="2" fill="#fff"/>
                                                </svg>
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
                $api_placeholder = 'https://your-n8n-instance.com/webhook/property-leads';
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
                if ($api === '' || $api === $api_placeholder) $create_errors[] = 'API is required and must be unique.';
                if ($pricing === '' || !is_numeric(str_replace([',','£','$'], '', $pricing))) $create_errors[] = 'Pricing must be a number.';
                if (empty($assigned_clients)) $create_errors[] = 'Please assign at least one client.';
                if (empty($create_errors)) {
                    $pricing_num = floatval(str_replace([',','£','$'], '', $pricing));
                    try {
                        // Check for duplicate API before insert
                        $stmt = $pdo->prepare('SELECT COUNT(*) FROM automations WHERE api = ?');
                        $stmt->execute([$api]);
                        if ($stmt->fetchColumn() > 0) {
                            $create_errors[] = 'API must be unique. This API is already used by another automation.';
                        } else {
                            $stmt = $pdo->prepare('INSERT INTO automations (agency_id, admin_id, name, api, description, automation_notes, type, pricing, pricing_model, is_trial_available, run_limit, tags, scheduling, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                            $stmt->execute([$agency_id, $_SESSION['admin_id'] ?? 1, $name, $api, $description, $automation_notes, $type, $pricing_num, $pricing_model, $is_trial_available, $run_limit, $tags, $scheduling, $status]);
                            $automation_id = $pdo->lastInsertId();
                            foreach ($assigned_clients as $client_id) {
                                $stmt2 = $pdo->prepare('INSERT INTO automation_users (automation_id, user_id) VALUES (?, ?)');
                                $stmt2->execute([$automation_id, $client_id]);
                            }
                            // Log automation creation activity
                            $stmt3 = $pdo->prepare('INSERT INTO activity_log (agency_id, admin_id, type, description) VALUES (?, ?, ?, ?)');
                            $stmt3->execute([$agency_id, $_SESSION['admin_id'] ?? 1, 'automation_created', "Automation '$name' created successfully"]);
                            $create_success = true;
                        }
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
                        <div id="tag-list" class="tag-list"></div>
                        <input type="hidden" name="tags" id="tags-hidden" />
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
            const tagsInput = document.getElementById('tag-input');
            const tagList = document.getElementById('tag-list');
            const tagsHidden = document.getElementById('tags-hidden');
            let tagsArr = [];
            
            if (tagsInput && tagList && tagsHidden) {
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
                    // Update hidden input with tags
                    tagsHidden.value = tagsArr.join(', ');
                // Disable input if 3 tags
                tagsInput.disabled = tagsArr.length >= 3;
                    if (tagsArr.length >= 3) {
                        tagsInput.placeholder = 'Maximum 3 tags reached';
                    } else {
                        tagsInput.placeholder = 'Add tags (max 3)';
            }
                }
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
      const card = btn.closest('.automation-card');
      const automationId = card.getAttribute('data-id');
      const automationName = card.querySelector('h3').textContent.trim();
      // Fetch assigned users via AJAX
      fetch('agency_handler/fetch_assigned_users.php?automation_id=' + encodeURIComponent(automationId))
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            if (data.users.length > 0) {
              deleteAssignedUsers.textContent = data.users.map(u => u.name + ' (' + u.email + ')').join(', ');
            } else {
              deleteAssignedUsers.textContent = 'No assigned users';
            }
          } else {
            deleteAssignedUsers.textContent = 'Error fetching users';
          }
        })
        .catch(() => {
          deleteAssignedUsers.textContent = 'Error fetching users';
        });
      // Store automation ID for deletion
      deleteModalBg.setAttribute('data-automation-id', automationId);
      deleteAutomationName.textContent = automationName;
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
    const automationId = deleteModalBg.getAttribute('data-automation-id');
    if (!automationId) {
      alert('Error: Automation ID not found');
      return;
    }
    
    // Send delete request
    const formData = new FormData();
    formData.append('automation_id', automationId);
    
    fetch('agency_handler/delete_automation.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Remove the card from the UI
        const card = document.querySelector(`[data-id="${automationId}"]`);
        if (card) {
          card.remove();
        }
        deleteModalBg.style.display = 'none';
      } else {
        alert('Error deleting automation: ' + (data.message || 'Unknown error'));
      }
    })
    .catch(error => {
      alert('Error deleting automation: ' + error.message);
    });
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
            e.preventDefault();
          var card = btn.closest('.automation-card');
      var automationId = card.getAttribute('data-id');
      var currentStatus = btn.classList.contains('active') ? 'active' : 'inactive';
      var newStatus = currentStatus === 'active' ? 'inactive' : 'active';
      // Optimistically update UI
      btn.classList.toggle('active');
      btn.classList.toggle('inactive');
      btn.querySelector('.active-inactive-label').textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
      if (card) card.setAttribute('data-status', newStatus);
      // Toggle icon display
      var playIcon = btn.querySelector('.play-icon');
      var pauseIcon = btn.querySelector('.pause-icon');
      if (newStatus === 'active') {
        playIcon.style.display = 'inline';
        pauseIcon.style.display = 'none';
      } else {
        playIcon.style.display = 'none';
        pauseIcon.style.display = 'inline';
      }
      // Send AJAX request to update status in DB
      fetch('agency_handler/edit_automation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'automation_id=' + encodeURIComponent(automationId) + '&status=' + encodeURIComponent(newStatus)
      })
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          // Revert UI if failed
          btn.classList.toggle('active');
          btn.classList.toggle('inactive');
          btn.querySelector('.active-inactive-label').textContent = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
          if (card) card.setAttribute('data-status', currentStatus);
          // Revert icon
          if (currentStatus === 'active') {
            playIcon.style.display = 'inline';
            pauseIcon.style.display = 'none';
        } else {
            playIcon.style.display = 'none';
            pauseIcon.style.display = 'inline';
          }
          alert('Failed to update status: ' + (data.message || 'Unknown error'));
        } else {
          // Show notification on success
          var notif = document.createElement('div');
          notif.textContent = data.notificationMsg || (newStatus === 'active' ? 'Automation has been activated' : 'Automation deactivated');
          notif.style.position = 'fixed';
          notif.style.top = '32px';
          notif.style.right = '32px';
          notif.style.zIndex = 9999;
          notif.style.padding = '16px 28px';
          notif.style.borderRadius = '12px';
          notif.style.fontWeight = '800';
          notif.style.fontSize = '1.1em';
          notif.style.boxShadow = '0 2px 16px #0008';
          notif.style.color = '#fff';
          notif.style.background = (data.notificationType === 'success' || newStatus === 'active') ? '#1ec97e' : '#e3342f';
          notif.style.transition = 'opacity 0.3s';
          notif.style.opacity = '1';
          document.body.appendChild(notif);
          setTimeout(function() {
            notif.style.opacity = '0';
            setTimeout(function() { notif.remove(); }, 400);
          }, 2200);
        }
      })
      .catch(() => {
        // Revert UI if failed
        btn.classList.toggle('active');
        btn.classList.toggle('inactive');
        btn.querySelector('.active-inactive-label').textContent = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
        if (card) card.setAttribute('data-status', currentStatus);
        // Revert icon
        if (currentStatus === 'active') {
          playIcon.style.display = 'inline';
          pauseIcon.style.display = 'none';
        } else {
          playIcon.style.display = 'none';
          pauseIcon.style.display = 'inline';
        }
        alert('Failed to update status. Please try again.');
      });
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
  var automationCardsList = document.getElementById('automation-cards-list');
  // Add or find the no-automations message element
  var noAutomationsMsg = document.getElementById('no-automations-message');
  if (!noAutomationsMsg && automationCardsList) {
    noAutomationsMsg = document.createElement('div');
    noAutomationsMsg.id = 'no-automations-message';
    noAutomationsMsg.style.color = '#4a5a6a';
    noAutomationsMsg.style.fontSize = '1.08em';
    noAutomationsMsg.style.fontWeight = '600';
    noAutomationsMsg.style.minHeight = '180px';
    noAutomationsMsg.style.display = 'none';
    noAutomationsMsg.style.alignItems = 'center';
    noAutomationsMsg.style.justifyContent = 'center';
    noAutomationsMsg.textContent = 'No Automations';
    automationCardsList.parentNode.insertBefore(noAutomationsMsg, automationCardsList);
  }
  function updateNoAutomationsMsg() {
    if (!automationCardsList) return;
    var anyVisible = false;
    automationCards.forEach(function(card) {
      if (card.style.display !== 'none') anyVisible = true;
    });
    if (!anyVisible) {
      if (noAutomationsMsg) noAutomationsMsg.style.display = 'flex';
      automationCardsList.style.display = 'none';
    } else {
      if (noAutomationsMsg) noAutomationsMsg.style.display = 'none';
      automationCardsList.style.display = 'flex';
    }
  }
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
      updateNoAutomationsMsg();
    });
  });
  // Initial check in case default filter is not 'all'
  updateNoAutomationsMsg();
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
<!-- Edit Modal (empty with fade animations) -->
<div id="edit-modal-bg" style="display:none;position:fixed;z-index:3000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.45);align-items:center;justify-content:center;">
  <div id="edit-modal" style="background:#14213d !important;padding:32px 48px;border-radius:20px;box-shadow:0 2px 16px #22325a33;max-width:95vw;width:600px;min-width:400px;text-align:left;">
    <span class="panel-title" style="font-size:1.45em;font-weight:900;color:#fff;font-family:inherit;margin-bottom:0;">Edit Automation</span>
    <div style="font-size:0.95em;font-weight:500;color:rgba(255,255,255,0.7);margin-bottom:4px;">Edit your automation details here.</div>
    <form class="create-automation-form" id="edit-automation-form" style="background:transparent !important;">
      <input type="hidden" name="edit_automation" value="1" />
      <label class="full-width">Name
        <input type="text" id="edit-name" name="name" required placeholder="Enter automation name" />
      </label>
      <label class="full-width">API
        <input type="text" id="edit-api" name="api" required placeholder="Place your n8n and make API here" />
      </label>
      <label>Description
        <textarea id="edit-description" name="description" required placeholder="Enter automation description"></textarea>
      </label>
      <label>Automation Notes
        <textarea id="edit-automation-notes" name="automation_notes" placeholder="Private notes (only visible to you)"></textarea>
        <!-- Helper text removed for edit modal -->
      </label>
      <label>Automation Type
        <select id="edit-type" name="type" required style="font-family: Consolas, 'Liberation Mono', Menlo, Courier, monospace;">
          <option value="Lead Gen">Lead Gen</option>
          <option value="Scraper">Scraper</option>
          <option value="Cold Email">Cold Email</option>
          <option value="Data Enrichment">Data Enrichment</option>
          <option value="CRM Sync">CRM Sync</option>
          <option value="Social Media">Social Media</option>
          <option value="Outreach">Outreach</option>
          <option value="Form Filler">Form Filler</option>
          <option value="Email Verifier">Email Verifier</option>
          <option value="Other">Other</option>
        </select>
      </label>
      <label>Pricing (£)
        <input type="number" id="edit-pricing" name="pricing" required min="0" step="0.01" placeholder="e.g. 1200.00" />
        <div class="pricing-note">Enter price such as (1200.00)</div>
      </label>
      <label>Pricing Model
        <select id="edit-pricing-model" name="pricing_model" required style="font-family: Consolas, 'Liberation Mono', Menlo, Courier, monospace;">
          <option value="one_time">One Time</option>
          <option value="monthly">Monthly</option>
          <option value="free_trial">Free Trial</option>
          <option value="first_run_free">First Run Free</option>
          <option value="per_run">Per Run</option>
        </select>
      </label>
      <label>Run Limit
        <input type="number" id="edit-run-limit" name="run_limit" min="0" placeholder="e.g. 100" />
        <div style="color: #7ecbff99; font-size: 0.85em; margin-top: 2px; font-family: Consolas, 'Liberation Mono', Menlo, Courier, monospace;">Maximum number of runs (leave empty for unlimited)</div>
      </label>
      <label>Scheduling
        <select id="edit-scheduling" name="scheduling" required style="font-family: Consolas, 'Liberation Mono', Menlo, Courier, monospace;">
          <option value="manual">Manual</option>
          <option value="daily">Daily</option>
          <option value="weekly">Weekly</option>
          <option value="monthly">Monthly</option>
        </select>
      </label>
      <label>Tags
        <div class="tags-input-container">
          <input type="text" id="edit-tag-input" placeholder="Add tags (max 3)" />
          <div id="edit-tag-list" class="tag-list"></div>
          <input type="hidden" name="tags" id="edit-tags-hidden" />
      </div>
      </label>
      <div style="display: flex; justify-content: flex-end; grid-column: 1 / -1; margin-top: 18px;">
        <button type="submit" class="create-automation-btn">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const editModalBg = document.getElementById('edit-modal-bg');
  const editModal = document.getElementById('edit-modal');
  const editForm = document.getElementById('edit-automation-form');
  let currentEditId = null;

  // Helper to get text content safely
  function getTextContent(el, selector) {
    const found = el.querySelector(selector);
    return found ? found.textContent.trim() : '';
  }

  // Edit button click handler
document.querySelectorAll('.edit-btn').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    const card = btn.closest('.automation-card');
      currentEditId = card.getAttribute('data-id');
      // Pre-fill fields
      document.getElementById('edit-name').value = getTextContent(card, 'h3');
      document.getElementById('edit-api').value = card.getAttribute('data-api') || '';
      document.getElementById('edit-description').value = getTextContent(card, 'p');
      // Automation Notes
      let notes = '';
      // Find the div with background:#22325a (private notes container)
      const notesContainer = Array.from(card.querySelectorAll('div')).find(div => div.style.background === 'rgb(34, 50, 90)');
      if (notesContainer) {
        // The actual note is in the last child div
        const noteDivs = notesContainer.querySelectorAll('div');
        if (noteDivs.length > 1) {
          notes = noteDivs[noteDivs.length-1].textContent.trim();
        }
      }
      document.getElementById('edit-automation-notes').value = notes;
      // Type
      const typeTag = card.querySelector('.automation-tag');
      document.getElementById('edit-type').value = typeTag ? typeTag.textContent.trim() : '';
      // Pricing
      document.getElementById('edit-pricing').value = card.getAttribute('data-pricing') || '';
      // Pricing Model
      document.getElementById('edit-pricing-model').value = card.getAttribute('data-pricing-model') || 'one_time';
      // Run Limit
      document.getElementById('edit-run-limit').value = card.getAttribute('data-run-limit') || '';
      // Scheduling
      document.getElementById('edit-scheduling').value = card.getAttribute('data-scheduling') || 'manual';
      // Tags
      let tags = '';
      const tagBubbles = card.querySelectorAll('.automation-tag-bubble');
      if (tagBubbles.length) {
        tags = Array.from(tagBubbles).map(t => t.textContent.replace('#','').trim()).join(', ');
      }
      document.getElementById('edit-tag-input').value = '';
      document.getElementById('edit-tags-hidden').value = tags;
      // Render tag pills
      const tagList = document.getElementById('edit-tag-list');
      tagList.innerHTML = '';
      if (tags) {
        tags.split(',').slice(0,3).forEach((tag, idx) => {
          let pill = document.createElement('span');
          pill.className = 'tag-pill';
          pill.textContent = tag.trim();
          // Add remove icon
          let remove = document.createElement('span');
          remove.className = 'remove-tag';
          remove.textContent = '×';
          remove.onclick = function(e) {
            e.stopPropagation();
            let arr = document.getElementById('edit-tags-hidden').value.split(',').map(t=>t.trim()).filter(Boolean);
            arr.splice(idx, 1);
            document.getElementById('edit-tags-hidden').value = arr.join(', ');
            pill.remove();
          };
          pill.appendChild(remove);
          tagList.appendChild(pill);
        });
      }
      // Show modal
      editModalBg.style.display = 'flex';
      editModal.classList.remove('hide');
      setTimeout(() => editModal.classList.add('show'), 10);
    });
  });

  // Tag input logic for edit modal (max 3 tags)
  const editTagInput = document.getElementById('edit-tag-input');
  const editTagList = document.getElementById('edit-tag-list');
  const editTagsHidden = document.getElementById('edit-tags-hidden');
  if (editTagInput && editTagList && editTagsHidden) {
    function renderEditTags() {
      let arr = editTagsHidden.value.split(',').map(t=>t.trim()).filter(Boolean);
      editTagList.innerHTML = '';
      arr.slice(0,3).forEach((tag, idx) => {
        let pill = document.createElement('span');
        pill.className = 'tag-pill';
        pill.textContent = tag;
        let remove = document.createElement('span');
        remove.className = 'remove-tag';
        remove.textContent = '×';
        remove.onclick = function(e) {
          e.stopPropagation();
          let arr2 = editTagsHidden.value.split(',').map(t=>t.trim()).filter(Boolean);
          arr2.splice(idx, 1);
          editTagsHidden.value = arr2.join(', ');
          renderEditTags();
        };
        pill.appendChild(remove);
        editTagList.appendChild(pill);
      });
      editTagInput.disabled = arr.length >= 3;
      editTagInput.placeholder = arr.length >= 3 ? 'Maximum 3 tags reached' : 'Add tags (max 3)';
    }
    editTagInput.addEventListener('keydown', function(e) {
      let arr = editTagsHidden.value.split(',').map(t=>t.trim()).filter(Boolean);
      if ((e.key === 'Enter' || e.key === ',') && arr.length < 3) {
        e.preventDefault();
        let val = editTagInput.value.trim();
        if (val && !arr.includes(val)) {
          arr.push(val);
          editTagsHidden.value = arr.join(', ');
          renderEditTags();
          editTagInput.value = '';
        }
      } else if ((e.key === 'Enter' || e.key === ',') && arr.length >= 3) {
        e.preventDefault();
      }
    });
    // Initial render
    renderEditTags();
    // Also re-render when modal opens
    editModalBg.addEventListener('click', renderEditTags);
  }

  // Save changes (AJAX submit)
  if (editForm) {
    editForm.addEventListener('submit', function(e) {
      e.preventDefault();
      if (!currentEditId) return;
      // Always sync tag pills to hidden input before submit
      if (editTagsHidden && editTagList) {
        let pills = Array.from(editTagList.querySelectorAll('.tag-pill'));
        let tags = pills.map(p => p.childNodes[0].nodeValue.trim()).filter(Boolean);
        editTagsHidden.value = tags.join(', ');
      }
      const formData = new FormData(editForm);
      formData.append('automation_id', currentEditId);
      fetch('agency_handler/edit_automation.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // Update the automation card in the UI
          const card = document.querySelector('.automation-card[data-id="' + currentEditId + '"]');
          if (card) {
            // Update tags visually
            const tagsVal = editTagsHidden.value;
            let tagsHtml = '';
            if (tagsVal) {
              tagsHtml = tagsVal.split(',').map(t => `<span class="automation-tag-bubble" style="background:#19253a;color:#7ecbff;padding:2px 10px;border-radius:12px;font-size:0.95em;font-weight:700;display:inline-block;transition:background 0.18s, color 0.18s;cursor:pointer;border:1.5px solid #7ecbff;">#${t.trim()}</span>`).join(' ');
            }
            const tagsListDiv = card.querySelector('.automation-tags-list');
            if (tagsListDiv) tagsListDiv.innerHTML = tagsHtml;
          }
          editModal.classList.remove('show');
          editModal.classList.add('hide');
          setTimeout(() => {
            editModalBg.style.display = 'none';
            editModal.classList.remove('hide');
          }, 350);
        } else {
          alert('Failed to update automation: ' + (data.message || 'Unknown error'));
        }
      })
      .catch(() => {
        alert('Failed to update automation. Please try again.');
      });
    });
  }

  // Close modal function (fade out)
  function closeModal() {
    editModal.classList.remove('show');
    editModal.classList.add('hide');
    setTimeout(() => {
      editModalBg.style.display = 'none';
      editModal.classList.remove('hide');
    }, 350);
  }

  // Close button and background click handlers
  const closeEditModal = document.getElementById('close-edit-modal');
  if (closeEditModal) {
    closeEditModal.addEventListener('click', closeModal);
  }
  editModalBg.addEventListener('click', function(e) {
    if (e.target === editModalBg) closeModal();
  });
});
</script>
<!-- Assign Users Modal (modular, fade in/out) -->
<div id="assign-users-modal-bg" style="display:none;position:fixed;z-index:3000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.45);align-items:center;justify-content:center;">
  <div id="assign-users-modal" style="background:#14213d;padding:40px 40px 28px 40px;border-radius:20px;box-shadow:0 2px 16px #22325a33;max-width:95vw;width:420px;text-align:left;opacity:0;transform:translateY(40px);">
    <div style="font-size:2.2em;font-weight:900;color:#fff;font-family:'Inter', Arial, sans-serif;margin-bottom:2px;">Assign Users</div>
    <div style="font-size:1.08em;color:#b6c6d7;font-weight:600;font-family:'Inter', Arial, sans-serif;opacity:0.7;margin-bottom:8px;">Select which users should have access to this automation.</div>
    <div id="assign-users-automation-name" style="font-size:1.08em;font-style:italic;color:#7ecbff;font-weight:700;font-family:'Inter', Arial, sans-serif;margin-bottom:14px;"></div>
    <div style="margin-bottom:10px;"><label style="font-weight:600;color:#7ecbff;"><input type="checkbox" id="select-all-assign-users" style="margin-right:6px;">Select All</label></div>
    <div id="assign-users-list" style="margin-bottom:18px;min-height:48px;"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px;">
      <button type="button" id="close-assign-users-modal" style="background:#22325a;color:#fff;font-weight:700;font-size:1.08em;padding:10px 28px;border:none;border-radius:8px;cursor:pointer;">Close</button>
      <button type="button" id="assign-users-btn" style="background:#178fff;color:#fff;font-weight:700;font-size:1.08em;padding:10px 28px;border:none;border-radius:8px;cursor:pointer;">Assign</button>
    </div>
  </div>
</div>
<style>
@keyframes fadeInUpModal {
  from { opacity: 0; transform: translateY(40px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeOutUpModal {
  from { opacity: 1; transform: translateY(0); }
  to { opacity: 0; transform: translateY(-40px); }
}
#assign-users-modal.fade-in {
  animation: fadeInUpModal 0.35s cubic-bezier(0.4,1.4,0.6,1) forwards;
}
#assign-users-modal.fade-out {
  animation: fadeOutUpModal 0.35s cubic-bezier(0.4,1.4,0.6,1) forwards;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const assignUsersModalBg = document.getElementById('assign-users-modal-bg');
  const assignUsersModal = document.getElementById('assign-users-modal');
  const closeAssignUsersModal = document.getElementById('close-assign-users-modal');
  const assignUsersList = document.getElementById('assign-users-list');
  const assignUsersAutomationName = document.getElementById('assign-users-automation-name');
  const selectAllAssignUsers = document.getElementById('select-all-assign-users');
  const assignUsersBtn = document.getElementById('assign-users-btn');
  let currentAutomationId = null;
  document.querySelectorAll('.users-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const card = btn.closest('.automation-card');
      const automationId = card.getAttribute('data-id');
      const automationName = card.querySelector('h3').textContent.trim();
      assignUsersAutomationName.textContent = automationName;
      currentAutomationId = automationId;
      assignUsersModalBg.style.display = 'flex';
      assignUsersModal.classList.remove('fade-out');
      void assignUsersModal.offsetWidth;
      assignUsersModal.classList.add('fade-in');
      assignUsersList.innerHTML = '<div style="color:#7ecbff;font-weight:700;">Loading users...</div>';
      fetch('agency_handler/fetch_agency_users.php?automation_id=' + encodeURIComponent(automationId))
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            if (data.users.length === 0) {
              assignUsersList.innerHTML = '<div style="color:#e3342f;font-weight:700;">No users found for this agency.</div>';
            } else {
              assignUsersList.innerHTML = '';
              data.users.forEach(user => {
                const label = document.createElement('label');
                label.style.display = 'block';
                label.style.marginBottom = '10px';
                label.style.fontWeight = '600';
                label.style.fontSize = '0.77em';
                label.style.color = '#fff';
                label.style.fontFamily = "'Inter', Arial, sans-serif";
                const cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.value = user.id;
                cb.checked = user.assigned;
                label.appendChild(cb);
                const nameSpan = document.createElement('span');
                nameSpan.textContent = ' ' + user.name;
                nameSpan.style.fontWeight = '600';
                nameSpan.style.color = '#fff';
                nameSpan.style.fontFamily = "'Inter', Arial, sans-serif";
                nameSpan.style.fontSize = '1.155em';
                label.appendChild(nameSpan);
                const emailSpan = document.createElement('span');
                emailSpan.textContent = ' (' + user.email + ')';
                emailSpan.style.fontWeight = '400';
                emailSpan.style.color = '#b6c6d7';
                emailSpan.style.fontFamily = "'Inter', Arial, sans-serif";
                emailSpan.style.fontSize = '1.155em';
                label.appendChild(emailSpan);
                assignUsersList.appendChild(label);
              });
              // After rendering, set up select all logic
              const userCheckboxes = assignUsersList.querySelectorAll('input[type="checkbox"]');
              function updateSelectAllState() {
                const allChecked = Array.from(userCheckboxes).every(cb => cb.checked);
                selectAllAssignUsers.checked = allChecked;
              }
              userCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateSelectAllState);
              });
              selectAllAssignUsers.addEventListener('change', function() {
                userCheckboxes.forEach(cb => { cb.checked = selectAllAssignUsers.checked; });
              });
              updateSelectAllState();
            }
          } else {
            assignUsersList.innerHTML = '<div style="color:#e3342f;font-weight:700;">Failed to fetch users</div>';
          }
        })
        .catch(() => {
          assignUsersList.innerHTML = '<div style="color:#e3342f;font-weight:700;">Error fetching users</div>';
        });
    });
  });
  function closeModal() {
    assignUsersModal.classList.remove('fade-in');
    assignUsersModal.classList.add('fade-out');
    setTimeout(() => {
      assignUsersModalBg.style.display = 'none';
      assignUsersModal.classList.remove('fade-out');
    }, 350);
  }
  closeAssignUsersModal.addEventListener('click', closeModal);
  assignUsersModalBg.addEventListener('click', function(e) {
    if (e.target === assignUsersModalBg) closeModal();
  });

  assignUsersBtn.addEventListener('click', function() {
    // Collect selected user IDs
    const userCheckboxes = assignUsersList.querySelectorAll('input[type="checkbox"]');
    const selectedUserIds = Array.from(userCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
    if (!currentAutomationId) return;
    assignUsersBtn.disabled = true;
    assignUsersBtn.textContent = 'Assigning...';
    fetch('agency_handler/assign_users_to_automation.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ automation_id: currentAutomationId, user_ids: selectedUserIds })
    })
    .then(res => res.json())
    .then(data => {
      assignUsersBtn.disabled = false;
      assignUsersBtn.textContent = 'Assign';
      if (data.success) {
        closeModal();
        // Optionally show a toast or success message
      } else {
        alert('Failed to assign users: ' + (data.message || 'Unknown error'));
      }
    })
    .catch(() => {
      assignUsersBtn.disabled = false;
      assignUsersBtn.textContent = 'Assign';
      alert('Failed to assign users. Please try again.');
    });
  });
});
</script>
</body>
</html> 