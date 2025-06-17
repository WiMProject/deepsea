<?php
require_once __DIR__ . '/../../config/config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$user = getCurrentUser();
$files = getUserFiles($user['id']);

header('Content-Type: application/json');
echo json_encode($files);
