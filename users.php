<?php
// users.php
$user_name = 'Admin'; // Replace with session or actual user logic if needed
// Include logout handler
include_once 'logs/logout_handler.php';
require_once __DIR__ . '/database_connection/connection.php';

// Fetch all agencies for filter dropdown and edit modal
$agency_options = [];
try {
    $stmt = $pdo->query('SELECT agency_id, agency_name FROM agency_admins WHERE role = "adminagency"');
    while ($row = $stmt->fetch()) {
        $agency_options[] = $row;
    }
} catch (Exception $e) {
    $agency_options = [];
}

// Pagination
$limit = 25;
$offset = 0;
if (isset($_GET['offset']) && is_numeric($_GET['offset'])) {
    $offset = (int)$_GET['offset'];
}

// Fetch users with agency name and count of active automations
$users = [];
$total_users = 0;
try {
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM users');
    $row = $stmt->fetch();
    $total_users = $row ? (int)$row['total'] : 0;
} catch (Exception $e) {
    $total_users = 0;
}
try {
    $stmt = $pdo->prepare('
        SELECT u.id, u.first_name, u.last_name, u.email, u.created_at, u.agency_id,
            (SELECT agency_name FROM agency_admins WHERE agency_id = u.agency_id AND role = "adminagency" LIMIT 1) AS agency_name,
            (
                SELECT COUNT(*) FROM automation_users au
                JOIN automations am ON am.id = au.automation_id
                WHERE au.user_id = u.id AND am.status = "active"
            ) AS active_automations
        FROM users u
        ORDER BY u.created_at DESC
        LIMIT :limit OFFSET :offset
    ');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        $users[] = $row;
    }
} catch (Exception $e) {
    $users = [];
}
$show_load_more = ($offset + $limit) < $total_users;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users - Admin Panel</title>
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
        .users-title { font-size:2.2em;font-weight:900;color:#14213d;margin-bottom:6px; }
        .users-subtitle { font-size:1.1em;color:#4a5a6a;margin-bottom:24px; }
        .action-icon { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; margin: 0 4px; border-radius: 8px; background: none; transition: background 0.18s, color 0.18s, filter 0.18s; vertical-align: middle; border: none; outline: none; cursor: pointer; }
        .action-icon svg { display: block; margin: auto; pointer-events: none; }
        .action-icon.edit:hover { background: #178fff; }
        .action-icon.edit:hover svg { stroke: #fff; }
        .action-icon.delete:hover { background: #e3342f; }
        .action-icon.delete:hover svg { stroke: #fff; }
        .action-icon.share:hover { background: #22bb55; }
        .action-icon.share:hover svg { stroke: #fff; }
        .action-icon.export:hover { background: #222; }
        .action-icon.export:hover svg { stroke: #fff; }
        .action-icon:not(:last-child) { margin-right: 8px; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(-40px); } }
        #delete-modal-bg[fade-up] > #delete-modal, #edit-modal-bg[fade-up] > #edit-modal { animation: fadeUp 0.45s cubic-bezier(.4,1.4,.6,1) forwards; }
        #delete-modal-bg[fade-out] > #delete-modal, #edit-modal-bg[fade-out] > #edit-modal { animation: fadeOut 0.45s cubic-bezier(.4,1.4,.6,1) forwards; }
    </style>
</head>
<body>
<div class="admin-layout">
<?php $active_page = 'users'; include 'sidebar.php'; ?>
    <main class="main-content">
        <div style="font-size:1em;font-weight:800;color:#111;margin-bottom:2px;">Admin View</div>
        <div class="welcome" style="margin-bottom:12px;font-size:2em;font-weight:700;">Logged in as <span style="color:#178fff;font-weight:900;">Admin</span></div>
        <div class="users-title">Users Panel</div>
        <div class="users-subtitle"><span class="icon">👤</span> Manage and view all registered users in the system.</div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <div>
                <select id="user-filter" style="padding:9px 16px;border-radius:8px;border:1.5px solid #e3e8f0;font-size:1.08em;outline:none;margin-right:16px;">
                    <option value="all">All</option>
                    <option value="recently-created">Recently Created</option>
                    <?php foreach ($agency_options as $agency): ?>
                        <option value="agency-<?= htmlspecialchars($agency['agency_id']) ?>">Agency: <?= htmlspecialchars($agency['agency_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="position:relative;">
                <input id="user-search" type="text" placeholder="Search users..." style="padding:10px 16px;border-radius:8px;border:1.5px solid #e3e8f0;font-size:1.08em;width:260px;outline:none;" />
                <svg style="position:absolute;right:12px;top:50%;transform:translateY(-50%);opacity:0.5;" width="18" height="18" fill="none" stroke="#22325a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
        </div>
        <div style="background:#fff;border-radius:14px;box-shadow:0 2px 12px #178fff11;padding:0 0 0 0;max-width:100%;overflow-x:auto;margin-bottom:32px;">
            <div style="max-height:340px;overflow-y:auto;">
                <table style="width:100%;min-width:950px;border-collapse:collapse;">
                    <thead>
                        <tr style="color:#888;font-size:1em;text-align:left;background:#f7f9fb;">
                            <th style="padding:14px 18px;font-weight:800;">Name</th>
                            <th style="padding:14px 18px;font-weight:800;">Email</th>
                            <th style="padding:14px 18px;font-weight:800;">Agency</th>
                            <th style="padding:14px 18px;font-weight:800;text-align:center;">Active Automations</th>
                            <th style="padding:14px 18px;font-weight:800;">Created</th>
                            <th style="padding:14px 18px;font-weight:800;text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body">
                        <?php foreach ($users as $user): ?>
                        <tr data-user-id="<?= htmlspecialchars($user['id']) ?>" data-first-name="<?= htmlspecialchars($user['first_name']) ?>" data-last-name="<?= htmlspecialchars($user['last_name']) ?>" data-email="<?= htmlspecialchars($user['email']) ?>" data-agency-id="<?= htmlspecialchars($user['agency_id']) ?>" data-agency-name="<?= htmlspecialchars($user['agency_name']) ?>" style="border-top:1px solid #e3e8f0;transition:background 0.15s;">
                            <td style="padding:12px 18px;font-weight:700;"> <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> </td>
                            <td style="padding:12px 18px;"> <?= htmlspecialchars($user['email']) ?> </td>
                            <td style="padding:12px 18px;"> <?= htmlspecialchars($user['agency_name'] ?? '-') ?> </td>
                            <td style="padding:12px 18px;text-align:center;"> <?= (int)$user['active_automations'] ?> </td>
                            <td style="padding:12px 18px;"> <?= date('Y-m-d', strtotime($user['created_at'])) ?> </td>
                            <td style="padding:12px 18px;text-align:center;">
                                <button class="action-icon edit" title="Edit User">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#178fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19.5 3 21l1.5-4L16.5 3.5z"/></svg>
                                </button>
                                <button class="action-icon delete" title="Delete User">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e3342f" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                      <polyline points="3 6 5 6 21 6"/>
                                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                      <line x1="10" y1="11" x2="10" y2="17"/>
                                      <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                </button>
                                <button class="action-icon share" title="Share User">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22bb55" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                                </button>
                                <button class="action-icon export" title="Export User">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#222" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12h8M8 16h8M8 8h8"/></svg>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                        <tr><td colspan="6" style="padding:12px 18px;color:#888;text-align:center;">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;align-items:center;margin-top:8px;">
            <span style="color:#888;font-size:1em;margin-right:18px;">Showing <?= $offset+1 ?>-<?= min($offset+$limit, $total_users) ?> of <?= $total_users ?></span>
            <?php if ($show_load_more): ?>
            <button id="load-more-btn" style="background:#178fff;color:#fff;font-weight:700;font-size:1.08em;padding:8px 22px;border:none;border-radius:8px;cursor:pointer;">Load More</button>
            <?php endif; ?>
        </div>
        <!-- Delete Confirmation Modal -->
        <div id="delete-modal-bg" style="display:none;position:fixed;z-index:3000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.45);align-items:center;justify-content:center;">
            <div id="delete-modal" style="background:#fff;padding:32px 32px 24px 32px;border-radius:16px;box-shadow:0 2px 16px #22325a33;max-width:95vw;width:370px;text-align:center;">
                <div style="font-size:1.25em;font-weight:800;color:#14213d;margin-bottom:12px;">Delete User</div>
                <div id="delete-modal-msg" style="font-size:1.08em;color:#22325a;margin-bottom:24px;"></div>
                <button id="confirm-delete-btn" style="background:#e3342f;color:#fff;font-weight:700;font-size:1.08em;padding:10px 28px;border:none;border-radius:8px;cursor:pointer;margin-right:12px;">Delete</button>
                <button id="cancel-delete-btn" style="background:#f7f9fb;color:#22325a;font-weight:700;font-size:1.08em;padding:10px 28px;border:none;border-radius:8px;cursor:pointer;">Cancel</button>
            </div>
        </div>
        <!-- Edit User Modal -->
        <div id="edit-modal-bg" style="display:none;position:fixed;z-index:3000;top:0;left:0;width:100vw;height:100vh;background:rgba(10,20,40,0.45);align-items:center;justify-content:center;">
            <div id="edit-modal" style="background:#fff;padding:32px 32px 24px 32px;border-radius:16px;box-shadow:0 2px 16px #22325a33;max-width:95vw;width:400px;text-align:left;">
                <div style="font-size:1.25em;font-weight:800;color:#14213d;margin-bottom:12px;">Edit User</div>
                <form id="edit-user-form" autocomplete="off">
                    <input type="hidden" name="user_id" id="edit-user-id">
                    <label style="font-weight:600;color:#22325a;">First Name
                        <input type="text" name="first_name" id="edit-user-first" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #e3e8f0;background:#f7f9fb;color:#22325a;width:100%;font-size:1.08em;" required />
                    </label>
                    <label style="font-weight:600;color:#22325a;display:block;margin-top:12px;">Last Name
                        <input type="text" name="last_name" id="edit-user-last" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1px solid #e3e8f0;background:#f7f9fb;color:#22325a;width:100%;font-size:1.08em;" required />
                    </label>
                    <label style="font-weight:600;color:#22325a;display:block;margin-top:12px;">Email
                        <input type="email" name="email" id="edit-user-email" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1.5px solid #e3e8f0;background:#f7f9fb;color:#22325a;width:100%;font-size:1.08em;" required />
                    </label>
                    <label style="font-weight:600;color:#22325a;display:block;margin-top:12px;">Agency
                        <select name="agency_id" id="edit-user-agency" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1.5px solid #e3e8f0;background:#f7f9fb;color:#22325a;width:100%;font-size:1.08em;">
                            <option value="">Select Agency</option>
                            <?php foreach ($agency_options as $agency): ?>
                                <option value="<?= htmlspecialchars($agency['agency_id']) ?>"><?= htmlspecialchars($agency['agency_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label style="font-weight:600;color:#22325a;display:block;margin-top:12px;">Password
                        <input type="text" name="password" id="edit-user-password" style="margin-top:4px;padding:10px 14px;box-sizing:border-box;border-radius:8px;border:1.5px solid #e3e8f0;background:#f7f9fb;color:#22325a;width:100%;font-size:1.08em;" autocomplete="new-password" />
                        <span id="edit-user-password-note" style="font-size:0.95em;color:#888;">Leave blank to keep current password.</span>
                    </label>
                    <div id="edit-user-error" style="display:none;color:#e3342f;background:#f8d7da;padding:10px;border-radius:6px;text-align:center;font-weight:700;margin-top:12px;"></div>
                    <div id="edit-user-success" style="display:none;color:#4bb543;background:#d4edda;padding:10px;border-radius:6px;text-align:center;font-weight:700;margin-top:12px;"></div>
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
            // --- Delete Modal Logic ---
            let deleteUserId = null;
            let deleteUserRow = null;
            let deleteUserName = '';
            const deleteModalBg = document.getElementById('delete-modal-bg');
            const deleteModalMsg = document.getElementById('delete-modal-msg');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
            document.querySelectorAll('.action-icon.delete').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const row = btn.closest('tr');
                    deleteUserId = row.getAttribute('data-user-id');
                    deleteUserName = row.getAttribute('data-first-name') + ' ' + row.getAttribute('data-last-name');
                    deleteUserRow = row;
                    deleteModalMsg.textContent = `Are you sure you want to delete user "${deleteUserName}"?`;
                    deleteModalBg.style.display = 'flex';
                    deleteModalBg.removeAttribute('fade-out');
                    deleteModalBg.setAttribute('fade-up', '');
                });
            });
            cancelDeleteBtn.addEventListener('click', function() {
                deleteModalBg.removeAttribute('fade-up');
                deleteModalBg.setAttribute('fade-out', '');
                setTimeout(function() {
                    deleteModalBg.style.display = 'none';
                    deleteModalBg.removeAttribute('fade-out');
                }, 450);
            });
            deleteModalBg.addEventListener('click', function(e) {
                if (e.target === deleteModalBg) {
                    deleteModalBg.removeAttribute('fade-up');
                    deleteModalBg.setAttribute('fade-out', '');
                    setTimeout(function() {
                        deleteModalBg.style.display = 'none';
                        deleteModalBg.removeAttribute('fade-out');
                    }, 450);
                }
            });
            confirmDeleteBtn.addEventListener('click', function() {
                confirmDeleteBtn.disabled = true;
                fetch('account_Creation/delete_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: deleteUserId })
                })
                .then(res => res.json())
                .then(data => {
                    confirmDeleteBtn.disabled = false;
                    if (data.success) {
                        if (deleteUserRow) deleteUserRow.remove();
                        deleteModalBg.removeAttribute('fade-up');
                        deleteModalBg.setAttribute('fade-out', '');
                        setTimeout(function() {
                            deleteModalBg.style.display = 'none';
                            deleteModalBg.removeAttribute('fade-out');
                        }, 450);
                    } else {
                        deleteModalMsg.textContent = data.message || 'Failed to delete user.';
                    }
                })
                .catch(() => {
                    confirmDeleteBtn.disabled = false;
                    deleteModalMsg.textContent = 'Server error. Please try again.';
                });
            });

            // --- Edit Modal Logic ---
            let editModalBg = document.getElementById('edit-modal-bg');
            let editForm = document.getElementById('edit-user-form');
            let editError = document.getElementById('edit-user-error');
            let editSuccess = document.getElementById('edit-user-success');
            let saveEditBtn = document.getElementById('save-edit-btn');
            let cancelEditBtn = document.getElementById('cancel-edit-btn');
            document.querySelectorAll('.action-icon.edit').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const row = btn.closest('tr');
                    document.getElementById('edit-user-id').value = row.getAttribute('data-user-id');
                    document.getElementById('edit-user-first').value = row.getAttribute('data-first-name');
                    document.getElementById('edit-user-last').value = row.getAttribute('data-last-name');
                    document.getElementById('edit-user-email').value = row.getAttribute('data-email');
                    document.getElementById('edit-user-agency').value = row.getAttribute('data-agency-id');
                    document.getElementById('edit-user-password').value = '';
                    editError.style.display = 'none';
                    editSuccess.style.display = 'none';
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
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                saveEditBtn.disabled = true;
                editError.style.display = 'none';
                editSuccess.style.display = 'none';
                const user_id = document.getElementById('edit-user-id').value;
                const first_name = document.getElementById('edit-user-first').value.trim();
                const last_name = document.getElementById('edit-user-last').value.trim();
                const email = document.getElementById('edit-user-email').value.trim();
                const agency_id = document.getElementById('edit-user-agency').value;
                const password = document.getElementById('edit-user-password').value;
                fetch('account_Creation/edit_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id, first_name, last_name, email, agency_id, password })
                })
                .then(res => res.json())
                .then(data => {
                    saveEditBtn.disabled = false;
                    if (data.success) {
                        editSuccess.textContent = 'User updated successfully!';
                        editSuccess.style.display = 'block';
                        // Update the table row
                        const row = document.querySelector('tr[data-user-id="' + user_id + '"]');
                        if (row) {
                            row.setAttribute('data-first-name', first_name);
                            row.setAttribute('data-last-name', last_name);
                            row.setAttribute('data-email', email);
                            row.setAttribute('data-agency-id', agency_id);
                            // Use agency_name from backend response
                            row.setAttribute('data-agency-name', data.agency_name || '-');
                            row.querySelectorAll('td')[4].textContent = data.agency_name || '-';
                            row.querySelectorAll('td')[1].textContent = first_name;
                            row.querySelectorAll('td')[2].textContent = last_name;
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
                        editError.textContent = data.message || 'Failed to update user.';
                        editError.style.display = 'block';
                    }
                })
                .catch(() => {
                    saveEditBtn.disabled = false;
                    editError.textContent = 'Server error. Please try again.';
                    editError.style.display = 'block';
                });
            });

            // --- Share Logic ---
            document.querySelectorAll('.action-icon.share').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const row = btn.closest('tr');
                    const userId = row.getAttribute('data-user-id');
                    const shareLink = window.location.origin + '/user/' + userId; // Example link
                    navigator.clipboard.writeText(shareLink).then(function() {
                        const notif = document.getElementById('copied-notification');
                        notif.textContent = 'Copied to clipboard!';
                        notif.style.opacity = '1';
                        notif.style.display = 'block';
                        setTimeout(function() {
                            notif.style.opacity = '0';
                            setTimeout(function() { notif.style.display = 'none'; }, 600);
                        }, 1500);
                    });
                });
            });

            // --- Export Logic ---
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
            function exportSingleUserToCSV(row, filename) {
                // Columns: First Name, Last Name, Email, Agency, Created
                const cols = row.querySelectorAll('td');
                let csv = 'First Name,Last Name,Email,Agency,Created\n';
                let rowData = [];
                rowData.push('"' + cols[1].textContent.trim().replace(/"/g, '""') + '"'); // First Name
                rowData.push('"' + cols[2].textContent.trim().replace(/"/g, '""') + '"'); // Last Name
                rowData.push('"' + cols[3].textContent.trim().replace(/"/g, '""') + '"'); // Email
                rowData.push('"' + cols[4].textContent.trim().replace(/"/g, '""') + '"'); // Agency
                rowData.push('"' + cols[5].textContent.trim().replace(/"/g, '""') + '"'); // Created
                csv += rowData.join(',') + '\n';
                downloadCSV(csv, filename);
            }
            document.querySelectorAll('.action-icon.export').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const row = btn.closest('tr');
                    exportSingleUserToCSV(row, 'user_stats.csv');
                });
            });

            // --- Search & Filter Logic ---
            const searchInput = document.getElementById('user-search');
            searchInput.addEventListener('input', function() {
                const filter = searchInput.value.toLowerCase();
                document.querySelectorAll('#users-table-body tr').forEach(function(row) {
                    const first = row.getAttribute('data-first-name').toLowerCase();
                    const last = row.getAttribute('data-last-name').toLowerCase();
                    const email = row.getAttribute('data-email').toLowerCase();
                    const agency = (row.getAttribute('data-agency-name') || '').toLowerCase();
                    if (first.includes(filter) || last.includes(filter) || email.includes(filter) || agency.includes(filter)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            const filterDropdown = document.getElementById('user-filter');
            filterDropdown.addEventListener('change', function() {
                const value = filterDropdown.value;
                let rows = Array.from(document.querySelectorAll('#users-table-body tr'));
                if (value === 'all') {
                    rows.forEach(row => row.style.display = '');
                } else if (value === 'recently-created') {
                    rows.sort((a, b) => new Date(b.querySelectorAll('td')[5].textContent) - new Date(a.querySelectorAll('td')[5].textContent));
                    rows.forEach(row => row.parentNode.appendChild(row));
                } else if (value.startsWith('agency-')) {
                    const agencyId = value.split('-')[1];
                    rows.forEach(row => {
                        if (row.getAttribute('data-agency-id') === agencyId) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }
            });
            // --- Pagination (Load More) ---
            const loadMoreBtn = document.getElementById('load-more-btn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    const params = new URLSearchParams(window.location.search);
                    let offset = <?= $offset ?> + <?= $limit ?>;
                    params.set('offset', offset);
                    window.location.search = params.toString();
                });
            }
        });
        </script>
    </main>
</div>
</body>
</html> 