# WEB Project: Presentation Generator

Разпределено уеб приложение за създаване и управление на презентации с:
- PHP MVC приложение (UI + REST API)
- Node.js PDF микросервиз (Puppeteer) за експорт в PDF
- Node.js WebSocket service за real-time синхронизация
- MySQL база данни

## Архитектура (накратко)
- **Browser** → **PHP App** (`http://localhost:8000`)
- **PHP App** → **MySQL**
- **PHP App** → **PDF Service** (`http://localhost:3001`) по REST (`/generate-pdf`)
- **Browser** ↔ **WebSocket Service** (`ws://localhost:3002`)

## Портове
- **8000**: PHP app
- **3001**: PDF microservice (Express + Puppeteer)
- **3002**: WebSocket service
- **3003**: WebSocket health check

## Изисквания
- PHP + Composer
- MySQL
- Node.js + npm

## Инсталация
### 1) PHP dependencies
```bash
composer install
```

### 2) Node.js dependencies
```bash
cd pdf-service && npm install
cd ../websocket-service && npm install
```

### 3) Database
Създай DB `presentation_generator` и импортни `presentation_generator.sql`.

## Стартиране (ръчно)
### Terminal 1 — PDF service
```bash
cd pdf-service
npm start
```

### Terminal 2 — WebSocket service
```bash
cd websocket-service
npm start
```

### Terminal 3 — PHP app
```bash
php -S localhost:8000 -t public
```

## Полезни линкове
- **App**: `http://localhost:8000`
- **Swagger UI**: `http://localhost:8000/api-docs.html`
- **API health**: `http://localhost:8000/api/health`
- **PDF health**: `http://localhost:3001/health`

## PDF export (важно)
При експорт към PDF, локалните assets (напр. `/assets/img/...`) се **вграждат като `data:` URI** преди да се изпратят към PDF service, за да се избегнат timeouts при `php -S` (еднонишков).
