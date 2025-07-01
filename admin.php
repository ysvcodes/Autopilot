<?php
// Simple admin dashboard placeholder
session_start();
// Optionally, you could check for a session variable here to restrict access
require_once __DIR__ . '/database_connection/connection.php';
$user_count = 0;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';
try {
    $stmt = $pdo->query('SELECT COUNT(DISTINCT id) as total FROM users');
    $row = $stmt->fetch();
    $user_count = $row ? (int)$row['total'] : 0;
} catch (Exception $e) {
    $user_count = 0;
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
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #f7f9fb;
            margin: 0;
            padding: 0;
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
            padding: 18px 32px 18px 32px;
            background: #f7f9fb;
            min-width: 0;
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
                    <button style="background:#eaf2fa;color:#1560d4;border:none;border-radius:10px;padding:12px 22px;font-size:1.08em;font-weight:700;box-shadow:0 2px 8px #178fff11;display:flex;align-items:center;gap:8px;cursor:pointer;transition:background 0.18s;outline:none;"
                        onmouseover="this.style.background='#d6e6f7'" onmouseout="this.style.background='#eaf2fa'">
                        <span style="font-size:1.2em;">+</span> Add User
                    </button>
                    <button style="background:#3a5a8c;color:#fff;border:none;border-radius:10px;padding:12px 22px;font-size:1.08em;font-weight:700;box-shadow:0 2px 8px #3a5a8c22;display:flex;align-items:center;gap:8px;cursor:pointer;transition:background 0.18s;outline:none;"
                        onmouseover="this.style.background='#2d466d'" onmouseout="this.style.background='#3a5a8c'">
                        <span style="font-size:1.2em;">+</span> Add Agency
                    </button>
                    <button style="background:#14213d;color:#fff;border:none;border-radius:10px;padding:12px 22px;font-size:1.08em;font-weight:700;box-shadow:0 2px 8px #14213d22;display:flex;align-items:center;gap:8px;cursor:pointer;transition:background 0.18s;outline:none;"
                        onmouseover="this.style.background='#22325a'" onmouseout="this.style.background='#14213d'">
                        <span style="font-size:1.2em;">+</span> Add Internal
                    </button>
                </div>
                <div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px #178fff11;padding:0;min-width:270px;width:100%;">
                    <a href="#" style="display:flex;align-items:center;gap:16px;padding:18px 22px 18px 18px;text-decoration:none;color:#222;font-weight:700;font-size:1.08em;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;background:#0a1a2f;border-radius:12px;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="3"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>
                        </span>
                        Manage Automations
                    </a>
                    <hr style="margin:0 18px;border:none;border-top:1px solid #e3e8f0;" />
                    <a href="#" id="edit-profile-link" style="display:flex;align-items:center;gap:16px;padding:18px 22px 18px 18px;text-decoration:none;color:#222;font-weight:700;font-size:1.08em;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;background:#0a1a2f;border-radius:12px;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 8-4 8-4s8 0 8 4"/></svg>
                        </span>
                        Edit Profile
                    </a>
                    <!-- Search Bar -->
                    <div style="padding:18px 22px 18px 18px;">
                      <div style="display:flex;align-items:center;background:#0a1a2f;border-radius:12px;padding:8px 14px;gap:10px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" placeholder="Search..." style="background:transparent;border:none;outline:none;color:#fff;font-size:1.08em;width:100%;" />
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
<!-- Hidden logout form for modal submission -->
<form id="logout-form" method="POST" style="display:none;"><input type="hidden" name="logout" value="1" /></form>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>
</body>
</html> 