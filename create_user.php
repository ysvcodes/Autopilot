<?php
header('Content-Type: application/json');
require_once __DIR__ . '/database_connection/connection.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['first_name'], $data['last_name'], $data['email'], $data['password'], $data['agency_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}
$first_name = trim($data['first_name']);
$last_name = trim($data['last_name']);
$email = trim($data['email']);
$password = $data['password'];
$agency_id = $data['agency_id'];
if ($first_name === '' || $last_name === '' || $email === '' || $password === '' || $agency_id === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}
try {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already exists.']);
        exit;
    }
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO users (agency_id, first_name, last_name, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, "user", NOW(), NOW())');
    $stmt->execute([$agency_id, $first_name, $last_name, $email, $password_hash]);
    $user_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare('INSERT INTO agency_users (agency_id, user_id) VALUES (?, ?)');
    $stmt->execute([$agency_id, $user_id]);
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'User account created successfully!']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 