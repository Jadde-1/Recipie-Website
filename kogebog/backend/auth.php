<?php
require_once __DIR__ . '/db.php';

function current_user() {
    if (!isset($_SESSION['user_id'])) return null;
    $db = get_db();
    $stmt = $db->prepare("SELECT id, username, email, is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function require_login() {
    $u = current_user();
    if (!$u) {
        http_response_code(401);
        echo json_encode(['error' => 'login_required']);
        exit;
    }
    return $u;
}

function require_admin() {
    $u = require_login();
    if (!$u['is_admin']) {
        http_response_code(403);
        echo json_encode(['error' => 'admin_required']);
        exit;
    }
    return $u;
}
