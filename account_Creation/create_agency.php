<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../database_connection/connection.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['agency_name'], $data['first_name'], $data['last_name'], $data['email'], $data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}
$agency_name = trim($data['agency_name']);
$first_name = trim($data['first_name']);
$last_name = trim($data['last_name']);
$email = trim($data['email']);
$password = $data['password'];
if ($agency_name === '' || $first_name === '' || $last_name === '' || $email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}
try {
    $stmt = $pdo->prepare('SELECT id FROM agency_admins WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already exists.']);
        exit;
    }
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->beginTransaction();
    // 1. Insert into agencies table
    $stmt = $pdo->prepare('INSERT INTO agencies (agency_name) VALUES (?)');
    $stmt->execute([$agency_name]);
    $agency_id = $pdo->lastInsertId();
    // 2. Insert into agency_admins with the new agency_id
    $stmt = $pdo->prepare('INSERT INTO agency_admins (agency_id, agency_name, first_name, last_name, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, "adminagency", NOW(), NOW())');
    $stmt->execute([$agency_id, $agency_name, $first_name, $last_name, $email, $password_hash]);
    $admin_id = $pdo->lastInsertId();
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Agency account created successfully!']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 