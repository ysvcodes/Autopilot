<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../database_connection/connection.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id.']);
    exit;
}
$user_id = $data['user_id'];
try {
    // Delete from agency_users
    $stmt = $pdo->prepare('DELETE FROM agency_users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    // Delete from users
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 