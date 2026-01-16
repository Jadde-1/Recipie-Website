<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = get_db();

if ($method === 'GET') {
    require_admin();
    $stmt = $db->query("SELECT id, username, email, is_admin, created_at FROM users ORDER BY id");
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $admin = require_admin();
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $is_admin = !empty($input['is_admin']) ? 1 : 0;

    if (!$username || !$email || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'missing_fields']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, is_admin) VALUES (?, ?, ?, ?)");
    try {
        $stmt->execute([$username, $email, $hash, $is_admin]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(['error' => 'user_exists']);
    }
    exit;
}

if ($method === 'DELETE') {
    $admin = require_admin();
    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $id = (int)($q['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'missing_id']);
        exit;
    }

    $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        exit;
    }

    if ($u['is_admin']) {
        $count = $db->query("SELECT COUNT(*) AS c FROM users WHERE is_admin = 1")->fetch()['c'];
        if ($count <= 1) {
            http_response_code(400);
            echo json_encode(['error' => 'cannot_delete_last_admin']);
            exit;
        }
    }

    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method_not_allowed']);
