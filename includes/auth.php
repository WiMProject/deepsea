<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function loginUser($email, $password) {
    $conn = getDBConnection();
    $email = $conn->real_escape_string($email);
    
    $sql = "SELECT id, password FROM users WHERE email = '$email' LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
    }
    
    return false;
}

function registerUser($name, $email, $password) {
    $conn = getDBConnection();
    
    // Escape input
    $name = $conn->real_escape_string($name);
    $email = $conn->real_escape_string($email);
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Check if email exists
    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        return false;
    }
    
    // Insert new user
    $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$hashedPassword')";
    return $conn->query($sql);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $conn = getDBConnection();
    $userId = $_SESSION['user_id'];
    $sql = "SELECT id, name, email FROM users WHERE id = $userId LIMIT 1";
    $result = $conn->query($sql);
    
    return $result->num_rows === 1 ? $result->fetch_assoc() : null;
}
