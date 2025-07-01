<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/database_connection/connection.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Missing password.']);
    exit;
}
$password = $data['password'];
// Check against hardcoded password 'admin'
if ($password === 'admin') {
    echo json_encode(['success' => true, 'message' => 'Password verified.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
} 