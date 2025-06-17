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
$message = trim($_POST['message'] ?? '');
$conversation = loadConversation($user['id']);

if (empty($message)) {
    http_response_code(400);
    die(json_encode(['error' => 'Message cannot be empty']));
}

// Handle file content if file was uploaded
$fileContent = null;
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $fileInfo = pathinfo($_FILES['file']['name']);
    $fileExt = strtolower($fileInfo['extension']);
    
    if (in_array($fileExt, ALLOWED_FILE_TYPES)) {
        $fileContent = extractTextFromFile($_FILES['file']['tmp_name'], $fileExt);
    }
}

$result = chatWithDeepSeek($message, $conversation, $fileContent);

if (isset($result['error'])) {
    http_response_code(500);
    die(json_encode(['error' => $result['error']]));
}

// Simpan percakapan terbaru
saveConversation($user['id'], $result['conversation']);

header('Content-Type: application/json');
echo json_encode(['response' => $result['response']]);
