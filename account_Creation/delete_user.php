<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../database_connection/connection.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id.']);
    exit;
}
$user_id = $data['user_id'];
// Fetch agency_id before deletion
$stmt = $pdo->prepare('SELECT agency_id, first_name, last_name, email FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$agency_id = $user ? $user['agency_id'] : null;
$first_name = $user ? $user['first_name'] : '';
$last_name = $user ? $user['last_name'] : '';
$email = $user ? $user['email'] : '';
try {
    // Delete from agency_users
    $stmt = $pdo->prepare('DELETE FROM agency_users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    // Delete from users
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    // Log user removal from agency
    if ($agency_id) {
        $stmt = $pdo->prepare('INSERT INTO activity_log (agency_id, user_id, type, description) VALUES (?, ?, ?, ?)');
        $stmt->execute([$agency_id, $user_id, 'user_removed', "User $first_name $last_name ($email) removed from agency"]);
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 