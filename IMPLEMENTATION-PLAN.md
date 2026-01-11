# План за имплементация - Разпределена система
**Проект:** Presentation Generator  
**Срок:** 11.01.2026 (11 дни)  
**Текущи точки:** 82 (след Task 1, 2, 3)  
**Целеви точки:** 92+ (отличен)

---

## 📊 ПРИОРИТИЗАЦИЯ

### ✅ ЗАВЪРШЕНИ ЗАДАЧИ
- **Task 1:** REST API endpoints (30т) ✅ COMPLETE
- **Task 3:** Node.js микросервиз (35т) ✅ COMPLETE + TESTED (28/28 tests)
- **Task 4:** WebSocket real-time (15т) ✅ COMPLETE + INTEGRATED
- **BONUS:** Swagger/OpenAPI документация (+2т) ✅ COMPLETE

### 🔴 СЛЕДВАЩА ЗАДАЧА (за финализиране)
- **Task 2:** Документация (10т) - 2 часа

---

## TASK 1: REST API Endpoints
**Точки:** 30 (Критерий 1)  
**Време:** 3-4 часа  
**Приоритет:** 🔴 КРИТИЧНО

### ЗАЩО ГО ПРАВИМ?
- Критерий 1: "комуникационна парадигма за отдалечено използване на функционалност"
- REST е **уеб услуга** (изрично споменато в заданието)
- Позволява external приложения да използват системата
- Най-лесният начин да спечелите 30 точки

### КАКВО ПРОМЕНЯ?
**ПРЕДИ:** Само HTML форми → монолитна система  
**СЛЕД:** REST API → разпределена система с HTTP комуникация

### КАКВО ТРЯБВА ДА СЕ НАПРАВИ?

#### 1.1 Създаване на API Controller
**Файл:** `app/controllers/ApiController.php`

```php
<?php
require_once __DIR__ . '/../core/AuthMiddleware.php';

class ApiController extends Controller
{
    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function authenticate() {
        // Bearer token или API key
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Проверка на token (сега: session)
        session_start();
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        return $_SESSION['user_id'];
    }

    // GET /api/presentations/{id}
    public function getPresentation($id) {
        $userId = $this->authenticate();
        
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        $slideModel = $this->model('Slide');
        
        $presentation = $presentationModel->getById($id);
        
        if (!$presentation) {
            $this->jsonResponse(['error' => 'Presentation not found'], 404);
        }
        
        if (!$workspaceModel->hasAccess($userId, $presentation['workspace_id'])) {
            $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $slides = $slideModel->getByPresentationId($id);
        
        $this->jsonResponse([
            'success' => true,
            'data' => [
                'presentation' => $presentation,
                'slides' => $slides
            ]
        ]);
    }

    // POST /api/presentations
    public function createPresentation() {
        $userId = $this->authenticate();
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!isset($data['workspace_id']) || !isset($data['title'])) {
            $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }
        
        $workspaceModel = $this->model('Workspace');
        if (!$workspaceModel->isOwner($userId, $data['workspace_id'])) {
            $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $presentationModel = $this->model('Presentation');
        $presentationId = $presentationModel->create(
            $data['workspace_id'],
            $data['title'],
            $data['language'] ?? 'bg',
            $data['theme'] ?? 'light'
        );
        
        if ($presentationId) {
            $this->jsonResponse([
                'success' => true,
                'data' => ['id' => $presentationId]
            ], 201);
        } else {
            $this->jsonResponse(['error' => 'Creation failed'], 500);
        }
    }

    // PUT /api/slides/{id}
    public function updateSlide($id) {
        $userId = $this->authenticate();
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        $slideModel = $this->model('Slide');
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        
        $slide = $slideModel->getById($id);
        if (!$slide) {
            $this->jsonResponse(['error' => 'Slide not found'], 404);
        }
        
        $presentation = $presentationModel->getById($slide['presentation_id']);
        if (!$workspaceModel->isOwner($userId, $presentation['workspace_id'])) {
            $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $success = $slideModel->update(
            $id,
            $data['title'] ?? $slide['title'],
            $data['layout'] ?? $slide['layout'],
            $data['content'] ?? $slide['content']
        );
        
        $this->jsonResponse([
            'success' => $success,
            'message' => $success ? 'Slide updated' : 'Update failed'
        ]);
    }

    // DELETE /api/slides/{id}
    public function deleteSlide($id) {
        $userId = $this->authenticate();
        
        $slideModel = $this->model('Slide');
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        
        $slide = $slideModel->getById($id);
        if (!$slide) {
            $this->jsonResponse(['error' => 'Slide not found'], 404);
        }
        
        $presentation = $presentationModel->getById($slide['presentation_id']);
        if (!$workspaceModel->isOwner($userId, $presentation['workspace_id'])) {
            $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $success = $slideModel->delete($id);
        
        $this->jsonResponse([
            'success' => $success,
            'message' => $success ? 'Slide deleted' : 'Deletion failed'
        ]);
    }

    // GET /api/workspaces - Бонус endpoint
    public function getWorkspaces() {
        $userId = $this->authenticate();
        
        $workspaceModel = $this->model('Workspace');
        $workspaces = $workspaceModel->getByUserId($userId);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $workspaces
        ]);
    }
}
```

#### 1.2 Обновяване на Router
**Файл:** `app/core/App.php`

Добавете API routes:

```php
// В метода parseUrl() или constructor
if ($url[0] === 'api') {
    array_shift($url);
    $this->controller = 'Api';
    // api/presentations/123 → ApiController->getPresentation(123)
}
```

#### 1.3 .htaccess за API routes
**Файл:** `public/.htaccess`

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ index.php?url=api/$1 [QSA,L]
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
```

### КРАЕН РЕЗУЛТАТ
✅ 4 работещи REST endpoints  
✅ JSON request/response  
✅ HTTP методи (GET, POST, PUT, DELETE)  
✅ Аутентикация чрез Bearer token  
✅ Error handling със статус кодове

### КАК ДА СЕ ТЕСТВА?

**Postman/cURL тестове:**

```bash
# 1. Login (get session cookie)
curl -X POST http://localhost/WEB_project_presentation_generator/auth/login \
  -d "username=test&password=test123"

# 2. GET презентация
curl -X GET http://localhost/WEB_project_presentation_generator/api/presentations/1 \
  -H "Authorization: Bearer SESSION_TOKEN" \
  -H "Cookie: PHPSESSID=your_session_id"

# 3. POST нова презентация
curl -X POST http://localhost/WEB_project_presentation_generator/api/presentations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SESSION_TOKEN" \
  -d '{"workspace_id": 1, "title": "API Test", "language": "en"}'

# 4. PUT update слайд
curl -X PUT http://localhost/WEB_project_presentation_generator/api/slides/5 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SESSION_TOKEN" \
  -d '{"title": "Updated via API", "content": "{}"}'

# 5. DELETE слайд
curl -X DELETE http://localhost/WEB_project_presentation_generator/api/slides/5 \
  -H "Authorization: Bearer SESSION_TOKEN"
```

**Expected responses:**
- 200: Success с JSON data
- 201: Created
- 401: Unauthorized
- 403: Forbidden
- 404: Not found
- 500: Server error

---

## TASK 2: Документация
**Точки:** 10 (Критерий 5)  
**Време:** 2 часа  
**Приоритет:** 🔴 КРИТИЧНО

### ЗАЩО ГО ПРАВИМ?
- Задължителен критерий 5
- Без документация = 0 точки за критерий 5
- Покажва професионализъм

### КАКВО ТРЯБВА ДА СЕ НАПРАВИ?

**Файл:** `DOCUMENTATION.md`

```markdown
# Документация - Presentation Generator
**Екип:** [Имена на 4-те студента]  
**Дата:** 11.01.2026

## 1. ОПИСАНИЕ НА СИСТЕМАТА

### 1.1 Обща архитектура
Разпределена софтуерна система за създаване и управление на презентации с:
- **Монолитно PHP приложение** (MVC архитектура)
- **REST API** за отдалечен достъп
- **Node.js микросервиз** за PDF генериране (опционално)
- **WebSocket сървър** за real-time комуникация (опционално)

### 1.2 Използвани технологии за комуникация
✅ **REST API** (Уеб услуги)
- HTTP методи: GET, POST, PUT, DELETE
- JSON формат за данни
- Bearer token аутентикация

✅ **AJAX** (Асинхронна комуникация)
- Fetch API за клиент-сървър комуникация
- Динамично обновяване без презареждане

✅ **Външна библиотека** (Интеграция)
- TCPDF за генериране на PDF

## 2. REST API СПЕЦИФИКАЦИЯ

### Endpoint 1: GET /api/presentations/{id}
**Описание:** Извличане на презентация със слайдове  
**Метод:** GET  
**Аутентикация:** Bearer token  
**Параметри:** id (integer) - ID на презентацията

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "presentation": {
      "id": 1,
      "title": "My Presentation",
      "workspace_id": 1,
      "language": "bg",
      "theme": "light"
    },
    "slides": [...]
  }
}
```

### Endpoint 2: POST /api/presentations
**Описание:** Създаване на нова презентация  
**Метод:** POST  
**Аутентикация:** Bearer token  
**Body (JSON):**
```json
{
  "workspace_id": 1,
  "title": "New Presentation",
  "language": "bg",
  "theme": "dark"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "id": 5
  }
}
```

### Endpoint 3: PUT /api/slides/{id}
**Описание:** Обновяване на съществуващ слайд  
**Метод:** PUT  
**Аутентикация:** Bearer token  
**Body (JSON):**
```json
{
  "title": "Updated Title",
  "layout": "text",
  "content": "{...}"
}
```

### Endpoint 4: DELETE /api/slides/{id}
**Описание:** Изтриване на слайд  
**Метод:** DELETE  
**Аутентикация:** Bearer token

## 3. АРХИТЕКТУРНА ДИАГРАМА

```
┌─────────────┐         REST API         ┌──────────────┐
│   Browser   │◄────────────────────────►│  PHP Server  │
│  (Client)   │     JSON over HTTP       │     (MVC)    │
└─────────────┘                          └──────┬───────┘
                                                 │
                                                 ▼
                                          ┌─────────────┐
                                          │   MySQL     │
                                          │  Database   │
                                          └─────────────┘
```

## 4. ИНСТАЛАЦИЯ

### 4.1 Изисквания
- PHP >= 7.4
- MySQL >= 5.7
- Composer
- Apache/Nginx

### 4.2 Стъпки
```bash
# 1. Clone проекта
git clone [repo]

# 2. Install dependencies
composer install

# 3. Конфигурация
cp config/config.example.php config/config.php
# Редактирайте DB credentials

# 4. Import database
mysql -u root -p < database/schema.sql

# 5. Start server
php -S localhost:8000 -t public/
```

## 5. ТЕСТВАНЕ

### 5.1 Unit тестове
[Ако имате]

### 5.2 Integration тестове
```bash
# Postman collection
curl -X GET http://localhost:8000/api/presentations/1
```

## 6. SECURITY
- Password hashing: bcrypt
- SQL injection prevention: PDO prepared statements
- XSS protection: htmlspecialchars()
- CSRF tokens: (planned)

## 7. ИЗПОЛЗВАНИ КОМУНИКАЦИОННИ ПАРАДИГМИ

| Парадигма | Имплементация | Критерий |
|-----------|---------------|----------|
| REST API | 4 endpoints | ✅ Критерий 1 (30т) |
| AJAX | Fetch API | ✅ Асинхронна комуникация |
| Външна библиотека | TCPDF | ✅ Критерий 4 (15т) |

## 8. ЗАКЛЮЧЕНИЕ
Системата имплементира REST архитектура за отдалечена комуникация между клиенти и сървър, позволявайки external приложения да управляват презентации чрез HTTP/JSON протокол.
```

### КРАЕН РЕЗУЛТАТ
✅ Пълна документация според шаблона  
✅ API спецификация  
✅ Архитектурна диаграма  
✅ Installation guide  
✅ Обяснение на комуникационните технологии

---

## TASK 3: Node.js Микросервиз за PDF ✅ ЗАВЪРШЕНА
**Точки:** 20 (Критерий 2) + 15 (Критерий 3) = 35 точки  
**Време:** 1 час implementation + 15 min setup  
**Статус:** ✅ **ЗАВЪРШЕНА**  
**Дата:** 31.12.2025

### РЕЗУЛТАТ
✅ **Node.js микросервиз създаден и работи**  
✅ **PHP интеграция готова**  
✅ **Тестове минават 100%**  
✅ **Документация завършена**

**Детайли:** Виж [TASK3-COMPLETE.md](TASK3-COMPLETE.md) и [TASK3-SETUP-GUIDE.md](TASK3-SETUP-GUIDE.md)

**Създадени файлове:**
- `pdf-service/server.js` - Node.js сървър с Express и Puppeteer
- `pdf-service/package.json` - Dependencies
- `pdf-service/test-service.js` - Automated tests (5 tests, 100% pass)
- `app/helpers/PdfServiceClient.php` - REST API клиент
- `app/controllers/PresentationController.php` - Добавен метод exportPdfViaService()
- `setup-pdf-service.ps1` - Automated setup script

**Как да стартирате:**
```powershell
cd pdf-service
npm install
npm start
```

**Тест:**
```powershell
node test-service.js
```

---

### ОРИГИНАЛЕН ПЛАН (за референция)

**Приоритет:** 🟡 ВИСОКО (ако имате време)

### ЗАЩО ГО ПРАВИМ?
- **Критерий 2:** "различни платформи" (PHP + Node.js) = 20 точки
- **Критерий 3:** "повече от една парадигма" (REST between services) = 15 точки
- **Общо:** 35 точки = най-голям възвращаемост

### КАКВО ПРОМЕНЯ?
**ПРЕДИ:** PHP генерира PDF с TCPDF  
**СЛЕД:** PHP → REST → Node.js → генерира PDF → връща на PHP

### КАКВО ТРЯБВА ДА СЕ НАПРАВИ?

#### 3.1 Node.js PDF Service
**Нова папка:** `pdf-service/`

**Файл:** `pdf-service/package.json`
```json
{
  "name": "presentation-pdf-service",
  "version": "1.0.0",
  "description": "Microservice for PDF generation",
  "main": "server.js",
  "scripts": {
    "start": "node server.js",
    "dev": "nodemon server.js"
  },
  "dependencies": {
    "express": "^4.18.2",
    "puppeteer": "^21.0.0",
    "cors": "^2.8.5"
  }
}
```

**Файл:** `pdf-service/server.js`
```javascript
const express = require('express');
const puppeteer = require('puppeteer');
const cors = require('cors');

const app = express();
app.use(express.json({ limit: '50mb' }));
app.use(cors());

// Health check
app.get('/health', (req, res) => {
    res.json({ status: 'OK', service: 'PDF Generator' });
});

// POST /generate-pdf
app.post('/generate-pdf', async (req, res) => {
    try {
        const { html, title } = req.body;
        
        if (!html) {
            return res.status(400).json({ error: 'HTML content required' });
        }

        // Launch headless browser
        const browser = await puppeteer.launch({
            headless: 'new',
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        });
        
        const page = await browser.newPage();
        
        // Set viewport for presentation size
        await page.setViewport({
            width: 1920,
            height: 1080
        });
        
        // Set HTML content
        await page.setContent(html, {
            waitUntil: 'networkidle0'
        });
        
        // Generate PDF
        const pdf = await page.pdf({
            format: 'A4',
            landscape: true,
            printBackground: true,
            margin: {
                top: '20px',
                right: '20px',
                bottom: '20px',
                left: '20px'
            }
        });
        
        await browser.close();
        
        // Return PDF as base64
        const pdfBase64 = pdf.toString('base64');
        
        res.json({
            success: true,
            pdf: pdfBase64,
            filename: `${title || 'presentation'}.pdf`
        });
        
    } catch (error) {
        console.error('PDF Generation Error:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

const PORT = process.env.PORT || 3001;
app.listen(PORT, () => {
    console.log(`PDF Service running on port ${PORT}`);
});
```

**Файл:** `pdf-service/README.md`
```markdown
# PDF Generation Microservice

Node.js микросервиз за генериране на PDF от HTML.

## Инсталация
```bash
npm install
npm start
```

## API
POST /generate-pdf
Body: { "html": "<html>...</html>", "title": "presentation" }
Response: { "success": true, "pdf": "base64..." }
```

#### 3.2 PHP клиент за микросервиза
**Файл:** `app/helpers/PdfServiceClient.php`

```php
<?php

class PdfServiceClient
{
    private $serviceUrl;
    
    public function __construct() {
        $this->serviceUrl = 'http://localhost:3001';
    }
    
    public function generatePdf($html, $title = 'presentation') {
        $ch = curl_init($this->serviceUrl . '/generate-pdf');
        
        $payload = json_encode([
            'html' => $html,
            'title' => $title
        ]);
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('PDF Service error: ' . $httpCode);
        }
        
        $data = json_decode($response, true);
        
        if (!$data['success']) {
            throw new Exception('PDF generation failed: ' . $data['error']);
        }
        
        return base64_decode($data['pdf']);
    }
    
    public function healthCheck() {
        $ch = curl_init($this->serviceUrl . '/health');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
}
```

#### 3.3 Използване в Controller
**Файл:** `app/controllers/PresentationController.php` (добавете метод)

```php
public function exportToPdfViaService($id) {
    $presentationModel = $this->model('Presentation');
    $slideModel = $this->model('Slide');
    
    $presentation = $presentationModel->getById($id);
    $slides = $slideModel->getByPresentationId($id);
    
    // Generate HTML
    $html = $this->renderSlidesAsHtml($slides, $presentation);
    
    // Call Node.js service
    $pdfClient = new PdfServiceClient();
    
    try {
        $pdfContent = $pdfClient->generatePdf($html, $presentation['title']);
        
        // Send to browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $presentation['title'] . '.pdf"');
        echo $pdfContent;
        exit;
        
    } catch (Exception $e) {
        // Fallback to TCPDF
        error_log('PDF Service unavailable: ' . $e->getMessage());
        $this->exportToPdf($id); // original method
    }
}

private function renderSlidesAsHtml($slides, $presentation) {
    $html = '<html><head><style>
        body { font-family: Arial; }
        .slide { page-break-after: always; padding: 40px; }
        h1 { color: #333; }
    </style></head><body>';
    
    foreach ($slides as $slide) {
        $html .= '<div class="slide">';
        $html .= '<h1>' . htmlspecialchars($slide['title']) . '</h1>';
        // Add slide content rendering
        $html .= '</div>';
    }
    
    $html .= '</body></html>';
    return $html;
}
```

### КРАЕН РЕЗУЛТАТ
✅ Node.js сървиз на порт 3001  
✅ PHP комуникира с Node.js чрез REST  
✅ Две платформи (PHP + Node.js)  
✅ Microservice архитектура  
✅ Fallback механизъм при грешка

### КАК ДА СЕ ТЕСТВА?

```bash
# 1. Start Node.js service
cd pdf-service
npm install
npm start

# 2. Test service directly
curl -X POST http://localhost:3001/generate-pdf \
  -H "Content-Type: application/json" \
  -d '{"html": "<h1>Test</h1>", "title": "test"}'

# 3. Test from PHP
# Browse to: /presentation/exportToPdfViaService/1
# Should download PDF generated by Node.js
```

---

## TASK 4: WebSocket Real-time ✅ ЗАВЪРШЕНА
**Точки:** 15 (Критерий 3)  
**Време:** 8 часа  
**Статус:** ✅ **ЗАВЪРШЕНА**  
**Дата:** 31.12.2025

### РЕЗУЛТАТ
✅ **WebSocket сървър създаден и работи**  
✅ **Client library имплементирана**  
✅ **10 теста минават 100%**  
✅ **Документация завършена**

**Детайли:** Виж [TASK4-COMPLETE.md](TASK4-COMPLETE.md)

**Създадени файлове:**
- `websocket-service/server.js` - Node.js WebSocket сървър (450 lines)
- `websocket-service/package.json` - Dependencies
- `websocket-service/test-websocket.js` - 10 automated tests (100% pass)
- `public/assets/js/websocket-client.js` - Client-side integration (450 lines)
- `public/assets/css/websocket.css` - Styling за real-time features (250 lines)
- `websocket-service/README.md` - Документация

**Как да стартирате:**
```powershell
cd websocket-service
npm install
npm start
```

**Тест:**
```powershell
npm test
```

---

### ОРИГИНАЛЕН ПЛАН (за референция)

### ЗАЩО ГО ПРАВИМ?
- Критерий 3: "повече от една парадигма"
- WebSocket = real-time двупосочна комуникация
- Впечатляващо за презентацията

### КАКВО ПРОМЕНЯ?
**ПРЕДИ:** Refresh за да видиш промени  
**СЛЕД:** Live updates при редактиране

### КАКВО ТРЯБВА ДА СЕ НАПРАВИ?

#### 4.1 WebSocket Server (Node.js)
**Файл:** `websocket-service/server.js`

```javascript
const WebSocket = require('ws');
const wss = new WebSocket.Server({ port: 3002 });

const rooms = new Map(); // presentationId -> Set of clients

wss.on('connection', (ws) => {
    console.log('Client connected');
    
    ws.on('message', (message) => {
        const data = JSON.parse(message);
        
        switch(data.type) {
            case 'join':
                // Join presentation room
                if (!rooms.has(data.presentationId)) {
                    rooms.set(data.presentationId, new Set());
                }
                rooms.get(data.presentationId).add(ws);
                ws.presentationId = data.presentationId;
                break;
                
            case 'slide-update':
                // Broadcast to all in room except sender
                const room = rooms.get(data.presentationId);
                if (room) {
                    room.forEach(client => {
                        if (client !== ws && client.readyState === WebSocket.OPEN) {
                            client.send(JSON.stringify({
                                type: 'slide-updated',
                                slideId: data.slideId,
                                content: data.content
                            }));
                        }
                    });
                }
                break;
        }
    });
    
    ws.on('close', () => {
        if (ws.presentationId && rooms.has(ws.presentationId)) {
            rooms.get(ws.presentationId).delete(ws);
        }
    });
});

console.log('WebSocket server running on port 3002');
```

#### 4.2 Client-side WebSocket
**Файл:** `public/assets/js/websocket-client.js`

```javascript
class PresentationWebSocket {
    constructor(presentationId) {
        this.presentationId = presentationId;
        this.ws = null;
        this.connect();
    }
    
    connect() {
        this.ws = new WebSocket('ws://localhost:3002');
        
        this.ws.onopen = () => {
            console.log('Connected to WebSocket');
            this.ws.send(JSON.stringify({
                type: 'join',
                presentationId: this.presentationId
            }));
        };
        
        this.ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            
            if (data.type === 'slide-updated') {
                this.handleSlideUpdate(data);
            }
        };
        
        this.ws.onerror = (error) => {
            console.error('WebSocket error:', error);
        };
        
        this.ws.onclose = () => {
            console.log('Disconnected. Reconnecting...');
            setTimeout(() => this.connect(), 3000);
        };
    }
    
    sendSlideUpdate(slideId, content) {
        if (this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                type: 'slide-update',
                presentationId: this.presentationId,
                slideId: slideId,
                content: content
            }));
        }
    }
    
    handleSlideUpdate(data) {
        // Update UI with received changes
        const slideElement = document.querySelector(`[data-slide-id="${data.slideId}"]`);
        if (slideElement) {
            slideElement.classList.add('updated-remotely');
            // Show notification: "Slide updated by another user"
            this.showNotification('Slide updated remotely');
        }
    }
    
    showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'ws-notification';
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => notification.remove(), 3000);
    }
}

// Initialize on presentation view page
if (window.location.pathname.includes('/presentation/view/')) {
    const presentationId = window.location.pathname.split('/').pop();
    const wsClient = new PresentationWebSocket(presentationId);
    
    // Hook into slide edit events
    document.addEventListener('slideEdited', (e) => {
        wsClient.sendSlideUpdate(e.detail.slideId, e.detail.content);
    });
}
```

### КРАЕН РЕЗУЛТАТ
✅ WebSocket сървър на порт 3002  
✅ Real-time комуникация  
✅ Broadcast на промени  
✅ Reconnection логика  
✅ Visual feedback за remote updates

### КАК ДА СЕ ТЕСТВА?

```bash
# 1. Start WebSocket server
cd websocket-service
npm install ws
node server.js

# 2. Open presentation in 2 browsers
Browser A: http://localhost:8000/presentation/view/1
Browser B: http://localhost:8000/presentation/view/1

# 3. Edit slide in Browser A
# 4. Browser B should see "Slide updated remotely" notification
```

---

## 📋 CHECKLIST ЗА ЗАЩИТА

### Преди защита проверете:
- [ ] REST API работи (Postman тестове готови)
- [ ] Документация попълнена с имена на екипа
- [ ] Микросервиз стартира (ако е имплементиран)
- [ ] WebSocket demo работи (ако е имплементиран)
- [ ] Презентация готова (10-15 слайда)
- [ ] Демо сценарий подготвен
- [ ] Code на GitHub/GitLab
- [ ] Всички dependency-та инсталирани

### Демо сценарий (5 мин):
1. Покажете REST API заявка (Postman)
2. Създайте презентация от UI
3. Export to PDF (през микросервиз)
4. Live preview (WebSocket - 2 browsers)
5. Обяснете архитектурата

---

## 🎯 АКТУАЛНО СЪСТОЯНИЕ И ТОЧКИ

### ✅ ЗАВЪРШЕНИ ЗАДАЧИ (97 точки → cap at 100)

| Задача | Точки | Статус | Дата |
|--------|-------|--------|------|
| Task 1: REST API | 30 | ✅ DONE | 31.12.2025 |
| Task 3: Node.js микросервиз | 35 (20+15) | ✅ DONE | 31.12.2025 |
| Task 4: WebSocket real-time | 15 | ✅ DONE | 31.12.2025 |
| External library (TCPDF) | 15 | ✅ DONE | Existing |
| Testing bonus | 2 | ✅ DONE | 31.12.2025 |
| **ОБЩО ТЕКУЩО** | **97** | | |

### 📝 СЛЕДВАЩИ СТЪПКИ (за финализиране)

| Задача | Точки | Време | Приоритет |
|--------|-------|-------|-----------|
| Task 2: Документация | 10 | 2 часа | 🔴 КРИТИЧНО |
| **ОБЩО С ДОКУМЕНТАЦИЯ** | **107 (cap at 100)** | | |

---

## 🎯 ОЧАКВАНИ ТОЧКИ (ФИНАЛЕН РЕЗУЛТАТ)

| Имплементация | Точки | Приоритет |
|---------------|-------|-----------|
| REST API only | 30 + 15 + 10 = **55т** | Минимум за добра оценка |
| REST + Node.js | 30 + 20 + 15 + 15 + 10 = **90т** | Отлично |
| REST + Node + WS | 30 + 20 + 15 + 15 + 15 + 10 = **105т** (макс 100) | ✅ **ТЕКУЩО: 99т (с Swagger)** |

**Текущ статус:** ✅ Максимална имплементация завършена! 99 точки (100 с документация).

**НОВИ ДОБАВКИ:**
- ✅ Swagger/OpenAPI документация за REST API (+2 точки бонус)
- ✅ WebSocket интегриран в PHP layout (100% функционален)
- ✅ 28/28 теста минават успешно
- ✅ Скрипт за бързо стартиране (start-all-services.ps1)

**Препоръка:** Финализирайте Task 2 (документация) за официален резултат от 100 точки.

---

## 📅 TIMELINE (11 дни)

### ДЕН 1-2 (31.12 - 01.01): REST API
- ApiController.php
- Router updates
- Postman тестове

### ДЕН 3-4 (02.01 - 03.01): Документация
- DOCUMENTATION.md
- API спецификация
- Диаграми

### ДЕН 5-7 (04.01 - 06.01): Node.js микросервиз (опционално)
- PDF service setup
- PHP integration
- Testing

### ДЕН 8-10 (07.01 - 09.01): WebSocket (опционално)
- WebSocket server
- Client integration
- Testing

### ДЕН 11 (10.01): Финализация
- Презентация
- Demo rehearsal
- Bug fixes

### 11.01.2026: DEADLINE 🎯

---

## ⚠️ ВАЖНИ НАПОМНЯНИЯ

1. **Не прекарвайте 8 часа върху WebSocket ако REST API не работи!**
2. **Документацията е задължителна** - без нея = -10 точки
3. **Тествайте всичко преди защита** - работещо demo = успех
4. **Commit-вайте често** - показва work in progress
5. **Подгответе fallback** - ако нещо не работи на защитата

---

## 🆘 TROUBLESHOOTING

**REST API не работи:**
- Проверете .htaccess rules
- Проверете Apache mod_rewrite enabled
- Проверете PHP error log

**Node.js connection refused:**
- Firewall блокира порт 3001?
- Service стартиран ли е?
- CORS настроен правилно?

**WebSocket не се свързва:**
- Browser блокира WS connection?
- Mixed content (HTTPS/WS) issue?
- Firewall на порт 3002?

---

**УСПЕХ! 🚀**
