<?php

/**
 * API Unit Tests
 * 
 * Simple test suite for REST API endpoints
 * Can be run standalone without PHPUnit
 */

class ApiTest
{
    private $baseUrl;
    private $sessionCookie;
    private $testResults = [];
    private $testUserId;
    private $testWorkspaceId;
    private $testPresentationId;
    private $testSlideId;

    public function __construct($baseUrl = 'http://localhost/WEB_project_presentation_generator/public')
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Run all tests
     */
    public function runAll()
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║          REST API UNIT TESTS - Presentation Generator          ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        $this->setupTestData();

        // Basic tests
        $this->testHealthEndpoint();
        
        // Authentication required tests
        if ($this->sessionCookie) {
            $this->testGetWorkspaces();
            $this->testCreatePresentation();
            $this->testGetPresentation();
            $this->testCreateSlide();
            $this->testGetSlide();
            $this->testUpdateSlide();
            $this->testDeleteSlide();
        } else {
            echo "⚠️  Skipping authenticated tests - no session cookie\n\n";
        }

        // Error handling tests
        $this->testUnauthorizedAccess();
        $this->testNotFoundError();
        $this->testBadRequest();

        $this->printSummary();
    }

    /**
     * Setup test data (login or use existing session)
     */
    private function setupTestData()
    {
        echo "🔧 Setup: Initializing test data...\n";
        
        // Try to get session from browser or create one
        // For now, we'll mark that manual login is needed
        echo "   ℹ️  Please login to the application first\n";
        echo "   ℹ️  Then run tests again with session cookie\n\n";
        
        // Check if we can access without auth
        $this->testUserId = 1;
        $this->testWorkspaceId = 1;
    }

    /**
     * Test 1: Health Check Endpoint
     */
    private function testHealthEndpoint()
    {
        $testName = "GET /api/health - Health Check";
        echo "🧪 Testing: $testName\n";

        $response = $this->makeRequest('GET', '/api/health');
        
        $pass = $response['status'] === 200 &&
                isset($response['body']['success']) &&
                $response['body']['success'] === true &&
                isset($response['body']['status']) &&
                $response['body']['status'] === 'OK';

        $this->recordTest($testName, $pass, $response);
    }

    /**
     * Test 2: Get Workspaces
     */
    private function testGetWorkspaces()
    {
        $testName = "GET /api/workspaces - List Workspaces";
        echo "🧪 Testing: $testName\n";

        $response = $this->makeRequest('GET', '/api/workspaces', null, true);
        
        $pass = $response['status'] === 200 &&
                isset($response['body']['success']) &&
                $response['body']['success'] === true &&
                isset($response['body']['data']['workspaces']);

        if ($pass && !empty($response['body']['data']['workspaces'])) {
            $this->testWorkspaceId = $response['body']['data']['workspaces'][0]['id'];
        }

        $this->recordTest($testName, $pass, $response);
    }

    /**
     * Test 3: Create Presentation
     */
    private function testCreatePresentation()
    {
        $testName = "POST /api/presentation - Create Presentation";
        echo "🧪 Testing: $testName\n";

        $data = [
            'workspace_id' => $this->testWorkspaceId,
            'title' => 'Unit Test Presentation - ' . date('Y-m-d H:i:s'),
            'language' => 'en',
            'theme' => 'dark'
        ];

        $response = $this->makeRequest('POST', '/api/presentation', $data, true);
        
        $pass = $response['status'] === 201 &&
                isset($response['body']['success']) &&
                $response['body']['success'] === true &&
                isset($response['body']['data']['id']);

        if ($pass) {
            $this->testPresentationId = $response['body']['data']['id'];
        }

        $this->recordTest($testName, $pass, $response);
    }

    /**
     * Test 4: Get Presentation
     */
    private function testGetPresentation()
    {
        if (!$this->testPresentationId) {
            echo "⏭️  Skipping: No presentation ID available\n\n";
            return;
        }

        $testName = "GET /api/presentation/{id} - Get Presentation";
        echo "🧪 Testing: $testName\n";

        $response = $this->makeRequest('GET', '/api/presentation/' . $this->testPresentationId, null, true);
        
        $pass = $response['status'] === 200 &&
                isset($response['body']['success']) &&
                $response['body']['success'] === true &&
                isset($response['body']['data']['presentation']) &&
                isset($response['body']['data']['slides']);

        $this->recordTest($testName, $pass, $response);
    }

    /**
     * Test 5: Create Slide
     */
    private function testCreateSlide()
    {
        if (!$this->testPresentationId) {
            echo "⏭️  Skipping: No presentation ID available\n\n";
            return;
        }

        $testName = "POST /api/slide - Create Slide";
        echo "🧪 Testing: $testName\n";

        $data = [
            'presentation_id' => $this->testPresentationId,
            'title' => 'Unit Test Slide',
            'layout' => 'text',
            'elements' => []
        ];

        $response = $this->makeRequest('POST', '/api/slide', $data, true);
        
        $pass = $response['status'] === 201 &&
                isset($response['body']['success']) &&
                $response['body']['success'] === true &&
                isset($response['body']['data']['id']);

        if ($pass) {
            $this->testSlideId = $response['body']['data']['id'];
        }

        $this->recordTest($testName, $pass, $response);
    }

    /**
     * Test 6: Get Slide
     */
    private function testGetSlide()
    {
        if (!$this->testSlideId) {
            echo "⏭️  Skipping: No slide ID available\n\n";
            return;
        }

        $testName = "GET /api/slide/{id} - Get Slide";
        echo "🧪 Testing: $testName\n";

        $response = $this->makeRequest('GET', '/api/slide/' . $this->testSlideId, null, true);
        
        $pass = $response['status'] === 200 &&
                isset($response['body']['success']) &&
                $response['body']['success'] === true &&
                isset($response['body']['data']);

        $this->recordTest($testName, $pass, $response);
    }

    /**
     * Test 7: Update Slide
     */
    private function testUpdateSlide()
    {
        if (!$this->testSlideId) {
            echo "⏭️  Skipping: No slide ID available\n\n";
            return;
        }

        $testName = "PUT /api/slide/{id} - Update Slide";
        echo "🧪 Testing: $testName\n";

        $data = [
            'title' => 'Updated Unit Test Slide',
            'layout' => 'two-column'
        ];

        $response = $this->makeRequest('PUT', '/api/slide/' . $this->testSlideId, $data, true);
        
        $pass = $response['status'] === 200 &&
                isset($response['body']['success']) &&
                $response['body']['success'] === true;

        $this->recordTest($testName, $pass, $response);
    }

    /**
     * Test 8: Delete Slide
     */
    private function testDeleteSlide()
    {
        if (!$this->testSlideId) {
            echo "⏭️  Skipping: No slide ID available\n\n";
            return;
        }

        $testName = "DELETE /api/slide/{id} - Delete Slide";
        echo "🧪 Testing: $testName\n";

        $response = $this->makeRequest('DELETE', '/api/slide/' . $this->testSlideId, null, true);
        
        $pass = $response['status'] === 200 &&
                isset($response['body']['success']) &&
                $response['body']['success'] === true;

        $this->recordTest($testName, $pass, $response);
    }

    /**
     * Test 9: Unauthorized Access
     */
    private function testUnauthorizedAccess()
    {
        $testName = "Error Handling - 401 Unauthorized";
        echo "🧪 Testing: $testName\n";

        $response = $this->makeRequest('GET', '/api/workspaces', null, false);
        
        $pass = $response['status'] === 401 &&
                isset($response['body']['success']) &&
                $response['body']['success'] === false;

        $this->recordTest($testName, $pass, $response);
    }

    /**
     * Test 10: Not Found Error
     */
    private function testNotFoundError()
    {
        $testName = "Error Handling - 404 Not Found";
        echo "🧪 Testing: $testName\n";

        $response = $this->makeRequest('GET', '/api/presentation/999999', null, true);
        
        $pass = $response['status'] === 404 &&
                isset($response['body']['success']) &&
                $response['body']['success'] === false;

        $this->recordTest($testName, $pass, $response);
    }

    /**
     * Test 11: Bad Request
     */
    private function testBadRequest()
    {
        $testName = "Error Handling - 400 Bad Request";
        echo "🧪 Testing: $testName\n";

        $data = [
            // Missing required fields
            'title' => 'Test'
        ];

        $response = $this->makeRequest('POST', '/api/presentation', $data, true);
        
        $pass = $response['status'] === 400 &&
                isset($response['body']['success']) &&
                $response['body']['success'] === false;

        $this->recordTest($testName, $pass, $response);
    }

    /**
     * Make HTTP request to API
     */
    private function makeRequest($method, $endpoint, $data = null, $auth = false)
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $headers = ['Content-Type: application/json'];
        
        if ($auth) {
            $headers[] = 'Authorization: Bearer test-token';
            if ($this->sessionCookie) {
                curl_setopt($ch, CURLOPT_COOKIE, $this->sessionCookie);
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        $responseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);
        
        curl_close($ch);

        return [
            'status' => $httpCode,
            'headers' => $responseHeaders,
            'body' => json_decode($responseBody, true) ?: ['raw' => $responseBody]
        ];
    }

    /**
     * Record test result
     */
    private function recordTest($name, $pass, $response)
    {
        $this->testResults[] = [
            'name' => $name,
            'pass' => $pass,
            'status' => $response['status'],
            'response' => $response['body']
        ];

        if ($pass) {
            echo "   ✅ PASS - Status: {$response['status']}\n";
        } else {
            echo "   ❌ FAIL - Status: {$response['status']}\n";
            echo "   Response: " . json_encode($response['body'], JSON_UNESCAPED_UNICODE) . "\n";
        }
        echo "\n";
    }

    /**
     * Print test summary
     */
    private function printSummary()
    {
        $total = count($this->testResults);
        $passed = count(array_filter($this->testResults, fn($r) => $r['pass']));
        $failed = $total - $passed;
        $percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

        echo "\n";
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║                         TEST SUMMARY                            ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "  Total Tests:  $total\n";
        echo "  ✅ Passed:     $passed\n";
        echo "  ❌ Failed:     $failed\n";
        echo "  📊 Success Rate: $percentage%\n";
        echo "\n";

        if ($failed > 0) {
            echo "Failed Tests:\n";
            foreach ($this->testResults as $result) {
                if (!$result['pass']) {
                    echo "  • {$result['name']} (Status: {$result['status']})\n";
                }
            }
            echo "\n";
        }

        if ($percentage >= 80) {
            echo "🎉 Great job! API is working well!\n";
        } elseif ($percentage >= 50) {
            echo "⚠️  Some issues detected. Please review failed tests.\n";
        } else {
            echo "❌ Major issues detected. API needs attention.\n";
        }
        echo "\n";
    }

    /**
     * Set session cookie for authenticated requests
     */
    public function setSessionCookie($cookie)
    {
        $this->sessionCookie = $cookie;
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $test = new ApiTest();
    
    // Check if session cookie provided as argument
    if (isset($argv[1])) {
        $test->setSessionCookie($argv[1]);
    }
    
    $test->runAll();
}
