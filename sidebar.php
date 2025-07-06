<?php
// sidebar.php
if (!isset($active_page)) $active_page = '';
?>
<aside class="sidebar" style="padding-top:0;margin-top:0;padding-bottom:0;background:#000f1d;">
    <a href="admin.php" class="logo" style="display: flex; align-items: center; justify-content: center; width: 100%; margin-bottom: 0; margin-top:0; padding-top:0;">
        <img src="assets/autoGB.png" alt="Logo" style="width: 235px; height: 235px; object-fit: contain; display: block; margin-top:0; padding-top:0;" />
    </a>
    <nav>
        <a href="admin.php"<?php if ($active_page === 'admin') echo ' class="active"'; ?>>
            <span style="display:inline-flex;align-items:center;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="4" fill="none"/><path d="M9 9h6v6H9z"/></svg>
            </span>
            Admin Panel
        </a>
        <a href="approval.php"<?php if ($active_page === 'approval') echo ' class="active"'; ?>>
            <span style="display:inline-flex;align-items:center;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
            </span>
            Approval
        </a>
        <a href="enquiries.php"<?php if ($active_page === 'enquiries') echo ' class="active"'; ?>>
            <span style="display:inline-flex;align-items:center;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/></svg>
            </span>
            Enquiries
        </a>
        <a href="agencies.php"<?php if ($active_page === 'agencies') echo ' class="active"'; ?>>
            <span style="display:inline-flex;align-items:center;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="10" rx="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </span>
            Agencies
        </a>
        <a href="users.php"<?php if ($active_page === 'users') echo ' class="active"'; ?>>
            <span style="display:inline-flex;align-items:center;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 8-4 8-4s8 0 8 4"/></svg>
            </span>
            Users
        </a>
        <a href="logs.php"<?php if ($active_page === 'logs') echo ' class="active"'; ?>>
            <span style="display:inline-flex;align-items:center;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 8h8M8 12h8M8 16h4"/></svg>
            </span>
            Logs and Errors
        </a>
        <a href="settings.php"<?php if ($active_page === 'settings') echo ' class="active"'; ?>>
            <span style="display:inline-flex;align-items:center;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 15.5v-7"/><path d="M8.5 12h7"/><circle cx="12" cy="12" r="10"/></svg>
            </span>
            System Settings
        </a>
        <a href="flags.php"<?php if ($active_page === 'flags') echo ' class="active"'; ?>>
            <span style="display:inline-flex;align-items:center;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 20V4"/>
                <path d="M4 4Q8 6,12 4Q16 2,20 4V14Q16 12,12 14Q8 16,4 14V4Z"/>
              </svg>
            </span>
            Flags and Reports
        </a>
        <a href="activity.php"<?php if ($active_page === 'activity') echo ' class="active"'; ?>>
            <span style="display:inline-flex;align-items:center;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </span>
            Activity Feed
        </a>
        <a href="store.php"<?php if ($active_page === 'store') echo ' class="active"'; ?>>
            <span style="display:inline-flex;align-items:center;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            </span>
            Store Management
        </a>
        <a href="#" id="sidebar-signout-btn" style="display:flex;align-items:center;gap:12px;padding:16px 0 16px 24px;text-decoration:none;color:#e3342f;font-weight:800;font-size:1.08em;cursor:pointer;margin-top:12px;border-radius:10px;transition:background 0.18s, color 0.18s, transform 0.18s;"
            onmouseover="this.style.background='#b22234';this.style.color='#fff';this.style.fontWeight='900';this.style.transform='scale(1.04)';this.querySelector('svg').style.stroke='#fff';"
            onmouseout="this.style.background='none';this.style.color='#e3342f';this.style.fontWeight='800';this.style.transform='none';this.querySelector('svg').style.stroke='#e3342f';">
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
<!-- Logout Confirmation Modal and Form (included on every page) -->
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