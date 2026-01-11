# REST API Testing Guide

## Prerequisites
1. Make sure you're logged in to the application first
2. Your session cookie will be used for authentication

## Test Endpoints with cURL

### 1. Health Check (No auth required)
```bash
curl -X GET "http://localhost/WEB_project_presentation_generator/public/api/health"
```

**Expected Response:**
```json
{
  "success": true,
  "status": "OK",
  "service": "Presentation Generator API",
  "version": "1.0.0",
  "timestamp": "2025-12-31 12:00:00"
}
```

---

### 2. GET All Workspaces
First, login to get your session cookie.

```bash
# For Windows PowerShell (adjust session cookie)
$session = "your_phpsessid_here"
curl -X GET "http://localhost/WEB_project_presentation_generator/public/api/workspaces" `
  -H "Cookie: PHPSESSID=$session" `
  -H "Authorization: Bearer token"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "workspaces": [
      {
        "id": 1,
        "name": "My Workspace",
        "role": "owner"
      }
    ],
    "count": 1
  }
}
```

---

### 3. GET Presentation by ID
```bash
# Replace {id} with actual presentation ID (e.g., 1)
curl -X GET "http://localhost/WEB_project_presentation_generator/public/api/presentation/1" `
  -H "Cookie: PHPSESSID=$session" `
  -H "Authorization: Bearer token"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "presentation": {
      "id": 1,
      "workspace_id": 1,
      "title": "My Presentation",
      "language": "bg",
      "theme": "light",
      "created_at": "2025-12-31 10:00:00"
    },
    "slides": [...],
    "slide_count": 5
  }
}
```

---

### 4. POST Create New Presentation
```bash
curl -X POST "http://localhost/WEB_project_presentation_generator/public/api/presentation" `
  -H "Content-Type: application/json" `
  -H "Cookie: PHPSESSID=$session" `
  -H "Authorization: Bearer token" `
  -d '{
    "workspace_id": 1,
    "title": "API Created Presentation",
    "language": "en",
    "theme": "dark"
  }'
```

**Expected Response (201 Created):**
```json
{
  "success": true,
  "message": "Presentation created successfully",
  "data": {
    "id": 5,
    "presentation": {
      "id": 5,
      "workspace_id": 1,
      "title": "API Created Presentation",
      "language": "en",
      "theme": "dark"
    }
  }
}
```

---

### 5. GET Slide by ID
```bash
curl -X GET "http://localhost/WEB_project_presentation_generator/public/api/slide/1" `
  -H "Cookie: PHPSESSID=$session" `
  -H "Authorization: Bearer token"
```

---

### 6. POST Create New Slide
```bash
curl -X POST "http://localhost/WEB_project_presentation_generator/public/api/slide" `
  -H "Content-Type: application/json" `
  -H "Cookie: PHPSESSID=$session" `
  -H "Authorization: Bearer token" `
  -d '{
    "presentation_id": 1,
    "title": "API Created Slide",
    "layout": "text",
    "elements": []
  }'
```

---

### 7. PUT Update Slide
```bash
curl -X PUT "http://localhost/WEB_project_presentation_generator/public/api/slide/1" `
  -H "Content-Type: application/json" `
  -H "Cookie: PHPSESSID=$session" `
  -H "Authorization: Bearer token" `
  -d '{
    "title": "Updated Title via API",
    "layout": "two-column"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Slide updated successfully",
  "data": {
    "id": 1,
    "title": "Updated Title via API",
    "layout": "two-column"
  }
}
```

---

### 8. DELETE Slide
```bash
curl -X DELETE "http://localhost/WEB_project_presentation_generator/public/api/slide/5" `
  -H "Cookie: PHPSESSID=$session" `
  -H "Authorization: Bearer token"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Slide deleted successfully"
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "error": "Unauthorized - Bearer token required"
}
```

### 403 Forbidden
```json
{
  "success": false,
  "error": "Access denied"
}
```

### 404 Not Found
```json
{
  "success": false,
  "error": "Presentation not found"
}
```

### 400 Bad Request
```json
{
  "success": false,
  "error": "Missing required fields: workspace_id, title"
}
```

---

## Testing with Postman

1. **Import Collection**: Create a new collection "Presentation API"

2. **Set Environment Variables**:
   - `base_url`: `http://localhost/WEB_project_presentation_generator/public`
   - `session_cookie`: Your PHPSESSID value

3. **Authorization Setup**:
   - Type: Bearer Token
   - Token: `token` (or any value while session is active)
   - OR use Cookies: `PHPSESSID={{session_cookie}}`

4. **Create Requests**:
   - GET Health: `{{base_url}}/api/health`
   - GET Workspaces: `{{base_url}}/api/workspaces`
   - GET Presentation: `{{base_url}}/api/presentation/1`
   - POST Presentation: `{{base_url}}/api/presentation`
   - GET Slide: `{{base_url}}/api/slide/1`
   - POST Slide: `{{base_url}}/api/slide`
   - PUT Slide: `{{base_url}}/api/slide/1`
   - DELETE Slide: `{{base_url}}/api/slide/5`

---

## Getting Your Session Cookie

### Method 1: Browser DevTools
1. Login to the application
2. Open DevTools (F12)
3. Go to Application/Storage → Cookies
4. Copy PHPSESSID value

### Method 2: cURL with Login
```bash
# Login and capture cookies
curl -X POST "http://localhost/WEB_project_presentation_generator/public/auth/login" `
  -d "username=your_username&password=your_password" `
  -c cookies.txt

# Use cookies for API calls
curl -X GET "http://localhost/WEB_project_presentation_generator/public/api/workspaces" `
  -b cookies.txt `
  -H "Authorization: Bearer token"
```

---

## Testing Checklist

- [ ] Health check returns 200 OK
- [ ] GET workspaces returns user's workspaces
- [ ] GET presentation returns full data with slides
- [ ] POST presentation creates new presentation (201)
- [ ] GET slide returns single slide data
- [ ] POST slide creates new slide (201)
- [ ] PUT slide updates existing slide
- [ ] DELETE slide removes slide
- [ ] 401 error when no authentication
- [ ] 403 error when accessing other user's data
- [ ] 404 error when resource not found
- [ ] 400 error when missing required fields

---

## Quick Test Script (PowerShell)

Save this as `test-api.ps1`:

```powershell
# Configuration
$baseUrl = "http://localhost/WEB_project_presentation_generator/public"
$session = "your_phpsessid_here"

# Test 1: Health Check
Write-Host "Testing Health Check..." -ForegroundColor Yellow
$response = Invoke-RestMethod -Uri "$baseUrl/api/health" -Method Get
Write-Host ($response | ConvertTo-Json) -ForegroundColor Green

# Test 2: Get Workspaces
Write-Host "`nTesting Get Workspaces..." -ForegroundColor Yellow
$headers = @{
    "Cookie" = "PHPSESSID=$session"
    "Authorization" = "Bearer token"
}
$response = Invoke-RestMethod -Uri "$baseUrl/api/workspaces" -Method Get -Headers $headers
Write-Host ($response | ConvertTo-Json) -ForegroundColor Green

# Test 3: Get Presentation
Write-Host "`nTesting Get Presentation..." -ForegroundColor Yellow
$response = Invoke-RestMethod -Uri "$baseUrl/api/presentation/1" -Method Get -Headers $headers
Write-Host ($response | ConvertTo-Json -Depth 3) -ForegroundColor Green

Write-Host "`nAll tests completed!" -ForegroundColor Cyan
```

Run with: `.\test-api.ps1`

---

## Next Steps

After verifying all endpoints work:
1. ✅ Document the API (already done in DOCUMENTATION.md)
2. ✅ Create Postman collection for presentation
3. ✅ Test all error cases
4. 🔄 Move to Task 2: Documentation finalization
5. 🔄 Move to Task 3: Node.js Microservice (optional)

**Current Score: 30 points (Criterion 1) + 15 points (Criterion 4) = 45 points**
