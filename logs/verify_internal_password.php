<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/database_connection/connection.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Missing password.']);
    exit;
}
if (!isset($_SESSION['user_name'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}
$user_name = $_SESSION['user_name'];
$password = $data['password'];
// Fetch the password hash for the logged-in internal user
$stmt = $pdo->prepare('SELECT password_hash FROM internal WHERE name = ?');
$stmt->execute([$user_name]);
$row = $stmt->fetch();
if ($row && password_verify($password, $row['password_hash'])) {
    echo json_encode(['success' => true, 'message' => 'Password verified.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
} 