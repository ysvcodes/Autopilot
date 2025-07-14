<?php
session_start();
require_once __DIR__ . '/database_connection/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['agency_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

$user_id = intval($_GET['user_id']);
$agency_id = $_SESSION['agency_id'];

try {
    $stmt = $pdo->prepare('
        SELECT a.id, a.name, a.type, a.description,
               au.custom_pricing, au.custom_pricing_model, au.custom_schedule, au.active,
               a.pricing, a.pricing_model
        FROM automations a
        JOIN automation_users au ON a.id = au.automation_id
        WHERE au.user_id = ? AND a.agency_id = ?
        ORDER BY a.name ASC
    ');
    
    $stmt->execute([$user_id, $agency_id]);
    $automations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'automations' => $automations
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 