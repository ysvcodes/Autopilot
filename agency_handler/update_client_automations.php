<?php
require_once '../database_connection/connection.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$updates = $data['updates'] ?? [];

if (!is_array($updates) || empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'No updates provided.']);
    exit;
}

try {
    foreach ($updates as $u) {
        $automation_id = $u['automation_id'];
        $user_id = $u['user_id'];
        $custom_pricing = $u['custom_pricing'] !== '' ? $u['custom_pricing'] : null;
        $custom_pricing_model = $u['custom_pricing_model'] ?? null;
        $custom_schedule = $u['custom_schedule'] ?? null;
        $active = isset($u['active']) ? (int)$u['active'] : 1;
        $stmt = $pdo->prepare('UPDATE automation_users SET custom_pricing = ?, custom_pricing_model = ?, custom_schedule = ?, active = ? WHERE automation_id = ? AND user_id = ?');
        $stmt->execute([$custom_pricing, $custom_pricing_model, $custom_schedule, $active, $automation_id, $user_id]);
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 