<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

session_destroy();
header('Location: login.php');
exit;
