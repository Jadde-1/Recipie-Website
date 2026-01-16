<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';

$db = get_db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->query("SELECT r.*, u.username AS created_by_name
                        FROM recipes r
                        LEFT JOIN users u ON r.created_by = u.id
                        ORDER BY r.created_at DESC");
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['ingredients'] = $r['ingredients'] ? json_decode($r['ingredients'], true) : [];
        $r['categories'] = $r['categories'] ? json_decode($r['categories'], true) : [];
    }
    echo json_encode($rows);
    exit;
}

if ($method === 'POST') {
    $user = require_login();
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $name = trim($input['name'] ?? '');
    if (!$name) {
        http_response_code(400);
        echo json_encode(['error' => 'missing_name']);
        exit;
    }
    $image = $input['image'] ?? '';
    $ingredients = $input['ingredients'] ?? [];
    $howto = $input['howto'] ?? '';
    $source = $input['source'] ?? '';
    $categories = $input['categories'] ?? [];

    $stmt = $db->prepare("INSERT INTO recipes (name, image, ingredients, howto, source, categories, created_by)
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $name,
        $image,
        json_encode($ingredients, JSON_UNESCAPED_UNICODE),
        $howto,
        $source,
        json_encode($categories, JSON_UNESCAPED_UNICODE),
        $user['id']
    ]);
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'PUT') {
    $user = require_login();
    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $id = (int)($q['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'missing_id']);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM recipes WHERE id = ?");
    $stmt->execute([$id]);
    $r = $stmt->fetch();
    if (!$r) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        exit;
    }

    $ownerId = $r['created_by'];
    $me = current_user();
    if (!$me['is_admin'] && $me['id'] != $ownerId) {
        http_response_code(403);
        echo json_encode(['error' => 'forbidden']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $name = trim($input['name'] ?? '');
    if (!$name) {
        http_response_code(400);
        echo json_encode(['error' => 'missing_name']);
        exit;
    }
    $image = $input['image'] ?? '';
    $ingredients = $input['ingredients'] ?? [];
    $howto = $input['howto'] ?? '';
    $source = $input['source'] ?? '';
    $categories = $input['categories'] ?? [];

    $stmt = $db->prepare("UPDATE recipes
                          SET name=?, image=?, ingredients=?, howto=?, source=?, categories=?
                          WHERE id=?");
    $stmt->execute([
        $name,
        $image,
        json_encode($ingredients, JSON_UNESCAPED_UNICODE),
        $howto,
        $source,
        json_encode($categories, JSON_UNESCAPED_UNICODE),
        $id
    ]);
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'DELETE') {
    $user = require_login();
    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $id = (int)($q['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'missing_id']);
        exit;
    }

    $stmt = $db->prepare("SELECT created_by FROM recipes WHERE id = ?");
    $stmt->execute([$id]);
    $r = $stmt->fetch();
    if (!$r) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        exit;
    }

    $me = current_user();
    if (!$me['is_admin'] && $me['id'] != $r['created_by']) {
        http_response_code(403);
        echo json_encode(['error' => 'forbidden']);
        exit;
    }

    $stmt = $db->prepare("DELETE FROM recipes WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method_not_allowed']);
