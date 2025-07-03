<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../database_connection/connection.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['name'], $data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}
$name = trim($data['name']);
$password = $data['password'];
if ($name === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}
try {
    // Check if internal user with this name already exists
    $stmt = $pdo->prepare('SELECT id FROM internal WHERE name = ?');
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'An internal user with this name already exists.']);
        exit;
    }
    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    // Insert the new internal user
    $stmt = $pdo->prepare('INSERT INTO internal (name, password_hash, role) VALUES (?, ?, "internal")');
    $stmt->execute([$name, $password_hash]);
    echo json_encode(['success' => true, 'message' => 'Internal user created successfully!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 