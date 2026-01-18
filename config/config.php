<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'presentation_generator');
define('DB_USER', 'root');
define('DB_PASS', ''); // смени при нужда

// RabbitMQ Configuration
define('RABBITMQ_HOST', 'localhost');
define('RABBITMQ_PORT', 5672);
define('RABBITMQ_USER', 'guest');
define('RABBITMQ_PASS', 'guest');
define('RABBITMQ_VHOST', '/');

// PDF Service Configuration
define('PDF_SERVICE_TYPE', 'rabbitmq'); // 'http' or 'rabbitmq'
define('PDF_SERVICE_HTTP_URL', 'http://localhost:3001');
define('PDF_SERVICE_TIMEOUT', 30);

// RabbitMQ PDF RPC
define('PDF_RPC_QUEUE', 'pdf.generate');
define('PDF_RPC_TIMEOUT_SECONDS', 90);

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$script_name = dirname($_SERVER['SCRIPT_NAME']);
$base_url = rtrim($protocol . $host . $script_name, '/');
define('BASE_URL', $base_url);

error_log("BASE_URL: " . BASE_URL);
error_log("SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME']);
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
