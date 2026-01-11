# Курсов проект
**Дисциплина:** Проектиране и интегриране на софтуерни системи  
**Тема:** Presentation Generator (WEB_project_presentation_generator)  
**Секция от документа:** Реализация на системата  
**Версия:** 1.x  
**Дата:** 11.01.2026

**Фак. №:** [Попълнете]  
**Име на студент:** [Попълнете]

---

## 1. Въведение

### 1.1 Цел
Целта на този документ е да опише реализацията на уеб-базирана система за създаване и управление на презентации (**Presentation Generator**), включително:

- архитектура и ключови компоненти;
- използваните технологии и външни библиотеки;
- реализацията на базата от данни;
- реализацията на бизнес логиката (MVC, REST API, микросервизи, WebSocket);
- реализацията на потребителския интерфейс;
- внедряване/стартиране на системата;
- разпределение на дейностите по реализацията.

#### 1.1.1 Обхват на системата
Системата покрива следните основни функционалности:

- **Потребители**
  - регистрация, вход, изход;
  - сесии и контрол на достъпа.
- **Работни пространства (Workspaces)**
  - създаване/редакция/изтриване;
  - споделяне на workspace към друг потребител по имейл;
  - роли в workspace (напр. owner/viewer).
- **Презентации**
  - създаване/редакция/изтриване;
  - избор на тема и език;
  - преглед на презентация и съдържание (слайдове).
- **Слайдове и елементи**
  - добавяне/редакция/изтриване на слайдове;
  - нареждане (slide_order);
  - добавяне на елементи към слайд (text/list/image/quote и др.).
- **Експорт/импорт**
  - експорт в **HTML/XML/SLIM**;
  - експорт в **PDF** чрез Node.js микросервиз (Puppeteer);
  - импорт от HTML/XML/SLIM към нова презентация.
- **Разпределени комуникации**
  - **REST API** за отдалечен достъп до функционалност;
  - **WebSocket** за real-time синхронизация/колаборация (демо сценарии).

#### 1.1.2 Критерии и проследимост към имплементацията
Проектът е реализиран така, че да демонстрира разпределена система и да покрие изискванията на курса чрез няколко комуникационни парадигми:

| Изискване/критерий | Имплементация | Доказателство (файлове/ресурси) |
|---|---|---|
| Уеб услуга / отдалечено използване | REST API (HTTP/JSON) | `app/controllers/ApiController.php`, `public/swagger.json`, `public/api-docs.html` |
| Различни платформи | PHP + Node.js | `pdf-service/*`, `websocket-service/*` |
| Повече от една парадигма | REST + WebSocket | `websocket-service/server.js`, `public/assets/js/websocket-client.js` |
| Външна функционалност/библиотека | TCPDF / Puppeteer / Swagger UI | `vendor/*`, `pdf-service/package.json`, `public/api-docs.html` |
| Документация | Този документ + проектна документация | `DOCUMENTATION.md`, `IMPLEMENTATION-STATUS.md` |

### 1.2 Резюме
Документът е структуриран по секциите от предоставения шаблон:

- **Секция 2** описва използваните технологии и комуникационни парадигми (REST, WebSocket, микросервиз).
- **Секция 3** описва структурата на базата данни и връзките между таблиците.
- **Секция 4** описва бизнес логиката чрез модулите (Controllers/Models/Helpers) и ключовите сценарии.
- **Секция 5** описва основните екранни форми и потребителския поток.
- **Секция 6** описва стъпките за внедряване и стартиране (PHP приложение + Node.js услуги).
- **Секция 7** предоставя шаблон за разпределение на задачите по членове на екипа.
- **Секция 8** съдържа приложения (връзки към API документация, тестове, допълнителни материали).

### 1.3 Дефиниции и акроними
- **MVC**: Model–View–Controller архитектурен шаблон.
- **REST**: Representational State Transfer – стил за изграждане на уеб услуги над HTTP.
- **API**: Application Programming Interface – интерфейс за достъп до функционалност/данни.
- **JSON**: JavaScript Object Notation – формат за обмен на данни.
- **CRUD**: Create, Read, Update, Delete – основни операции върху данни.
- **WebSocket (WS)**: протокол за двупосочна комуникация в реално време.
- **CORS**: Cross-Origin Resource Sharing – механизъм за контрол на заявки между домейни.
- **PDO**: PHP Data Objects – абстракция за работа с база данни.
- **TCPDF**: PHP библиотека за генериране на PDF.
- **Puppeteer**: Node.js библиотека за управление на headless Chrome (рендериране/експорт).
- **SPA**: Single Page Application (не е основната архитектура тук, но се използва частична динамика чрез AJAX).
- **RBAC**: Role-Based Access Control – контрол на достъп чрез роли.
- **Health check**: endpoint за проверка дали услуга е „жива“ и отговаря.
- **Idempotency**: свойство на операции, които дават един и същ резултат при повторение (важно при PUT/DELETE).

---

## 2. Използвани технологии

### 2.1 Backend (основно приложение)
- **PHP 7.4+** – реализация на основното приложение.
- **MVC структура** – `app/controllers/`, `app/models/`, `app/views/`, `app/core/`.
- **PDO (MySQL driver)** – достъп до база данни (виж `app/core/Database.php`).

#### 2.1.1 Организация на проекта (папки)
- `app/core/` – ядро (router, middleware, DB, базов Controller/Model).
- `app/controllers/` – уеб контролери (UI) + REST контролер.
- `app/models/` – бизнес данни (CRUD, връзка с DB).
- `app/helpers/` – спомагателни класове (логове, рендериране, интеграции).
- `app/views/` – UI шаблони, разделени по домейн.
- `public/` – публично достъпни ресурси (front controller, assets, Swagger UI).
- `pdf-service/` – Node.js микросервиз за PDF.
- `websocket-service/` – Node.js WebSocket услуга.

#### 2.1.2 Причини за избор на PHP MVC подход
- **Подходящ за web приложения** с ясно разделение на отговорности (controllers/models/views).
- **Лесна поддръжка** и разширяемост: добавяне на нови controllers/views без промяна на общата архитектура.
- **Сигурност**: възможност за централизирани проверки (middleware + модели за достъп).

### 2.2 База данни
- **MySQL 5.7+** – съхранение на потребители, работни пространства, презентации и слайдове.

#### 2.2.1 Причини за избор на MySQL
- релационен модел с ясни връзки (1:N и M:N);
- широко използвана и лесна за deployment в учебна среда;
- добра интеграция с PHP чрез PDO.

### 2.3 Frontend (UI)
- **HTML/CSS/JavaScript** – интерфейс и динамика.
- **Fetch API (AJAX)** – асинхронна комуникация в браузъра (вкл. за UI операции).

#### 2.3.1 CSS/JS ресурси
Статичните ресурси са в `public/assets/`:
- CSS: `public/assets/css/*.css` (dashboard/presentation/slides/workspace + websocket стилове).
- JS: `public/assets/js/*.js` (основен JS + WebSocket client).

#### 2.3.2 UX решения (кратко)
- формите валидират входни данни и показват ясни съобщения за грешки;
- използва се модулен CSS по страници/модули, за да се намали coupling-а;
- за real-time функционалност има визуален status indicator и нотификации (WebSocket).

### 2.4 Разпределена архитектура (допълнителни услуги)
- **REST API** в PHP приложението – уеб услуги за отдалечен достъп (`app/controllers/ApiController.php`).
- **Node.js PDF микросервиз** (`pdf-service/`) – генериране на PDF през Puppeteer (порт 3001).
- **Node.js WebSocket услуга** (`websocket-service/`) – real-time синхронизация/колаборация (порт 3002, health порт 3003).

#### 2.4.1 Архитектурна диаграма (високо ниво)

```
┌─────────────────────────────────────────────────────────────┐
│                         Browser Client                       │
│  - UI (PHP templates)                                        │
│  - AJAX (Fetch API)                                          │
│  - WebSocket client                                          │
└───────────────┬───────────────────────────┬─────────────────┘
                │ HTTP/HTML + REST (JSON)   │ WebSocket
                ▼                           ▼
┌─────────────────────────────────────────────────────────────┐
│                  PHP Application (Port 8000)                 │
│  - MVC (Controllers/Models/Views)                            │
│  - REST API: /api/*                                          │
│  - Auth (sessions) + Authorization (workspace access)        │
│  - Export: HTML/XML/SLIM + PDF via microservice              │
└───────────────┬───────────────────────────┬─────────────────┘
                │ SQL (PDO)                 │ REST (JSON)
                ▼                           ▼
┌──────────────────────────┐      ┌────────────────────────────┐
│        MySQL DB           │      │  Node.js PDF Service        │
│  users/workspaces/...     │      │  (Port 3001, Puppeteer)     │
└──────────────────────────┘      └────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│           Node.js WebSocket Service (Port 3002)              │
│  - Rooms per presentation                                    │
│  - Broadcast updates                                         │
│  - Health check (Port 3003)                                  │
└─────────────────────────────────────────────────────────────┘
```

#### 2.4.2 Портове и отговорности
| Компонент | Порт | Протокол | Роля |
|---|---:|---|---|
| PHP приложение | 8000 | HTTP | UI + REST API + интеграция към DB/услуги |
| MySQL | 3306 | TCP | Персистентност |
| PDF микросервиз | 3001 | HTTP (REST) | HTML → PDF през Puppeteer |
| WebSocket сървър | 3002 | WS | Real-time съобщения |
| WebSocket health | 3003 | HTTP | Health endpoint за WS услугата |

#### 2.4.3 Причини за отделни Node.js услуги
- PDF рендерирането чрез headless Chrome е по-стабилно и качествено в Node.js (Puppeteer).
- WebSocket сървърът е отделен процес (изолация и независим restart).
- Разпределението демонстрира multi-platform архитектура и междусървисна комуникация.

### 2.5 Документация и тестване
- **Swagger/OpenAPI** – интерактивна документация на REST API (`public/swagger.json`, `public/api-docs.html`).
- **Автоматизирани тестове** – тестови скриптове за PDF услугата и WebSocket услугата (описани в `IMPLEMENTATION-STATUS.md`).

#### 2.5.1 Къде е „истината“ за API
- `public/swagger.json` е основният source-of-truth за REST API (OpenAPI 3.0).
- `public/api-docs.html` визуализира спецификацията и позволява интерактивно тестване („Try it out“).

---

## 3. Реализация на базата от данни

### 3.1 Тип на базата данни
Използва се **релационна база данни (MySQL)**. Достъпът се осъществява чрез PDO и подготвени заявки (prepared statements), което намалява риска от SQL injection.

### 3.2 Основни таблици и връзки
На база на заявките в моделите (`app/models/*.php`) и функционалността на системата, базата данни съдържа следните основни таблици:

- **`users`**
  - **Роля**: регистрирани потребители.
  - **Полета (минимум)**: `id`, `username`, `email`, `password_hash`, `created_at` (по избор).

- **`workspaces`**
  - **Роля**: логически контейнери/проекти, в които се съхраняват презентации.
  - **Полета (минимум)**: `id`, `name`, `created_at`.

- **`user_workspaces`**
  - **Роля**: M:N връзка между потребители и работни пространства, с роли.
  - **Полета (минимум)**: `workspace_id`, `user_id`, `role` (напр. `owner`, `viewer`).
  - **Връзки**: `users (1) ↔ (N) user_workspaces (N) ↔ (1) workspaces`.

- **`presentations`**
  - **Роля**: презентации, принадлежащи към workspace.
  - **Полета (минимум)**: `id`, `workspace_id`, `title`, `language`, `theme`, `created_at`.
  - **Връзки**: `workspaces (1) → (N) presentations`.

- **`slides`**
  - **Роля**: слайдове към презентация.
  - **Полета (минимум)**: `id`, `presentation_id`, `title`, `layout`, `slide_order`.
  - **Връзки**: `presentations (1) → (N) slides`.

- **`slide_elements`**
  - **Роля**: елементи на слайд (текст, списък, изображение, цитат и т.н.).
  - **Полета (минимум)**: `id`, `slide_id`, `element_order`, `type`, `title`, `content`, `text`, `style`.
  - **Връзки**: `slides (1) → (N) slide_elements`.

### 3.3 ER диаграма (логически модел)

```
 users                       user_workspaces                       workspaces
┌───────────┐               ┌────────────────┐                    ┌───────────┐
│ id (PK)   │◄──────────────┤ user_id (FK)   │                    │ id (PK)   │
│ username  │               │ workspace_id   ├──────────────►     │ name      │
│ email     │               │ role           │                    │ created_at│
│ pass_hash │               └────────────────┘                    └───────────┘
└───────────┘                         │
                                      │ (workspace_id)
                                      ▼
                              presentations
                             ┌─────────────────┐
                             │ id (PK)         │
                             │ workspace_id FK │
                             │ title           │
                             │ language        │
                             │ theme           │
                             │ created_at      │
                             └─────────────────┘
                                      │ (presentation_id)
                                      ▼
                                   slides
                             ┌─────────────────┐
                             │ id (PK)         │
                             │ presentation_id │
                             │ title           │
                             │ layout          │
                             │ slide_order     │
                             └─────────────────┘
                                      │ (slide_id)
                                      ▼
                               slide_elements
                             ┌────────────────────┐
                             │ id (PK)            │
                             │ slide_id (FK)      │
                             │ element_order      │
                             │ type               │
                             │ title/content/text │
                             │ style (JSON)       │
                             └────────────────────┘
```

### 3.4 Примерна DDL схема (референция)
> Заб.: В проекта няма отделен `schema.sql` файл в репото. Следната DDL е **референция**, извлечена като модел от заявките в кодa (модели и контролери). При нужда от предаване, може да се приложи като „примерна схема“.

```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE workspaces (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_workspaces (
  workspace_id INT NOT NULL,
  user_id INT NOT NULL,
  role ENUM('owner','viewer') NOT NULL DEFAULT 'viewer',
  PRIMARY KEY (workspace_id, user_id),
  CONSTRAINT fk_uw_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_uw_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE presentations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  workspace_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  language VARCHAR(10) NOT NULL DEFAULT 'bg',
  theme VARCHAR(50) NOT NULL DEFAULT 'light',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_p_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
);

CREATE TABLE slides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  presentation_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  layout VARCHAR(50) NOT NULL DEFAULT 'full',
  slide_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_s_presentation FOREIGN KEY (presentation_id) REFERENCES presentations(id) ON DELETE CASCADE,
  INDEX idx_slide_order (presentation_id, slide_order)
);

CREATE TABLE slide_elements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slide_id INT NOT NULL,
  element_order INT NOT NULL DEFAULT 0,
  type VARCHAR(50) NOT NULL,
  title VARCHAR(255) DEFAULT NULL,
  content TEXT DEFAULT NULL,
  text TEXT DEFAULT NULL,
  style JSON DEFAULT NULL,
  CONSTRAINT fk_se_slide FOREIGN KEY (slide_id) REFERENCES slides(id) ON DELETE CASCADE,
  INDEX idx_element_order (slide_id, element_order)
);
```

### 3.5 Забележки по реализацията
- В `app/models/Slide.php` при инициализация се гарантира наличието на колоната `slide_order` (DDL `ALTER TABLE` при липса), за да се поддържа нареждане на слайдовете.
- Достъпите се проверяват чрез таблицата `user_workspaces` (роля и принадлежност), което се използва за authorization на UI и REST API.

### 3.6 Транзакции и консистентност
При операции, които включват промяна на повече от една таблица (пример: създаване/ъпдейт на слайд + елементи), се използват транзакции:
- `beginTransaction()` → `commit()` при успех
- `rollBack()` при грешка  
Това гарантира, че няма да се получат „полусъздадени“ записи (slide без elements или обратно).

---

## 4. Реализация на бизнес логиката

### 4.1 Архитектурен модел (MVC)
Приложението използва MVC структура:

- **Controllers** (`app/controllers/`): обработват HTTP заявки, проверяват права, извикват модели и връщат view/JSON.
- **Models** (`app/models/`): инкапсулират достъп до базата данни (CRUD).
- **Views** (`app/views/`): шаблони за визуализация (PHP templates).
- **Core** (`app/core/`): router (`App.php`), middleware (`AuthMiddleware.php`), DB layer (`Database.php`).

### 4.2 Routing
Router-ът е реализиран в `app/core/App.php`:

- стандартни маршрути (пример): `/dashboard/index`, `/presentation/viewPresentation/{id}`.
- **API маршрути**: при URL започващ с `/api/*`, се пренасочва към `ApiController` и методът се определя от следващия сегмент (пример: `/api/presentation/1`).

#### 4.2.1 Front controller и rewrite (кратко)
Приложението следва front controller подход:
- всички заявки се насочват към `public/index.php`;
- router-ът (`app/core/App.php`) парсира URL и избира controller + method;
- при `/api/*` се активира API режим и се връща JSON.

### 4.3 Аутентикация и authorization
- **Аутентикация**: чрез PHP сесия в `AuthController` (login/register/logout).
- **Middleware**: `AuthMiddleware::requireLogin()` защитава страници изискващи login.
- **Authorization**:
  - достъп до workspace: `Workspace::hasAccess($userId, $workspaceId)`
  - собственик: `Workspace::isOwner($userId, $workspaceId)`
  - при REST API се използват същите проверки, за да се гарантира, че външен клиент не може да достъпи чужди ресурси.

#### 4.3.1 RBAC модел в workspace
Ролите се пазят в `user_workspaces.role`. Основни правила:
- **owner**: може да създава/редактира/изтрива презентации и слайдове в workspace, както и да споделя workspace.
- **viewer**: може да разглежда workspace и презентации (read-only).

### 4.4 Основни модули/контролери

#### 4.4.1 `AuthController`
- регистрация и вход на потребители.
- паролите се съхраняват като hash (`password_hash` / `password_verify`).

**Сценарий: Вход (Login)**
1. Потребителят подава email и password през `auth/login`.
2. Системата намира потребител по email (`User::findByEmail()`).
3. Паролата се валидира чрез `password_verify()`.
4. При успех се сетва `$_SESSION['user_id']` и се пренасочва към начална страница/табло.

#### 4.4.2 `DashboardController`
- управление на работни пространства:
  - създаване, редакция, изтриване;
  - списък на workspaces за текущия потребител;
  - споделяне на workspace към друг потребител по email (role-based).

**Сценарий: Споделяне на workspace**
1. Owner въвежда email на потребител.
2. Системата търси user по email.
3. Ако user съществува и няма достъп, се добавя запис в `user_workspaces` с роля (по подразбиране viewer).
4. Потребителят вижда workspace в dashboard при следващо влизане.

#### 4.4.3 `PresentationController`
- CRUD за презентации (create/edit/delete/view).
- работа със слайдове и елементи (чрез `Slide`/`SlideElement` модели).
- експорт на презентация:
  - **HTML / XML / SLIM** формати;
  - **PDF експорт през микросервиз**: `exportPdfViaService()` – PHP → REST → Node.js → PDF → връщане към браузър.
- интеграция с WebSocket (broadcast при update на подредбата на слайдовете чрез `WebSocketNotifier`).

##### 4.4.3.1 Сценарий: Експорт към PDF през микросервиз
Последователност (sequence):

```
Browser -> PHP: GET /presentation/exportPdfViaService/{id}
PHP -> DB: SELECT presentation + slides + slide_elements
PHP -> PHP: Render slides to HTML
PHP -> PDF Service: POST http://localhost:3001/generate-pdf { html, title, options }
PDF Service -> Puppeteer: render HTML in headless Chrome
PDF Service -> PHP: JSON { success, pdf(base64), filename }
PHP -> Browser: application/pdf (download)
```

Характеристики:
- комуникацията PHP → Node.js е **REST (HTTP/JSON)**;
- PDF се генерира с **Puppeteer**, което осигурява добро визуално качество;
- при проблем с услугата се връща контрол към UI с грешка и инструкции за стартиране на PDF service.

#### 4.4.4 `ApiController` (REST API)
Реализира JSON REST endpoints (CRUD), включително:

- `GET /api/health` – проверка на статуса на API (без auth).
- `GET /api/workspaces` – списък на workspaces за потребителя (auth).
- `GET /api/presentation/{id}` – детайли на презентация + слайдове (auth + access checks).
- `POST /api/presentation` – създаване на презентация (auth + owner checks).
- `GET/POST/PUT/DELETE /api/slide[...]` – операции върху слайдове/елементи (auth + owner checks).

Аутентикацията за API се реализира в `ApiController::authenticate()`:
- приема активна сесия (в browser) или Bearer token (за външни клиенти/демо).
- поддържа **CORS** и `OPTIONS` заявки.

### 4.5 Разпределени комуникационни парадигми (обобщение)
- **REST (HTTP/JSON)**: основна комуникация за API и за междусървисна комуникация.
- **WebSocket**: двупосочна real-time комуникация за синхронизация/колаборация.
- **AJAX (Fetch API)**: асинхронни UI операции без презареждане (където е приложимо).

### 4.6 REST API спецификация (подробно)
> Заб.: Пълната интерактивна спецификация е достъпна в Swagger UI: `http://localhost:8000/api-docs.html`.

#### 4.6.1 Общи правила
- **Base URL**: `http://localhost:8000`
- **Content-Type**: `application/json`
- **Аутентикация**:
  - при браузър тестове: активна PHP сесия (login в приложението);
  - при външен клиент: `Authorization: Bearer <token>` (в демо режим се приема сесия; за production е нужна реална валидация).
- **Конвенции за отговор**:
  - `success: true/false`
  - при грешка: `error` + подходящ HTTP статус.

#### 4.6.2 Endpoint: Health check
- **GET** `/api/health`
- **Auth**: не

Примерен отговор:
```json
{
  "success": true,
  "status": "OK",
  "service": "Presentation Generator API",
  "version": "1.0.0",
  "timestamp": "2026-01-11 12:00:00"
}
```

#### 4.6.3 Endpoint: Workspaces
- **GET** `/api/workspaces`
- **Auth**: да

Отговор (пример):
```json
{
  "success": true,
  "data": {
    "workspaces": [
      { "id": 1, "name": "My Workspace", "role": "owner", "created_at": "2026-01-01 10:00:00" }
    ],
    "count": 1
  }
}
```

#### 4.6.4 Endpoint: Presentation (GET/POST)
- **GET** `/api/presentation/{id}` – детайли + слайдове
- **POST** `/api/presentation` – създаване

POST body (пример):
```json
{
  "workspace_id": 1,
  "title": "Demo Presentation",
  "language": "bg",
  "theme": "dark"
}
```

GET response (пример, съкратено):
```json
{
  "success": true,
  "data": {
    "presentation": { "id": 1, "workspace_id": 1, "title": "Demo", "language": "bg", "theme": "dark" },
    "slides": [
      { "id": 10, "presentation_id": 1, "title": "Slide 1", "layout": "full", "slide_order": 1, "elements": [] }
    ],
    "slide_count": 1
  }
}
```

#### 4.6.5 Endpoint: Slide (CRUD)
- **GET** `/api/slide/{id}`
- **POST** `/api/slide`
- **PUT** `/api/slide/{id}`
- **DELETE** `/api/slide/{id}`

POST body (пример – slide с елементи):
```json
{
  "presentation_id": 1,
  "title": "Architecture",
  "layout": "two-columns",
  "slide_order": 2,
  "elements": [
    { "type": "text", "title": "Overview", "text": "Distributed architecture with REST + WS." },
    { "type": "list", "title": "Components", "content": "PHP MVC\nNode PDF\nNode WS\nMySQL" }
  ]
}
```

Статуси при грешки (общо за API):
- **400**: липсващи/невалидни данни (invalid JSON, missing fields).
- **401**: неаутентициран потребител.
- **403**: липса на права (не owner / няма достъп до workspace).
- **404**: ресурсът не съществува.
- **500**: сървърна грешка.

### 4.7 WebSocket реализация (протокол и сценарии)
WebSocket услугата позволява real-time събития при промени по презентация (пример: update на слайд, присъствие на потребители).

#### 4.7.1 Адреси
- WebSocket: `ws://localhost:3002`
- Health: `http://localhost:3003/health`

#### 4.7.2 Основни message types (примерна схема)
Client → Server (пример join):
```json
{
  "type": "join",
  "presentationId": "123",
  "userId": "1",
  "username": "Student"
}
```

Client → Server (пример slide-update):
```json
{
  "type": "slide-update",
  "presentationId": "123",
  "slideId": "456",
  "content": "{...}",
  "title": "Updated",
  "layout": "text"
}
```

Server → Client (пример slide-updated broadcast):
```json
{
  "type": "slide-updated",
  "slideId": "456",
  "content": "{...}",
  "updatedBy": "Student",
  "timestamp": 1735660800000
}
```

#### 4.7.3 Сценарий за демо (2 браузъра)
1. Стартирайте WS услугата.
2. Отворете една и съща презентация в два прозореца.
3. При промяна в единия, другият получава събитие/нотификация (status indicator + highlight).

---

## 5. Реализация на потребителския интерфейс

### 5.1 Основни екрани (views)
UI е реализиран като PHP templates в `app/views/`:

- **Аутентикация**
  - `app/views/auth/login.php` – вход
  - `app/views/auth/register.php` – регистрация

- **Dashboard / Workspaces**
  - `app/views/dashboard/index.php` – списък с workspaces
  - `app/views/dashboard/workspace.php` – детайли на workspace + презентации
  - `app/views/dashboard/create_workspace.php` / `edit_workspace.php` / `delete_workspace.php`
  - `app/views/dashboard/share_workspace.php` – споделяне

- **Presentations**
  - `app/views/presentation/create.php` / `edit.php` / `delete.php`
  - `app/views/presentation/view.php` – разглеждане на презентация и слайдове
  - `app/views/presentation/review.php` – преглед

- **Slides**
  - `app/views/slides/create.php` / `edit.php` / `delete.php`
  - `app/views/slides/templates/*` – шаблони за слайд елементи (text/list/quote/image/two-column)

### 5.1.1 Навигация и layout
Основният layout е в `app/views/layouts/main.php` и включва:
- общи стилове и модулни стилове (dashboard/presentation/slides/...);
- условно зареждане на WebSocket ресурси при страници за презентация;
- body атрибути за текущ потребител (идентификатор/име), използвани от WebSocket client.

### 5.2 Основен потребителски поток
1. Потребителят се регистрира/логва.
2. Създава workspace или влиза в съществуващ.
3. Създава презентация в workspace.
4. Добавя/редактира слайдове и елементи (по шаблони).
5. Експортира презентация (HTML/XML/SLIM/PDF).
6. (По избор) използва real-time функционалност при отворена презентация в две сесии.

### 5.3 Екранни форми (приложение)
В тази секция по шаблон се очакват **снимки/скрийншоти**. Препоръчителни скрийншоти:

- Login/Register
- Dashboard (списък workspaces)
- Workspace view (презентации)
- Presentation view (слайдове + бутони за export)
- WebSocket статус/нотификации (ако е включено)
- Swagger UI (`/api-docs.html`)

#### 5.3.1 Шаблон за поставяне на скрийншоти
> В Word/PDF версията на документа поставете изображения и кратки описания. Пример:

- **Фигура 1:** Login форма (валидиране при грешен email/парола)
- **Фигура 2:** Dashboard – списък с workspaces + бутони
- **Фигура 3:** Workspace – списък с презентации
- **Фигура 4:** Presentation view – слайдове и елементи
- **Фигура 5:** Swagger UI – „Try it out“ за GET /api/health
- **Фигура 6:** WebSocket – 2 прозореца и real-time нотификация

---

## 6. Внедряване на системата

### 6.1 Изисквания
- PHP 7.4+
- MySQL 5.7+
- Composer
- Node.js 16+
- npm
- Web server (Apache/Nginx) **или** PHP built-in server за разработка

### 6.2 Конфигурация
- Настройки за БД: `config/config.php` (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).

#### 6.2.1 Логове и диагностика
- Приложение: `logs/app.log` и `logs/php_errors.log`.
- При проблеми с база данни (setup), `app/core/Database.php` логва диагностични съобщения за DSN/връзка/заявки.

### 6.3 Стартиране (ръчно)
**PHP приложение:**

```bash
php -S localhost:8000 -t public/
```

**PDF микросервиз (порт 3001):**

```bash
cd pdf-service
npm install
npm start
```

**WebSocket услуга (порт 3002, health 3003):**

```bash
cd websocket-service
npm install
npm start
```

### 6.4 Достъпни адреси
- Приложение: `http://localhost:8000`
- Swagger UI: `http://localhost:8000/api-docs.html`
- REST API: `http://localhost:8000/api/*`
- PDF Service health: `http://localhost:3001/health`
- WebSocket health: `http://localhost:3003/health`

### 6.5 Troubleshooting (чести проблеми)
- **API връща 404**:
  - проверете дали стартирате от `public/` (при PHP built-in server);
  - ако проектът е под поддиректория, router-ът чисти сегменти като `public` и `WEB_project_presentation_generator` (виж `App::parseUrl()`).
- **401 Unauthorized**:
  - логнете се в UI (session) или подайте Bearer token според Swagger инструкциите.
- **PDF export не работи**:
  - уверете се, че PDF service е стартиран на порт 3001 и `/health` връща 200.
- **WebSocket не се свързва**:
  - уверете се, че WS service е стартиран на порт 3002 и health на 3003;
  - проверете firewall/портове.

---

## 7. Разпределение на дейностите по реализацията

> Заб.: В този раздел попълнете реалните имена/фак. № и точните дейности. По-долу е готов шаблон, съобразен с реализираните задачи (Task 1/3/4 + документация).

| Член на екипа | Фак. № | Дейности | Артефакти/файлове |
|---|---|---|---|
| [Име 1] | [№] | REST API + Swagger | `app/controllers/ApiController.php`, `public/swagger.json`, `public/api-docs.html`, `API-TESTING.md` |
| [Име 2] | [№] | PDF микросервиз + интеграция | `pdf-service/*`, `app/helpers/PdfServiceClient.php`, `PresentationController::exportPdfViaService()` |
| [Име 3] | [№] | WebSocket услуга + client интеграция | `websocket-service/*`, `public/assets/js/websocket-client.js`, `public/assets/css/websocket.css`, layout integration |
| [Име 4] | [№] | Документация + тестове | `DOCUMENTATION.md`, `IMPLEMENTATION-STATUS.md`, тестови скриптове в услугите |

---

## 8. Приложения

### 8.1 Документация и полезни файлове в проекта
- `DOCUMENTATION.md` – пълна техническа документация (архитектура, API, инсталация, тестване, security).
- `IMPLEMENTATION-STATUS.md` – отчет за имплементацията и тест резултати.
- `TASK1-COMPLETE.md`, `TASK3-SUMMARY.md`, `TASK4-COMPLETE.md` – описания на задачите/имплементациите.
- `public/swagger.json`, `public/api-docs.html` – Swagger/OpenAPI.

### 8.2 Какво да приложите при предаване (препоръчително)
- PDF/Word версия на този документ (експорт от `.md` или попълване на `.docx`).
- Скрийншоти от UI (секция 5.3).
- Кратък лог/скрийншоти от тестове (28/28 за PDF услугата, WS тестове или manual demo).

### 8.3 Тестове (резюме)
Резултатите са описани в `IMPLEMENTATION-STATUS.md`. Накратко:

- **PDF микросервиз**: 28/28 automated tests PASS (basic + extended + PHP integration).
- **WebSocket услуга**: наличен тестов suite (10 теста) и manual demo сценарий с 2 браузъра.

### 8.3.1 Как се стартират тестовете (подробно)

#### A) PDF микросервиз – Basic tests
Предварително: услугата трябва да работи на порт 3001.

```bash
cd pdf-service
npm start
```

В отделен терминал:
```bash
cd pdf-service
node test-service.js
```

Очаквано:
- health check да върне 200;
- да се генерира PDF (непразен buffer);
- коректно error handling при невалидни входни данни.

#### B) PDF микросервиз – Extended tests
```bash
cd pdf-service
node test-service-extended.js
```

Покрива:
- големи HTML документи (стрес тест);
- различни формати/ориентации;
- Unicode/Cyrillic;
- performance измерване.

#### C) PHP ↔ PDF Service интеграционни тестове
Тези тестове демонстрират междуплатформена комуникация (PHP клиент → Node.js).

```bash
php "pdf-service/test-pdf-integration.php"
```

> Заб.: При различни среди може да е нужен конкретен път към PHP изпълнимия файл.

#### D) WebSocket – automated tests / manual
Автоматизираните тестове са в `websocket-service/test-websocket.js`:

```bash
cd websocket-service
npm start
```

В отделен терминал:
```bash
cd websocket-service
npm test
```

Manual тест (препоръчан за защита):
- отворете една и съща презентация в 2 браузъра;
- направете промяна/действие в единия;
- във втория трябва да се появи нотификация/индикатор за remote update.

### 8.4 Security (резюме)
- пароли: `password_hash()` / `password_verify()` (bcrypt).
- SQL injection: prepared statements (PDO).
- XSS: `htmlspecialchars()` във views и експорт.
- Authorization: проверки за достъп до workspace/presentation преди CRUD операции.

### 8.4.1 Security мерки (детайлно)

#### Аутентикация
- UI използва PHP сесии (cookie-based).
- API приема сесия (browser context) и/или Bearer header (демо режим).

#### Authorization (контрол на достъп)
- Всеки resource (workspace/presentation/slide) минава през проверка за принадлежност:
  - `Workspace::hasAccess()` – достъп за членове
  - `Workspace::isOwner()` – права за промяна
- Това правило се използва едновременно в UI и в REST API.

#### Валидация на вход
- JSON payload-и се валидират (parse + required fields).
- При грешки се връщат подходящи статус кодове (400/401/403/404/500).

#### Защита от XSS
- Използва се `htmlspecialchars()` при визуализация и при export HTML/PDF.

#### Защита от SQL injection
- Използват се prepared statements (PDO), а не string concatenation.

#### CORS
- API set-ва CORS headers (полезно за demo/интеграции). В production среда достъпът трябва да се ограничи до доверени origin-и.

### 8.4.2 Потенциални рискове и ограничения
- Bearer token в демо режим не валидира реален token store (подходящо за учебен проект, но не и за production).
- Няма отделен CSRF механизъм за UI формите (възможно бъдещо подобрение).

### 8.5 Ограничения и бъдещо развитие

#### Ограничения (текуща версия)
- Липсва централизирана миграционна система за DB (DDL се подразбира от модела; частично се „самопоправя“ чрез `Slide::ensureSlideOrderColumn()`).
- PDF service изисква инсталация на Node dependencies (Puppeteer може да е тежък download).
- WebSocket demo изисква стартирана отделна услуга и отворени 2 сесии.

#### Бъдещо развитие
- JWT auth за API вместо демо Bearer подход.
- CSRF защита за UI форми.
- Docker compose за 1-командно стартиране (PHP + MySQL + Node services).
- Rate limiting за API endpoints.
- Audit trail (кой е променил слайд, кога) + версия на презентации.

### 8.5 Демо сценарий за защита (5 мин)
1. Показване на архитектурната диаграма (разпределени компоненти).
2. Swagger UI: изпълнение на `GET /api/health` и (след login) `GET /api/workspaces`.
3. PDF export: `exportPdfViaService` → генериран PDF.
4. WebSocket demo: отворете презентация в 2 браузъра → промяна → нотификация.
5. Тестове: покажете резюме 28/28 PASS (доказателство за качество).

### 8.6 Приложение: cURL примери (REST API)
> Ако тествате извън браузър, най-лесно е да вземете `PHPSESSID` cookie от DevTools след login и да го използвате като Bearer (демо режим) или cookie.

```bash
# Health check
curl -s http://localhost:8000/api/health

# Workspaces (пример с Bearer)
curl -s http://localhost:8000/api/workspaces \\
  -H "Authorization: Bearer YOUR_PHPSESSID"

# Create presentation (пример)
curl -s -X POST http://localhost:8000/api/presentation \\
  -H "Content-Type: application/json" \\
  -H "Authorization: Bearer YOUR_PHPSESSID" \\
  -d '{\"workspace_id\":1,\"title\":\"API Demo\",\"language\":\"bg\",\"theme\":\"light\"}'
```

