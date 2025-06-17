<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$user = getCurrentUser();

if (!isset($_FILES['file_upload']) || $_FILES['file_upload']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    die(json_encode(['error' => 'No file uploaded or upload error']));
}

$fileInfo = pathinfo($_FILES['file_upload']['name']);
$fileExt = strtolower($fileInfo['extension']);

if (!in_array($fileExt, ALLOWED_FILE_TYPES)) {
    http_response_code(400);
    die(json_encode(['error' => 'File type not allowed']));
}

if ($_FILES['file_upload']['size'] > MAX_FILE_SIZE) {
    http_response_code(400);
    die(json_encode(['error' => 'File too large']));
}

$uploadDir = __DIR__ . '/../../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$fileName = uniqid() . '.' . $fileExt;
$uploadPath = $uploadDir . $fileName;

if (!move_uploaded_file($_FILES['file_upload']['tmp_name'], $uploadPath)) {
    http_response_code(500);
    die(json_encode(['error' => 'Failed to save file']));
}

// Simpan ke database
saveUploadedFile($user['id'], $_FILES['file_upload']['name'], $fileName);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
