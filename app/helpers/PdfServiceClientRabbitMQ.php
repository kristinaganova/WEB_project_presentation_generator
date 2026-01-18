<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * PDF Service Client with RabbitMQ
 * 
 * Client for communicating with the Node.js PDF generation microservice via RabbitMQ.
 * Implements message queue communication for asynchronous PDF generation.
 * 
 * Purpose: Demonstrates distributed system architecture with message queues
 * - PHP (main application platform)
 * - Node.js (PDF microservice platform)
 * - RabbitMQ (message broker for inter-service communication)
 * 
 * Advantages over HTTP:
 * - Asynchronous processing
 * - Message persistence
 * - Load balancing across multiple workers
 * - Automatic retry on failure
 * - Better scalability
 */
class PdfServiceClientRabbitMQ
{
    private $connection;
    private $channel;
    private $requestQueue = 'pdf_generation_requests';
    private $responseQueue;
    private $timeout;
    
    /**
     * Constructor
     * 
     * @param string $host RabbitMQ host (default: localhost)
     * @param int $port RabbitMQ port (default: 5672)
     * @param string $user RabbitMQ username (default: guest)
     * @param string $password RabbitMQ password (default: guest)
     * @param int $timeout Response timeout in seconds (default: 30)
     * @throws Exception If connection fails
     */
    public function __construct(
        $host = 'localhost', 
        $port = 5672, 
        $user = 'guest', 
        $password = 'guest',
        $timeout = 30
    ) {
        try {
            // Create connection to RabbitMQ
            $this->connection = new AMQPStreamConnection($host, $port, $user, $password);
            $this->channel = $this->connection->channel();
            $this->timeout = $timeout;
            
            // Declare durable request queue
            $this->channel->queue_declare(
                $this->requestQueue,  // queue name
                false,                // passive
                true,                 // durable
                false,                // exclusive
                false                 // auto_delete
            );
            
            // Create exclusive response queue for this client
            list($this->responseQueue, ,) = $this->channel->queue_declare(
                "",     // let RabbitMQ generate queue name
                false,  // passive
                false,  // durable
                true,   // exclusive (deleted when connection closes)
                false   // auto_delete
            );
            
            Logger::info("PdfServiceClientRabbitMQ: Connected to RabbitMQ at {$host}:{$port}");
            
        } catch (Exception $e) {
            Logger::error("PdfServiceClientRabbitMQ: Connection failed - " . $e->getMessage());
            throw new Exception("Failed to connect to RabbitMQ: " . $e->getMessage());
        }
    }
    
    /**
     * Generate PDF from HTML
     * 
     * @param string $html HTML content to convert to PDF
     * @param string $title PDF filename (without .pdf extension)
     * @param array $options Optional PDF generation options
     * @return string Binary PDF content
     * @throws Exception If PDF generation fails or times out
     */
    public function generatePdf($html, $title = 'presentation', $options = []) {
        $correlationId = uniqid('pdf_', true);
        $response = null;
        $errorMsg = null;
        
        Logger::info("PdfServiceClientRabbitMQ: Generating PDF '{$title}' (correlation: {$correlationId})");
        
        try {
            // Prepare request payload
            $payload = json_encode([
                'html' => $html,
                'title' => $title,
                'options' => $options
            ]);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to encode request: ' . json_last_error_msg());
            }
            
            // Create AMQP message
            $msg = new AMQPMessage($payload, [
                'correlation_id' => $correlationId,
                'reply_to' => $this->responseQueue,
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type' => 'application/json'
            ]);
            
            // Set up callback to receive response
            $callback = function($responseMsg) use ($correlationId, &$response, &$errorMsg) {
                if ($responseMsg->get('correlation_id') === $correlationId) {
                    $data = json_decode($responseMsg->body, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $errorMsg = 'Invalid JSON response from PDF service';
                        return;
                    }
                    
                    if (isset($data['success']) && $data['success'] === true) {
                        $response = $data;
                    } else {
                        $errorMsg = $data['error'] ?? 'Unknown error from PDF service';
                    }
                }
            };
            
            // Start consuming responses
            $this->channel->basic_consume(
                $this->responseQueue,  // queue
                '',                    // consumer_tag
                false,                 // no_local
                true,                  // no_ack (auto-acknowledge)
                false,                 // exclusive
                false,                 // nowait
                $callback              // callback
            );
            
            // Publish request to queue
            $this->channel->basic_publish($msg, '', $this->requestQueue);
            Logger::info("PdfServiceClientRabbitMQ: Request published to queue");
            
            // Wait for response with timeout
            $startTime = time();
            while (!$response && !$errorMsg && (time() - $startTime) < $this->timeout) {
                try {
                    $this->channel->wait(null, false, 1); // wait max 1 second per iteration
                } catch (Exception $e) {
                    Logger::warning("PdfServiceClientRabbitMQ: Wait interrupted - " . $e->getMessage());
                    break;
                }
            }
            
            // Check results
            if ($errorMsg) {
                throw new Exception("PDF generation failed: {$errorMsg}");
            }
            
            if (!$response) {
                throw new Exception("PDF generation timeout after {$this->timeout} seconds");
            }
            
            if (!isset($response['pdf'])) {
                throw new Exception('PDF data missing in response');
            }
            
            // Decode base64 PDF
            $pdfContent = base64_decode($response['pdf'], true);
            
            if ($pdfContent === false) {
                throw new Exception('Failed to decode PDF data');
            }
            
            Logger::info("PdfServiceClientRabbitMQ: PDF generated successfully (" . strlen($pdfContent) . " bytes)");
            return $pdfContent;
            
        } catch (Exception $e) {
            Logger::error("PdfServiceClientRabbitMQ: Error - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate PDF asynchronously (fire and forget)
     * 
     * @param string $html HTML content to convert to PDF
     * @param string $title PDF filename
     * @param array $options Optional PDF generation options
     * @param callable|null $callback Optional callback when PDF is ready
     * @return string Correlation ID to track the request
     */
    public function generatePdfAsync($html, $title = 'presentation', $options = [], $callback = null) {
        $correlationId = uniqid('pdf_async_', true);
        
        Logger::info("PdfServiceClientRabbitMQ: Async PDF generation '{$title}' (correlation: {$correlationId})");
        
        $payload = json_encode([
            'html' => $html,
            'title' => $title,
            'options' => $options
        ]);
        
        $properties = [
            'correlation_id' => $correlationId,
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'content_type' => 'application/json'
        ];
        
        // Add reply queue if callback provided
        if ($callback !== null) {
            $properties['reply_to'] = $this->responseQueue;
            // Store callback for later (you'd need to implement callback storage)
        }
        
        $msg = new AMQPMessage($payload, $properties);
        $this->channel->basic_publish($msg, '', $this->requestQueue);
        
        Logger::info("PdfServiceClientRabbitMQ: Async request published");
        
        return $correlationId;
    }
    
    /**
     * Check if RabbitMQ service is available
     * 
     * @return bool True if connected, false otherwise
     */
    public function isAvailable() {
        try {
            return $this->connection !== null && $this->connection->isConnected();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get connection information
     * 
     * @return array Connection details
     */
    public function getConnectionInfo() {
        return [
            'connected' => $this->isAvailable(),
            'request_queue' => $this->requestQueue,
            'response_queue' => $this->responseQueue,
            'timeout' => $this->timeout
        ];
    }
    
    /**
     * Close connection and clean up
     */
    public function close() {
        try {
            if ($this->channel !== null) {
                $this->channel->close();
            }
            if ($this->connection !== null) {
                $this->connection->close();
            }
            Logger::info("PdfServiceClientRabbitMQ: Connection closed");
        } catch (Exception $e) {
            Logger::warning("PdfServiceClientRabbitMQ: Error closing connection - " . $e->getMessage());
        }
    }
    
    /**
     * Destructor - ensure connection is closed
     */
    public function __destruct() {
        $this->close();
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
}
