<?php
require_once '../database_connection/connection.php';
header('Content-Type: application/json');
$automation_id = $_GET['automation_id'] ?? null;
if (!$automation_id) {
    echo json_encode(['success' => false, 'message' => 'Missing automation ID.', 'users' => []]);
    exit;
}
try {
    $stmt = $pdo->prepare('SELECT u.first_name, u.last_name, u.email FROM automation_users au JOIN users u ON au.user_id = u.id WHERE au.automation_id = ?');
    $stmt->execute([$automation_id]);
    $users = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users[] = [
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'email' => $row['email']
        ];
    }
    echo json_encode(['success' => true, 'users' => $users]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error', 'users' => []]);
} 