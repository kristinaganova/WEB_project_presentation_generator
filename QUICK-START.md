# 🚀 Quick Start: Testing Your REST API

## Step 1: Start Your Server

**Option A: If you have XAMPP/WAMP already running**
- Just go to Step 2, it's already working!

**Option B: PHP Built-in Server**
```powershell
cd "c:\Uni\web\WEB_project_presentation_generator"
php -S localhost:8000 -t public
```

---

## Step 2: Test Health Endpoint (No Login Required)

**Open your browser and go to:**
```
http://localhost/WEB_project_presentation_generator/public/api/health
```

**You should see:**
```json
{
  "success": true,
  "status": "OK",
  "service": "Presentation Generator API",
  "version": "1.0.0",
  "timestamp": "2025-12-31 15:30:00"
}
```

✅ If you see this → **API IS WORKING!** 🎉

---

## Step 3: Login to Your Application

Before testing authenticated endpoints, you need to login:

```
http://localhost/WEB_project_presentation_generator/public/auth/login
```

Login with your credentials.

---

## Step 4: Use the Interactive Test Console

**Open:**
```
http://localhost/WEB_project_presentation_generator/public/api-test.html
```

**Now you can click buttons to test:**
1. ✅ Health Check
2. ✅ Get Workspaces
3. ✅ Get Presentation
4. ✅ Create Presentation
5. ✅ Get Slide
6. ✅ Create Slide
7. ✅ Update Slide
8. ✅ Delete Slide

All responses will show in real-time with color coding!

---

## Step 5: For Demo/Defense

### Show These 3 Things:

#### 1. Health Endpoint (in browser)
```
http://localhost/WEB_project_presentation_generator/public/api/health
```
→ Shows JSON response

#### 2. Get Workspaces (in api-test.html)
- Click "Get Workspaces"
- Shows your workspaces in JSON format

#### 3. Create Presentation (in api-test.html)
- Modify the JSON:
```json
{
  "workspace_id": 1,
  "title": "Demo Presentation Created via API",
  "language": "bg",
  "theme": "dark"
}
```
- Click "Create Presentation"
- See the new presentation ID in response

**Say:** "This demonstrates REST API communication - external applications can manage presentations through HTTP/JSON requests"

---

## 🎯 Quick Checklist

- [ ] Health endpoint returns 200 OK
- [ ] Can see workspaces after login
- [ ] Can create new presentation via API
- [ ] Can update existing slide
- [ ] All responses are in JSON format
- [ ] Error messages show proper status codes

**If all checked → You're ready! 🚀**

---

## 📞 Troubleshooting

**"404 Not Found"**
- Make sure .htaccess is in `public/` folder
- Check Apache mod_rewrite is enabled

**"401 Unauthorized"**  
- Login to the application first
- Use same browser for api-test.html

**"Can't access api-test.html"**
- Check file exists: `public/api-test.html`
- Try full URL: `http://localhost/WEB_project_presentation_generator/public/api-test.html`

---

## 🎓 For Your Defense Presentation

### Slide 1: Architecture
"We implemented a REST API that allows remote applications to communicate with our system"

### Slide 2: Live Demo
Show api-test.html → Click buttons → Show JSON responses

### Slide 3: Endpoints
- GET /api/presentation/{id} - Retrieve data
- POST /api/presentation - Create resources
- PUT /api/slide/{id} - Update resources
- DELETE /api/slide/{id} - Remove resources

### Key Phrase:
**"This is a web service (REST) providing remote procedure functionality through HTTP protocol, allowing distributed applications to integrate with our presentation system."**

---

## ✅ You Have Successfully Completed Task 1!

**Points Earned:** 45 / 100  
**Time Remaining:** 10 days  
**Next Task:** Documentation (2 hours → +10 points = 55 total)

**You're on track! 🎉**
