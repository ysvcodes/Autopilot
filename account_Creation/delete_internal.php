<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../database_connection/connection.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing user ID.']);
    exit;
}
$id = $data['id'];
try {
    $stmt = $pdo->prepare('DELETE FROM internal WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Internal user deleted successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 