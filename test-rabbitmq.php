<?php
/**
 * RabbitMQ Test Script
 * 
 * Tests the RabbitMQ-based PDF generation system
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/helpers/Logger.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/helpers/PdfServiceClientRabbitMQ.php';

echo "========================================\n";
echo "  RabbitMQ PDF Service Test\n";
echo "========================================\n\n";

try {
    echo "[1/4] Connecting to RabbitMQ...\n";
    echo "      Host: " . RABBITMQ_HOST . "\n";
    echo "      Port: " . RABBITMQ_PORT . "\n";
    echo "      User: " . RABBITMQ_USER . "\n\n";
    
    $client = new PdfServiceClientRabbitMQ(
        RABBITMQ_HOST,
        RABBITMQ_PORT,
        RABBITMQ_USER,
        RABBITMQ_PASS,
        30
    );
    
    echo "✓ Connection successful!\n\n";
    
    echo "[2/4] Connection info:\n";
    $info = $client->getConnectionInfo();
    foreach ($info as $key => $value) {
        echo "      " . str_pad($key . ':', 20) . $value . "\n";
    }
    echo "\n";
    
    echo "[3/4] Generating test PDF...\n";
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                padding: 50px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            .container {
                background: white;
                color: #333;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            }
            h1 {
                color: #667eea;
                border-bottom: 3px solid #764ba2;
                padding-bottom: 10px;
            }
            .info {
                margin: 20px 0;
                padding: 15px;
                background: #f5f5f5;
                border-left: 4px solid #667eea;
            }
            .success {
                color: #28a745;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🐰 RabbitMQ PDF Test</h1>
            <p>This PDF was generated via <strong>RabbitMQ message queue</strong>!</p>
            
            <div class="info">
                <h3>System Information:</h3>
                <ul>
                    <li><strong>Date:</strong> ' . date('Y-m-d H:i:s') . '</li>
                    <li><strong>Communication:</strong> RabbitMQ AMQP</li>
                    <li><strong>Queue:</strong> pdf_generation_requests</li>
                    <li><strong>PHP Version:</strong> ' . PHP_VERSION . '</li>
                </ul>
            </div>
            
            <p class="success">✓ PDF Generation Successful!</p>
            
            <h2>Architecture:</h2>
            <ol>
                <li>PHP sends message to RabbitMQ queue</li>
                <li>Node.js worker picks up the message</li>
                <li>Puppeteer generates PDF from HTML</li>
                <li>Response sent back via RabbitMQ</li>
                <li>PHP receives the PDF data</li>
            </ol>
            
            <h2>Benefits:</h2>
            <ul>
                <li>✓ Asynchronous processing</li>
                <li>✓ Message persistence</li>
                <li>✓ Load balancing</li>
                <li>✓ Automatic retries</li>
                <li>✓ Horizontal scaling</li>
            </ul>
        </div>
    </body>
    </html>
    ';
    
    $startTime = microtime(true);
    $pdf = $client->generatePdf($html, 'rabbitmq-test', [
        'format' => 'A4',
        'landscape' => false,
        'margin' => [
            'top' => '20px',
            'right' => '20px',
            'bottom' => '20px',
            'left' => '20px'
        ]
    ]);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    echo "✓ PDF generated in {$duration}ms\n";
    echo "      Size: " . number_format(strlen($pdf)) . " bytes\n\n";
    
    echo "[4/4] Saving PDF...\n";
    $filename = 'test-rabbitmq-' . date('YmdHis') . '.pdf';
    file_put_contents(__DIR__ . '/' . $filename, $pdf);
    echo "✓ PDF saved as: {$filename}\n\n";
    
    echo "========================================\n";
    echo "  ✓ Test Completed Successfully!\n";
    echo "========================================\n\n";
    
    $client->close();
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    
    echo "Troubleshooting:\n";
    echo "1. Make sure RabbitMQ is running: rabbitmq-diagnostics status\n";
    echo "2. Start RabbitMQ: rabbitmq-service start\n";
    echo "3. Check if PDF service is running: cd pdf-service && node server-rabbitmq.js\n";
    echo "4. Check RabbitMQ credentials in config/config.php\n";
    echo "5. Make sure composer install was run\n\n";
    
    exit(1);
}
