/**
 * Extended Test Suite for PDF Generator Microservice
 * Tests edge cases, performance, and advanced features
 * Run: node test-service-extended.js
 */

const http = require('http');
const fs = require('fs');
const path = require('path');

const SERVICE_URL = 'http://localhost:3001';

function makeRequest(method, path, data = null, timeout = 30000) {
    return new Promise((resolve, reject) => {
        const url = new URL(path, SERVICE_URL);
        const options = {
            hostname: url.hostname,
            port: url.port,
            path: url.pathname,
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            timeout: timeout
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
        req.on('timeout', () => {
            req.destroy();
            reject(new Error('Request timeout'));
        });

        if (data) {
            req.write(JSON.stringify(data));
        }

        req.end();
    });
}

async function runTests() {
    console.log('╔════════════════════════════════════════════════════════════╗');
    console.log('║   PDF Microservice - Extended Test Suite                  ║');
    console.log('╚════════════════════════════════════════════════════════════╝\n');

    let passed = 0;
    let failed = 0;
    const results = [];

    // Test 1: Empty HTML handling
    console.log('🧪 Test 1: Empty HTML String');
    try {
        const response = await makeRequest('POST', '/generate-pdf', {
            html: '',
            title: 'empty'
        });

        if (response.status === 400 && response.data.success === false) {
            console.log('   ✅ PASS - Correctly rejected empty HTML\n');
            passed++;
        } else {
            console.log('   ❌ FAIL - Should reject empty HTML\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Test 2: Very large HTML (stress test)
    console.log('🧪 Test 2: Large HTML Document (Stress Test)');
    try {
        let largeHtml = '<html><head><style>body{font-family:Arial;}</style></head><body>';
        for (let i = 0; i < 100; i++) {
            largeHtml += `<div class="page"><h1>Page ${i + 1}</h1>`;
            for (let j = 0; j < 50; j++) {
                largeHtml += `<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Paragraph ${j + 1}</p>`;
            }
            largeHtml += '</div>';
        }
        largeHtml += '</body></html>';

        const startTime = Date.now();
        const response = await makeRequest('POST', '/generate-pdf', {
            html: largeHtml,
            title: 'large-document'
        }, 60000);
        const duration = Date.now() - startTime;

        if (response.status === 200 && response.data.success === true) {
            console.log('   ✅ PASS - Large document processed');
            console.log(`   Size: ${response.data.metadata.size} ${response.data.metadata.sizeUnit}`);
            console.log(`   Time: ${duration}ms`);
            console.log(`   Pages: ~100 pages\n`);
            passed++;
        } else {
            console.log('   ❌ FAIL - Failed to process large document\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Test 3: HTML with complex CSS
    console.log('🧪 Test 3: Complex CSS Styling');
    try {
        const complexHtml = `
            <html>
            <head>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { font-family: 'Arial', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
                    .container { max-width: 1200px; margin: 50px auto; padding: 40px; background: white; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
                    h1 { color: #667eea; font-size: 48px; margin-bottom: 20px; text-align: center; }
                    .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 30px; }
                    .card { padding: 20px; background: #f8f9fa; border-left: 4px solid #667eea; border-radius: 5px; }
                    .card h2 { color: #333; font-size: 24px; margin-bottom: 10px; }
                    .card p { color: #666; line-height: 1.6; }
                    table { width: 100%; border-collapse: collapse; margin-top: 30px; }
                    th { background: #667eea; color: white; padding: 12px; text-align: left; }
                    td { padding: 12px; border-bottom: 1px solid #ddd; }
                    tr:hover { background: #f8f9fa; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>Complex Styled Document</h1>
                    <p style="text-align: center; color: #666; margin-bottom: 30px;">Testing CSS rendering capabilities</p>
                    <div class="grid">
                        <div class="card"><h2>Card 1</h2><p>Content with styling</p></div>
                        <div class="card"><h2>Card 2</h2><p>More styled content</p></div>
                        <div class="card"><h2>Card 3</h2><p>Even more content</p></div>
                    </div>
                    <table>
                        <tr><th>Column 1</th><th>Column 2</th><th>Column 3</th></tr>
                        <tr><td>Data 1</td><td>Data 2</td><td>Data 3</td></tr>
                        <tr><td>Data 4</td><td>Data 5</td><td>Data 6</td></tr>
                    </table>
                </div>
            </body>
            </html>
        `;

        const response = await makeRequest('POST', '/generate-pdf', {
            html: complexHtml,
            title: 'complex-css'
        });

        if (response.status === 200 && response.data.success === true) {
            console.log('   ✅ PASS - Complex CSS rendered successfully');
            console.log(`   Size: ${response.data.metadata.size} ${response.data.metadata.sizeUnit}\n`);
            passed++;
        } else {
            console.log('   ❌ FAIL - CSS rendering failed\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Test 4: Different page formats
    console.log('🧪 Test 4: Different PDF Formats');
    const formats = ['A4', 'Letter', 'Legal'];
    for (const format of formats) {
        try {
            const response = await makeRequest('POST', '/generate-pdf', {
                html: `<html><body><h1>Format: ${format}</h1></body></html>`,
                title: `format-${format}`,
                options: { format: format }
            });

            if (response.status === 200 && response.data.success === true) {
                console.log(`   ✅ PASS - ${format} format generated`);
            } else {
                console.log(`   ❌ FAIL - ${format} format failed`);
                failed++;
                continue;
            }
        } catch (error) {
            console.log(`   ❌ FAIL - ${format} error:`, error.message);
            failed++;
            continue;
        }
    }
    console.log('');
    passed++;

    // Test 5: Portrait vs Landscape
    console.log('🧪 Test 5: Orientation (Portrait vs Landscape)');
    try {
        const portrait = await makeRequest('POST', '/generate-pdf', {
            html: '<html><body><h1>Portrait</h1></body></html>',
            title: 'portrait',
            options: { landscape: false }
        });

        const landscape = await makeRequest('POST', '/generate-pdf', {
            html: '<html><body><h1>Landscape</h1></body></html>',
            title: 'landscape',
            options: { landscape: true }
        });

        if (portrait.status === 200 && landscape.status === 200) {
            console.log('   ✅ PASS - Both orientations work');
            console.log(`   Portrait: ${portrait.data.metadata.size} KB`);
            console.log(`   Landscape: ${landscape.data.metadata.size} KB\n`);
            passed++;
        } else {
            console.log('   ❌ FAIL - Orientation test failed\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Test 6: Custom margins
    console.log('🧪 Test 6: Custom Margins');
    try {
        const response = await makeRequest('POST', '/generate-pdf', {
            html: '<html><body><h1>Custom Margins</h1><p>Testing custom margin settings</p></body></html>',
            title: 'custom-margins',
            options: {
                margin: {
                    top: '50px',
                    right: '50px',
                    bottom: '50px',
                    left: '50px'
                }
            }
        });

        if (response.status === 200 && response.data.success === true) {
            console.log('   ✅ PASS - Custom margins applied\n');
            passed++;
        } else {
            console.log('   ❌ FAIL - Margins not applied\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Test 7: HTML with images (base64)
    console.log('🧪 Test 7: HTML with Embedded Images');
    try {
        const htmlWithImage = `
            <html>
            <body>
                <h1>Document with Image</h1>
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==" 
                     alt="Test Image" 
                     style="width: 100px; height: 100px;">
                <p>Image embedded as base64</p>
            </body>
            </html>
        `;

        const response = await makeRequest('POST', '/generate-pdf', {
            html: htmlWithImage,
            title: 'with-image'
        });

        if (response.status === 200 && response.data.success === true) {
            console.log('   ✅ PASS - Image rendering works\n');
            passed++;
        } else {
            console.log('   ❌ FAIL - Image rendering failed\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Test 8: Special characters and Unicode
    console.log('🧪 Test 8: Special Characters & Unicode');
    try {
        const unicodeHtml = `
            <html>
            <head><meta charset="UTF-8"></head>
            <body>
                <h1>Тест с Кирилица</h1>
                <p>Български текст: АБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЬЮЯ</p>
                <p>Емоджи: 🎉 ✅ ❌ 🚀 📄</p>
                <p>Специални символи: © ® ™ € £ ¥</p>
                <p>Math: α β γ δ ε ∑ ∫ ∞</p>
            </body>
            </html>
        `;

        const response = await makeRequest('POST', '/generate-pdf', {
            html: unicodeHtml,
            title: 'unicode-test'
        });

        if (response.status === 200 && response.data.success === true) {
            console.log('   ✅ PASS - Unicode characters handled\n');
            passed++;
        } else {
            console.log('   ❌ FAIL - Unicode handling failed\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Test 9: Malformed HTML
    console.log('🧪 Test 9: Malformed HTML Recovery');
    try {
        const malformedHtml = '<html><body><h1>Missing closing tags<p>Content</body>';

        const response = await makeRequest('POST', '/generate-pdf', {
            html: malformedHtml,
            title: 'malformed'
        });

        if (response.status === 200 && response.data.success === true) {
            console.log('   ✅ PASS - Malformed HTML handled gracefully\n');
            passed++;
        } else {
            console.log('   ❌ FAIL - Could not handle malformed HTML\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Test 10: Performance test - Multiple sequential requests
    console.log('🧪 Test 10: Performance - Sequential Requests');
    try {
        const iterations = 5;
        const times = [];
        
        for (let i = 0; i < iterations; i++) {
            const start = Date.now();
            const response = await makeRequest('POST', '/generate-pdf', {
                html: `<html><body><h1>Request ${i + 1}</h1></body></html>`,
                title: `perf-${i}`
            });
            const duration = Date.now() - start;
            times.push(duration);
            
            if (response.status !== 200) {
                throw new Error(`Request ${i + 1} failed`);
            }
        }

        const avgTime = times.reduce((a, b) => a + b, 0) / times.length;
        const minTime = Math.min(...times);
        const maxTime = Math.max(...times);

        console.log('   ✅ PASS - All sequential requests completed');
        console.log(`   Average: ${avgTime.toFixed(0)}ms`);
        console.log(`   Min: ${minTime}ms, Max: ${maxTime}ms\n`);
        passed++;
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Test 11: Invalid JSON handling
    console.log('🧪 Test 11: Invalid Request Body');
    try {
        const response = await makeRequest('POST', '/generate-pdf', {
            invalid: 'field',
            missing: 'html'
        });

        if (response.status === 400) {
            console.log('   ✅ PASS - Invalid request rejected properly\n');
            passed++;
        } else {
            console.log('   ❌ FAIL - Should reject invalid request\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Test 12: Service info endpoint
    console.log('🧪 Test 12: Service Info Endpoint');
    try {
        const response = await makeRequest('GET', '/');

        if (response.status === 200 && response.data.service) {
            console.log('   ✅ PASS - Service info available');
            console.log(`   Service: ${response.data.service}`);
            console.log(`   Version: ${response.data.version}\n`);
            passed++;
        } else {
            console.log('   ❌ FAIL - Service info not available\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Test 13: Realistic presentation HTML
    console.log('🧪 Test 13: Realistic Presentation Format');
    try {
        const presentationHtml = `
            <html>
            <head>
                <style>
                    body { margin: 0; padding: 0; font-family: Arial; }
                    .slide { width: 100%; min-height: 100vh; padding: 80px; page-break-after: always; }
                    h1 { font-size: 48px; color: #2196F3; margin-bottom: 30px; }
                    h2 { font-size: 36px; color: #333; margin-top: 30px; }
                    p { font-size: 20px; line-height: 1.6; }
                    ul { font-size: 20px; line-height: 2; }
                </style>
            </head>
            <body>
                <div class="slide">
                    <h1>Presentation Title</h1>
                    <p>A comprehensive test of presentation formatting</p>
                </div>
                <div class="slide">
                    <h2>Agenda</h2>
                    <ul>
                        <li>Introduction</li>
                        <li>Main Content</li>
                        <li>Conclusion</li>
                    </ul>
                </div>
                <div class="slide">
                    <h2>Conclusion</h2>
                    <p>Thank you for your attention!</p>
                </div>
            </body>
            </html>
        `;

        const response = await makeRequest('POST', '/generate-pdf', {
            html: presentationHtml,
            title: 'presentation',
            options: { landscape: true, format: 'A4' }
        });

        if (response.status === 200 && response.data.success === true) {
            console.log('   ✅ PASS - Presentation format rendered');
            console.log(`   Size: ${response.data.metadata.size} ${response.data.metadata.sizeUnit}`);
            console.log(`   Time: ${response.data.metadata.generationTime} ${response.data.metadata.generationTimeUnit}\n`);
            passed++;
        } else {
            console.log('   ❌ FAIL - Presentation rendering failed\n');
            failed++;
        }
    } catch (error) {
        console.log('   ❌ FAIL - Error:', error.message, '\n');
        failed++;
    }

    // Summary
    console.log('╔════════════════════════════════════════════════════════════╗');
    console.log('║                   EXTENDED TEST SUMMARY                    ║');
    console.log('╚════════════════════════════════════════════════════════════╝');
    console.log(`  Total Tests:  ${passed + failed}`);
    console.log(`  ✅ Passed:     ${passed}`);
    console.log(`  ❌ Failed:     ${failed}`);
    console.log(`  Success Rate: ${Math.round((passed / (passed + failed)) * 100)}%`);
    console.log('');

    if (failed === 0) {
        console.log('🎉 All extended tests passed! Microservice is robust.\n');
        process.exit(0);
    } else {
        console.log(`⚠️  ${failed} test(s) failed. Review output above.\n`);
        process.exit(1);
    }
}

// Run tests
console.log('Starting extended test suite...');
console.log('Make sure the PDF service is running on http://localhost:3001\n');

setTimeout(() => {
    runTests().catch(error => {
        console.error('Test suite error:', error);
        process.exit(1);
    });
}, 1000);
