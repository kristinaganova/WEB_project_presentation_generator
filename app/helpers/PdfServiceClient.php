<?php

/**
 * PDF Service Client
 * 
 * Client for communicating with the Node.js PDF generation microservice.
 * Implements REST API communication between PHP and Node.js.
 * 
 * Purpose: Demonstrates distributed system architecture
 * - PHP (main application platform)
 * - Node.js (PDF microservice platform)
 * - REST API (inter-service communication)
 * 
 * Points: 20 (Different platforms) + 15 (Multiple paradigms) = 35 points
 */
class PdfServiceClient
{
    private $serviceUrl;
    private $timeout;
    private $connectTimeout;
    
    /**
     * Constructor
     * 
     * @param string $serviceUrl URL of the PDF microservice (default: http://localhost:3001)
     * @param int $timeout Request timeout in seconds (default: 30)
     */
    public function __construct($serviceUrl = 'http://localhost:3001', $timeout = 30) {
        $this->serviceUrl = rtrim($serviceUrl, '/');
        $this->timeout = $timeout;
        $this->connectTimeout = 5;
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
        
        // Make API request
        $response = $this->makeRequest('POST', '/generate-pdf', $payload);
        
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
        return $this->makeRequest('GET', '/health');
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
}
