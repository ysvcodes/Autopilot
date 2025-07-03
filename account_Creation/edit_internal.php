<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../database_connection/connection.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id'], $data['name'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}
$id = $data['id'];
$name = trim($data['name']);
$password = isset($data['password']) ? $data['password'] : '';
if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Name is required.']);
    exit;
}
try {
    // Check for duplicate name (other than this user)
    $stmt = $pdo->prepare('SELECT id FROM internal WHERE name = ? AND id != ?');
    $stmt->execute([$name, $id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Name already exists for another internal user.']);
        exit;
    }
    if ($password !== '') {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE internal SET name = ?, password_hash = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$name, $password_hash, $id]);
    } else {
        $stmt = $pdo->prepare('UPDATE internal SET name = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$name, $id]);
    }
    echo json_encode(['success' => true, 'message' => 'Internal user updated successfully!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 