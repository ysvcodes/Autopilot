<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['agency_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing agency_id.']);
    exit;
}
echo json_encode(['success' => true, 'password' => 'changeme123']); 