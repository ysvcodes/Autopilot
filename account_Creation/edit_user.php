<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../database_connection/connection.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['user_id'], $data['first_name'], $data['last_name'], $data['email'], $data['agency_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}
$user_id = $data['user_id'];
$first_name = trim($data['first_name']);
$last_name = trim($data['last_name']);
$email = trim($data['email']);
$agency_id = $data['agency_id'];
$password = isset($data['password']) ? $data['password'] : '';
if ($first_name === '' || $last_name === '' || $email === '' || $agency_id === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}
try {
    // Check for duplicate email (other than this user)
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already exists for another user.']);
        exit;
    }
    if ($password !== '') {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, password_hash = ?, agency_id = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$first_name, $last_name, $email, $password_hash, $agency_id, $user_id]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, agency_id = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$first_name, $last_name, $email, $agency_id, $user_id]);
    }
    // Update agency_users table for this user
    $stmt = $pdo->prepare('DELETE FROM agency_users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $stmt = $pdo->prepare('INSERT INTO agency_users (agency_id, user_id) VALUES (?, ?)');
    $stmt->execute([$agency_id, $user_id]);
    // Fetch agency name for response
    $stmt = $pdo->prepare('SELECT agency_name FROM agencies WHERE agency_id = ? LIMIT 1');
    $stmt->execute([$agency_id]);
    $agency_row = $stmt->fetch();
    $agency_name = $agency_row ? $agency_row['agency_name'] : '';
    echo json_encode(['success' => true, 'agency_name' => $agency_name]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 