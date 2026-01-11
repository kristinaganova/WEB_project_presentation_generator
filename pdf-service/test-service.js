/**
 * Test script for PDF Generator Microservice
 * Run: node test-service.js
 */

const http = require('http');

const SERVICE_URL = 'http://localhost:3001';

function makeRequest(method, path, data = null) {
    return new Promise((resolve, reject) => {
        const url = new URL(path, SERVICE_URL);
        const options = {
            hostname: url.hostname,
            port: url.port,
            path: url.pathname,
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        const req = http.request(options, (res) => {
            let body = '';
            res.on('data', (chunk) => body += chunk);
            res.on('end', () => {
                try {
                    const response = JSON.parse(body);
                    resolve({ status: res.statusCode, data: response });
                } catch (e) {
                    resolve({ status: res.statusCode, data: body });
                }
            });
        });

        req.on('error', reject);

        if (data) {
            req.write(JSON.stringify(data));
        }

        req.end();
    });
}

async function runTests() {
    console.log('╔════════════════════════════════════════════════════════════╗');
    console.log('║      PDF Generator Microservice - Test Suite              ║');
    console.log('╚════════════════════════════════════════════════════════════╝\n');

    let passed = 0;
    let failed = 0;

    // Test 1: Health Check
    console.log('🧪 Test 1: Health Check');
    try {
        const response = await makeRequest('GET', '/health');
        if (response.status === 200 && response.data.status === 'OK') {
            console.log('   ✅ PASS - Service is healthy\n');
            passed++;
        } else {
            console.log('   ❌ FAIL - Unexpected response\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Service not running\n');
        console.log('   Error:', error.message);
        console.log('\n   Please start the service first: npm start\n');
        process.exit(1);
    }

    // Test 2: Generate Simple PDF
    console.log('🧪 Test 2: Generate Simple PDF');
    try {
        const html = '<html><head><title>Test</title></head><body><h1>Test PDF</h1><p>This is a test document.</p></body></html>';
        const response = await makeRequest('POST', '/generate-pdf', {
            html: html,
            title: 'test'
        });

        if (response.status === 200 && response.data.success === true && response.data.pdf) {
            console.log('   ✅ PASS - PDF generated successfully');
            console.log(`   Size: ${response.data.metadata.size} ${response.data.metadata.sizeUnit}`);
            console.log(`   Time: ${response.data.metadata.generationTime} ${response.data.metadata.generationTimeUnit}\n`);
            passed++;
        } else {
            console.log('   ❌ FAIL - PDF generation failed\n');
            console.log('   Response:', JSON.stringify(response.data, null, 2), '\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Test 3: Invalid Request (Missing HTML)
    console.log('🧪 Test 3: Error Handling - Missing HTML');
    try {
        const response = await makeRequest('POST', '/generate-pdf', {
            title: 'test'
        });

        if (response.status === 400 && response.data.success === false) {
            console.log('   ✅ PASS - Correctly rejected invalid request\n');
            passed++;
        } else {
            console.log('   ❌ FAIL - Should have returned 400 error\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Test 4: Generate PDF with Custom Options
    console.log('🧪 Test 4: Generate PDF with Custom Options');
    try {
        const html = `
            <html>
                <head>
                    <style>
                        body { font-family: Arial; padding: 40px; }
                        h1 { color: #333; }
                        .slide { page-break-after: always; }
                    </style>
                </head>
                <body>
                    <div class="slide">
                        <h1>Slide 1</h1>
                        <p>Content for slide 1</p>
                    </div>
                    <div class="slide">
                        <h1>Slide 2</h1>
                        <p>Content for slide 2</p>
                    </div>
                </body>
            </html>
        `;
        
        const response = await makeRequest('POST', '/generate-pdf', {
            html: html,
            title: 'presentation-test',
            options: {
                format: 'A4',
                landscape: true,
                margin: {
                    top: '20px',
                    bottom: '20px'
                }
            }
        });

        if (response.status === 200 && response.data.success === true) {
            console.log('   ✅ PASS - PDF with custom options generated\n');
            passed++;
        } else {
            console.log('   ❌ FAIL - Failed to generate with custom options\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Test 5: 404 Handler
    console.log('🧪 Test 5: 404 Handler');
    try {
        const response = await makeRequest('GET', '/nonexistent');
        if (response.status === 404) {
            console.log('   ✅ PASS - 404 handler working\n');
            passed++;
        } else {
            console.log('   ❌ FAIL - Should return 404\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Summary
    console.log('╔════════════════════════════════════════════════════════════╗');
    console.log('║                      TEST SUMMARY                          ║');
    console.log('╚════════════════════════════════════════════════════════════╝');
    console.log(`  Total Tests:  ${passed + failed}`);
    console.log(`  ✅ Passed:     ${passed}`);
    console.log(`  ❌ Failed:     ${failed}`);
    console.log(`  Success Rate: ${Math.round((passed / (passed + failed)) * 100)}%`);
    console.log('');

    if (failed === 0) {
        console.log('🎉 All tests passed! Service is working correctly.\n');
        process.exit(0);
    } else {
        console.log('⚠️  Some tests failed. Please check the output above.\n');
        process.exit(1);
    }
}

// Run tests
runTests().catch(error => {
    console.error('Test suite error:', error);
    process.exit(1);
});
