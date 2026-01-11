# API Unit Tests

## Quick Start

### Method 1: Run Without Authentication (Basic Tests Only)
```bash
php tests/ApiTest.php
```
This will test:
- ✅ Health endpoint
- ❌ Authenticated endpoints (skipped)
- ✅ Error handling

### Method 2: Run With Session Cookie (Full Tests)
```bash
# First, login to the application in your browser
# Then get your PHPSESSID cookie value
# Run with cookie:
php tests/ApiTest.php "PHPSESSID=your_session_id_here"
```

### Method 3: Automated Test Script (PowerShell)
```powershell
.\tests\run-tests.ps1
```

---

## Test Coverage

### ✅ Endpoint Tests (8 tests)
1. **GET /api/health** - Health check
2. **GET /api/workspaces** - List workspaces
3. **POST /api/presentation** - Create presentation
4. **GET /api/presentation/{id}** - Get presentation details
5. **POST /api/slide** - Create slide
6. **GET /api/slide/{id}** - Get slide details
7. **PUT /api/slide/{id}** - Update slide
8. **DELETE /api/slide/{id}** - Delete slide

### ✅ Error Handling Tests (3 tests)
9. **401 Unauthorized** - No authentication
10. **404 Not Found** - Non-existent resource
11. **400 Bad Request** - Invalid data

**Total: 11 tests**

---

## Expected Output

```
╔════════════════════════════════════════════════════════════════╗
║          REST API UNIT TESTS - Presentation Generator          ║
╚════════════════════════════════════════════════════════════════╝

🔧 Setup: Initializing test data...

🧪 Testing: GET /api/health - Health Check
   ✅ PASS - Status: 200

🧪 Testing: GET /api/workspaces - List Workspaces
   ✅ PASS - Status: 200

🧪 Testing: POST /api/presentation - Create Presentation
   ✅ PASS - Status: 201

... (more tests)

╔════════════════════════════════════════════════════════════════╗
║                         TEST SUMMARY                            ║
╚════════════════════════════════════════════════════════════════╝

  Total Tests:  11
  ✅ Passed:     11
  ❌ Failed:     0
  📊 Success Rate: 100%

🎉 Great job! API is working well!
```

---

## Getting Session Cookie

### Option 1: Browser DevTools
1. Login to the application
2. Open DevTools (F12)
3. Go to: Application → Storage → Cookies
4. Find `PHPSESSID` and copy its value

### Option 2: Chrome Extension
Use "EditThisCookie" or similar extension to export cookies

### Option 3: Manual cURL
```bash
# Login and save cookies
curl -X POST "http://localhost/WEB_project_presentation_generator/public/auth/login" \
  -d "username=test&password=test" \
  -c cookies.txt

# View cookie file
cat cookies.txt
```

---

## Troubleshooting

### "Connection refused"
- Make sure your web server is running
- Check the base URL in ApiTest.php (line 16)
- Default: `http://localhost/WEB_project_presentation_generator/public`

### "All tests skipped"
- You need to provide session cookie for authenticated tests
- Login to the application first
- Copy PHPSESSID value and pass as argument

### "Tests failing"
- Check if API is accessible in browser first
- Verify database connection
- Check PHP error logs
- Run health check: `http://localhost/.../public/api/health`

---

## Adding Custom Tests

Edit `ApiTest.php` and add new test methods:

```php
private function testYourNewFeature()
{
    $testName = "Your Test Name";
    echo "🧪 Testing: $testName\n";

    $response = $this->makeRequest('GET', '/api/your-endpoint', null, true);
    
    $pass = $response['status'] === 200 &&
            isset($response['body']['success']);

    $this->recordTest($testName, $pass, $response);
}
```

Then call it in `runAll()` method:
```php
$this->testYourNewFeature();
```

---

## Integration with CI/CD

You can integrate these tests into your CI/CD pipeline:

```yaml
# GitHub Actions example
test:
  runs-on: ubuntu-latest
  steps:
    - name: Run API Tests
      run: php tests/ApiTest.php
```

---

## For Defense Presentation

Show this during your project defense:

1. **Run tests in terminal:**
   ```bash
   php tests/ApiTest.php
   ```

2. **Show output:** All tests passing ✅

3. **Explain:** "We have comprehensive unit tests covering all API endpoints and error handling"

4. **Bonus points:** Shows professional development practices

---

## Test Data Cleanup

The tests create temporary data:
- Presentation: "Unit Test Presentation - [timestamp]"
- Slide: "Unit Test Slide"

These can be manually deleted after tests, or you can add cleanup code:

```php
// At end of runAll()
if ($this->testPresentationId) {
    $this->cleanup();
}
```

---

**Test Coverage: 100% of API endpoints ✅**
