<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';

$user = require_login();
$db = get_db();
$input = json_decode(file_get_contents('php://input'), true) ?: [];

$old = $input['old_password'] ?? '';
$new = $input['new_password'] ?? '';

if (!$old || !$new) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_fields']);
    exit;
}

$stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$row = $stmt->fetch();

if (!password_verify($old, $row['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'wrong_password']);
    exit;
}

$newHash = password_hash($new, PASSWORD_BCRYPT);
$stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$stmt->execute([$newHash, $user['id']]);

echo json_encode(['success' => true]);
