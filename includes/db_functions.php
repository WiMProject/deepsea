<?php
function saveConversation($userId, $conversation) {
    $conn = getDBConnection();
    $conversationJson = $conn->real_escape_string(json_encode($conversation));
    
    $sql = "INSERT INTO conversations (user_id, conversation_data) 
            VALUES ($userId, '$conversationJson')
            ON DUPLICATE KEY UPDATE conversation_data = '$conversationJson'";
    
    return $conn->query($sql);
}

function loadConversation($userId) {
    $conn = getDBConnection();
    $sql = "SELECT conversation_data FROM conversations WHERE user_id = $userId LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        return json_decode($data['conversation_data'], true) ?: [];
    }
    
    return [];
}

function saveUploadedFile($userId, $fileName, $filePath) {
    $conn = getDBConnection();
    $fileName = $conn->real_escape_string($fileName);
    $filePath = $conn->real_escape_string($filePath);
    
    $sql = "INSERT INTO uploaded_files (user_id, file_name, file_path) 
            VALUES ($userId, '$fileName', '$filePath')";
    
    return $conn->query($sql);
}

function getUserFiles($userId) {
    $conn = getDBConnection();
    $sql = "SELECT id, file_name, uploaded_at FROM uploaded_files WHERE user_id = $userId ORDER BY uploaded_at DESC";
    $result = $conn->query($sql);
    
    $files = [];
    while ($row = $result->fetch_assoc()) {
        $files[] = $row;
    }
    
    return $files;
}
