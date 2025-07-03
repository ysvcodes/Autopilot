<?php
// approval.php
$user_name = 'Admin'; // Replace with session or actual user logic if needed
// Include logout handler
include_once 'logs/logout_handler.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approval - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        html, body { height: 100vh; overflow-y: hidden; }
        body { font-family: 'Inter', Arial, sans-serif; background: #f7f9fb; margin: 0; padding: 0; padding-bottom: 0 !important; overflow-x: hidden; }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { background: #000f1d; color: #fff; width: 240px; display: flex; flex-direction: column; align-items: flex-start; padding: 0; }
        .sidebar .logo { display: flex; align-items: center; justify-content: center; width: 100%; margin-bottom: 0; margin-top: 0; padding-top: 0; }
        .sidebar .logo img { height: 48px; width: auto; object-fit: contain; display: block; margin: 0; padding: 0; box-sizing: border-box; }
        .sidebar nav { width: 96%; background: #000f1d; border-radius: 22px; margin: 0 auto; padding: 0; margin-top: 0; }
        .sidebar nav a { display: flex; align-items: center; gap: 12px; color: #b6c6d7; text-decoration: none; font-weight: 600; padding: 12px 32px; border-left: 4px solid transparent; transition: background 0.2s, color 0.2s, border-color 0.2s; font-size: 1.08em; border-radius: 12px; background: transparent; margin-bottom: 12px; box-shadow: none; }
        .sidebar nav a.active, .sidebar nav a:hover { background: #178fff22; color: #fff; border-left: 4px solid #1a8cff; box-shadow: 0 2px 8px #178fff11; }
        .sidebar nav a:not(.active):not(:hover) { background: #000f1d; color: #b6c6d7; border-left: 4px solid transparent; }
        .main-content { flex: 1; padding: 18px 32px 0 32px; background: #f7f9fb; min-width: 0; height: 100vh; overflow-y: auto; }
        .topbar { display: flex; justify-content: flex-end; align-items: center; margin-bottom: 18px; }
        .welcome { font-size: 2.3em; font-weight: 800; color: #17406a; margin-bottom: 28px; }
        .top-section { display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px;gap:32px;margin-top:-12px; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php $active_page = 'approval'; include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar"></div>
        <div class="top-section">
            <div style="flex:1;min-width:260px;">
                <div style="font-size:1em;font-weight:800;color:#111;margin-bottom:2px;">Admin View</div>
                <div class="welcome" style="margin-bottom:12px;font-size:2em;font-weight:700;">Logged in as <span style="color:#178fff;font-weight:900;">Admin</span></div>
                <div class="approval-title" style="font-size:2.2em;font-weight:900;color:#14213d;margin-bottom:6px;">Approval Panel</div>
                <div class="approval-subtitle" style="font-size:1.1em;color:#4a5a6a;margin-bottom:24px;"><span class="icon">✅</span> <span style="font-weight:900;color:#14213d;">Approval Requests:</span> Manage and approve pending requests in the system.</div>
            </div>
        </div>
    </main>
</div>
</body>
</html> 