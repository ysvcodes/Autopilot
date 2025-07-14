<?php
require_once '../database_connection/connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$automation_id = $_POST['automation_id'] ?? null;

if (!$automation_id) {
    echo json_encode(['success' => false, 'message' => 'Missing automation ID.']);
    exit;
}

try {
    // Get automation details before deletion for logging
    $stmt = $pdo->prepare('SELECT agency_id, name FROM automations WHERE id = ?');
    $stmt->execute([$automation_id]);
    $automation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$automation) {
        echo json_encode(['success' => false, 'message' => 'Automation not found.']);
        exit;
    }
    
    $agency_id = $automation['agency_id'];
    $automation_name = $automation['name'];
    
    // Delete automation (this will cascade delete related records due to foreign key constraints)
    $stmt = $pdo->prepare('DELETE FROM automations WHERE id = ?');
    $stmt->execute([$automation_id]);
    
    // Log automation deletion activity
    $stmt2 = $pdo->prepare('INSERT INTO activity_log (agency_id, admin_id, type, description) VALUES (?, ?, ?, ?)');
    $stmt2->execute([$agency_id, $_SESSION['admin_id'] ?? 1, 'automation_deleted', "Automation '$automation_name' deleted successfully"]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 