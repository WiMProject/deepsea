<?php
// Pengaturan dasar
session_start();

define('DEEPSEEK_API_KEY', 'sk-0a35fe238a41480f9cb1d70facb5024d');
define('DEEPSEEK_API_URL', 'https://api.deepseek.com/v1/chat/completions');
define('ALLOWED_FILE_TYPES', ['pdf', 'txt']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// URL dasar
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
define('BASE_URL', $base_url);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/deepseek_functions.php';
