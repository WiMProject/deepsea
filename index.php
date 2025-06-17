<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$conversation = loadConversation($user['id']);
$error = '';

// Handle file upload
$fileContent = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $fileInfo = pathinfo($_FILES['file_upload']['name']);
        $fileExt = strtolower($fileInfo['extension']);
        
        if (!in_array($fileExt, ALLOWED_FILE_TYPES)) {
            $error = 'Jenis file tidak didukung. Hanya PDF dan TXT yang diperbolehkan.';
        } elseif ($_FILES['file_upload']['size'] > MAX_FILE_SIZE) {
            $error = 'Ukuran file terlalu besar. Maksimal 5MB.';
        } else {
            $uploadDir = __DIR__ . '/uploads/';
            $fileName = uniqid() . '.' . $fileExt;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $uploadPath)) {
                saveUploadedFile($user['id'], $_FILES['file_upload']['name'], $fileName);
                $fileContent = extractTextFromFile($uploadPath, $fileExt);
            } else {
                $error = 'Gagal mengunggah file.';
            }
        }
    }
    
    if (empty($error) && isset($_POST['message']) && !empty(trim($_POST['message']))) {
        $message = trim($_POST['message']);
        $result = chatWithDeepSeek($message, $conversation, $fileContent);
        
        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            $conversation = $result['conversation'];
            saveConversation($user['id'], $conversation);
        }
    }
}

$userFiles = getUserFiles($user['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar bg-dark text-white">
                <div class="sidebar-header p-3">
                    <h4>Chatbot</h4>
                    <div class="user-info">
                        <p class="mb-1">Welcome, <?= htmlspecialchars($user['name']) ?></p>
                        <small><?= htmlspecialchars($user['email']) ?></small>
                    </div>
                </div>
                
                <hr>
                
                <div class="file-upload-section p-3">
                    <h5>Upload File</h5>
                    <form method="post" enctype="multipart/form-data" class="mb-3">
                        <div class="mb-3">
                            <input type="file" class="form-control" name="file_upload" accept=".pdf,.txt">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100" name="upload">
                            <i class="fas fa-upload me-1"></i> Upload
                        </button>
                    </form>
                    
                    <h5>Your Files</h5>
                    <div class="file-list">
                        <?php if (empty($userFiles)): ?>
                            <p class="text-muted">No files uploaded yet.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($userFiles as $file): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?= htmlspecialchars($file['file_name']) ?></span>
                                        <small class="text-muted"><?= date('M d, Y', strtotime($file['uploaded_at'])) ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-auto p-3">
                    <a href="logout.php" class="btn btn-danger w-100">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="chat-container">
                    <div class="chat-header bg-primary text-white p-3">
                        <h3><i class="fas fa-robot me-2"></i>Chat Bot</h3>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger m-3"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <div class="chat-box" id="chatBox">
                        <?php foreach ($conversation as $msg): ?>
                            <div class="message <?= $msg['role'] === 'user' ? 'user-message' : 'bot-message' ?>">
                                <div class="message-header">
                                    <strong><?= ucfirst($msg['role']) ?></strong>
                                </div>
                                <div class="message-content">
                                    <?= nl2br(htmlspecialchars($msg['content'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="chat-input p-3 bg-light">
                        <form method="post" id="chatForm">
                            <div class="input-group">
                                <input type="text" name="message" class="form-control" placeholder="Type your message..." required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Send
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Auto scroll chat to bottom
        const chatBox = document.getElementById('chatBox');
        chatBox.scrollTop = chatBox.scrollHeight;
        
        // Handle form submission
        document.getElementById('chatForm').addEventListener('submit', function(e) {
            const input = this.querySelector('input[name="message"]');
            if (input.value.trim() === '') {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
