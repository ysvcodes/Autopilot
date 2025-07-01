<?php
// Simple admin dashboard placeholder
session_start();
// Optionally, you could check for a session variable here to restrict access
require_once __DIR__ . '/database_connection/connection.php';
$user_count = 0;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';
// Get user count per agency
$agency_counts = [];
// Fetch agency names for the user modal dropdown
$agency_options = [];
try {
    $stmt1 = $pdo->query('SELECT COUNT(*) as total FROM users');
    $row1 = $stmt1->fetch();
    $stmt2 = $pdo->query("SELECT COUNT(*) as total FROM agency_admins WHERE role = 'adminagency'");
    $row2 = $stmt2->fetch();
    $user_count = ($row1 ? (int)$row1['total'] : 0) + ($row2 ? (int)$row2['total'] : 0);
} catch (Exception $e) {
    $user_count = 0;
}
try {
    $stmt = $pdo->query('SELECT aa.agency_name, COUNT(au.user_id) as user_count FROM agency_admins aa LEFT JOIN agency_users au ON aa.agency_id = au.agency_id WHERE aa.role = "adminagency" GROUP BY aa.agency_id, aa.agency_name');
    while ($row = $stmt->fetch()) {
        $agency_counts[] = $row;
    }
} catch (Exception $e) {
    $agency_counts = [];
}
try {
    $stmt = $pdo->query('SELECT agency_id, agency_name FROM agency_admins WHERE role = "adminagency"');
    while ($row = $stmt->fetch()) {
        $agency_options[] = $row;
    }
} catch (Exception $e) {
    $agency_options = [];
}
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        html, body {
            height: 100vh;
            overflow-y: hidden;
        }
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #f7f9fb;
            margin: 0;
            padding: 0;
            padding-bottom: 0 !important;
            overflow-x: hidden;
        }
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            background: #000f1d;
            color: #fff;
            width: 240px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding: 0;
        }
        .sidebar .logo {
            display: flex; align-items: center; justify-content: center; width: 100%; margin-bottom: 0; margin-top: 0; padding-top: 0;
        }
        .sidebar .logo img {
            height: 48px;
            width: auto;
            object-fit: contain;
            display: block;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .sidebar nav {
            width: 96%;
            background: #000f1d;
            border-radius: 22px;
            margin: 0 auto;
            padding: 0;
            margin-top: 0;
        }
        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #b6c6d7;
            text-decoration: none;
            font-weight: 600;
            padding: 12px 32px;
            border-left: 4px solid transparent;
            transition: background 0.2s, color 0.2s, border-color 0.2s;
            font-size: 1.08em;
            border-radius: 12px;
            background: transparent;
            margin-bottom: 12px;
            box-shadow: none;
        }
        .sidebar nav a.active, .sidebar nav a:hover {
            background: #178fff22;
            color: #fff;
            border-left: 4px solid #1a8cff;
            box-shadow: 0 2px 8px #178fff11;
        }
        .sidebar nav a:not(.active):not(:hover) {
            background: #000f1d;
            color: #b6c6d7;
            border-left: 4px solid transparent;
        }
        .main-content {
            flex: 1;
            padding: 18px 32px 0 32px;
            background: #f7f9fb;
            min-width: 0;
            height: 100vh;
            overflow-y: auto;
        }
        .topbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 18px;
        }
        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .profile .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #e3e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
            font-weight: 700;
            color: #0769b0;
        }
        .welcome {
            font-size: 2.3em;
            font-weight: 800;
            color: #17406a;
            margin-bottom: 28px;
        }
        .stats {
            display: flex;
            gap: 24px;
            margin-bottom: 36px;
        }
        .stat-card {
            background: #183a5a;
            color: #fff;
            border-radius: 18px;
            padding: 28px 36px;
            min-width: 210px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            box-shadow: 0 2px 12px rgba(23,143,255,0.08);
            font-weight: 600;
        }
        .stat-card .stat-value {
            font-size: 2.1em;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .stat-card .stat-label {
            font-size: 1.1em;
            opacity: 0.95;
        }
        .stat-card .stat-icon {
            font-size: 1.5em;
            margin-bottom: 10px;
        }
        .automations-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }
        .automations-header h2 {
            font-size: 1.4em;
            font-weight: 800;
            color: #1a2a3a;
        }
        .create-btn {
            background: #183a5a;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 22px;
            font-size: 1em;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.18s;
        }
        .create-btn:hover {
            background: #25476b;
        }
        .automations-list {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }
        .automation-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(23,143,255,0.06);
            padding: 22px 28px;
            min-width: 260px;
            flex: 1 1 260px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            border: 1px solid #e3e8f0;
        }
        .automation-card .icon {
            font-size: 1.3em;
            color: #178fff;
            margin-bottom: 4px;
        }
        @media (max-width: 900px) {
            .main-content { padding: 24px 8px; }
            .stats { flex-direction: column; gap: 16px; }
            .automations-list { flex-direction: column; }
        }
        .top-section {
            display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px;gap:32px;margin-top:-12px;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-40px); }
        }
        #add-agency-modal-bg[fade-up] > div {
            animation: fadeUp 0.45s cubic-bezier(.4,1.4,.6,1) forwards;
        }
        #add-agency-modal-bg[fade-out] > div {
            animation: fadeOut 0.45s cubic-bezier(.4,1.4,.6,1) forwards;
        }
        #add-user-modal-bg[fade-up] > div {
            animation: fadeUp 0.45s cubic-bezier(.4,1.4,.6,1) forwards;
        }
        #add-user-modal-bg[fade-out] > div {
            animation: fadeOut 0.45s cubic-bezier(.4,1.4,.6,1) forwards;
        }
        #internal-modal-bg[fade-up] > div {
            animation: fadeUp 0.45s cubic-bezier(.4,1.4,.6,1) forwards;
        }
        #internal-modal-bg[fade-out] > div {
            animation: fadeOut 0.45s cubic-bezier(.4,1.4,.6,1) forwards;
        }
        #add-internal-modal-bg[fade-up] > div {
            animation: fadeUp 0.45s cubic-bezier(.4,1.4,.6,1) forwards;
        }
        #add-internal-modal-bg[fade-out] > div {
            animation: fadeOut 0.45s cubic-bezier(.4,1.4,.6,1) forwards;
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar" style="padding-top:0;margin-top:0;padding-bottom:0;background:#000f1d;">
        <a href="admin.php" class="logo" style="display: flex; align-items: center; justify-content: center; width: 100%; margin-bottom: 0; margin-top:0; padding-top:0;">
            <img src="assets/autoGB.png" alt="Logo" style="width: 235px; height: 235px; object-fit: contain; display: block; margin-top:0; padding-top:0;" />
        </a>
        <nav>
            <a href="#" class="active">
                <span style="display:inline-flex;align-items:center;">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="4" fill="none"/><path d="M9 9h6v6H9z"/></svg>
                </span>
                Admin Panel
            </a>
            <a href="#">
                <span style="display:inline-flex;align-items:center;">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
                </span>
                Approval
            </a>
            <a href="#">
                <span style="display:inline-flex;align-items:center;">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/></svg>
                </span>
                Enquiries
            </a>
            <a href="#">
                <span style="display:inline-flex;align-items:center;">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                Agencies
            </a>
            <a href="#">
                <span style="display:inline-flex;align-items:center;">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 8-4 8-4s8 0 8 4"/></svg>
                </span>
                Users
            </a>
            <a href="#">
                <span style="display:inline-flex;align-items:center;">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 8h8M8 12h8M8 16h4"/></svg>
                </span>
                Logs and Errors
            </a>
            <a href="#">
                <span style="display:inline-flex;align-items:center;">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 15.5v-7"/><path d="M8.5 12h7"/><circle cx="12" cy="12" r="10"/></svg>
                </span>
                System Settings
            </a>
            <a href="#">
                <span style="display:inline-flex;align-items:center;">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 20V4"/>
                    <path d="M4 4Q8 6,12 4Q16 2,20 4V14Q16 12,12 14Q8 16,4 14V4Z"/>
                  </svg>
                </span>
                Flags and Reports
            </a>
            <a href="#">
                <span style="display:inline-flex;align-items:center;">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </span>
                Activity Feed
            </a>
            <a href="#">
                <span style="display:inline-flex;align-items:center;">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                </span>
                Store Management
            </a>
            <a href="#" id="sidebar-signout-btn" style="display:flex;align-items:center;gap:12px;padding:16px 0 16px 24px;text-decoration:none;color:#e3342f;font-weight:800;font-size:1.08em;cursor:pointer;margin-top:12px;border-radius:10px;transition:background 0.18s, color 0.18s;"
                onmouseover="this.style.background='#e3342f';this.style.color='#fff';this.style.fontWeight='900';this.querySelector('svg').style.stroke='#fff';"
                onmouseout="this.style.background='none';this.style.color='#e3342f';this.style.fontWeight='800';this.querySelector('svg').style.stroke='#e3342f';">
                <span style="display:inline-flex;align-items:center;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#e3342f" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                </span>
                Sign Out
            </a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="topbar">
            <!-- Remove Sign Out button from topbar -->
        </div>
        <div class="top-section" style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px;gap:32px;margin-top:-12px;">
            <div style="flex:1;min-width:260px;">
                <div style="font-size:1em;font-weight:800;color:#111;margin-bottom:2px;">Admin View</div>
                <div class="welcome" style="margin-bottom:12px;font-size:2em;font-weight:700;">Logged in as <span style="color:#178fff;font-weight:900;"><?php echo htmlspecialchars($user_name); ?></span></div>
                <div class="stat-card" style="background:#14213d;color:#fff;border-radius:16px;padding:22px 28px 18px 28px;min-width:210px;display:flex;flex-direction:column;align-items:flex-start;box-shadow:0 2px 12px rgba(23,143,255,0.08);font-weight:600;max-width:260px;margin-top:0;">
                    <span class="stat-icon" style="font-size:1.7em;margin-bottom:10px;display:inline-flex;align-items:center;gap:6px;">
                      <!-- Group/Users Icon -->
                      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                      </svg>
                      <!-- Checkmark in Circle Icon -->
                      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#4bb543" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9 12l2 2l4-4"/>
                      </svg>
                    </span>
                    <span class="stat-value" style="font-size:2.1em;font-weight:800;margin-bottom:6px;color:#fff;"><?php echo $user_count; ?></span>
                    <span class="stat-label" style="font-size:1.1em;opacity:0.95;color:#fff;">Overall Users</span>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:18px;min-width:320px;">
                <div style="display:flex;gap:12px;margin-bottom:8px;">
                    <button id="add-user-btn" style="background:#eaf2fa;color:#1560d4;border:none;border-radius:10px;padding:12px 22px;font-size:1.08em;font-weight:700;box-shadow:0 2px 8px #178fff11;display:flex;align-items:center;gap:8px;cursor:pointer;transition:background 0.18s;outline:none;"
                        onmouseover="this.style.background='#d6e6f7'" onmouseout="this.style.background='#eaf2fa'">
                        <span style="font-size:1.2em;">+</span> Add User
                    </button>
                    <button id="add-agency-btn" style="background:#3a5a8c;color:#fff;border:none;border-radius:10px;padding:12px 22px;font-size:1.08em;font-weight:700;box-shadow:0 2px 8px #3a5a8c22;display:flex;align-items:center;gap:8px;cursor:pointer;transition:background 0.18s;outline:none;"
                        onmouseover="this.style.background='#2d466d'" onmouseout="this.style.background='#3a5a8c'">
                        <span style="font-size:1.2em;">+</span> Add Agency
                    </button>
                    <button id="add-internal-btn" style="background:#14213d;color:#fff;border:none;border-radius:10px;padding:12px 22px;font-size:1.08em;font-weight:700;box-shadow:0 2px 8px #14213d22;display:flex;align-items:center;gap:8px;cursor:pointer;transition:background 0.18s;outline:none;"
                        onmouseover="this.style.background='#22325a'" onmouseout="this.style.background='#14213d'">
                        <span style="font-size:1.2em;">+</span> Add Internal
                    </button>
                </div>
                <div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px #178fff11;padding:0;min-width:270px;width:100%;display:flex;flex-direction:column;">
                    <div id="card-scroll-list" style="max-height:180px;overflow-y:auto;display:flex;flex-direction:column;gap:0 0 8px 0;padding-bottom:0;">
                        <a href="#" class="card-row-hover" style="display:flex;align-items:center;gap:16px;padding:18px 22px 18px 18px;text-decoration:none;color:#222;font-weight:700;font-size:1.08em;transition:box-shadow 0.18s, transform 0.18s;border-radius:12px;margin:6px 10px 0 10px;">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;background:#0a1a2f;border-radius:12px;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="3"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>
                            </span>
                            Manage Automations
                        </a>
                        <hr style="margin:0 18px;border:none;border-top:1px solid #e3e8f0;" />
                        <a href="#" id="edit-profile-link" class="card-row-hover" style="display:flex;align-items:center;gap:16px;padding:18px 22px 18px 18px;text-decoration:none;color:#222;font-weight:700;font-size:1.08em;transition:box-shadow 0.18s, transform 0.18s;border-radius:12px;margin:6px 10px 0 10px;">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;background:#0a1a2f;border-radius:12px;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 8-4 8-4s8 0 8 4"/></svg>
                            </span>
                            Edit Profiles
                        </a>
                    </div>
                    <!-- Search Bar -->
                    <div style="padding:18px 22px 18px 18px;">
                        <div id="search-bar-container" style="display:flex;align-items:center;background:#0a1a2f;border-radius:12px;padding:8px 14px;gap:10px;transition:background 0.18s, border 0.18s;border:1.5px solid transparent;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input id="search-bar-input" type="text" placeholder="Search..." style="background:transparent;border:none;outline:none;color:#fff;font-size:1.08em;width:100%;" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="automations-header">
            <h2>Panel</h2>
        </div>
        <div class="automations-list">
            <div class="automation-card">
                <span class="icon">✉️</span>
                <strong>Email Notifications</strong>
                <span style="font-size:0.97em; color:#444;">Send automatic email updates</span>
            </div>
            <div class="automation-card">
                <span class="icon">💾</span>
                <strong>Data Backup</strong>
                <span style="font-size:0.97em; color:#444;">Backup important data periodically</span>
            </div>
            <div class="automation-card">
                <span class="icon">📄</span>
                <strong>Report Generation</strong>
                <span style="font-size:0.97em; color:#444;">Generate reports from your data</span>
            </div>
            <div class="automation-card">
                <span class="icon">📝</span>
                <strong>Task Assignment</strong>
                <span style="font-size:0.97em; color:#444;">Automate team task assignments</span>
            </div>
        </div>
        <!-- Agencies User Count Table -->
        <div style="margin-top:32px;max-width:420px;background:#fff;border-radius:14px;box-shadow:0 2px 12px #178fff11;padding:18px 24px;max-height:340px;overflow-y:auto;">
          <h3 style="font-size:1.18em;font-weight:800;color:#14213d;margin-bottom:10px;">Agencies & User Count</h3>
          <table id="agencies-user-count-table" style="width:100%;border-collapse:collapse;">
            <thead>
              <tr style="color:#888;font-size:0.98em;text-align:left;">
                <th style="padding:6px 0;">Agency Name</th>
                <th style="padding:6px 0;">User Count</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($agency_counts as $agency): ?>
                <tr style="border-top:1px solid #e3e8f0;">
                  <td style="padding:7px 0;font-weight:700;">
                    <?= !empty($agency['agency_name']) ? htmlspecialchars($agency['agency_name']) : '' ?>
                  </td>
                  <td style="padding:7px 0;font-weight:700;">
                    <?= isset($agency['user_count']) ? htmlspecialchars($agency['user_count']) : '0' ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($agency_counts)): ?>
                <tr><td style="padding:7px 0;color:#888;">No agencies found.</td><td style="padding:7px 0;color:#888;">N/A</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
    </main>
</div>
<!-- Edit Profile Modal -->
<div id="edit-profile-modal-bg" style="display:none;position:fixed;z-index:1000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.45);align-items:center;justify-content:center;">
  <div id="edit-profile-modal" style="background:#fff;border-radius:18px;box-shadow:0 8px 32px #00132333;padding:32px 32px 24px 32px;min-width:320px;max-width:95vw;width:370px;display:flex;flex-direction:column;gap:18px;align-items:stretch;">
    <h2 style="font-size:1.3em;font-weight:800;margin-bottom:8px;color:#14213d;">Edit Profile</h2>
    <form id="edit-profile-form" style="display:flex;flex-direction:column;gap:14px;">
      <label style="font-weight:600;color:#14213d;">First Name
        <input type="text" name="first_name" value="John" style="margin-top:4px;padding:10px 12px;border-radius:8px;border:1px solid #e3e8f0;outline:none;" />
      </label>
      <label style="font-weight:600;color:#14213d;">Last Name
        <input type="text" name="last_name" value="Doe" style="margin-top:4px;padding:10px 12px;border-radius:8px;border:1px solid #e3e8f0;outline:none;" />
      </label>
      <label style="font-weight:600;color:#14213d;">Email
        <input type="email" name="email" value="john@example.com" style="margin-top:4px;padding:10px 12px;border-radius:8px;border:1px solid #e3e8f0;outline:none;" />
      </label>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px;">
        <button type="button" id="cancel-edit-profile" style="background:#e3e8f0;color:#14213d;border:none;border-radius:8px;padding:8px 18px;font-weight:700;cursor:pointer;">Cancel</button>
        <button type="submit" style="background:#14213d;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-weight:700;cursor:pointer;">Save</button>
      </div>
    </form>
  </div>
</div>
<!-- Add floating big plus button at bottom right -->
<button id="floating-create-btn" style="position:fixed;bottom:32px;right:32px;z-index:1001;width:68px;height:68px;border-radius:50%;background:#14213d;color:#fff;display:flex;align-items:center;justify-content:center;font-size:2.6em;box-shadow:0 4px 24px #14213d44;border:none;cursor:pointer;transition:background 0.18s, box-shadow 0.18s, transform 0.18s;outline:none;"
    onmouseover="this.style.background='#0d1627';this.style.boxShadow='0 8px 32px #14213d66';this.style.transform='translateY(-7px)';"
    onmouseout="this.style.background='#14213d';this.style.boxShadow='0 4px 24px #14213d44';this.style.transform='none';">
    <span style="display:inline-block;line-height:1;">+</span>
</button>
<!-- Logout Confirmation Modal -->
<div id="logout-modal-bg" style="display:none;position:fixed;z-index:2000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.45);align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;box-shadow:0 8px 32px #00132333;padding:32px 32px 24px 32px;min-width:320px;max-width:95vw;width:350px;display:flex;flex-direction:column;gap:18px;align-items:stretch;">
    <h2 style="font-size:1.2em;font-weight:800;margin-bottom:8px;color:#e3342f;">Are you sure you want to log out?</h2>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px;">
      <button id="cancel-logout" type="button" style="background:#e3e8f0;color:#14213d;border:none;border-radius:8px;padding:8px 18px;font-weight:700;cursor:pointer;">Cancel</button>
      <button id="confirm-logout" type="submit" form="logout-form" name="logout" value="1" style="background:#b22234;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-weight:800;cursor:pointer;transition:background 0.18s, font-weight 0.18s;">Log Out</button>
    </div>
  </div>
</div>
<form id="logout-form" method="POST" style="display:none;"><input type="hidden" name="logout" value="1" /></form>
<!-- Internal Password Confirmation Modal -->
<div id="internal-modal-bg" style="display:none;position:fixed;z-index:3000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.55);align-items:center;justify-content:center;">
  <div style="background:#0a1020;border-radius:18px;box-shadow:0 8px 32px #00132366;padding:32px 32px 24px 32px;min-width:320px;max-width:95vw;width:370px;display:flex;flex-direction:column;gap:18px;align-items:stretch;">
    <h2 style="font-size:1.4em;font-weight:800;margin-bottom:2px;color:#fff;text-align:center;">Confirm Internal Password</h2>
    <div style="font-size:1.08em;color:#b6c6d7;text-align:center;margin-bottom:10px;">Enter your master password to add an internal user.</div>
    <input id="internal-master-pass" type="password" placeholder="Master Password" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #fff;background:#fff;color:#22325a;font-weight:700;width:100%;" />
    <div id="internal-pass-error" style="display:none;color:#e3342f;background:#f8d7da;padding:10px;border-radius:6px;text-align:center;font-weight:700;margin-top:8px;">Incorrect password. Please try again.</div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px;">
      <button type="button" id="cancel-internal" style="background:none;color:#b6c6d7;font-size:1em;border:none;cursor:pointer;">Cancel</button>
      <button type="button" id="confirm-internal" style="background:#22325a;color:#fff;font-weight:700;font-size:1.08em;padding:10px 24px;border:none;border-radius:10px;cursor:pointer;">Confirm</button>
    </div>
  </div>
</div>
<!-- Add Internal Modal (after password confirmation) -->
<div id="add-internal-modal-bg" style="display:none;position:fixed;z-index:3000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.55);align-items:center;justify-content:center;">
  <div style="background:#0a1020;border-radius:18px;box-shadow:0 8px 32px #00132366;padding:32px 32px 24px 32px;min-width:320px;max-width:95vw;width:370px;display:flex;flex-direction:column;gap:18px;align-items:stretch;">
    <h2 style="font-size:2em;font-weight:800;margin-bottom:2px;color:#fff;text-align:center;">Add Internal User</h2>
    <div style="font-size:1.08em;color:#b6c6d7;text-align:center;margin-bottom:10px;">Create a new Internal User</div>
    <form id="add-internal-form" style="display:flex;flex-direction:column;gap:14px;">
      <div id="add-internal-success" style="display:none;color:#4bb543;background:#d4edda;padding:10px;border-radius:6px;text-align:center;font-weight:700;"></div>
      <div id="add-internal-error" style="display:none;color:#e3342f;background:#f8d7da;padding:10px;border-radius:6px;text-align:center;font-weight:700;"></div>
      <label style="font-weight:600;color:#fff;">Name
        <input type="text" name="name" placeholder="Name" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #fff;background:#fff;color:#22325a;font-weight:700;width:100%;" />
      </label>
      <label style="font-weight:600;color:#fff;">Password
        <input type="password" name="password" placeholder="Password" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #fff;background:#fff;color:#22325a;font-weight:700;width:100%;" />
      </label>
      <button type="submit" id="create-internal-btn" style="margin-top:8px;background:#0a1627;color:#fff;font-weight:700;font-size:1.08em;padding:12px 0 12px 0;border:none;border-radius:12px;cursor:pointer;transition:background 0.18s, color 0.18s, box-shadow 0.18s;box-shadow:0 2px 8px #22325a55;display:flex;align-items:center;justify-content:center;gap:8px;">
        <span style="font-size:1.2em;font-weight:900;">+</span> Create account
      </button>
      <button type="button" id="cancel-add-internal" style="margin-top:2px;background:none;color:#b6c6d7;font-size:1em;border:none;cursor:pointer;">Cancel</button>
    </form>
  </div>
</div>
<!-- Add Agency Modal -->
<div id="add-agency-modal-bg" style="display:none;position:fixed;z-index:3000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.45);display:flex;align-items:center;justify-content:center;">
  <div style="background:#0a1a2f;border-radius:18px;box-shadow:0 8px 32px #00132333;padding:32px 18px 24px 18px;min-width:320px;max-width:95vw;width:370px;display:flex;flex-direction:column;gap:18px;align-items:stretch;justify-content:center;">
    <h2 style="font-size:2em;font-weight:800;margin-bottom:2px;color:#fff;text-align:center;">Create an Agency's Account</h2>
    <div style="font-size:1.08em;color:#b6c6d7;text-align:center;margin-bottom:10px;">Add a New AutoPilots Agency Account</div>
    <form id="add-agency-form" style="display:flex;flex-direction:column;gap:14px;">
      <div id="add-agency-success" style="display:none;color:#4bb543;background:#d4edda;padding:10px;border-radius:6px;text-align:center;font-weight:700;"></div>
      <div id="add-agency-error" style="display:none;color:#e3342f;background:#f8d7da;padding:10px;border-radius:6px;text-align:center;font-weight:700;"></div>
      <label style="font-weight:600;color:#fff;">Agency Name
        <input type="text" name="agency_name" placeholder="Agency name" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #22325a;background:#14213d;color:#fff;width:100%;" />
      </label>
      <label style="font-weight:600;color:#fff;">First Name
        <input type="text" name="first_name" placeholder="First name" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #22325a;background:#14213d;color:#fff;width:100%;" />
      </label>
      <label style="font-weight:600;color:#fff;">Last Name
        <input type="text" name="last_name" placeholder="Last name" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #22325a;background:#14213d;color:#fff;width:100%;" />
      </label>
      <label style="font-weight:600;color:#fff;">Email
        <input type="email" name="email" placeholder="Email" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #22325a;background:#14213d;color:#fff;width:100%;" />
      </label>
      <label style="font-weight:600;color:#fff;">Password
        <input type="password" name="password" placeholder="Password" value="Password" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #22325a;background:#14213d;color:#fff;width:100%;" />
      </label>
      <div style="font-size:0.85em;color:#8a97a8;text-align:left;margin-top:-8px;margin-bottom:8px;padding-left:4px;padding-right:4px;">
        Default password is <b>"Password"</b>. This can be reset and changed by the agency admin later.
      </div>
      <button type="submit" id="create-agency-btn" style="margin-top:8px;background:#14213d;color:#fff;font-weight:700;font-size:1.08em;padding:12px 0 12px 0;border:none;border-radius:12px;cursor:pointer;transition:background 0.18s, color 0.18s;box-shadow:0 2px 8px #3a5a8c22;display:flex;align-items:center;justify-content:center;gap:8px;">
        <span style="font-size:1.2em;font-weight:900;">+</span> Create account
      </button>
      <button type="button" id="cancel-add-agency" style="margin-top:2px;background:none;color:#b6c6d7;font-size:1em;border:none;cursor:pointer;">Cancel</button>
    </form>
  </div>
</div>
<!-- Add User Modal -->
<div id="add-user-modal-bg" style="display:none;position:fixed;z-index:3000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.45);align-items:center;justify-content:center;">
  <div style="background:#22325a;border-radius:18px;box-shadow:0 8px 32px #00132333;padding:32px 32px 24px 32px;min-width:320px;max-width:95vw;width:370px;display:flex;flex-direction:column;gap:18px;align-items:stretch;">
    <h2 style="font-size:2em;font-weight:800;margin-bottom:2px;color:#fff;text-align:center;">Create a User Account</h2>
    <div style="font-size:1.08em;color:#fff;text-align:center;margin-bottom:10px;">Add a New AutoPilot User Account </div>
    <form id="add-user-form" style="display:flex;flex-direction:column;gap:14px;">
      <div id="add-user-success" style="display:none;color:#4bb543;background:#d4edda;padding:10px;border-radius:6px;text-align:center;font-weight:700;"></div>
      <div id="add-user-error" style="display:none;color:#e3342f;background:#f8d7da;padding:10px;border-radius:6px;text-align:center;font-weight:700;"></div>
      <label style="font-weight:600;color:#fff;">First Name
        <input type="text" name="first_name" placeholder="First name" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #fff;background:#fff;color:#22325a;font-weight:700;width:100%;" />
      </label>
      <label style="font-weight:600;color:#fff;">Last Name
        <input type="text" name="last_name" placeholder="Last name" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #fff;background:#fff;color:#22325a;font-weight:700;width:100%;" />
      </label>
      <label style="font-weight:600;color:#fff;">Email
        <input type="email" name="email" placeholder="Email" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #fff;background:#fff;color:#22325a;font-weight:700;width:100%;" />
      </label>
      <label style="font-weight:600;color:#fff;">Password
        <input type="password" name="password" placeholder="Password" value="Password" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #fff;background:#fff;color:#22325a;font-weight:700;width:100%;" />
      </label>
      <div style="font-size:0.85em;color:#8a97a8;text-align:left;margin-top:-8px;margin-bottom:8px;padding-left:4px;padding-right:4px;">
        Default password is <b>"Password"</b>. This can be reset and changed by the agency admin later.
      </div>
      <label style="font-weight:600;color:#fff;">Agency
        <select name="agency_id" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #fff;background:#fff;color:#22325a;font-weight:700;width:100%;">
          <option value="" style="color:#22325a;background:#fff;font-weight:700;">Select Agency</option>
          <?php foreach ($agency_options as $agency): ?>
            <option value="<?= htmlspecialchars($agency['agency_id']) ?>" style="color:#22325a;background:#fff;font-weight:700;"><?= htmlspecialchars($agency['agency_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button type="submit" id="create-user-btn" style="margin-top:8px;background:#14213d;color:#fff;font-weight:700;font-size:1.08em;padding:12px 0 12px 0;border:none;border-radius:12px;cursor:pointer;transition:background 0.18s, color 0.18s, box-shadow 0.18s;box-shadow:0 2px 8px #3a5a8c22;display:flex;align-items:center;justify-content:center;gap:8px;">
        <span style="font-size:1.2em;font-weight:900;">+</span> Create account
      </button>
      <button type="button" id="cancel-add-user" style="margin-top:2px;background:none;color:#b6c6d7;font-size:1em;border:none;cursor:pointer;">Cancel</button>
    </form>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Force hide the modal on page load
  var addAgencyModalBg = document.getElementById('add-agency-modal-bg');
  if (addAgencyModalBg) {
    addAgencyModalBg.style.display = 'none';
  }
  var editProfileLink = document.getElementById('edit-profile-link');
  var modalBg = document.getElementById('edit-profile-modal-bg');
  var cancelBtn = document.getElementById('cancel-edit-profile');
  if (editProfileLink && modalBg && cancelBtn) {
    editProfileLink.addEventListener('click', function(e) {
      e.preventDefault();
      modalBg.style.display = 'flex';
    });
    cancelBtn.addEventListener('click', function() {
      modalBg.style.display = 'none';
    });
    modalBg.addEventListener('click', function(e) {
      if (e.target === modalBg) {
        modalBg.style.display = 'none';
      }
    });
  }
  var sidebarSignoutBtn = document.getElementById('sidebar-signout-btn');
  if (sidebarSignoutBtn) {
    sidebarSignoutBtn.addEventListener('click', function(e) {
      e.preventDefault();
      var modalBg = document.getElementById('logout-modal-bg');
      if (modalBg) modalBg.style.display = 'flex';
    });
  }
  var searchBar = document.getElementById('search-bar-container');
  var searchInput = document.getElementById('search-bar-input');
  if (searchBar && searchInput) {
    searchBar.addEventListener('mouseover', function() {
      searchBar.style.background = '#183a5a';
      searchBar.style.border = '2px solid #178fff';
    });
    searchBar.addEventListener('mouseout', function() {
      if (document.activeElement !== searchInput) {
        searchBar.style.background = '#0a1a2f';
        searchBar.style.border = '1.5px solid transparent';
      }
    });
    searchInput.addEventListener('focus', function() {
      searchBar.style.background = '#183a5a';
      searchBar.style.border = '2px solid #178fff';
    });
    searchInput.addEventListener('blur', function() {
      searchBar.style.background = '#0a1a2f';
      searchBar.style.border = '1.5px solid transparent';
    });
  }
  var cardRows = document.querySelectorAll('.card-row-hover');
  cardRows.forEach(function(row) {
    row.addEventListener('mouseover', function() {
      row.style.boxShadow = '0 4px 18px #178fff22';
      row.style.transform = 'translateY(-2px)';
    });
    row.addEventListener('mouseout', function() {
      row.style.boxShadow = 'none';
      row.style.transform = 'none';
    });
  });
  var addInternalBtn = document.getElementById('add-internal-btn');
  var internalModalBg = document.getElementById('internal-modal-bg');
  var addInternalModalBg = document.getElementById('add-internal-modal-bg');
  var cancelInternal = document.getElementById('cancel-internal');
  var cancelAddInternal = document.getElementById('cancel-add-internal');
  var confirmInternal = document.getElementById('confirm-internal');
  var internalPassInput = document.getElementById('internal-master-pass');
  var internalPassError = document.getElementById('internal-pass-error');
  if (addInternalBtn && internalModalBg && addInternalModalBg && cancelInternal && cancelAddInternal && confirmInternal && internalPassInput && internalPassError) {
    addInternalBtn.addEventListener('click', function(e) {
      e.preventDefault();
      internalModalBg.style.display = 'flex';
      internalModalBg.removeAttribute('fade-out');
      internalModalBg.setAttribute('fade-up', '');
      internalPassInput.value = '';
      internalPassError.style.display = 'none';
      internalPassInput.focus();
    });
    cancelInternal.addEventListener('click', function() {
      internalModalBg.removeAttribute('fade-up');
      internalModalBg.setAttribute('fade-out', '');
      setTimeout(function() {
        internalModalBg.style.display = 'none';
        internalModalBg.removeAttribute('fade-out');
      }, 450);
    });
    cancelAddInternal.addEventListener('click', function() {
      addInternalModalBg.removeAttribute('fade-up');
      addInternalModalBg.setAttribute('fade-out', '');
      setTimeout(function() {
        addInternalModalBg.style.display = 'none';
        addInternalModalBg.removeAttribute('fade-out');
      }, 450);
    });
    internalModalBg.addEventListener('click', function(e) {
      if (e.target === internalModalBg) {
        internalModalBg.removeAttribute('fade-up');
        internalModalBg.setAttribute('fade-out', '');
        setTimeout(function() {
          internalModalBg.style.display = 'none';
          internalModalBg.removeAttribute('fade-out');
        }, 450);
      }
    });
    addInternalModalBg.addEventListener('click', function(e) {
      if (e.target === addInternalModalBg) {
        addInternalModalBg.removeAttribute('fade-up');
        addInternalModalBg.setAttribute('fade-out', '');
        setTimeout(function() {
          addInternalModalBg.style.display = 'none';
          addInternalModalBg.removeAttribute('fade-out');
        }, 450);
      }
    });
    confirmInternal.addEventListener('click', function() {
      var masterPass = internalPassInput.value;
      fetch('verify_internal_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ password: masterPass })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          internalModalBg.removeAttribute('fade-up');
          internalModalBg.setAttribute('fade-out', '');
          setTimeout(function() {
            internalModalBg.style.display = 'none';
            internalModalBg.removeAttribute('fade-out');
            addInternalModalBg.style.display = 'flex';
            addInternalModalBg.removeAttribute('fade-out');
            addInternalModalBg.setAttribute('fade-up', '');
          }, 450);
        } else {
          internalPassError.style.display = 'block';
        }
      })
      .catch(() => {
        internalPassError.style.display = 'block';
      });
    });
    internalPassInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        confirmInternal.click();
      }
    });
  }
  var addAgencyBtn = document.getElementById('add-agency-btn');
  var cancelAddAgency = document.getElementById('cancel-add-agency');
  var addAgencyForm = document.getElementById('add-agency-form');
  var addAgencyError = document.getElementById('add-agency-error');
  var addAgencySuccess = document.getElementById('add-agency-success');
  if (addAgencyBtn && addAgencyModalBg && cancelAddAgency && addAgencyForm && addAgencyError && addAgencySuccess) {
    addAgencyBtn.addEventListener('click', function(e) {
      e.preventDefault();
      console.log('Add Agency button clicked, opening modal');
      addAgencyModalBg.style.display = 'flex';
      addAgencyModalBg.removeAttribute('fade-out');
      addAgencyModalBg.setAttribute('fade-up', '');
      addAgencyForm.reset();
      addAgencyError.style.display = 'none';
      addAgencySuccess.style.display = 'none';
    });
    cancelAddAgency.addEventListener('click', function() {
      addAgencyModalBg.removeAttribute('fade-up');
      addAgencyModalBg.setAttribute('fade-out', '');
      setTimeout(function() {
        addAgencyModalBg.style.display = 'none';
        addAgencyModalBg.removeAttribute('fade-out');
      }, 450);
    });
    addAgencyModalBg.addEventListener('click', function(e) {
      if (e.target === addAgencyModalBg) {
        addAgencyModalBg.removeAttribute('fade-up');
        addAgencyModalBg.setAttribute('fade-out', '');
        setTimeout(function() {
          addAgencyModalBg.style.display = 'none';
          addAgencyModalBg.removeAttribute('fade-out');
        }, 450);
      }
    });
    addAgencyForm.addEventListener('submit', function(e) {
      e.preventDefault();
      addAgencyError.style.display = 'none';
      addAgencySuccess.style.display = 'none';
      var agencyName = addAgencyForm.agency_name.value.trim();
      var firstName = addAgencyForm.first_name.value.trim();
      var lastName = addAgencyForm.last_name.value.trim();
      var email = addAgencyForm.email.value.trim();
      var password = addAgencyForm.password.value;
      if (!agencyName || !firstName || !lastName || !email || !password) {
        addAgencyError.textContent = 'All fields are required.';
        addAgencyError.style.display = 'block';
        return;
      }
      if (!/^\S+@\S+\.\S+$/.test(email)) {
        addAgencyError.textContent = 'Invalid email address.';
        addAgencyError.style.display = 'block';
        return;
      }
      fetch('create_agency.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          agency_name: agencyName,
          first_name: firstName,
          last_name: lastName,
          email: email,
          password: password
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          addAgencySuccess.textContent = data.message || 'Agency account created successfully!';
          addAgencySuccess.style.display = 'block';
          refreshAgenciesTable();
          setTimeout(function() {
            addAgencyModalBg.removeAttribute('fade-up');
            addAgencyModalBg.setAttribute('fade-out', '');
            setTimeout(function() {
              addAgencyModalBg.style.display = 'none';
              addAgencyModalBg.removeAttribute('fade-out');
            }, 450);
          }, 3000);
        } else {
          addAgencyError.textContent = data.message || 'Failed to create agency.';
          addAgencyError.style.display = 'block';
        }
      })
      .catch(() => {
        addAgencyError.textContent = 'Server error. Please try again.';
        addAgencyError.style.display = 'block';
      });
    });
  }
  var createAgencyBtn = document.getElementById('create-agency-btn');
  if (createAgencyBtn) {
    createAgencyBtn.addEventListener('mouseover', function() {
      this.style.background = '#fff';
      this.style.color = '#14213d';
    });
    createAgencyBtn.addEventListener('mouseout', function() {
      this.style.background = '#14213d';
      this.style.color = '#fff';
    });
  }
  function refreshAgenciesTable() {
    fetch('fetch_agencies.php')
      .then(res => res.text())
      .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newTable = doc.querySelector('#agencies-user-count-table');
        if (newTable) {
          document.getElementById('agencies-user-count-table').innerHTML = newTable.innerHTML;
        }
      });
  }
  var addUserBtn = document.getElementById('add-user-btn');
  var addUserModalBg = document.getElementById('add-user-modal-bg');
  var cancelAddUser = document.getElementById('cancel-add-user');
  var addUserForm = document.getElementById('add-user-form');
  var addUserError = document.getElementById('add-user-error');
  var addUserSuccess = document.getElementById('add-user-success');
  var createUserBtn = document.getElementById('create-user-btn');
  if (createUserBtn) {
    createUserBtn.addEventListener('mouseover', function() {
      this.style.background = '#fff';
      this.style.color = '#14213d';
      this.style.boxShadow = '0 2px 8px #3a5a8c22';
    });
    createUserBtn.addEventListener('mouseout', function() {
      this.style.background = '#14213d';
      this.style.color = '#fff';
      this.style.boxShadow = '0 2px 8px #3a5a8c22';
    });
  }
  if (addUserBtn && addUserModalBg && cancelAddUser && addUserForm && addUserError && addUserSuccess) {
    addUserBtn.addEventListener('click', function(e) {
      e.preventDefault();
      addUserModalBg.style.display = 'flex';
      addUserModalBg.removeAttribute('fade-out');
      addUserModalBg.setAttribute('fade-up', '');
      addUserForm.reset();
      addUserError.style.display = 'none';
      addUserSuccess.style.display = 'none';
    });
    cancelAddUser.addEventListener('click', function() {
      addUserModalBg.removeAttribute('fade-up');
      addUserModalBg.setAttribute('fade-out', '');
      setTimeout(function() {
        addUserModalBg.style.display = 'none';
        addUserModalBg.removeAttribute('fade-out');
      }, 450);
    });
    addUserModalBg.addEventListener('click', function(e) {
      if (e.target === addUserModalBg) {
        addUserModalBg.removeAttribute('fade-up');
        addUserModalBg.setAttribute('fade-out', '');
        setTimeout(function() {
          addUserModalBg.style.display = 'none';
          addUserModalBg.removeAttribute('fade-out');
        }, 450);
      }
    });
    addUserForm.addEventListener('submit', function(e) {
      e.preventDefault();
      addUserError.style.display = 'none';
      addUserSuccess.style.display = 'none';
      var firstName = addUserForm.first_name.value.trim();
      var lastName = addUserForm.last_name.value.trim();
      var email = addUserForm.email.value.trim();
      var password = addUserForm.password.value;
      var agencyId = addUserForm.agency_id.value;
      if (!firstName || !lastName || !email || !password || !agencyId) {
        addUserError.textContent = 'All fields are required.';
        addUserError.style.display = 'block';
        return;
      }
      if (!/^\S+@\S+\.\S+$/.test(email)) {
        addUserError.textContent = 'Invalid email address.';
        addUserError.style.display = 'block';
        return;
      }
      fetch('create_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          first_name: firstName,
          last_name: lastName,
          email: email,
          password: password,
          agency_id: agencyId
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          addUserSuccess.textContent = data.message || 'User account created successfully!';
          addUserSuccess.style.display = 'block';
          refreshAgenciesTable && refreshAgenciesTable();
          setTimeout(function() {
            addUserModalBg.removeAttribute('fade-up');
            addUserModalBg.setAttribute('fade-out', '');
            setTimeout(function() {
              addUserModalBg.style.display = 'none';
              addUserModalBg.removeAttribute('fade-out');
            }, 450);
          }, 2000);
        } else {
          addUserError.textContent = data.message || 'Failed to create user.';
          addUserError.style.display = 'block';
        }
      })
      .catch(() => {
        addUserError.textContent = 'Server error. Please try again.';
        addUserError.style.display = 'block';
      });
    });
  }
  var addInternalForm = document.getElementById('add-internal-form');
  var addInternalError = document.getElementById('add-internal-error');
  var addInternalSuccess = document.getElementById('add-internal-success');
  if (addInternalForm && addInternalError && addInternalSuccess) {
    addInternalForm.addEventListener('submit', function(e) {
      e.preventDefault();
      addInternalError.style.display = 'none';
      addInternalSuccess.style.display = 'none';
      var name = addInternalForm.name.value.trim();
      var password = addInternalForm.password.value;
      if (!name || !password) {
        addInternalError.textContent = 'All fields are required.';
        addInternalError.style.display = 'block';
        return;
      }
      fetch('create_internal.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: name,
          password: password
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          addInternalSuccess.textContent = data.message || 'Internal user created successfully!';
          addInternalSuccess.style.display = 'block';
          addInternalForm.reset();
          addInternalModalBg.removeAttribute('fade-up');
          addInternalModalBg.setAttribute('fade-out', '');
          setTimeout(function() {
            addInternalModalBg.style.display = 'none';
            addInternalModalBg.removeAttribute('fade-out');
            addInternalSuccess.style.display = 'none';
          }, 450);
        } else {
          addInternalError.textContent = data.message || 'Failed to create internal user.';
          addInternalError.style.display = 'block';
        }
      })
      .catch(() => {
        addInternalError.textContent = 'Server error. Please try again.';
        addInternalError.style.display = 'block';
      });
    });
  }
  var createInternalBtn = document.getElementById('create-internal-btn');
  var confirmInternalBtn = document.getElementById('confirm-internal');
  if (createInternalBtn) {
    createInternalBtn.style.transition = 'background 0.18s, color 0.18s';
    createInternalBtn.addEventListener('mouseover', function() {
      this.style.background = '#fff';
      this.style.color = '#22325a';
    });
    createInternalBtn.addEventListener('mouseout', function() {
      this.style.background = '#0a1627';
      this.style.color = '#fff';
    });
  }
  if (confirmInternalBtn) {
    confirmInternalBtn.style.transition = 'background 0.18s, color 0.18s';
    confirmInternalBtn.addEventListener('mouseover', function() {
      this.style.background = '#fff';
      this.style.color = '#22325a';
    });
    confirmInternalBtn.addEventListener('mouseout', function() {
      this.style.background = '#22325a';
      this.style.color = '#fff';
    });
  }
});
</script>
</body>
</html> 