<?php
// agencies.php
$user_name = 'Admin'; // Replace with session or actual user logic if needed
$active_page = 'agencies';
// Include logout handler
include_once 'logs/logout_handler.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Agencies - Admin Panel</title>
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
        .agencies-header {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.25em;
            font-weight: 700;
            color: #b6c6d7;
            margin-bottom: 18px;
        }
        .agencies-title {
            font-size: 2.2em;
            font-weight: 900;
            color: #14213d;
            margin-bottom: 6px;
        }
        .agencies-subtitle {
            font-size: 1.1em;
            color: #4a5a6a;
            margin-bottom: 24px;
        }
        .action-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            margin: 0 4px;
            border-radius: 8px;
            background: none;
            transition: background 0.18s, color 0.18s, filter 0.18s;
            vertical-align: middle;
            border: none;
            outline: none;
            cursor: pointer;
        }
        .action-icon svg {
            display: block;
            margin: auto;
            pointer-events: none;
        }
        .action-icon.edit:hover {
            background: #178fff;
        }
        .action-icon.edit:hover svg {
            stroke: #fff;
        }
        .action-icon.delete:hover {
            background: #e3342f;
        }
        .action-icon.delete:hover svg {
            stroke: #fff;
        }
        .action-icon.share:hover {
            background: #22bb55;
        }
        .action-icon.share:hover svg {
            stroke: #fff;
        }
        .action-icon.export:hover {
            background: #222;
        }
        .action-icon.export:hover svg {
            stroke: #fff;
        }
        .action-icon:not(:last-child) {
            margin-right: 8px;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-40px); }
        }
        #delete-modal-bg[fade-up] > #delete-modal,
        #edit-modal-bg[fade-up] > #edit-modal {
            animation: fadeUp 0.45s cubic-bezier(.4,1.4,.6,1) forwards;
        }
        #delete-modal-bg[fade-out] > #delete-modal,
        #edit-modal-bg[fade-out] > #edit-modal {
            animation: fadeOut 0.45s cubic-bezier(.4,1.4,.6,1) forwards;
        }
    </style>
</head>
<body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div style="font-size:1em;font-weight:800;color:#111;margin-bottom:2px;">Admin View</div>
        <div class="welcome" style="margin-bottom:12px;font-size:2em;font-weight:700;">Logged in as <span style="color:#178fff;font-weight:900;">Admin</span></div>
        <div class="agencies-title" style="font-size:2.2em;font-weight:900;color:#14213d;margin-bottom:6px;">Agencies</div>
        <div class="agencies-subtitle" style="font-size:1.1em;color:#4a5a6a;margin-bottom:24px;"><span class="icon">🏢</span> Manage and view all registered agencies in the system.</div>
        <?php
        require_once __DIR__ . '/database_connection/connection.php';
        // Fetch all agencies for stats (not paginated)
        $all_agencies = [];
        try {
            $stmt = $pdo->query('SELECT aa.agency_id, aa.agency_name, aa.first_name, aa.last_name, aa.email, aa.created_at, COUNT(DISTINCT au.user_id) as user_count, COUNT(DISTINCT a.id) as automation_count FROM agency_admins aa LEFT JOIN agency_users au ON aa.agency_id = au.agency_id LEFT JOIN automations a ON aa.agency_id = a.agency_id WHERE aa.role = "adminagency" GROUP BY aa.agency_id, aa.agency_name, aa.first_name, aa.last_name, aa.email, aa.created_at ORDER BY automation_count DESC, user_count DESC, aa.created_at DESC');
            while ($row = $stmt->fetch()) {
                $all_agencies[] = $row;
            }
        } catch (Exception $e) {
            $all_agencies = [];
        }
        $most_active = !empty($all_agencies) ? $all_agencies[0] : null;
        $least_active = !empty($all_agencies) ? $all_agencies[count($all_agencies)-1] : null;
        // Pagination
        $limit = 25;
        $offset = 0;
        if (isset($_GET['offset']) && is_numeric($_GET['offset'])) {
            $offset = (int)$_GET['offset'];
        }
        $agencies = [];
        try {
            $stmt = $pdo->prepare('SELECT aa.agency_id, aa.agency_name, aa.first_name, aa.last_name, aa.email, aa.created_at, COUNT(DISTINCT au.user_id) as user_count, COUNT(DISTINCT a.id) as automation_count FROM agency_admins aa LEFT JOIN agency_users au ON aa.agency_id = au.agency_id LEFT JOIN automations a ON aa.agency_id = a.agency_id WHERE aa.role = "adminagency" GROUP BY aa.agency_id, aa.agency_name, aa.first_name, aa.last_name, aa.email, aa.created_at ORDER BY automation_count DESC, user_count DESC, aa.created_at DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            while ($row = $stmt->fetch()) {
                $agencies[] = $row;
            }
        } catch (Exception $e) {
            $agencies = [];
        }
        $total_agencies = count($all_agencies);
        $show_load_more = ($offset + $limit) < $total_agencies;
        // Fetch automations for each agency
        $agency_automations = [];
        try {
            $stmt = $pdo->query('SELECT agency_id, name FROM automations');
            while ($row = $stmt->fetch()) {
                $agency_automations[$row['agency_id']][] = $row['name'];
            }
        } catch (Exception $e) {
            $agency_automations = [];
        }
        // Fetch all automations for dropdown
        $all_automations = [];
        try {
            $stmt = $pdo->query('SELECT id, name, agency_id FROM automations');
            while ($row = $stmt->fetch()) {
                $all_automations[] = $row;
            }
        } catch (Exception $e) {
            $all_automations = [];
        }
        ?>
        <div style="display:flex;gap:24px;margin-bottom:32px;">
            <div style="background:#14213d;color:#fff;border-radius:18px;padding:28px 36px;min-width:210px;display:flex;flex-direction:column;align-items:flex-start;box-shadow:0 2px 12px #22325a22;font-weight:600;">
                <div style="font-size:1.5em;margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                    <svg width="32" height="32" fill="none" stroke="#7ecbff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span style="font-size:1.1em;font-weight:700;">Most Active Agency</span>
                </div>
                <div style="font-size:2.1em;font-weight:800;margin-bottom:6px;">
                    <?= $most_active ? htmlspecialchars($most_active['agency_name']) : '-' ?>
                </div>
                <div style="font-size:1.1em;opacity:0.95;">Users: <b><?= $most_active ? htmlspecialchars($most_active['user_count']) : '0' ?></b></div>
            </div>
            <div style="background:#14213d;color:#fff;border-radius:18px;padding:28px 36px;min-width:210px;display:flex;flex-direction:column;align-items:flex-start;box-shadow:0 2px 12px #22325a22;font-weight:600;">
                <div style="font-size:1.5em;margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                    <svg width="32" height="32" fill="none" stroke="#e3342f" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span style="font-size:1.1em;font-weight:700;">Least Active Agency</span>
                </div>
                <div style="font-size:2.1em;font-weight:800;margin-bottom:6px;">
                    <?= $least_active ? htmlspecialchars($least_active['agency_name']) : '-' ?>
                </div>
                <div style="font-size:1.1em;opacity:0.95;">Users: <b><?= $least_active ? htmlspecialchars($least_active['user_count']) : '0' ?></b></div>
            </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <div>
                <select id="agency-filter" style="padding:9px 16px;border-radius:8px;border:1.5px solid #e3e8f0;font-size:1.08em;outline:none;margin-right:16px;">
                    <option value="all">All</option>
                    <option value="most-users">Most Users</option>
                    <option value="recently-created">Recently Created</option>
                </select>
            </div>
            <div style="position:relative;">
                <input id="agency-search" type="text" placeholder="Search agencies..." style="padding:10px 16px;border-radius:8px;border:1.5px solid #e3e8f0;font-size:1.08em;width:260px;outline:none;" />
                <svg style="position:absolute;right:12px;top:50%;transform:translateY(-50%);opacity:0.5;" width="18" height="18" fill="none" stroke="#22325a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
        </div>
        <div style="background:#fff;border-radius:14px;box-shadow:0 2px 12px #178fff11;padding:0 0 0 0;max-width:100%;overflow-x:auto;margin-bottom:32px;">
            <div style="max-height:340px;overflow-y:auto;">
                <table style="width:100%;min-width:950px;border-collapse:collapse;">
                    <thead>
                        <tr style="color:#888;font-size:1em;text-align:left;background:#f7f9fb;">
                            <th style="padding:14px 18px;font-weight:800;">ID</th>
                            <th style="padding:14px 18px;font-weight:800;">Agency Name</th>
                            <th style="padding:14px 18px;font-weight:800;">Admin Name</th>
                            <th style="padding:14px 18px;font-weight:800;">Email</th>
                            <th style="padding:14px 18px;font-weight:800;">Created</th>
                            <th style="padding:14px 18px;font-weight:800;text-align:center;">User Count</th>
                            <th style="padding:14px 18px;font-weight:800;text-align:center;">Automations</th>
                            <th style="padding:14px 18px;font-weight:800;text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="agencies-table-body">
                        <?php foreach ($agencies as $agency): ?>
                        <tr data-agency-id="<?= htmlspecialchars($agency['agency_id']) ?>" data-agency-name="<?= htmlspecialchars($agency['agency_name']) ?>" data-admin-name="<?= htmlspecialchars($agency['first_name'] . ' ' . $agency['last_name']) ?>" data-email="<?= htmlspecialchars($agency['email']) ?>" style="border-top:1px solid #e3e8f0;transition:background 0.15s;">
                            <td style="padding:12px 18px;font-weight:700;"> <?= htmlspecialchars($agency['agency_id']) ?> </td>
                            <td style="padding:12px 18px;font-weight:700;"> <?= htmlspecialchars($agency['agency_name']) ?> </td>
                            <td style="padding:12px 18px;"> <?= htmlspecialchars($agency['first_name'] . ' ' . $agency['last_name']) ?> </td>
                            <td style="padding:12px 18px;"> <?= htmlspecialchars($agency['email']) ?> </td>
                            <td style="padding:12px 18px;"> <?= date('Y-m-d', strtotime($agency['created_at'])) ?> </td>
                            <td style="padding:12px 18px;text-align:center;"> <?= htmlspecialchars($agency['user_count']) ?> </td>
                            <td style="padding:12px 18px;text-align:center;">
                                <?php
                                    $auto = $agency_automations[$agency['agency_id']] ?? [];
                                    echo $auto ? htmlspecialchars(implode(', ', $auto)) : '-';
                                ?>
                            </td>
                            <td style="padding:12px 18px;text-align:center;white-space:nowrap;">
                                <a href="#" title="Edit" class="action-icon edit">
                                    <svg width="20" height="20" fill="none" stroke="#178fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19.5 3 21l1.5-4L16.5 3.5z"/></svg>
                                </a>
                                <a href="#" title="Delete" class="action-icon delete">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e3342f" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                      <polyline points="3 6 5 6 21 6"/>
                                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                      <line x1="10" y1="11" x2="10" y2="17"/>
                                      <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                </a>
                                <a href="#" title="Share" class="action-icon share">
                                    <svg width="20" height="20" fill="none" stroke="#22bb55" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                                </a>
                                <a href="#" title="Export" class="action-icon export">
                                    <svg width="20" height="20" fill="none" stroke="#222" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12h8M8 16h8M8 8h8"/></svg>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($agencies)): ?>
                        <tr><td colspan="8" style="padding:12px 18px;color:#888;text-align:center;">No users</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;align-items:center;margin-top:8px;">
            <span style="color:#888;font-size:1em;margin-right:18px;">Showing <?= $offset+1 ?>-<?= min($offset+$limit, $total_agencies) ?> of <?= $total_agencies ?></span>
            <?php if ($show_load_more): ?>
            <button id="load-more-btn" style="background:#178fff;color:#fff;font-weight:700;font-size:1.08em;padding:8px 22px;border:none;border-radius:8px;cursor:pointer;">Load More</button>
            <?php endif; ?>
        </div>
        <!-- Delete Confirmation Modal -->
        <div id="delete-modal-bg" style="display:none;position:fixed;z-index:3000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.45);align-items:center;justify-content:center;">
            <div id="delete-modal" style="background:#fff;padding:32px 32px 24px 32px;border-radius:16px;box-shadow:0 2px 16px #22325a33;max-width:95vw;width:370px;text-align:center;">
                <div style="font-size:1.25em;font-weight:800;color:#14213d;margin-bottom:12px;">Delete Agency</div>
                <div id="delete-modal-msg" style="font-size:1.08em;color:#22325a;margin-bottom:24px;"></div>
                <button id="confirm-delete-btn" style="background:#e3342f;color:#fff;font-weight:700;font-size:1.08em;padding:10px 28px;border:none;border-radius:8px;cursor:pointer;margin-right:12px;">Delete</button>
                <button id="cancel-delete-btn" style="background:#f7f9fb;color:#22325a;font-weight:700;font-size:1.08em;padding:10px 28px;border:none;border-radius:8px;cursor:pointer;">Cancel</button>
            </div>
        </div>
        <!-- Edit Agency Modal -->
        <div id="edit-modal-bg" style="display:none;position:fixed;z-index:3000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.45);align-items:center;justify-content:center;">
            <div id="edit-modal" style="background:#fff;padding:32px 32px 24px 32px;border-radius:16px;box-shadow:0 2px 16px #22325a33;max-width:95vw;width:400px;text-align:left;">
                <div style="font-size:1.25em;font-weight:800;color:#14213d;margin-bottom:12px;">Edit Agency</div>
                <form id="edit-agency-form" autocomplete="off">
                    <input type="hidden" name="agency_id" id="edit-agency-id">
                    <label style="font-weight:600;color:#22325a;">Agency Name
                        <input type="text" name="agency_name" id="edit-agency-name" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #e3e8f0;background:#f7f9fb;color:#22325a;width:100%;font-size:1.08em;" required />
                    </label>
                    <label style="font-weight:600;color:#22325a;display:block;margin-top:12px;">Admin First Name
                        <input type="text" name="first_name" id="edit-admin-first" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #e3e8f0;background:#f7f9fb;color:#22325a;width:100%;font-size:1.08em;" required />
                    </label>
                    <label style="font-weight:600;color:#22325a;display:block;margin-top:12px;">Admin Last Name
                        <input type="text" name="last_name" id="edit-admin-last" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #e3e8f0;background:#f7f9fb;color:#22325a;width:100%;font-size:1.08em;" required />
                    </label>
                    <label style="font-weight:600;color:#22325a;display:block;margin-top:12px;">Admin Email
                        <input type="email" name="email" id="edit-admin-email" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1.5px solid #e3e8f0;background:#f7f9fb;color:#22325a;width:100%;font-size:1.08em;" required />
                    </label>
                    <label style="font-weight:600;color:#22325a;display:block;margin-top:12px;">Password
                        <input type="text" name="password" id="edit-admin-password" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1.5px solid #e3e8f0;background:#f7f9fb;color:#22325a;width:100%;font-size:1.08em;" autocomplete="new-password" />
                        <span id="edit-admin-password-note" style="font-size:0.95em;color:#888;"></span>
                    </label>
                    <label style="font-weight:600;color:#22325a;display:block;margin-top:12px;">Automations
                        <select name="automation_ids[]" id="edit-agency-automations" multiple style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1.5px solid #e3e8f0;background:#f7f9fb;color:#22325a;width:100%;font-size:1.08em;">
                            <?php foreach ($all_automations as $automation): ?>
                                <option value="<?= htmlspecialchars($automation['id']) ?>" data-agency-id="<?= htmlspecialchars($automation['agency_id']) ?>">
                                    <?= htmlspecialchars($automation['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span style="font-size:0.95em;color:#888;">Hold Ctrl (Windows) or Cmd (Mac) to select multiple</span>
                    </label>
                    <div id="edit-agency-error" style="display:none;color:#e3342f;background:#f8d7da;padding:10px;border-radius:6px;text-align:center;font-weight:700;margin-top:12px;"></div>
                    <div id="edit-agency-success" style="display:none;color:#4bb543;background:#d4edda;padding:10px;border-radius:6px;text-align:center;font-weight:700;margin-top:12px;"></div>
                    <div style="margin-top:22px;text-align:right;">
                        <button type="button" id="cancel-edit-btn" style="background:#f7f9fb;color:#22325a;font-weight:700;font-size:1.08em;padding:10px 28px;border:none;border-radius:8px;cursor:pointer;margin-right:12px;">Cancel</button>
                        <button type="submit" id="save-edit-btn" style="background:#178fff;color:#fff;font-weight:700;font-size:1.08em;padding:10px 28px;border:none;border-radius:8px;cursor:pointer;">Save</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Copied Notification -->
        <div id="copied-notification" style="display:none;position:fixed;top:32px;right:32px;z-index:4000;background:#d4edda;color:#22bb55;font-weight:700;font-size:1.08em;padding:14px 32px;border-radius:10px;box-shadow:0 2px 12px #22bb5522;transition:opacity 0.5s;opacity:0;">
            Copied to clipboard!
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            let deleteAgencyId = null;
            let deleteAgencyRow = null;
            let deleteAgencyName = '';
            const modalBg = document.getElementById('delete-modal-bg');
            const modalMsg = document.getElementById('delete-modal-msg');
            const confirmBtn = document.getElementById('confirm-delete-btn');
            const cancelBtn = document.getElementById('cancel-delete-btn');
            document.querySelectorAll('.action-icon.delete').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const row = btn.closest('tr');
                    deleteAgencyId = row.getAttribute('data-agency-id');
                    deleteAgencyName = row.getAttribute('data-agency-name');
                    deleteAgencyRow = row;
                    modalMsg.textContent = `Are you sure you want to delete "${deleteAgencyName}"?`;
                    modalBg.style.display = 'flex';
                    modalBg.removeAttribute('fade-out');
                    modalBg.setAttribute('fade-up', '');
                });
            });
            cancelBtn.addEventListener('click', function() {
                modalBg.removeAttribute('fade-up');
                modalBg.setAttribute('fade-out', '');
                setTimeout(function() {
                    modalBg.style.display = 'none';
                    modalBg.removeAttribute('fade-out');
                }, 450);
            });
            confirmBtn.addEventListener('click', function() {
                if (!deleteAgencyId) return;
                confirmBtn.disabled = true;
                fetch('agency_handler/delete_agency.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ agency_id: deleteAgencyId })
                })
                .then(res => res.json())
                .then(data => {
                    confirmBtn.disabled = false;
                    if (data.success) {
                        if (deleteAgencyRow) deleteAgencyRow.remove();
                        modalBg.removeAttribute('fade-up');
                        modalBg.setAttribute('fade-out', '');
                        setTimeout(function() {
                            modalBg.style.display = 'none';
                            modalBg.removeAttribute('fade-out');
                        }, 450);
                    } else {
                        modalMsg.textContent = data.message || 'Failed to delete agency.';
                    }
                })
                .catch(() => {
                    confirmBtn.disabled = false;
                    modalMsg.textContent = 'Server error. Please try again.';
                });
            });
            modalBg.addEventListener('click', function(e) {
                if (e.target === modalBg) {
                    modalBg.removeAttribute('fade-up');
                    modalBg.setAttribute('fade-out', '');
                    setTimeout(function() {
                        modalBg.style.display = 'none';
                        modalBg.removeAttribute('fade-out');
                    }, 450);
                }
            });
            // Edit logic
            let editModalBg = document.getElementById('edit-modal-bg');
            let editForm = document.getElementById('edit-agency-form');
            let cancelEditBtn = document.getElementById('cancel-edit-btn');
            let editError = document.getElementById('edit-agency-error');
            let editSuccess = document.getElementById('edit-agency-success');
            let saveEditBtn = document.getElementById('save-edit-btn');
            document.querySelectorAll('.action-icon.edit').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const row = btn.closest('tr');
                    document.getElementById('edit-agency-id').value = row.getAttribute('data-agency-id');
                    document.getElementById('edit-agency-name').value = row.getAttribute('data-agency-name');
                    let adminName = row.getAttribute('data-admin-name').split(' ');
                    document.getElementById('edit-admin-first').value = adminName[0] || '';
                    document.getElementById('edit-admin-last').value = adminName.slice(1).join(' ') || '';
                    document.getElementById('edit-admin-email').value = row.getAttribute('data-email');
                    editError.style.display = 'none';
                    editSuccess.style.display = 'none';
                    // Fetch current password (non-hashed) via AJAX
                    const passwordInput = document.getElementById('edit-admin-password');
                    const passwordNote = document.getElementById('edit-admin-password-note');
                    passwordInput.value = '';
                    passwordNote.textContent = 'Loading password...';
                    fetch('agency_handler/get_agency_password.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ agency_id: row.getAttribute('data-agency-id') })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            passwordInput.value = data.password;
                            passwordNote.textContent = 'Current password (change to update)';
                        } else {
                            passwordInput.value = '';
                            passwordNote.textContent = 'Unable to fetch password.';
                        }
                    })
                    .catch(() => {
                        passwordInput.value = '';
                        passwordNote.textContent = 'Unable to fetch password.';
                    });
                    editModalBg.style.display = 'flex';
                    editModalBg.removeAttribute('fade-out');
                    editModalBg.setAttribute('fade-up', '');
                });
            });
            cancelEditBtn.addEventListener('click', function() {
                editModalBg.removeAttribute('fade-up');
                editModalBg.setAttribute('fade-out', '');
                setTimeout(function() {
                    editModalBg.style.display = 'none';
                    editModalBg.removeAttribute('fade-out');
                }, 450);
            });
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                saveEditBtn.disabled = true;
                editError.style.display = 'none';
                editSuccess.style.display = 'none';
                const agency_id = document.getElementById('edit-agency-id').value;
                const agency_name = document.getElementById('edit-agency-name').value.trim();
                const first_name = document.getElementById('edit-admin-first').value.trim();
                const last_name = document.getElementById('edit-admin-last').value.trim();
                const email = document.getElementById('edit-admin-email').value.trim();
                const password = document.getElementById('edit-admin-password').value;
                fetch('agency_handler/edit_agency.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ agency_id, agency_name, first_name, last_name, email, password })
                })
                .then(res => res.json())
                .then(data => {
                    saveEditBtn.disabled = false;
                    if (data.success) {
                        editSuccess.textContent = 'Agency updated successfully!';
                        editSuccess.style.display = 'block';
                        // Update the table row
                        const row = document.querySelector('tr[data-agency-id="' + agency_id + '"]');
                        if (row) {
                            row.querySelectorAll('td')[1].textContent = agency_name;
                            row.querySelectorAll('td')[2].textContent = first_name + ' ' + last_name;
                            row.querySelectorAll('td')[3].textContent = email;
                        }
                        setTimeout(() => {
                            editModalBg.removeAttribute('fade-up');
                            editModalBg.setAttribute('fade-out', '');
                            setTimeout(function() {
                                editModalBg.style.display = 'none';
                                editModalBg.removeAttribute('fade-out');
                            }, 450);
                        }, 900);
                    } else {
                        editError.textContent = data.message || 'Failed to update agency.';
                        editError.style.display = 'block';
                    }
                })
                .catch(() => {
                    saveEditBtn.disabled = false;
                    editError.textContent = 'Server error. Please try again.';
                    editError.style.display = 'block';
                });
            });
            editModalBg.addEventListener('click', function(e) {
                if (e.target === editModalBg) {
                    editModalBg.removeAttribute('fade-up');
                    editModalBg.setAttribute('fade-out', '');
                    setTimeout(function() {
                        editModalBg.style.display = 'none';
                        editModalBg.removeAttribute('fade-out');
                    }, 450);
                }
            });
            // Search filter
            const searchInput = document.getElementById('agency-search');
            searchInput.addEventListener('input', function() {
                const filter = searchInput.value.toLowerCase();
                document.querySelectorAll('#agencies-table-body tr').forEach(function(row) {
                    const agency = row.getAttribute('data-agency-name').toLowerCase();
                    const admin = row.getAttribute('data-admin-name').toLowerCase();
                    const email = row.getAttribute('data-email').toLowerCase();
                    if (agency.includes(filter) || admin.includes(filter) || email.includes(filter)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            // Load More
            const loadMoreBtn = document.getElementById('load-more-btn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    const params = new URLSearchParams(window.location.search);
                    let offset = <?= $offset ?> + <?= $limit ?>;
                    params.set('offset', offset);
                    window.location.search = params.toString();
                });
            }
            // Filter dropdown logic
            const filterDropdown = document.getElementById('agency-filter');
            filterDropdown.addEventListener('change', function() {
                const value = filterDropdown.value;
                let rows = Array.from(document.querySelectorAll('#agencies-table-body tr'));
                if (value === 'all') {
                    rows.forEach(row => row.style.display = '');
                } else if (value === 'most-users') {
                    rows.sort((a, b) => parseInt(b.querySelectorAll('td')[5].textContent) - parseInt(a.querySelectorAll('td')[5].textContent));
                    rows.forEach(row => row.parentNode.appendChild(row));
                } else if (value === 'recently-created') {
                    rows.sort((a, b) => new Date(b.querySelectorAll('td')[4].textContent) - new Date(a.querySelectorAll('td')[4].textContent));
                    rows.forEach(row => row.parentNode.appendChild(row));
                }
            });
            // Export only specific agency stats
            function downloadCSV(csv, filename) {
                const csvFile = new Blob([csv], {type: 'text/csv'});
                const downloadLink = document.createElement('a');
                downloadLink.download = filename;
                downloadLink.href = window.URL.createObjectURL(csvFile);
                downloadLink.style.display = 'none';
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            }
            function exportSingleAgencyToCSV(row, filename) {
                // Columns: Agency Name, User Count, Email, Created
                const cols = row.querySelectorAll('td');
                let csv = 'Agency Name,User Count,Email,Created\n';
                let rowData = [];
                rowData.push('"' + cols[1].textContent.trim().replace(/"/g, '""') + '"'); // Agency Name
                rowData.push('"' + cols[5].textContent.trim().replace(/"/g, '""') + '"'); // User Count
                rowData.push('"' + cols[3].textContent.trim().replace(/"/g, '""') + '"'); // Email
                rowData.push('"' + cols[4].textContent.trim().replace(/"/g, '""') + '"'); // Created
                csv += rowData.join(',') + '\n';
                downloadCSV(csv, filename);
            }
            document.querySelectorAll('.action-icon.export').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const row = btn.closest('tr');
                    exportSingleAgencyToCSV(row, 'agency_stats.csv');
                });
            });
            // Share logic
            document.querySelectorAll('.action-icon.share').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const row = btn.closest('tr');
                    const agencyId = row.getAttribute('data-agency-id');
                    const shareUrl = window.location.origin + window.location.pathname + '?agency_id=' + agencyId;
                    navigator.clipboard.writeText(shareUrl).then(function() {
                        const notif = document.getElementById('copied-notification');
                        notif.style.display = 'block';
                        notif.style.opacity = '1';
                        setTimeout(function() {
                            notif.style.opacity = '0';
                            setTimeout(function() {
                                notif.style.display = 'none';
                            }, 500);
                        }, 1500);
                    });
                });
            });
        });
        </script>
    </main>
</div>
</body>
</html> 