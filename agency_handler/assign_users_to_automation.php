<?php
require_once '../database_connection/connection.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$automation_id = $data['automation_id'] ?? null;
$user_ids = $data['user_ids'] ?? null;

if (!$automation_id || !is_array($user_ids)) {
    echo json_encode(['success' => false, 'message' => 'Missing automation_id or user_ids.']);
    exit;
}

try {
    // Remove all current assignments for this automation
    $stmt = $pdo->prepare('DELETE FROM automation_users WHERE automation_id = ?');
    $stmt->execute([$automation_id]);
    // Add new assignments
    if (count($user_ids) > 0) {
        $stmt = $pdo->prepare('INSERT INTO automation_users (automation_id, user_id) VALUES (?, ?)');
        foreach ($user_ids as $uid) {
            $stmt->execute([$automation_id, $uid]);
        }
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
} 