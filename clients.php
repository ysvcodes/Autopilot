<?php
$active_page = 'clients';
session_start();
include_once 'logs/logout_handler.php';
require_once __DIR__ . '/database_connection/connection.php';

$clients = [];
try {
    $agency_id = $_SESSION['agency_id'];
    $stmt = $pdo->prepare('
        SELECT u.id, u.first_name, u.last_name, u.email, u.created_at,
            (
                SELECT COUNT(au.automation_id)
                FROM automation_users au
                JOIN automations a ON a.id = au.automation_id
                WHERE au.user_id = u.id AND a.agency_id = ?
            ) AS total_automations,
            (
                SELECT COALESCE(SUM(a.pricing), 0)
                FROM automation_users au
                JOIN automations a ON a.id = au.automation_id
                WHERE au.user_id = u.id AND a.agency_id = ?
            ) AS total_payment,
            (
                SELECT MAX(ar.created_at)
                FROM automation_runs ar
                WHERE ar.user_id = u.id AND ar.agency_id = ?
            ) AS last_activity
        FROM users u
        WHERE u.agency_id = ?
        ORDER BY u.created_at DESC
    ');
    $stmt->execute([$agency_id, $agency_id, $agency_id, $agency_id]);
    while ($row = $stmt->fetch()) {
        $clients[] = $row;
    }
} catch (Exception $e) {
    $clients = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clients</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        html, body { height: 100vh; margin: 0; padding: 0; font-family: 'Inter', Arial, sans-serif; background: #0a1622; }
        .admin-layout { display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 32px 48px 0 48px; background: #0a1622; min-width: 0; height: 100vh; overflow-y: auto; }
        .clients-title { font-size:2.2em;font-weight:900;color:#fff;margin-bottom:6px; }
        .welcome { font-size:1.15em;font-weight:700;margin-bottom:2px;color:#fff; }
        .clients-desc { font-size:1.08em;color:#b6c6d7;margin-bottom:32px; }
        .clients-table-container { background:#101c2c;border-radius:14px;box-shadow:0 2px 12px #178fff11;padding:0 0 0 0;max-width:100%;overflow-x:auto;margin-bottom:32px; }
        .clients-table { width:100%;min-width:700px;border-collapse:collapse; }
        .clients-table th { padding:14px 18px;font-weight:800;color:#7ecbff;text-align:left;background:#0a1833; }
        .clients-table td { padding:12px 18px;color:#fff;border-top:1px solid #22325a; }
        .clients-table tr:hover { background:#14213d; }
        
        .view-automations-btn {
            background: #178fff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.9em;
            transition: background 0.2s;
        }
        .view-automations-btn:hover {
            background: #0d6efd;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
        }
        .modal.show {
            display: block;
        }
        .modal-fade-in {
            animation: fadeUpIn 0.45s cubic-bezier(0.23, 1, 0.32, 1);
        }
        .modal-fade-out {
            animation: fadeUpOut 0.35s cubic-bezier(0.23, 1, 0.32, 1);
        }
        @keyframes fadeUpIn {
            0% {
                opacity: 0;
                transform: translateY(40px) scale(0.98);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        @keyframes fadeUpOut {
            0% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
            100% {
                opacity: 0;
                transform: translateY(-30px) scale(0.97);
            }
        }
        
        .modal-content {
            background: #101c2c;
            margin: 5% auto;
            padding: 0;
            border-radius: 14px;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        
        .modal-header {
            background: #0a1833;
            padding: 20px 24px;
            border-radius: 14px 14px 0 0;
            border-bottom: 1px solid #22325a;
            position: relative;
        }
        
        .modal-title {
            color: #fff;
            font-size: 1.4em;
            font-weight: 800;
            margin: 0;
        }
        
        .modal-body {
            padding: 24px;
        }

        /* Stylized input and select fields inside the modal */
        #client-automations-form input[type="number"],
        #client-automations-form select {
            background: #101c2c;
            color: #7ecbff;
            border: 1.5px solid #22325a;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 1.08em;
            margin-right: 8px;
            margin-bottom: 8px;
            transition: border 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 4px #178fff22;
            outline: none;
        }

        #client-automations-form input[type="number"]:focus,
        #client-automations-form select:focus {
            border: 1.5px solid #178fff;
            box-shadow: 0 0 0 2px #178fff55;
        }

        #client-automations-form label,
        #client-automations-form .pricing-info,
        #client-automations-form .automation-details > div {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        #client-automations-form .automation-item {
            margin-bottom: 18px;
            background: #17213a;
            border-radius: 12px;
            border: 1.5px solid #22325a;
            box-shadow: 0 2px 8px #178fff11;
            padding: 18px 20px 14px 20px;
        }

        #client-automations-form .automation-name {
            font-size: 1.15em;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .automation-details {
            color: #b6c6d7;
            font-size: 0.9em;
            line-height: 1.4;
        }
        
        .pricing-info {
            color: #178fff;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .close {
            color: #aaa;
            position: absolute;
            top: 18px;
            right: 32px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .close:hover {
            color: #fff;
        }
        
        .no-automations {
            color: #888;
            text-align: center;
            padding: 20px;
            font-style: italic;
        }
        .status-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: linear-gradient(90deg,#178fff 0%,#7ecbff 100%);
            color: #fff;
            font-weight: 800;
            font-size: 0.93em;
            border: none;
            border-radius: 12px;
            padding: 3px 12px 3px 10px;
            cursor: pointer;
            box-shadow: 0 1px 4px #178fff22;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            outline: none;
            margin-top: 0;
        }
        .status-toggle-btn.inactive {
            background: linear-gradient(90deg,#7ecbff 0%,#178fff 100%);
            color: #fff;
            opacity: 0.7;
        }
        .status-toggle-btn .status-icon {
            font-size: 1em;
            margin-left: 4px;
        }
    </style>
</head>
<body>
<div class="admin-layout">
<?php include 'agency_sidebar.php'; ?>
    <main class="main-content">
        <div class="clients-title">Clients</div>
        <div class="welcome" style="font-size:1.15em;font-weight:700;margin-bottom:2px;color:#fff;">Logged in as <span style="color:#178fff;font-weight:900;"><?= htmlspecialchars($_SESSION['agency_name'] ?? 'Admin') ?></span></div>
        <div class="clients-desc">This is where you will manage, handle, and assign your <b>clients</b>.</div>
        <div class="clients-table-container">
            <table class="clients-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Signup Date</th>
                        <th>Automations</th>
                        <th>Total Payment</th>
                        <th>Last Activity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?></td>
                        <td><?= htmlspecialchars($client['email']) ?></td>
                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($client['created_at']))) ?></td>
                        <td>
                            <button class="view-automations-btn" onclick="openAutomationsModal(<?= $client['id'] ?>, '<?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?>')">
                                View Automations (<?= $client['total_automations'] ?>)
                            </button>
                        </td>
                        <td><span style="color:#7ecbff;font-weight:700;">$<?= number_format($client['total_payment'], 2) ?></span></td>
                        <td><?= $client['last_activity'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($client['last_activity']))) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($clients)): ?>
                    <tr><td colspan="5" style="color:#888;text-align:center;">No clients found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal -->
<div id="automationsModal" class="modal">
    <div class="modal-content" id="modalContent">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Client Automations</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="no-automations">Loading automations...</div>
        </div>
    </div>
</div>

<script>
function openAutomationsModal(userId, clientName) {
    const modal = document.getElementById('automationsModal');
    const modalContent = document.getElementById('modalContent');
    document.getElementById('modalTitle').textContent = clientName + ' - Automations';
    document.getElementById('modalBody').innerHTML = '<div class="no-automations">Loading automations...</div>';
    modal.style.display = 'block';
    modal.classList.add('show');
    modalContent.classList.remove('modal-fade-out');
    modalContent.classList.add('modal-fade-in');
    
    // Fetch automations for this client
    fetch('get_client_automations.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            let html = '';
            if (data.automations && data.automations.length > 0) {
                html += '<form id="client-automations-form">';
                data.automations.forEach((automation, idx) => {
                    const isActive = automation.active == 1;
                    html += `
                        <div class="automation-item" data-automation-id="${automation.id}">
                            <div class="automation-name" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                                <span>${automation.name}</span>
                                <button type="button" class="status-toggle-btn${isActive ? '' : ' inactive'}" data-automation-id="${automation.id}">
                                    ${isActive ? 'Active' : 'Inactive'}
                                    <span class="status-icon">${isActive ? '&#9654;' : '&#10073;&#10073;'}</span>
                                </button>
                                <input type="hidden" name="active_${automation.id}" value="${isActive ? '1' : '0'}">
                            </div>
                            <div class="automation-details">
                                <div><strong>Type:</strong> ${automation.type}</div>
                                <div><strong>Description:</strong> ${automation.description || 'No description available'}</div>
                                <div class="pricing-info">
                                    <strong>Pricing:</strong> 
                                    <input type="number" step="0.01" min="0" name="custom_pricing_${automation.id}" value="${automation.custom_pricing !== null ? automation.custom_pricing : automation.pricing}" style="width:90px;"> 
                                    <select name="custom_pricing_model_${automation.id}">
                                        <option value="one_time" ${automation.custom_pricing_model === 'one_time' ? 'selected' : ''}>One Time</option>
                                        <option value="monthly" ${automation.custom_pricing_model === 'monthly' ? 'selected' : ''}>Monthly</option>
                                        <option value="per_run" ${automation.custom_pricing_model === 'per_run' ? 'selected' : ''}>Per Run</option>
                                        <option value="free_trial" ${automation.custom_pricing_model === 'free_trial' ? 'selected' : ''}>Free Trial</option>
                                        <option value="first_run_free" ${automation.custom_pricing_model === 'first_run_free' ? 'selected' : ''}>First Run Free</option>
                                    </select>
                                </div>
                                <div style="margin-top:8px;">
                                    <strong>Schedule:</strong> 
                                    <select name="custom_schedule_${automation.id}">
                                        <option value="manual" ${automation.custom_schedule === 'manual' ? 'selected' : ''}>Manual</option>
                                        <option value="daily" ${automation.custom_schedule === 'daily' ? 'selected' : ''}>Daily</option>
                                        <option value="weekly" ${automation.custom_schedule === 'weekly' ? 'selected' : ''}>Weekly</option>
                                        <option value="monthly" ${automation.custom_schedule === 'monthly' ? 'selected' : ''}>Monthly</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '<div style="text-align:right;margin-top:18px;"><button type="submit" id="save-client-automations-btn" style="background:#178fff;color:#fff;font-weight:700;font-size:1.08em;padding:10px 28px;border:none;border-radius:8px;cursor:pointer;">Save</button></div>';
                html += '</form>';
            } else {
                html = '<div class="no-automations">No automations assigned to this client.</div>';
            }
            document.getElementById('modalBody').innerHTML = html;

            // Add save handler
            const form = document.getElementById('client-automations-form');
            if (form) {
                form.onsubmit = function(e) {
                    e.preventDefault();
                    const formData = new FormData(form);
                    const updates = [];
                    data.automations.forEach(automation => {
                        updates.push({
                            automation_id: automation.id,
                            user_id: userId,
                            custom_pricing: formData.get('custom_pricing_' + automation.id),
                            custom_pricing_model: formData.get('custom_pricing_model_' + automation.id),
                            custom_schedule: formData.get('custom_schedule_' + automation.id),
                            active: formData.get('active_' + automation.id) ? 1 : 0
                        });
                    });
                    fetch('agency_handler/update_client_automations.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ updates })
                    })
                    .then(res => res.json())
                    .then(resp => {
                        if (resp.success) {
                            alert('Saved successfully!');
                        } else {
                            alert('Failed to save: ' + (resp.message || 'Unknown error'));
                        }
                    })
                    .catch(() => {
                        alert('Failed to save. Please try again.');
                    });
                };
            }
            // Add toggle logic for status buttons
            document.querySelectorAll('.status-toggle-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const automationId = btn.getAttribute('data-automation-id');
                    const hiddenInput = btn.parentElement.querySelector('input[name="active_' + automationId + '"]');
                    const isActive = btn.classList.contains('inactive') ? false : true;
                    if (isActive) {
                        btn.classList.add('inactive');
                        btn.innerHTML = 'Inactive <span class="status-icon">&#10073;&#10073;</span>';
                        hiddenInput.value = '0';
                    } else {
                        btn.classList.remove('inactive');
                        btn.innerHTML = 'Active <span class="status-icon">&#9654;</span>';
                        hiddenInput.value = '1';
                    }
                });
            });
        })
        .catch(error => {
            document.getElementById('modalBody').innerHTML = '<div class="no-automations">Error loading automations. Please try again.</div>';
        });
}

function closeModal() {
    const modal = document.getElementById('automationsModal');
    const modalContent = document.getElementById('modalContent');
    modalContent.classList.remove('modal-fade-in');
    modalContent.classList.add('modal-fade-out');
    // Wait for animation to finish before hiding
    modalContent.addEventListener('animationend', function handler(e) {
        if (e.animationName === 'fadeUpOut') {
            modal.style.display = 'none';
            modal.classList.remove('show');
            modalContent.removeEventListener('animationend', handler);
        }
    });
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('automationsModal');
    const modalContent = document.getElementById('modalContent');
    if (event.target == modal) {
        closeModal();
    }
}
</script>
</body>
</html> 