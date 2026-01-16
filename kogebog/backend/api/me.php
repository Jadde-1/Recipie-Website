<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';

$u = current_user();
if (!$u) {
    echo json_encode(['user' => null]);
} else {
    echo json_encode(['user' => $u]);
}
