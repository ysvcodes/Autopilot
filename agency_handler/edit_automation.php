<?php
require_once '../database_connection/connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$automation_id = $_POST['automation_id'] ?? null;
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$tags = trim($_POST['tags'] ?? '');
$pricing = trim($_POST['pricing'] ?? '');
$pricing_model = trim($_POST['pricing_model'] ?? 'one_time');
$status = trim($_POST['status'] ?? 'inactive');

if (!$automation_id || $name === '' || $description === '' || $pricing === '' || !is_numeric($pricing)) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid fields.']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE automations SET name = ?, description = ?, tags = ?, pricing = ?, pricing_model = ?, status = ? WHERE id = ?');
    $stmt->execute([$name, $description, $tags, $pricing, $pricing_model, $status, $automation_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 