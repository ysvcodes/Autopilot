<?php
header('Content-Type: application/json');
require_once __DIR__ . '/database_connection/connection.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['agency_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing agency_id.']);
    exit;
}
$agency_id = $data['agency_id'];
try {
    // Set users' agency_id to NULL
    $stmt = $pdo->prepare('UPDATE users SET agency_id = NULL WHERE agency_id = ?');
    $stmt->execute([$agency_id]);
    // Delete from agency_users
    $stmt = $pdo->prepare('DELETE FROM agency_users WHERE agency_id = ?');
    $stmt->execute([$agency_id]);
    // Delete from agency_admins
    $stmt = $pdo->prepare('DELETE FROM agency_admins WHERE agency_id = ?');
    $stmt->execute([$agency_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 