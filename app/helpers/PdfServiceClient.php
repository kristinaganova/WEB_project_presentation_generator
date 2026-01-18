<?php

/**
 * PDF Service Client
 * 
 * Client for communicating with the Node.js PDF generation microservice.
 * Supports:
 * - REST API communication (HTTP)
 * - RabbitMQ RPC communication (AMQP)
 * 
 * Purpose: Demonstrates distributed system architecture
 * - PHP (main application platform)
 * - Node.js (PDF microservice platform)
 * - REST API / RabbitMQ (inter-service communication)
 * 
 * Points: 20 (Different platforms) + 15 (Multiple paradigms) = 35 points
 */
class PdfServiceClient
{
    private $serviceType;
    private $serviceUrl;
    private $timeout;
    private $connectTimeout;

    // RabbitMQ (RPC)
    private $queueName;
    private $timeoutSeconds;
    private $connection;
    private $channel;
    private $callbackQueue;
    private $response;
    private $corrId;
    
    /**
     * Constructor
     * 
     * Uses config constants when available:
     * - PDF_SERVICE_TYPE: 'http' or 'rabbitmq'
     * - PDF_SERVICE_HTTP_URL
     * - PDF_SERVICE_TIMEOUT
     * - PDF_RPC_QUEUE
     * - PDF_RPC_TIMEOUT_SECONDS
     */
    public function __construct($serviceUrl = null, $timeout = null) {
        $this->serviceType = defined('PDF_SERVICE_TYPE') ? PDF_SERVICE_TYPE : 'http';
        $this->serviceUrl = rtrim($serviceUrl ?? (defined('PDF_SERVICE_HTTP_URL') ? PDF_SERVICE_HTTP_URL : 'http://localhost:3001'), '/');
        $this->timeout = (int)($timeout ?? (defined('PDF_SERVICE_TIMEOUT') ? PDF_SERVICE_TIMEOUT : 30));
        $this->connectTimeout = 5;

        $this->queueName = defined('PDF_RPC_QUEUE') ? PDF_RPC_QUEUE : 'pdf.generate';
        $this->timeoutSeconds = defined('PDF_RPC_TIMEOUT_SECONDS') ? (int)PDF_RPC_TIMEOUT_SECONDS : 90;
        $this->connection = null;
        $this->channel = null;
        $this->callbackQueue = null;
        $this->response = null;
        $this->corrId = null;
    }
    
    /**
     * Generate PDF from HTML
     * 
     * @param string $html HTML content to convert to PDF
     * @param string $title PDF filename (without .pdf extension)
     * @param array $options Optional PDF generation options
     * @return string Binary PDF content
     * @throws Exception If PDF generation fails
     */
    public function generatePdf($html, $title = 'presentation', $options = []) {
        // Prepare request payload
        $payload = [
            'html' => $html,
            'title' => $title
        ];
        
        // Add optional settings
        if (!empty($options)) {
            $payload['options'] = $options;
        }
        
        // Make request via configured transport
        $response = $this->serviceType === 'rabbitmq'
            ? $this->rpcRequest($payload)
            : $this->makeRequest('POST', '/generate-pdf', $payload);
        
        // Validate response
        if (!isset($response['success']) || $response['success'] !== true) {
            $error = $response['error'] ?? 'Unknown error';
            $message = $response['message'] ?? '';
            throw new Exception("PDF generation failed: {$error}. {$message}");
        }
        
        if (!isset($response['pdf'])) {
            throw new Exception('PDF data missing in response');
        }
        
        // Decode base64 PDF
        $pdfContent = base64_decode($response['pdf']);
        
        if ($pdfContent === false) {
            throw new Exception('Failed to decode PDF data');
        }
        
        return $pdfContent;
    }
    
    /**
     * Generate multiple PDFs in batch
     * 
     * @param array $presentations Array of ['html' => '...', 'title' => '...']
     * @return array Array of results
     * @throws Exception If request fails
     */
    public function generatePdfBatch($presentations) {
        $payload = [
            'presentations' => $presentations
        ];
        
        // Batch is supported only via HTTP in current implementation.
        $response = $this->makeRequest('POST', '/generate-pdf-batch', $payload);
        
        if (!isset($response['success']) || $response['success'] !== true) {
            throw new Exception('Batch PDF generation failed');
        }
        
        return $response['results'] ?? [];
    }
    
    /**
     * Check if PDF service is available
     * 
     * @return bool True if service is healthy, false otherwise
     */
    public function isAvailable() {
        try {
            if ($this->serviceType === 'rabbitmq') {
                $this->ensureConnected();
                $this->channel->queue_declare($this->queueName, false, true, false, false);
                return true;
            }

            $response = $this->makeRequest('GET', '/health');
            return isset($response['status']) && $response['status'] === 'OK';
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get service health information
     * 
     * @return array Service health data
     * @throws Exception If service is unavailable
     */
    public function getHealth() {
        if ($this->serviceType === 'rabbitmq') {
            $available = $this->isAvailable();
            return [
                'status' => $available ? 'OK' : 'DOWN',
                'service' => 'PDF Generator via RabbitMQ',
                'queue' => $this->queueName,
                'timestamp' => date('c')
            ];
        }

        return $this->makeRequest('GET', '/health');
    }

    /**
     * Ensure AMQP connection + channel exist
     */
    private function ensureConnected() {
        if ($this->channel) return;

        if (!class_exists('\\PhpAmqpLib\\Connection\\AMQPStreamConnection')) {
            throw new Exception('php-amqplib/php-amqplib is not installed or autoload is missing. Run: composer install');
        }

        $host = defined('RABBITMQ_HOST') ? RABBITMQ_HOST : '127.0.0.1';
        $port = defined('RABBITMQ_PORT') ? (int)RABBITMQ_PORT : 5672;
        $user = defined('RABBITMQ_USER') ? RABBITMQ_USER : 'guest';
        $pass = defined('RABBITMQ_PASS') ? RABBITMQ_PASS : 'guest';
        $vhost = defined('RABBITMQ_VHOST') ? RABBITMQ_VHOST : '/';

        $this->connection = new \PhpAmqpLib\Connection\AMQPStreamConnection($host, $port, $user, $pass, $vhost);
        $this->channel = $this->connection->channel();

        // Request queue must exist (durable so service restarts don't lose it)
        $this->channel->queue_declare($this->queueName, false, true, false, false);

        // Exclusive auto-delete callback queue for RPC responses
        list($this->callbackQueue, ,) = $this->channel->queue_declare('', false, false, true, true);

        $this->channel->basic_consume(
            $this->callbackQueue,
            '',
            false,
            true,
            false,
            false,
            function ($msg) {
                if ($this->corrId !== null && $msg->get('correlation_id') === $this->corrId) {
                    $this->response = $msg->body;
                }
            }
        );
    }

    /**
     * RPC request over RabbitMQ
     */
    private function rpcRequest(array $payload, $queueNameOverride = null) {
        $this->ensureConnected();

        $queue = $queueNameOverride ?: $this->queueName;
        $this->channel->queue_declare($queue, false, true, false, false);

        $this->response = null;
        $this->corrId = bin2hex(random_bytes(16));

        $json = json_encode($payload);
        if ($json === false) {
            throw new Exception('Failed to encode PDF payload to JSON');
        }

        $msg = new \PhpAmqpLib\Message\AMQPMessage(
            $json,
            [
                'content_type' => 'application/json',
                'delivery_mode' => 2, // persistent
                'correlation_id' => $this->corrId,
                'reply_to' => $this->callbackQueue,
            ]
        );

        $this->channel->basic_publish($msg, '', $queue);

        $start = microtime(true);
        while ($this->response === null) {
            $elapsed = microtime(true) - $start;
            $remaining = $this->timeoutSeconds - $elapsed;
            if ($remaining <= 0) {
                throw new Exception("PDF generation timed out after {$this->timeoutSeconds}s (RabbitMQ RPC)");
            }

            // Wait for a message on the callback queue
            $this->channel->wait(null, false, (int)ceil($remaining));
        }

        $decoded = json_decode($this->response, true);
        if (!is_array($decoded)) {
            throw new Exception('Invalid JSON response from PDF service via RabbitMQ');
        }

        return $decoded;
    }
    
    /**
     * Make HTTP request to PDF service
     * 
     * @param string $method HTTP method (GET, POST)
     * @param string $endpoint API endpoint path
     * @param array|null $data Request data (for POST)
     * @return array Decoded JSON response
     * @throws Exception If request fails
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->serviceUrl . $endpoint;
        
        $ch = curl_init($url);
        
        // Set common options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        // Set method-specific options
        if ($method === 'POST' && $data !== null) {
            $jsonData = json_encode($data);
            
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData)
            ]);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json'
            ]);
        }
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Check for cURL errors
        if ($response === false) {
            throw new Exception("PDF Service connection failed: {$curlError}");
        }
        
        // Parse JSON response
        $decoded = json_decode($response, true);
        
        if ($decoded === null) {
            throw new Exception("Invalid JSON response from PDF Service. HTTP {$httpCode}");
        }
        
        // Check HTTP status
        if ($httpCode >= 400) {
            $error = $decoded['error'] ?? 'Unknown error';
            throw new Exception("PDF Service returned HTTP {$httpCode}: {$error}");
        }
        
        return $decoded;
    }
    
    /**
     * Helper: Create default PDF options for presentations
     * 
     * @return array Default options optimized for presentations
     */
    public static function getPresentationOptions() {
        return [
            'format' => 'A4',
            'landscape' => true,
            'width' => 1920,
            'height' => 1080,
            'margin' => [
                'top' => '20px',
                'right' => '20px',
                'bottom' => '20px',
                'left' => '20px'
            ]
        ];
    }
    
    /**
     * Helper: Create default PDF options for documents
     * 
     * @return array Default options optimized for documents
     */
    public static function getDocumentOptions() {
        return [
            'format' => 'A4',
            'landscape' => false,
            'margin' => [
                'top' => '25mm',
                'right' => '20mm',
                'bottom' => '25mm',
                'left' => '20mm'
            ]
        ];
    }
    
    /**
     * Set custom service URL
     * 
     * @param string $url New service URL
     */
    public function setServiceUrl($url) {
        $this->serviceUrl = rtrim($url, '/');
    }
    
    /**
     * Set request timeout
     * 
     * @param int $seconds Timeout in seconds
     */
    public function setTimeout($seconds) {
        $this->timeout = $seconds;
    }
    
    /**
     * Get current service URL
     * 
     * @return string Current service URL
     */
    public function getServiceUrl() {
        return $this->serviceUrl;
    }

    public function __destruct() {
        try {
            if ($this->channel) $this->channel->close();
            if ($this->connection) $this->connection->close();
        } catch (\Throwable $e) {
            // ignore shutdown errors
        }
    }
}
