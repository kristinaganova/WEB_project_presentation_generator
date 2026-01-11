<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Disabled to prevent binary corruption
ini_set('display_startup_errors', 0);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

session_start();

require_once __DIR__ . '/config.php';

function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, __DIR__ . '/../logs/php_errors.log');
}

error_log("Session started");
error_log("User ID: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Script Name: " . $_SERVER['SCRIPT_NAME']);
error_log("PHP Self: " . $_SERVER['PHP_SELF']); 