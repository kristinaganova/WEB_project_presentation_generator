<?php

/**
 * PHP Integration Tests for PDF Microservice
 * Tests the communication between PHP and Node.js PDF service
 * 
 * Run: php test-pdf-integration.php
 */

require_once __DIR__ . '/app/helpers/PdfServiceClient.php';

class PdfIntegrationTest
{
    private $client;
    private $passed = 0;
    private $failed = 0;
    
    public function __construct() {
        $this->client = new PdfServiceClient();
    }
    
    public function runAll() {
        $this->printHeader();
        
        // Run all tests
        $this->testHealthCheck();
        $this->testServiceAvailability();
        $this->testSimplePdfGeneration();
        $this->testComplexHtmlPdfGeneration();
        $this->testPresentationOptions();
        $this->testDocumentOptions();
        $this->testErrorHandling();
        $this->testLargePdfGeneration();
        $this->testUnicodeContent();
        $this->testCustomTimeout();
        
        $this->printSummary();
    }
    
    private function printHeader() {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║   PHP Integration Tests - PDF Microservice                ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }
    
    private function testHealthCheck() {
        echo "🧪 Test 1: Health Check\n";
        
        try {
            $health = $this->client->getHealth();
            
            if (isset($health['status']) && $health['status'] === 'OK') {
                echo "   ✅ PASS - Service is healthy\n";
                echo "   Service: " . ($health['service'] ?? 'Unknown') . "\n";
                echo "   Version: " . ($health['version'] ?? 'Unknown') . "\n\n";
                $this->passed++;
            } else {
                echo "   ❌ FAIL - Invalid health response\n\n";
                $this->failed++;
            }
        } catch (Exception $e) {
            echo "   ❌ FAIL - " . $e->getMessage() . "\n\n";
            $this->failed++;
        }
    }
    
    private function testServiceAvailability() {
        echo "🧪 Test 2: Service Availability Check\n";
        
        try {
            $isAvailable = $this->client->isAvailable();
            
            if ($isAvailable) {
                echo "   ✅ PASS - Service is available\n\n";
                $this->passed++;
            } else {
                echo "   ❌ FAIL - Service not available\n\n";
                $this->failed++;
            }
        } catch (Exception $e) {
            echo "   ❌ FAIL - " . $e->getMessage() . "\n\n";
            $this->failed++;
        }
    }
    
    private function testSimplePdfGeneration() {
        echo "🧪 Test 3: Simple PDF Generation\n";
        
        try {
            $html = '<html><body><h1>Test PDF from PHP</h1><p>This is a test document.</p></body></html>';
            $pdf = $this->client->generatePdf($html, 'php-test');
            
            if (strlen($pdf) > 0 && substr($pdf, 0, 4) === '%PDF') {
                echo "   ✅ PASS - PDF generated successfully\n";
                echo "   Size: " . round(strlen($pdf) / 1024, 2) . " KB\n\n";
                $this->passed++;
                
                // Optionally save to file for manual inspection
                // file_put_contents(__DIR__ . '/test-output-simple.pdf', $pdf);
            } else {
                echo "   ❌ FAIL - Invalid PDF content\n\n";
                $this->failed++;
            }
        } catch (Exception $e) {
            echo "   ❌ FAIL - " . $e->getMessage() . "\n\n";
            $this->failed++;
        }
    }
    
    private function testComplexHtmlPdfGeneration() {
        echo "🧪 Test 4: Complex HTML with CSS\n";
        
        try {
            $html = '
                <html>
                <head>
                    <style>
                        body { font-family: Arial; padding: 40px; }
                        h1 { color: #2196F3; font-size: 36px; }
                        .box { background: #f0f0f0; padding: 20px; margin: 20px 0; border-left: 4px solid #2196F3; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th { background: #2196F3; color: white; padding: 10px; }
                        td { padding: 10px; border-bottom: 1px solid #ddd; }
                    </style>
                </head>
                <body>
                    <h1>Complex Document</h1>
                    <div class="box">
                        <h2>Styled Box</h2>
                        <p>This is content in a styled box.</p>
                    </div>
                    <table>
                        <tr><th>Column 1</th><th>Column 2</th></tr>
                        <tr><td>Data 1</td><td>Data 2</td></tr>
                    </table>
                </body>
                </html>
            ';
            
            $pdf = $this->client->generatePdf($html, 'complex-test');
            
            if (strlen($pdf) > 0 && substr($pdf, 0, 4) === '%PDF') {
                echo "   ✅ PASS - Complex HTML rendered\n";
                echo "   Size: " . round(strlen($pdf) / 1024, 2) . " KB\n\n";
                $this->passed++;
            } else {
                echo "   ❌ FAIL - Failed to render complex HTML\n\n";
                $this->failed++;
            }
        } catch (Exception $e) {
            echo "   ❌ FAIL - " . $e->getMessage() . "\n\n";
            $this->failed++;
        }
    }
    
    private function testPresentationOptions() {
        echo "🧪 Test 5: Presentation Options\n";
        
        try {
            $html = '<html><body><h1>Presentation Format</h1></body></html>';
            $options = PdfServiceClient::getPresentationOptions();
            $pdf = $this->client->generatePdf($html, 'presentation-test', $options);
            
            if (strlen($pdf) > 0 && substr($pdf, 0, 4) === '%PDF') {
                echo "   ✅ PASS - Presentation options work\n";
                echo "   Options: A4, Landscape\n\n";
                $this->passed++;
            } else {
                echo "   ❌ FAIL - Presentation options failed\n\n";
                $this->failed++;
            }
        } catch (Exception $e) {
            echo "   ❌ FAIL - " . $e->getMessage() . "\n\n";
            $this->failed++;
        }
    }
    
    private function testDocumentOptions() {
        echo "🧪 Test 6: Document Options\n";
        
        try {
            $html = '<html><body><h1>Document Format</h1></body></html>';
            $options = PdfServiceClient::getDocumentOptions();
            $pdf = $this->client->generatePdf($html, 'document-test', $options);
            
            if (strlen($pdf) > 0 && substr($pdf, 0, 4) === '%PDF') {
                echo "   ✅ PASS - Document options work\n";
                echo "   Options: A4, Portrait\n\n";
                $this->passed++;
            } else {
                echo "   ❌ FAIL - Document options failed\n\n";
                $this->failed++;
            }
        } catch (Exception $e) {
            echo "   ❌ FAIL - " . $e->getMessage() . "\n\n";
            $this->failed++;
        }
    }
    
    private function testErrorHandling() {
        echo "🧪 Test 7: Error Handling (Empty HTML)\n";
        
        try {
            $pdf = $this->client->generatePdf('', 'empty');
            echo "   ❌ FAIL - Should have thrown exception\n\n";
            $this->failed++;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'HTML content') !== false) {
                echo "   ✅ PASS - Error handled correctly\n";
                echo "   Error: " . $e->getMessage() . "\n\n";
                $this->passed++;
            } else {
                echo "   ❌ FAIL - Unexpected error\n\n";
                $this->failed++;
            }
        }
    }
    
    private function testLargePdfGeneration() {
        echo "🧪 Test 8: Large Document Generation\n";
        
        try {
            $html = '<html><body>';
            for ($i = 1; $i <= 10; $i++) {
                $html .= "<div style='page-break-after: always;'>";
                $html .= "<h1>Page $i</h1>";
                for ($j = 1; $j <= 20; $j++) {
                    $html .= "<p>Paragraph $j with some content to make it realistic.</p>";
                }
                $html .= "</div>";
            }
            $html .= '</body></html>';
            
            $startTime = microtime(true);
            $pdf = $this->client->generatePdf($html, 'large-document');
            $duration = (microtime(true) - $startTime) * 1000;
            
            if (strlen($pdf) > 0) {
                echo "   ✅ PASS - Large document generated\n";
                echo "   Size: " . round(strlen($pdf) / 1024, 2) . " KB\n";
                echo "   Time: " . round($duration, 0) . " ms\n\n";
                $this->passed++;
            } else {
                echo "   ❌ FAIL - Failed to generate large document\n\n";
                $this->failed++;
            }
        } catch (Exception $e) {
            echo "   ❌ FAIL - " . $e->getMessage() . "\n\n";
            $this->failed++;
        }
    }
    
    private function testUnicodeContent() {
        echo "🧪 Test 9: Unicode and Cyrillic Content\n";
        
        try {
            $html = '
                <html>
                <head><meta charset="UTF-8"></head>
                <body>
                    <h1>Тест с Кирилица</h1>
                    <p>Български текст: АБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЬЮЯ</p>
                    <p>абвгдежзийклмнопрстуфхцчшщъьюя</p>
                    <p>Специални символи: © ® ™ € £</p>
                </body>
                </html>
            ';
            
            $pdf = $this->client->generatePdf($html, 'unicode-test');
            
            if (strlen($pdf) > 0 && substr($pdf, 0, 4) === '%PDF') {
                echo "   ✅ PASS - Unicode content handled\n\n";
                $this->passed++;
            } else {
                echo "   ❌ FAIL - Unicode handling failed\n\n";
                $this->failed++;
            }
        } catch (Exception $e) {
            echo "   ❌ FAIL - " . $e->getMessage() . "\n\n";
            $this->failed++;
        }
    }
    
    private function testCustomTimeout() {
        echo "🧪 Test 10: Custom Timeout Setting\n";
        
        try {
            $this->client->setTimeout(60);
            $url = $this->client->getServiceUrl();
            
            if ($url === 'http://localhost:3001') {
                echo "   ✅ PASS - Configuration methods work\n";
                echo "   Service URL: $url\n\n";
                $this->passed++;
            } else {
                echo "   ❌ FAIL - Configuration failed\n\n";
                $this->failed++;
            }
        } catch (Exception $e) {
            echo "   ❌ FAIL - " . $e->getMessage() . "\n\n";
            $this->failed++;
        }
    }
    
    private function printSummary() {
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║               PHP INTEGRATION TEST SUMMARY                 ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n";
        
        $total = $this->passed + $this->failed;
        $successRate = $total > 0 ? round(($this->passed / $total) * 100) : 0;
        
        echo "  Total Tests:  $total\n";
        echo "  ✅ Passed:     {$this->passed}\n";
        echo "  ❌ Failed:     {$this->failed}\n";
        echo "  Success Rate: {$successRate}%\n";
        echo "\n";
        
        if ($this->failed === 0) {
            echo "🎉 All PHP integration tests passed!\n";
            echo "   PHP ↔ Node.js communication is working perfectly.\n\n";
            exit(0);
        } else {
            echo "⚠️  Some tests failed. Check the output above.\n";
            echo "   Make sure the PDF service is running: npm start\n\n";
            exit(1);
        }
    }
}

// Run tests
echo "\nPHP Integration Test Suite\n";
echo "Testing communication between PHP and Node.js PDF Microservice\n";
echo "Make sure the service is running: cd pdf-service && npm start\n";

$tester = new PdfIntegrationTest();
$tester->runAll();
