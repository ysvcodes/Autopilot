<?php
header('Content-Type: application/json');
require_once __DIR__ . '/database_connection/connection.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['agency_id'], $data['agency_name'], $data['first_name'], $data['last_name'], $data['email'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}
$agency_id = $data['agency_id'];
$agency_name = trim($data['agency_name']);
$first_name = trim($data['first_name']);
$last_name = trim($data['last_name']);
$email = trim($data['email']);
$password = isset($data['password']) ? $data['password'] : '';
if ($agency_name === '' || $first_name === '' || $last_name === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}
try {
    // Check for duplicate email (other than this agency)
    $stmt = $pdo->prepare('SELECT id FROM agency_admins WHERE email = ? AND agency_id != ?');
    $stmt->execute([$email, $agency_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already exists for another agency.']);
        exit;
    }
    if ($password !== '') {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE agency_admins SET agency_name = ?, first_name = ?, last_name = ?, email = ?, password_hash = ?, updated_at = NOW() WHERE agency_id = ?');
        $stmt->execute([$agency_name, $first_name, $last_name, $email, $password_hash, $agency_id]);
    } else {
        $stmt = $pdo->prepare('UPDATE agency_admins SET agency_name = ?, first_name = ?, last_name = ?, email = ?, updated_at = NOW() WHERE agency_id = ?');
        $stmt->execute([$agency_name, $first_name, $last_name, $email, $agency_id]);
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 