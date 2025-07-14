<?php
if (!isset($active_page)) $active_page = '';
?>
<style>
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
    .sidebar nav a svg {
        stroke: #22325a;
        transition: stroke 0.2s, filter 0.2s;
    }
    .sidebar nav a.active svg, .sidebar nav a:hover svg {
        stroke: #178fff;
        filter: drop-shadow(0 0 6px #178fff88);
    }
    .sidebar .signout svg {
        stroke: #e3342f;
    }
    .sidebar .signout:hover svg {
        stroke: #fff;
        filter: drop-shadow(0 0 6px #b22234cc);
    }
</style>
<aside class="sidebar">
    <a href="agencyspanel.php" class="logo" style="display: flex; align-items: center; justify-content: center; width: 100%; margin-bottom: 0; margin-top:0; padding-top:0;">
        <img src="assets/logo-light.png" alt="Logo" style="width: 235px; height: 235px; object-fit: contain; display: block; margin-top:0; padding-top:0;" />
    </a>
    <nav>
        <a href="agencyspanel.php" class="<?php if ($active_page === 'agency_overview') echo 'active'; ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="4" fill="none"/><path d="M9 9h6v6H9z"/></svg>Agency Overview</a>
        <a href="automations.php" class="<?php if ($active_page === 'automations') echo 'active'; ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="7" width="16" height="10" rx="4"/><circle cx="8.5" cy="12" r="1"/><circle cx="15.5" cy="12" r="1"/><path d="M10 16h4"/><line x1="12" y1="3" x2="12" y2="7"/></svg>Automations</a>
        <a href="store.php" class="<?php if ($active_page === 'store') echo 'active'; ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1.5"/><circle cx="20" cy="21" r="1.5"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>My Store</a>
        <a href="clients.php" class="<?php if ($active_page === 'clients') echo 'active'; ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 8-4 8-4s8 0 8 4"/></svg>Clients</a>
        <a href="logs.php" class="<?php if ($active_page === 'logs') echo 'active'; ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 8h8M8 12h8M8 16h4"/></svg>Logs and Errors</a>
        <a href="inbox.php" class="<?php if ($active_page === 'inbox') echo 'active'; ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Inbox</a>
        <button class="signout" id="sidebar-signout-btn">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Sign Out
        </button>
    </nav>
</aside> 