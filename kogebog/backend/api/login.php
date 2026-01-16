<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_fields']);
    exit;
}

$db = get_db();
$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_credentials']);
    exit;
}

$_SESSION['user_id'] = $user['id'];

echo json_encode([
    'success' => true,
    'username' => $user['username'],
    'email' => $user['email'],
    'is_admin' => (bool)$user['is_admin']
]);
