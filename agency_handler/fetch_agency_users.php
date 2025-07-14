<?php
require_once '../database_connection/connection.php';
header('Content-Type: application/json');
$automation_id = $_GET['automation_id'] ?? null;
if (!$automation_id) {
    echo json_encode(['success' => false, 'message' => 'Missing automation ID.', 'users' => []]);
    exit;
}
try {
    // Get agency_id for the automation
    $stmt = $pdo->prepare('SELECT agency_id FROM automations WHERE id = ?');
    $stmt->execute([$automation_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Automation not found.', 'users' => []]);
        exit;
    }
    $agency_id = $row['agency_id'];
    // Get all users for the agency
    $stmt = $pdo->prepare('SELECT id, first_name, last_name, email FROM users WHERE agency_id = ?');
    $stmt->execute([$agency_id]);
    $users = [];
    while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users[] = [
            'id' => $user['id'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'email' => $user['email'],
            'assigned' => false
        ];
    }
    // Get assigned user ids
    $stmt = $pdo->prepare('SELECT user_id FROM automation_users WHERE automation_id = ?');
    $stmt->execute([$automation_id]);
    $assigned_ids = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'user_id');
    // Mark assigned
    foreach ($users as &$user) {
        if (in_array($user['id'], $assigned_ids)) {
            $user['assigned'] = true;
        }
    }
    echo json_encode(['success' => true, 'users' => $users]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error', 'users' => []]);
} 