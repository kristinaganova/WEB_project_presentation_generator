# RabbitMQ Setup Guide for PDF Service

## Какво трябва да инсталираш

### 1. **RabbitMQ Server**

#### Windows (Chocolatey):

```powershell
# Инсталирай Chocolatey първо (ако нямаш)
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

# Инсталирай Erlang (изисква се от RabbitMQ)
choco install erlang -y

# Инсталирай RabbitMQ
choco install rabbitmq -y

# Стартирай RabbitMQ
rabbitmq-service start
```

#### Windows (Ръчна инсталация):

1. Изтегли Erlang: https://www.erlang.org/downloads
2. Инсталирай Erlang
3. Изтегли RabbitMQ: https://www.rabbitmq.com/install-windows.html
4. Инсталирай RabbitMQ
5. Стартирай от Services или команден ред:
   ```cmd
   "C:\Program Files\RabbitMQ Server\rabbitmq_server-3.x.x\sbin\rabbitmq-server.bat"
   ```

#### Активирай Management Plugin (препоръчително):

```powershell
rabbitmq-plugins enable rabbitmq_management
```

След това отвори: http://localhost:15672

- Username: `guest`
- Password: `guest`

---

### 2. **PHP зависимости**

```bash
cd c:\xampp\htdocs\WEB_project_presentation_generator
composer install
```

Това ще инсталира `php-amqplib/php-amqplib` библиотеката.

---

### 3. **Node.js зависимости**

```bash
cd c:\xampp\htdocs\WEB_project_presentation_generator\pdf-service
npm install
```

Това ще инсталира `amqplib` за Node.js.

---

## Как да стартираш

### 1. Стартирай RabbitMQ (ако не работи)

```powershell
rabbitmq-service start
```

Провери дали работи:

```powershell
rabbitmq-diagnostics status
```

### 2. Стартирай PDF Service с RabbitMQ

```bash
cd c:\xampp\htdocs\WEB_project_presentation_generator\pdf-service
node server-rabbitmq.js
```

Трябва да видиш:

```
========================================
  PDF Generation Service (RabbitMQ)
========================================
Service: PDF Generator with RabbitMQ
Version: 2.0.0 (Message Queue)
Node: vXX.X.X
RabbitMQ URL: amqp://localhost
Queue: pdf_generation_requests
========================================

[RabbitMQ] Connecting to amqp://localhost...
[RabbitMQ] Connected successfully
[RabbitMQ] Channel created
[RabbitMQ] Waiting for PDF generation requests in queue: pdf_generation_requests
```

### 3. Промени PHP кода да използва RabbitMQ

В контролерите си, вместо:

```php
require_once '../app/helpers/PdfServiceClient.php';
$pdfClient = new PdfServiceClient();
```

Използвай:

```php
require_once '../app/helpers/PdfServiceClientRabbitMQ.php';
$pdfClient = new PdfServiceClientRabbitMQ(
    RABBITMQ_HOST,
    RABBITMQ_PORT,
    RABBITMQ_USER,
    RABBITMQ_PASS
);
```

---

## Тестване

### Тест скрипт за PHP

Създай `test-rabbitmq.php`:

```php
<?php
require_once 'config/config.php';
require_once 'app/helpers/Logger.php';
require_once 'app/helpers/PdfServiceClientRabbitMQ.php';

try {
    echo "Connecting to RabbitMQ...\n";

    $client = new PdfServiceClientRabbitMQ(
        RABBITMQ_HOST,
        RABBITMQ_PORT,
        RABBITMQ_USER,
        RABBITMQ_PASS,
        30
    );

    echo "Connection successful!\n";
    echo "Connection info: " . print_r($client->getConnectionInfo(), true) . "\n";

    echo "Generating test PDF...\n";

    $html = '<html><body><h1>RabbitMQ Test PDF</h1><p>This is a test PDF generated via RabbitMQ!</p></body></html>';

    $pdf = $client->generatePdf($html, 'rabbitmq-test');

    echo "PDF generated! Size: " . strlen($pdf) . " bytes\n";

    // Запази PDF
    file_put_contents('test-rabbitmq.pdf', $pdf);
    echo "PDF saved as test-rabbitmq.pdf\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
```

Стартирай:

```bash
php test-rabbitmq.php
```

---

## Проверка на опашките

### С Management UI:

1. Отвори http://localhost:15672
2. Login: `guest` / `guest`
3. Отиди на "Queues"
4. Трябва да видиш `pdf_generation_requests`

### С команден ред:

```bash
rabbitmqctl list_queues name messages consumers
```

---

## Конфигурация

### Промени в `config/config.php`:

```php
// Избери тип на PDF сървиса
define('PDF_SERVICE_TYPE', 'rabbitmq'); // или 'http'

// RabbitMQ настройки
define('RABBITMQ_HOST', 'localhost');
define('RABBITMQ_PORT', 5672);
define('RABBITMQ_USER', 'guest');
define('RABBITMQ_PASS', 'guest');
```

---

## Честа проблеми

### 1. "Connection refused"

- Провери дали RabbitMQ работи: `rabbitmq-diagnostics status`
- Стартирай го: `rabbitmq-service start`

### 2. "Authentication failed"

- Провери username/password в config.php
- По подразбиране: guest/guest (само за localhost)

### 3. "Maximum execution time exceeded"

- Увеличи timeout в PHP или PdfServiceClientRabbitMQ
- Провери дали PDF service работи

### 4. RabbitMQ не се стартира

- Провери дали Erlang е инсталиран: `erl -version`
- Провери логовете: `C:\Users\<user>\AppData\Roaming\RabbitMQ\log\`

---

## Скалиране (Multiple Workers)

За да стартираш повече PDF workers:

```bash
# Terminal 1
cd pdf-service
node server-rabbitmq.js

# Terminal 2
cd pdf-service
node server-rabbitmq.js

# Terminal 3
cd pdf-service
node server-rabbitmq.js
```

RabbitMQ автоматично ще разпредели заявките между тях!

---

## Мониторинг

### Провери статус на опашката:

```bash
rabbitmqctl list_queues name messages_ready messages_unacknowledged
```

### Провери consumers:

```bash
rabbitmqctl list_consumers
```

### Management UI статистики:

- http://localhost:15672/#/queues
- Виж графики за message rate, consumers, etc.

---

## Връщане към HTTP режим

Ако искаш да се върнеш към HTTP:

1. Промени в config.php:

   ```php
   define('PDF_SERVICE_TYPE', 'http');
   ```

2. Стартирай HTTP сървъра:

   ```bash
   cd pdf-service
   node server.js
   ```

3. Използвай оригиналния клиент:
   ```php
   require_once 'app/helpers/PdfServiceClient.php';
   $pdfClient = new PdfServiceClient();
   ```

---

## Заключение

След като инсталираш и настроиш всичко:

✅ RabbitMQ Server (с Erlang)
✅ PHP зависимости (composer install)
✅ Node.js зависимости (npm install)
✅ Конфигурация в config.php
✅ Стартиран RabbitMQ service
✅ Стартиран Node.js PDF consumer

Ще имаш работеща RabbitMQ-базирана PDF генерация система! 🚀
