<?php
require_once '../database_connection/connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$automation_id = $_POST['automation_id'] ?? null;
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$tags = trim($_POST['tags'] ?? '');
$pricing = trim($_POST['pricing'] ?? '');
$pricing_model = trim($_POST['pricing_model'] ?? 'one_time');
$status = trim($_POST['status'] ?? 'inactive');

// If only automation_id and status are provided, just update status
if ($automation_id && $status && empty($_POST['name']) && empty($_POST['description']) && empty($_POST['tags']) && empty($_POST['pricing']) && empty($_POST['pricing_model'])) {
    try {
        // Fetch automation name and agency_id for logging
        $stmt = $pdo->prepare('SELECT name, agency_id FROM automations WHERE id = ?');
        $stmt->execute([$automation_id]);
        $automation = $stmt->fetch(PDO::FETCH_ASSOC);
        $automation_name = $automation['name'] ?? '';
        $agency_id = $automation['agency_id'] ?? null;
        $admin_id = $_SESSION['admin_id'] ?? 1;
        $stmt = $pdo->prepare('UPDATE automations SET status = ? WHERE id = ?');
        $stmt->execute([$status, $automation_id]);
        // Log activity
        if ($status === 'active') {
            $type = 'automation_activated';
            $desc = "Automation '$automation_name' has been activated";
            $notifType = 'success';
            $notifMsg = $desc;
        } else {
            $type = 'automation_deactivated';
            $desc = "Automation '$automation_name' deactivated";
            $notifType = 'error';
            $notifMsg = $desc;
        }
        if ($agency_id) {
            $stmt2 = $pdo->prepare('INSERT INTO activity_log (agency_id, admin_id, type, description) VALUES (?, ?, ?, ?)');
            $stmt2->execute([$agency_id, $admin_id, $type, $desc]);
        }
        echo json_encode(['success' => true, 'notificationType' => $notifType, 'notificationMsg' => $notifMsg]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

if (!$automation_id || $name === '' || $description === '' || $pricing === '' || !is_numeric($pricing)) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid fields.']);
    exit;
}

try {
    // Get agency_id for the automation
    $stmt = $pdo->prepare('SELECT agency_id FROM automations WHERE id = ?');
    $stmt->execute([$automation_id]);
    $automation = $stmt->fetch(PDO::FETCH_ASSOC);
    $agency_id = $automation['agency_id'];
    
    // Remove status from the main update query and its parameter list
    $stmt = $pdo->prepare('UPDATE automations SET name = ?, description = ?, tags = ?, pricing = ?, pricing_model = ? WHERE id = ?');
    $stmt->execute([$name, $description, $tags, $pricing, $pricing_model, $automation_id]);
    
    // Log automation update activity
    $stmt2 = $pdo->prepare('INSERT INTO activity_log (agency_id, admin_id, type, description) VALUES (?, ?, ?, ?)');
    $stmt2->execute([$agency_id, $_SESSION['admin_id'] ?? 1, 'automation_updated', "Automation '$name' updated successfully"]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 