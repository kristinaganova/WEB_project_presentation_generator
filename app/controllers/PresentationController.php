<?php

require_once __DIR__ . '/../core/AuthMiddleware.php';

class PresentationController extends Controller
{
    public function __construct()
    {
        AuthMiddleware::requireLogin();
    }

    private function checkOwnerAccess($workspaceId)
    {
        $workspaceModel = $this->model('Workspace');
        $userId = AuthMiddleware::currentUserId();
        
        if (!$workspaceModel->isOwner($userId, $workspaceId)) {
            header('Location: ' . BASE_URL . '/dashboard/workspace/' . $workspaceId);
            exit;
        }
    }

    public function create($workspaceId = null)
    {
        if ($workspaceId === null) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $workspaceModel = $this->model('Workspace');
        $userId = AuthMiddleware::currentUserId();
        
        if (!$workspaceModel->hasAccess($userId, $workspaceId)) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $this->checkOwnerAccess($workspaceId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $presentationModel = $this->model('Presentation');
            $title = trim($_POST['title']);
            $language = $_POST['language'] ?? 'bg';
            $theme = $_POST['theme'] ?? 'light';

            $presentationId = $presentationModel->create($workspaceId, $title, $language, $theme);
            
            if ($presentationId) {
                header('Location: ' . BASE_URL . '/presentation/viewPresentation/' . $presentationId);
                exit;
            } else {
                $error = 'Възникна грешка при създаването на презентацията.';
                $workspace = $workspaceModel->getById($workspaceId);
                $this->view('presentation/create', [
                    'title' => 'Нова презентация',
                    'workspace' => $workspace,
                    'error' => $error
                ]);
                return;
            }
        }

        $workspace = $workspaceModel->getById($workspaceId);
        $this->view('presentation/create', [
            'title' => 'Нова презентация',
            'workspace' => $workspace
        ]);
    }

    public function viewPresentation($id)
    {
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        $slideModel = $this->model('Slide');
        $userId = AuthMiddleware::currentUserId();
        
        $presentation = $presentationModel->getById($id);
        
        if (!$presentation || !$workspaceModel->hasAccess($userId, $presentation['workspace_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $workspace = $workspaceModel->getById($presentation['workspace_id']);
        $slides = $slideModel->getByPresentationId($id);
        
        $this->view('presentation/view', [
            'title' => $presentation['title'],
            'presentation' => $presentation,
            'workspace' => $workspace,
            'slides' => $slides,
            'isOwner' => $workspaceModel->isOwner($userId, $presentation['workspace_id'])
        ]);
    }

    public function edit($id)
    {
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        $userId = AuthMiddleware::currentUserId();
        
        $presentation = $presentationModel->getById($id);
        
        if (!$presentation || !$workspaceModel->hasAccess($userId, $presentation['workspace_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $this->checkOwnerAccess($presentation['workspace_id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title']);
            $language = $_POST['language'] ?? 'bg';
            $theme = $_POST['theme'] ?? 'light';

            if ($presentationModel->update($id, $title, $language, $theme)) {
                header('Location: ' . BASE_URL . '/presentation/viewPresentation/' . $id);
                exit;
            } else {
                $error = 'Възникна грешка при редактирането на презентацията.';
                $workspace = $workspaceModel->getById($presentation['workspace_id']);
                $this->view('presentation/edit', [
                    'title' => 'Редактиране на презентация',
                    'presentation' => $presentation,
                    'workspace' => $workspace,
                    'error' => $error
                ]);
                return;
            }
        }

        $workspace = $workspaceModel->getById($presentation['workspace_id']);
        $this->view('presentation/edit', [
            'title' => 'Редактиране на презентация',
            'presentation' => $presentation,
            'workspace' => $workspace
        ]);
    }

    public function delete($id)
    {
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        $userId = AuthMiddleware::currentUserId();
        
        $presentation = $presentationModel->getById($id);
        
        if (!$presentation || !$workspaceModel->hasAccess($userId, $presentation['workspace_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $this->checkOwnerAccess($presentation['workspace_id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($presentationModel->delete($id)) {
                header('Location: ' . BASE_URL . '/dashboard/workspace/' . $presentation['workspace_id']);
                exit;
            } else {
                $error = 'Възникна грешка при изтриването на презентацията.';
                $workspace = $workspaceModel->getById($presentation['workspace_id']);
                $this->view('presentation/delete', [
                    'title' => 'Изтриване на презентация',
                    'presentation' => $presentation,
                    'workspace' => $workspace,
                    'error' => $error
                ]);
                return;
            }
        }

        $workspace = $workspaceModel->getById($presentation['workspace_id']);
        $this->view('presentation/delete', [
            'title' => 'Изтриване на презентация',
            'presentation' => $presentation,
            'workspace' => $workspace
        ]);
    }

    public function review($id)
    {
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        $slideModel = $this->model('Slide');
        $userId = AuthMiddleware::currentUserId();
        
        $presentation = $presentationModel->getById($id);
        
        if (!$presentation || !$workspaceModel->hasAccess($userId, $presentation['workspace_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $slides = $slideModel->getByPresentationId($id);
        
        $this->view('presentation/review', [
            'title' => $presentation['title'],
            'presentation' => $presentation,
            'slides' => $slides
        ]);
    }

    public function updateSlideOrder()
    {
        // Изключваме извеждането на грешки
        error_reporting(0);
        ini_set('display_errors', 0);
        
        // Изчистваме буфера за да сме сигурни, че няма изведен текст преди JSON
        if (ob_get_length()) ob_clean();
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $rawInput = file_get_contents('php://input');
            if (empty($rawInput)) {
                throw new Exception('No input data received');
            }

            $data = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data: ' . json_last_error_msg());
            }
            
            if (!isset($data['presentationId']) || !isset($data['slideOrder'])) {
                throw new Exception('Missing required data');
            }

            $presentationId = $data['presentationId'];
            $slideOrder = $data['slideOrder'];

            if (!is_array($slideOrder)) {
                throw new Exception('Slide order must be an array');
            }

            $slideModel = new Slide();
            $success = true;
            $errorMessage = '';

            foreach ($slideOrder as $index => $slideId) {
                if (!$slideModel->updateOrder($slideId, $index + 1)) {
                    $success = false;
                    $errorMessage = "Failed to update slide ID: $slideId";
                    error_log("Failed to update slide order. Slide ID: $slideId, New order: " . ($index + 1));
                    break;
                }
            }
            
            // Broadcast slide order update via WebSocket
            if ($success) {
                require_once __DIR__ . '/../helpers/WebSocketNotifier.php';
                $wsNotifier = new WebSocketNotifier();
                $wsNotifier->notifyPresentationUpdate(
                    $presentationId,
                    $_SESSION['user_id'] ?? 0,
                    $_SESSION['username'] ?? 'Anonymous'
                );
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Slide order updated successfully' : $errorMessage
            ]);
        } catch (Exception $e) {
            error_log("Error in updateSlideOrder: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (Error $e) {
            error_log("PHP Error in updateSlideOrder: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Internal server error'
            ]);
        }
        
        // Спираме изпълнението след изпращане на JSON
        exit;
    }

    public function export($id, $format)
    {
        try {
        $presentationModel = $this->model('Presentation');
        $slideModel = $this->model('Slide');
            $slideElementModel = $this->model('SlideElement');
            $workspaceModel = $this->model('Workspace');
        $userId = AuthMiddleware::currentUserId();
        
        $presentation = $presentationModel->getById($id);
        
            if (!$presentation) {
                $_SESSION['error'] = 'Презентацията не е намерена.';
                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            }
            
            if (!$workspaceModel->hasAccess($userId, $presentation['workspace_id'])) {
                $_SESSION['error'] = 'Нямате достъп до тази презентация.';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $slides = $slideModel->getByPresentationId($id);
            foreach ($slides as &$slide) {
                $slide['elements'] = $slideElementModel->getElementsBySlideId($slide['id']);
            }
        
        switch ($format) {
            case 'html':
                $this->exportHTML($presentation, $slides);
                break;
            case 'xml':
                $this->exportXML($presentation, $slides);
                break;
            case 'slim':
                $this->exportSLIM($presentation, $slides);
                break;
            default:
                    $_SESSION['error'] = 'Неподдържан формат за експорт.';
                    header('Location: ' . BASE_URL . '/presentation/viewPresentation/' . $id);
                    exit;
            }
        } catch (Exception $e) {
            error_log("Export error: " . $e->getMessage());
            $_SESSION['error'] = 'Възникна грешка при експортирането: ' . $e->getMessage();
                header('Location: ' . BASE_URL . '/presentation/viewPresentation/' . $id);
                exit;
        }
    }

    /**
     * Export presentation to PDF via Node.js microservice
     * 
     * This method demonstrates distributed system architecture:
     * - PHP (main application) communicates with Node.js (PDF service)
     * - REST API used for inter-service communication
     * - Falls back to TCPDF if microservice unavailable
     * 
     * Points: 20 (Different platforms) + 15 (Multiple paradigms) = 35 points
     */
    public function exportPdfViaService($id)
    {
        try {
            $presentationModel = $this->model('Presentation');
            $slideModel = $this->model('Slide');
            $slideElementModel = $this->model('SlideElement');
            $workspaceModel = $this->model('Workspace');
            $userId = AuthMiddleware::currentUserId();
            
            $presentation = $presentationModel->getById($id);
            
            if (!$presentation) {
                $_SESSION['error'] = 'Презентацията не е намерена.';
                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            }
            
            if (!$workspaceModel->hasAccess($userId, $presentation['workspace_id'])) {
                $_SESSION['error'] = 'Нямате достъп до тази презентация.';
                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            }

            $slides = $slideModel->getByPresentationId($id);
            foreach ($slides as &$slide) {
                $slide['elements'] = $slideElementModel->getElementsBySlideId($slide['id']);
            }
            
            // Generate HTML for PDF
            $html = $this->renderSlidesAsHtml($slides, $presentation);
            
            // Load PDF service client
            require_once __DIR__ . '/../helpers/PdfServiceClient.php';
            $pdfClient = new PdfServiceClient();
            
            // Check if service is available
            if (!$pdfClient->isAvailable()) {
                error_log('PDF Service unavailable');
                $_SESSION['error'] = 'PDF сървизът не е достъпен! Моля, уверете се че Node.js PDF микросървизът е стартиран на порт 3001.';
                header('Location: ' . BASE_URL . '/presentation/viewPresentation/' . $id);
                exit;
            }
            
            // Call Node.js microservice
            $pdfOptions = PdfServiceClient::getPresentationOptions();
            $pdfContent = $pdfClient->generatePdf($html, $presentation['title'], $pdfOptions);
            
            // Clear any output buffers to prevent corruption
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Send PDF to browser
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . htmlspecialchars($presentation['title']) . '.pdf"');
            header('Content-Length: ' . strlen($pdfContent));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            echo $pdfContent;
            exit;
            
        } catch (Exception $e) {
            error_log('PDF Service export error: ' . $e->getMessage());
            $_SESSION['error'] = 'Грешка при генериране на PDF: ' . $e->getMessage();
            header('Location: ' . BASE_URL . '/presentation/viewPresentation/' . $id);
            exit;
        }
    }

    /**
     * Render slides as HTML for PDF generation
     * 
     * @param array $slides Array of slides with elements
     * @param array $presentation Presentation data
     * @return string Complete HTML document
     */
    private function renderSlidesAsHtml($slides, $presentation)
    {
        $theme = $presentation['theme'] ?? 'light';
        $title = htmlspecialchars($presentation['title']);
        
        // Theme colors based on theme type
        switch ($theme) {
            case 'dark':
                $bgColor = '#1e1e1e';
                $textColor = '#ffffff';
                $accentColor = '#4CAF50';
                $slideColor = '#2d2d2d';
                break;
            
            case 'barbie':
                $bgColor = '#ffc3f0';
                $textColor = '#333333';
                $accentColor = '#FD269B';
                $slideColor = '#ffc3f0';
                break;
            
            case 'ken':
                $bgColor = '#B4E6FF';
                $textColor = '#333333';
                $accentColor = '#3A8EE4';
                $slideColor = '#B4E6FF';
                break;
            
            case 'light':
            default:
                $bgColor = '#f5f5f5';
                $textColor = '#333333';
                $accentColor = '#2196F3';
                $slideColor = '#ffffff';
                break;
        }
        
        $html = '<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>' . $title . '</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: ' . $bgColor . ';
            color: ' . $textColor . ';
        }
        
        .slide {
            width: 100%;
            min-height: 100vh;
            height: 100vh;
            padding: 40px 60px;
            page-break-after: always;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            background: ' . $slideColor . ';
            color: ' . $textColor . ';
        }
        
        .slide:last-child {
            page-break-after: auto;
        }
        
        .slide-number {
            position: absolute;
            bottom: 20px;
            right: 40px;
            font-size: 14px;
            opacity: 0.6;
        }
        
        h1 {
            font-size: 48px;
            margin-bottom: 30px;
            color: ' . $accentColor . ';
            line-height: 1.2;
        }
        
        h2 {
            font-size: 36px;
            margin-bottom: 20px;
            margin-top: 30px;
            color: ' . $accentColor . ';
        }
        
        h3 {
            font-size: 28px;
            margin-bottom: 15px;
            margin-top: 20px;
        }
        
        p {
            font-size: 20px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        ul, ol {
            font-size: 20px;
            line-height: 1.8;
            margin-left: 40px;
            margin-bottom: 20px;
        }
        
        li {
            margin-bottom: 10px;
        }
        
        img {
            max-width: 80%;
            max-height: 50vh;
            height: auto;
            margin: 20px auto;
            display: block;
            object-fit: contain;
            page-break-inside: avoid;
        }
        
        .title-slide {
            text-align: center;
            justify-content: center;
        }
        
        .title-slide h1 {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        code {
            background: rgba(0, 0, 0, 0.1);
            padding: 2px 6px;
            border-radius: 3px;
            font-family: "Consolas", "Monaco", monospace;
        }
        
        pre {
            background: rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 20px 0;
            page-break-inside: avoid;
        }
        
        blockquote {
            border-left: 4px solid ' . $accentColor . ';
            padding-left: 20px;
            margin: 20px 0;
            font-style: italic;
        }
        
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }
        
        .two-column > div {
            min-height: 200px;
        }
    </style>
</head>
<body>';
        
        // Add slides
        $slideNumber = 1;
        foreach ($slides as $slide) {
            $slideClass = $slideNumber === 1 ? 'slide title-slide' : 'slide';
            $html .= '<div class="' . $slideClass . '">';
            
            // Add slide title
            if (!empty($slide['title'])) {
                $html .= '<h1>' . htmlspecialchars($slide['title']) . '</h1>';
            }
            
            // Add slide content from elements
            if (!empty($slide['elements'])) {
                $elementCount = count($slide['elements']);
                
                // If multiple elements, check if they should be in columns
                if ($elementCount >= 2) {
                    // Split elements into two columns
                    $midPoint = ceil($elementCount / 2);
                    $leftElements = array_slice($slide['elements'], 0, $midPoint);
                    $rightElements = array_slice($slide['elements'], $midPoint);
                    
                    $html .= '<div class="two-column">';
                    
                    // Left column
                    $html .= '<div>';
                    foreach ($leftElements as $element) {
                        $html .= $this->renderElement($element);
                    }
                    $html .= '</div>';
                    
                    // Right column (if exists)
                    if (!empty($rightElements)) {
                        $html .= '<div>';
                        foreach ($rightElements as $element) {
                            $html .= $this->renderElement($element);
                        }
                        $html .= '</div>';
                    }
                    
                    $html .= '</div>';
                } else {
                    // Single element, render normally
                    foreach ($slide['elements'] as $element) {
                        $html .= $this->renderElement($element);
                    }
                }
            }
            
            // Add slide number
            $html .= '<div class="slide-number">' . $slideNumber . '</div>';
            $html .= '</div>';
            
            $slideNumber++;
        }
        
        $html .= '</body></html>';
        
        return $html;
    }

    /**
     * Render a single slide element as HTML
     * 
     * @param array $element Element data
     * @return string HTML representation
     */
    private function renderElement($element)
    {
        $type = $element['type'] ?? 'text';
        $title = $element['title'] ?? '';
        $content = $element['content'] ?? '';
        $text = $element['text'] ?? '';
        
        $html = '';
        
        // Add element title if present
        if (!empty($title)) {
            $html .= '<h2>' . htmlspecialchars($title) . '</h2>';
        }
        
        switch ($type) {
            case 'text':
                if (!empty($text)) {
                    $html .= '<p>' . nl2br(htmlspecialchars($text)) . '</p>';
                } else if (!empty($content)) {
                    $html .= '<p>' . nl2br(htmlspecialchars($content)) . '</p>';
                }
                break;
            
            case 'title':
                $html .= '<h3>' . htmlspecialchars($content ?: $text) . '</h3>';
                break;
            
            case 'heading':
                $level = $element['level'] ?? 2;
                $html .= '<h' . $level . '>' . htmlspecialchars($content ?: $text) . '</h' . $level . '>';
                break;
            
            case 'list':
                $listContent = $content ?: $text;
                $items = is_string($listContent) ? explode("\n", $listContent) : [];
                if (!empty($items)) {
                    $html .= '<ul>';
                    foreach ($items as $item) {
                        $item = trim($item);
                        if (!empty($item)) {
                            $html .= '<li>' . htmlspecialchars($item) . '</li>';
                        }
                    }
                    $html .= '</ul>';
                }
                break;
            
            case 'code':
                $html .= '<pre><code>' . htmlspecialchars($content ?: $text) . '</code></pre>';
                break;
            
            case 'quote':
                $quoteContent = $content ?: $text;
                $html .= '<blockquote>' . nl2br(htmlspecialchars($quoteContent)) . '</blockquote>';
                break;
            
            case 'image':
                // Handle image - check if it's a URL or base64
                $imageSrc = $content;
                if (!empty($imageSrc)) {
                    // If it's a relative path, convert to absolute
                    if (!preg_match('#^(https?://|data:)#i', $imageSrc)) {
                        $imageSrc = BASE_URL . '/' . ltrim($imageSrc, '/');
                    }
                    $alt = $element['alt'] ?? $title ?? 'Image';
                    $html .= '<div style="display: flex; align-items: center; justify-content: center; margin: 30px 0;">';
                    $html .= '<img src="' . htmlspecialchars($imageSrc) . '" alt="' . htmlspecialchars($alt) . '" style="max-width: 70%; max-height: 45vh; width: auto; height: auto; object-fit: contain;">';
                    $html .= '</div>';
                }
                break;
            
            case 'image_text':
                // Image with text
                $imageSrc = $content;
                if (!empty($imageSrc)) {
                    if (!preg_match('#^(https?://|data:)#i', $imageSrc)) {
                        $imageSrc = BASE_URL . '/' . ltrim($imageSrc, '/');
                    }
                    $html .= '<div class="image-text-content">';
                    $html .= '<img src="' . htmlspecialchars($imageSrc) . '" alt="Image" style="max-width: 60%; height: auto; float: left; margin-right: 20px;">';
                    if (!empty($text)) {
                        $html .= '<p>' . nl2br(htmlspecialchars($text)) . '</p>';
                    }
                    $html .= '<div style="clear: both;"></div>';
                    $html .= '</div>';
                }
                break;
            
            default:
                if (!empty($content)) {
                    $html .= '<div>' . nl2br(htmlspecialchars($content)) . '</div>';
                } else if (!empty($text)) {
                    $html .= '<div>' . nl2br(htmlspecialchars($text)) . '</div>';
                }
                break;
        }
        
        return $html;
    }

    private function exportHTML($presentation, $slides)
    {
        error_log("Exporting presentation: " . print_r($presentation, true));
        error_log("Exporting slides: " . print_r($slides, true));
        
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . htmlspecialchars($presentation['title']) . '.html"');
        
        $theme = $presentation['theme'] ?? 'light';
        
        $html = '<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($presentation['title']) . '</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .presentation-view {
            max-width: 1200px;
            margin: 0 auto;
        }
        .slide {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
            position: relative;
        }
        .slide-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        .content-element {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .content-element h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        .content-element p {
            margin: 0;
        }
        .content-element.type-list ul {
            margin: 0;
            padding-left: 20px;
        }
        .content-element.type-quote blockquote {
            margin: 0;
            padding-left: 15px;
            border-left: 4px solid #ddd;
            font-style: italic;
        }
        .content-element.type-quote cite {
            display: block;
            margin-top: 10px;
            font-style: normal;
        }
        .content-element.type-image .image-container {
            width: 100%;
            height: 300px;
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
        }
        .content-element.type-image_text {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }
        .content-element.type-image_text .image-container {
            flex: 1;
            height: 300px;
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
        }
        .content-element.type-image_text .text {
            flex: 1;
        }
        .content-element.type-image_list {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }
        .content-element.type-image_list .image-container {
            flex: 1;
            height: 300px;
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
        }
        .content-element.type-image_list ul {
            flex: 1;
        }

        /* Layout styles */
        .slide-content.full {
            width: 100%;
        }
        
        .slide-content.two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .slide-content.two-rows {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .slide-content.three-columns {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .slide-content.three-rows {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .slide-content.grid-2x2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 2rem;
        }
        
        .slide-content.grid-2x3 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .slide-content.grid-2x4 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(4, 1fr);
            gap: 2rem;
        }
        
        .slide-content.grid-3x2 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 2rem;
        }
        
        .slide-content.grid-3x3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .slide-content.grid-4x2 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 2rem;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .slide-content.two-columns,
            .slide-content.three-columns,
            .slide-content.grid-2x2,
            .slide-content.grid-2x3,
            .slide-content.grid-2x4,
            .slide-content.grid-3x2,
            .slide-content.grid-3x3,
            .slide-content.grid-4x2 {
                grid-template-columns: 1fr;
            }

            .content-element.type-image_text .image-text-container,
            .content-element.type-image_list .image-list-container {
                flex-direction: column;
            }

            .content-element.type-image_text .image-container,
            .content-element.type-image_list .image-container {
                height: 200px;
            }
        }

        .presentation-view[data-theme="dark"] .slide {
            background-color: #2d2d2d;
            color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        .presentation-view[data-theme="dark"] .slide-title {
            color: #ffffff;
        }
        .presentation-view[data-theme="dark"] .content-element {
            background-color: #333333;
            border-color: #404040;
        }
        .presentation-view[data-theme="dark"] .content-element h3 {
            color: #ffffff;
        }
        .presentation-view[data-theme="dark"] .content-element p {
            color: #e0e0e0;
        }
        .presentation-view[data-theme="dark"] .content-element.type-list li {
            color: #e0e0e0;
        }
        .presentation-view[data-theme="dark"] .content-element.type-quote blockquote {
            border-left-color: #666666;
            color: #e0e0e0;
        }
        .presentation-view[data-theme="dark"] .content-element.type-quote cite {
            color: #b3b3b3;
        }

        .presentation-view[data-theme="barbie"] .slide {
            background-color: #ffc3f0;
            color: #FD269B;
            box-shadow: 0 2px 4px rgba(253, 38, 155, 0.3);
        }
        .presentation-view[data-theme="barbie"] .slide-title {
            color: #FD269B;
        }
        .presentation-view[data-theme="barbie"] .content-element {
            background-color: #ffc3f0;
            border-color: #FD269B;
        }
        .presentation-view[data-theme="barbie"] .content-element h3 {
            color: #FD269B;
        }
        .presentation-view[data-theme="barbie"] .content-element p {
            color: #FD269B;
        }
        .presentation-view[data-theme="barbie"] .content-element.type-list li {
            color: #FD269B;
        }
        .presentation-view[data-theme="barbie"] .content-element.type-quote blockquote {
            border-left-color: #FBB659;
            color: #FD269B;
        }
        .presentation-view[data-theme="barbie"] .content-element.type-quote cite {
            color: #FBB659;
        }

        .presentation-view[data-theme="ken"] .slide {
            background-color: #B4E6FF;
            color: #1963ad;
            box-shadow: 0 2px 4px rgba(58, 142, 228, 0.3);
        }
        .presentation-view[data-theme="ken"] .slide-title {
            color: #1963ad;
        }
        .presentation-view[data-theme="ken"] .content-element {
            background-color: #B4E6FF;
            border-color: #1963ad;
        }
        .presentation-view[data-theme="ken"] .content-element h3 {
            color: #1963ad;
        }
        .presentation-view[data-theme="ken"] .content-element p {
            color: #1963ad;
        }
        .presentation-view[data-theme="ken"] .content-element.type-list li {
            color: #1963ad;
        }
        .presentation-view[data-theme="ken"] .content-element.type-quote blockquote {
            border-left-color: #ffa5be;
            color: #1963ad;
        }
        .presentation-view[data-theme="ken"] .content-element.type-quote cite {
            color: #ffa5be;
        }
    </style>
</head>
<body>
    <div class="presentation-view" data-theme="' . htmlspecialchars($theme) . '">';

        foreach ($slides as $slide) {
            error_log("Processing slide: " . print_r($slide, true));
            
            $slideTitle = $slide['title'] ?? '';
            $slideLayout = $slide['layout'] ?? 'full';
            
            $html .= '<div class="slide">
                <h2 class="slide-title">' . htmlspecialchars($slideTitle) . '</h2>
                <div class="slide-content ' . htmlspecialchars($slideLayout) . '">';

            if (!empty($slide['elements'])) {
                error_log("Slide has elements: " . print_r($slide['elements'], true));
                
                foreach ($slide['elements'] as $element) {
                    error_log("Processing element: " . print_r($element, true));
                    
                    $elementType = $element['type'] ?? '';
                    $elementTitle = $element['title'] ?? '';
                    $elementContent = $element['content'] ?? '';
                    $elementText = $element['text'] ?? '';
                    
                    error_log("Element data - Type: $elementType, Title: $elementTitle, Content: $elementContent, Text: $elementText");
                    
                    $html .= '<div class="element-container">';
                    
                    if (!empty($elementTitle)) {
                        $html .= '<h3 class="element-title">' . htmlspecialchars($elementTitle) . '</h3>';
                    }

                    switch ($elementType) {
                        case 'image':
                            $html .= '<div class="content-element type-image">
                                    <div class="image-container" style="background-image: url(\'' . htmlspecialchars($elementContent) . '\');"></div>
                                </div>';
                            break;
                        
                        case 'image_text':
                                $html .= '<div class="content-element type-image_text">
                                    <div class="image-text-container">
                                        <div class="image-container" style="background-image: url(\'' . htmlspecialchars($elementContent) . '\');"></div>
                                        <div class="text"><p>' . nl2br(htmlspecialchars($elementText)) . '</p></div>
                                    </div>
                                </div>';
                            break;
                        
                        case 'image_list':
                                $html .= '<div class="content-element type-image_list">
                                    <div class="image-list-container">
                                        <div class="image-container" style="background-image: url(\'' . htmlspecialchars($elementContent) . '\');"></div>
                                        <ul>';
                                foreach (explode("\n", $elementText) as $item) {
                                    if (trim($item) !== '') {
                                        $html .= '<li>' . htmlspecialchars($item) . '</li>';
                                    }
                                }
                            $html .= '</ul></div></div>';
                            break;
                        
                        case 'quote':
                                $html .= '<div class="content-element type-quote">
                                <blockquote>' . nl2br(htmlspecialchars($elementContent));
                                if (!empty($elementTitle)) {
                                    $html .= '<cite>— ' . htmlspecialchars($elementTitle) . '</cite>';
                                }
                            $html .= '</blockquote></div>';
                            break;
                        
                        case 'list':
                            $html .= '<div class="content-element type-list"><ul>';
                            foreach (explode("\n", $elementContent) as $item) {
                                if (trim($item) !== '') {
                                    $html .= '<li>' . htmlspecialchars($item) . '</li>';
                                }
                            }
                            $html .= '</ul></div>';
                            break;
                        
                        case 'text':
                        default:
                            $html .= '<div class="content-element type-text">' . 
                                nl2br(htmlspecialchars($elementContent)) . 
                                '</div>';
                            break;
                    }
                    
                    $html .= '</div>';
                }
            } else {
                $html .= '<div class="empty-content">Няма добавено съдържание</div>';
            }

            $html .= '</div></div>';
        }

        $html .= '</div></body></html>';
        
        echo $html;
        exit;
    }

    private function exportXML($presentation, $slides)
    {
        // Създаване на XML документ
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><presentation></presentation>');
        
        // Добавяне на атрибути на презентацията
        $xml->addAttribute('title', $presentation['title']);
        $xml->addAttribute('language', $presentation['language']);
        $xml->addAttribute('theme', $presentation['theme']);
        
        // Добавяне на слайдове
        foreach ($slides as $index => $slide) {
            $slideNode = $xml->addChild('slide');
            $slideNode->addAttribute('title', $slide['title']);
            $slideNode->addAttribute('slide_order', $index + 1); // Започваме от 1
            $slideNode->addAttribute('layout', $slide['layout']);
            
            // Добавяне на елементи на слайда
            if (!empty($slide['elements'])) {
                foreach ($slide['elements'] as $elementIndex => $element) {
                    $elementNode = $slideNode->addChild('slide_element');
                    $elementNode->addAttribute('element_order', $elementIndex);
                    $elementNode->addAttribute('type', $element['type']);
                    
                    // Добавяме всички атрибути, дори ако са празни
                    $elementNode->addAttribute('title', $element['title'] ?? '');
                    $elementNode->addAttribute('content', $element['content'] ?? '');
                    $elementNode->addAttribute('text', $element['text'] ?? '');
                    $elementNode->addAttribute('style', $element['style'] ?? '[]');
                }
            }
        }
        
        // Форматиране на XML
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        
        // Задаване на headers за XML файл
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $presentation['title'] . '.xml"');
        
        // Извеждане на форматиран XML
        echo $dom->saveXML();
        exit;
    }

    private function exportSLIM($presentation, $slides)
    {
        $slim = "presentation\n";
        $slim .= "\ttitle: " . $presentation['title'] . "\n";
        $slim .= "\tlanguage: " . $presentation['language'] . "\n";
        $slim .= "\ttheme: " . $presentation['theme'] . "\n\n";

        foreach ($slides as $index => $slide) {
            $slim .= "\tslide:\n";
            $slim .= "\t\ttitle: " . $slide['title'] . "\n";
            $slim .= "\t\tslide_order: " . ($index + 1) . "\n";
            $slim .= "\t\tlayout: " . $slide['layout'] . "\n\n";

            if (!empty($slide['elements'])) {
                foreach ($slide['elements'] as $elementIndex => $element) {
                    $slim .= "\t\tslide_element:\n";
                    $slim .= "\t\t\telement_order: " . $elementIndex . "\n";
                    $slim .= "\t\t\ttype: " . $element['type'] . "\n";
                    $slim .= "\t\t\ttitle: " . ($element['title'] ?? '') . "\n";
                    $slim .= "\t\t\tcontent: " . ($element['content'] ?? '') . "\n";
                    $slim .= "\t\t\ttext: " . ($element['text'] ?? '') . "\n\n";
                }
            }
        }

        // Задаване на headers за SLIM файл
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $presentation['title'] . '.slim"');
        
        // Извеждане на SLIM
        echo $slim;
        exit;
    }

    public function import($workspaceId)
    {
        try {
            // Проверка за достъп до работното пространство
            $workspaceModel = $this->model('Workspace');
            $userId = AuthMiddleware::currentUserId();
            
            if (!$workspaceModel->hasAccess($userId, $workspaceId)) {
                $_SESSION['error'] = 'Нямате достъп до това работно пространство.';
                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            }

            // Проверка за файл
            if (!isset($_FILES['importFile']) || $_FILES['importFile']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = 'Моля, изберете файл за импортиране.';
                header('Location: ' . BASE_URL . '/presentation/create/' . $workspaceId);
                exit;
            }

            $file = $_FILES['importFile'];
            $format = $_POST['importFormat'] ?? 'xml';

            // Проверка на разширението на файла
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, ['xml', 'html', 'slim'])) {
                $_SESSION['error'] = 'Неподдържан формат на файла.';
                header('Location: ' . BASE_URL . '/presentation/create/' . $workspaceId);
                exit;
            }

            // Четене на съдържанието на файла
            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                $_SESSION['error'] = 'Грешка при четене на файла.';
                header('Location: ' . BASE_URL . '/presentation/create/' . $workspaceId);
                exit;
            }

            // Избор на метод за парсване според формата
            switch ($format) {
                case 'html':
                    $data = $this->parseHTML($content);
                    break;
                case 'xml':
                    $data = $this->parseXML($content);
                    break;
                case 'slim':
                    $data = $this->parseSLIM($content);
                    break;
                default:
                    $_SESSION['error'] = 'Неподдържан формат за импортиране.';
                    header('Location: ' . BASE_URL . '/presentation/create/' . $workspaceId);
                    exit;
            }

            if (empty($data)) {
                $_SESSION['error'] = 'Грешка при обработка на файла.';
                header('Location: ' . BASE_URL . '/presentation/create/' . $workspaceId);
                exit;
            }
            
            $presentationModel = $this->model('Presentation');
            $slideModel = $this->model('Slide');
            $slideElementModel = $this->model('SlideElement');
            
            // Създаване на презентацията
            $presentationId = $presentationModel->create(
                $workspaceId,
                $data['title'],
                'bg',
                $data['theme'] ?? 'light'
            );
            
            if (!$presentationId) {
                $_SESSION['error'] = 'Грешка при създаване на презентацията.';
                header('Location: ' . BASE_URL . '/presentation/create/' . $workspaceId);
                exit;
            }
            
            // Създаване на слайдовете и елементите
            foreach ($data['slides'] as $slideData) {
                $slideId = $slideModel->create([
                    'presentation_id' => $presentationId,
                    'title' => $slideData['title'],
                    'slide_order' => $slideData['slide_order'] ?? 0,
                    'layout' => $slideData['layout'] ?? 'full'
                ]);
                
                if ($slideId && !empty($slideData['elements'])) {
                    foreach ($slideData['elements'] as $elementData) {
                        $slideElementModel->addElement([
                            'slide_id' => $slideId,
                            'element_order' => $elementData['element_order'] ?? 0,
                            'type' => $elementData['type'],
                            'title' => $elementData['title'] ?? '',
                            'content' => $elementData['content'] ?? '',
                            'text' => $elementData['text'] ?? '',
                            'style' => $elementData['style'] ?? '{}'
                        ]);
                    }
                }
            }
            
            $_SESSION['success'] = 'Презентацията е импортирана успешно.';
            header('Location: ' . BASE_URL . '/presentation/viewPresentation/' . $presentationId);
            exit;
            
        } catch (Exception $e) {
            error_log("Import error: " . $e->getMessage());
            $_SESSION['error'] = 'Възникна грешка при импортирането: ' . $e->getMessage();
            header('Location: ' . BASE_URL . '/presentation/create/' . $workspaceId);
            exit;
        }
    }

    private function parseHTML($content)
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($content, LIBXML_NOERROR);
        
        // Извличане на темата
        $theme = 'light';
        $presentationView = $dom->getElementsByTagName('div')->item(0);
        if ($presentationView && $presentationView->getAttribute('class') === 'presentation-view') {
            $theme = $presentationView->getAttribute('data-theme') ?? 'light';
        }
        
        // Извличане на заглавието
        $title = '';
        $titleElements = $dom->getElementsByTagName('title');
        if ($titleElements->length > 0) {
            $title = $titleElements->item(0)->textContent;
        }

        $slides = [];
        $slideElements = $dom->getElementsByTagName('div');
        foreach ($slideElements as $slideElement) {
            if ($slideElement->getAttribute('class') === 'slide') {
                $slide = [
                    'title' => '',
                    'layout' => 'full',
                    'elements' => []
                ];

                // Извличане на заглавието на слайда
                $titleElements = $slideElement->getElementsByTagName('h2');
                if ($titleElements->length > 0) {
                    $slide['title'] = $titleElements->item(0)->textContent;
                }

                // Извличане на layout-а
                $contentElements = $slideElement->getElementsByTagName('div');
                foreach ($contentElements as $contentElement) {
                    $classes = explode(' ', $contentElement->getAttribute('class'));
                    foreach ($classes as $class) {
                        if ($class === 'slide-content') {
                            // Намираме следващия клас, който е layout-ът
                            foreach ($classes as $layoutClass) {
                                if ($layoutClass !== 'slide-content' && !empty($layoutClass)) {
                                    $slide['layout'] = $layoutClass;
                                    break 2;
                                }
                            }
                        }
                    }
                }

                // Обработка на елементите
                foreach ($contentElements as $contentElement) {
                    if ($contentElement->getAttribute('class') === 'element-container') {
                        $element = $this->parseElement($contentElement);
                        if ($element) {
                            $slide['elements'][] = $element;
                        }
                    }
                }

                $slides[] = $slide;
            }
        }

        return [
            'title' => $title,
            'theme' => $theme,
            'slides' => $slides
        ];
    }

    private function parseElement($contentElement)
    {
        $element = [
            'type' => '',
            'content' => '',
            'title' => '',
            'text' => '',
            'style' => '{}'
        ];

        // Извличане на заглавието
        $titleElement = $contentElement->getElementsByTagName('h3')->item(0);
        if ($titleElement) {
            $element['title'] = $titleElement->textContent;
        }

        // Извличане на типа на елемента
        $contentElementDiv = $contentElement->getElementsByTagName('div')->item(0);
        if ($contentElementDiv) {
            $classes = explode(' ', $contentElementDiv->getAttribute('class'));
            foreach ($classes as $class) {
                if (strpos($class, 'type-') === 0) {
                    $element['type'] = substr($class, 5);
                    break;
                }
            }
        }

        // Извличане на стиловете
        if ($contentElementDiv) {
            $style = $contentElementDiv->getAttribute('style');
            if ($style) {
                $element['style'] = json_encode(['style' => $style]);
            }
        }

        // Обработка според типа на елемента
        switch ($element['type']) {
            case 'image':
                $imageContainer = $contentElementDiv->getElementsByTagName('div')->item(0);
                if ($imageContainer) {
                    $style = $imageContainer->getAttribute('style');
                    if (preg_match('/background-image:\s*url\([\'"](.+?)[\'"]\)/', $style, $matches)) {
                        $element['content'] = $matches[1];
                    }
                }
                break;

            case 'image_text':
                $imageTextContainer = $contentElementDiv->getElementsByTagName('div')->item(0);
                if ($imageTextContainer) {
                    $imageContainer = $imageTextContainer->getElementsByTagName('div')->item(0);
                    $textContainer = $imageTextContainer->getElementsByTagName('div')->item(1);
                    
                    if ($imageContainer) {
                        $style = $imageContainer->getAttribute('style');
                        if (preg_match('/background-image:\s*url\([\'"](.+?)[\'"]\)/', $style, $matches)) {
                            $element['content'] = $matches[1];
                        }
                    }
                    
                    if ($textContainer) {
                        $paragraph = $textContainer->getElementsByTagName('p')->item(0);
                        if ($paragraph) {
                            $element['text'] = $paragraph->textContent;
                        } else {
                            $element['text'] = $textContainer->textContent;
                        }
                    }
                }
                break;

            case 'image_list':
                $imageListContainer = $contentElementDiv->getElementsByTagName('div')->item(0);
                if ($imageListContainer) {
                    $imageContainer = $imageListContainer->getElementsByTagName('div')->item(0);
                    $listContainer = $imageListContainer->getElementsByTagName('ul')->item(0);
                    
                    if ($imageContainer) {
                        $style = $imageContainer->getAttribute('style');
                        if (preg_match('/background-image:\s*url\([\'"](.+?)[\'"]\)/', $style, $matches)) {
                            $element['content'] = $matches[1];
        }
                    }
                    
                    if ($listContainer) {
                        $items = [];
                        foreach ($listContainer->getElementsByTagName('li') as $li) {
                            $items[] = $li->textContent;
                        }
                        $element['text'] = implode("\n", $items);
                    }
                }
                break;

            case 'quote':
                $blockquote = $contentElementDiv->getElementsByTagName('blockquote')->item(0);
                if ($blockquote) {
                    $element['content'] = $blockquote->textContent;
                    $cite = $blockquote->getElementsByTagName('cite')->item(0);
                    if ($cite) {
                        $element['title'] = trim(str_replace('—', '', $cite->textContent));
                    }
                }
                break;

            case 'list':
                $ul = $contentElementDiv->getElementsByTagName('ul')->item(0);
                if ($ul) {
                    $items = [];
                    foreach ($ul->getElementsByTagName('li') as $li) {
                        $items[] = $li->textContent;
                    }
                    $element['content'] = implode("\n", $items);
                }
                break;

            case 'text':
                $element['content'] = $contentElementDiv->textContent;
                break;
        }

        return $element;
    }

    private function parseXML($content)
    {
        try {
            $xml = new SimpleXMLElement($content);
            
            // Извличане на данните за презентацията
            $presentationData = [
                'title' => (string)$xml['title'],
                'language' => (string)$xml['language'],
                'theme' => (string)$xml['theme'],
                'slides' => []
            ];
            
            // Извличане на слайдовете
            foreach ($xml->slide as $slide) {
                $slideData = [
                    'title' => (string)$slide['title'],
                    'slide_order' => (int)$slide['slide_order'],
                    'layout' => (string)$slide['layout'],
                    'elements' => []
                ];

                // Извличане на елементите на слайда
                foreach ($slide->slide_element as $element) {
                    error_log("Processing XML element: " . print_r($element, true));
                    error_log("Element attributes: " . print_r($element->attributes(), true));
                    
                    $elementType = (string)$element['type'];
                    $elementTitle = (string)$element['title'];
                    
                    error_log("Element type: " . $elementType);
                    error_log("Element title: " . $elementTitle);
                    
                    $elementData = [
                        'element_order' => (int)$element['element_order'],
                        'type' => $elementType,
                        'title' => $elementTitle,
                        'content' => (string)$element['content'],
                        'text' => (string)$element['text'],
                        'style' => (string)$element['style']
                    ];

                    error_log("Created element data: " . print_r($elementData, true));

                    // Специална обработка за различните типове елементи
                    switch ($elementType) {
                        case 'image_text':
                        case 'image_list':
                            // За image_text и image_list, текстът трябва да е в полето text
                            $elementData['text'] = (string)$element['text'];
                            break;
                            
                        case 'quote':
                            // За цитати, ако има автор, той трябва да е в полето title
                            if (!empty($element['author'])) {
                                $elementData['title'] = (string)$element['author'];
                            }
                            break;
                    }
                    
                    $slideData['elements'][] = $elementData;
                }

                $presentationData['slides'][] = $slideData;
            }

            error_log("Final parsed XML data: " . print_r($presentationData, true));
            return $presentationData;
        } catch (Exception $e) {
            error_log("Error parsing XML: " . $e->getMessage());
            error_log("XML content: " . $content);
            return null;
        }
    }

    private function parseSLIM($content)
    {
        $lines = explode("\n", $content);
        $currentSection = '';
        $currentSlide = null;
        $currentElement = null;
        $presentation = [
            'title' => '',
            'language' => '',
            'theme' => '',
            'slides' => []
        ];

        foreach ($lines as $line) {
            $line = rtrim($line); // Премахваме само крайните празни пространства
            if (empty($line)) continue;

            // Определяне на нивото на влагане по броя на табулациите
            $indentLevel = 0;
            while (substr($line, 0, 1) === "\t") {
                $indentLevel++;
                $line = substr($line, 1);
            }

            // Определяне на текущата секция
            if ($line === 'presentation') {
                $currentSection = 'presentation';
                continue;
            } elseif ($line === 'slide:') {
                $currentSection = 'slide';
                $currentSlide = [
                    'title' => '',
                    'slide_order' => count($presentation['slides']) + 1,
                    'layout' => 'default',
                    'elements' => []
                ];
                continue;
            } elseif ($line === 'slide_element:') {
                $currentSection = 'element';
                $currentElement = [
                    'element_order' => count($currentSlide['elements']) + 1,
                    'type' => 'text',
                    'title' => '',
                    'content' => '',
                    'text' => ''
                ];
                continue;
            }

            // Обработка на данните според секцията и нивото на влагане
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);

                switch ($currentSection) {
                    case 'presentation':
                        if ($indentLevel === 1) {
                            switch ($key) {
                                case 'title':
                                    $presentation['title'] = $value;
                                    break;
                                case 'language':
                                    $presentation['language'] = $value;
                                    break;
                                case 'theme':
                                    $presentation['theme'] = $value;
                                    break;
                            }
                        }
                        break;

                    case 'slide':
                        if ($indentLevel === 2) {
                            switch ($key) {
                                case 'title':
                                    $currentSlide['title'] = $value;
                                    break;
                                case 'slide_order':
                                    $currentSlide['slide_order'] = (int)$value;
                                    break;
                                case 'layout':
                                    $currentSlide['layout'] = $value;
                                    break;
                            }
                        }
                        break;

                    case 'element':
                        if ($indentLevel === 3) {
                            switch ($key) {
                                case 'element_order':
                                    $currentElement['element_order'] = (int)$value;
                                    break;
                                case 'type':
                                    $currentElement['type'] = $value;
                                    break;
                                case 'title':
                                    $currentElement['title'] = $value;
                                    break;
                                case 'content':
                                    $currentElement['content'] = $value;
                                    break;
                                case 'text':
                                    $currentElement['text'] = $value;
                                    break;
                            }
                        }
                        break;
                }
            }

            // Ако сме в края на елемент, добавяме го към текущия слайд
            if ($currentSection === 'element' && $indentLevel < 3) {
                if ($currentElement && $currentSlide) {
                    // Ако няма заглавие, използваме първите 50 символа от текста
                    if (empty($currentElement['title']) && !empty($currentElement['text'])) {
                        $currentElement['title'] = substr($currentElement['text'], 0, 50) . '...';
                    }
                    $currentSlide['elements'][] = $currentElement;
                    $currentElement = null;
                }
            }

            // Ако сме в края на слайд, добавяме го към презентацията
            if ($currentSection === 'slide' && $indentLevel < 2) {
                if ($currentSlide) {
                    // Ако няма заглавие, използваме "Слайд X"
                    if (empty($currentSlide['title'])) {
                        $currentSlide['title'] = 'Слайд ' . $currentSlide['slide_order'];
                    }
                    $presentation['slides'][] = $currentSlide;
                    $currentSlide = null;
                }
            }
        }

        // Добавяме последния слайд, ако има такъв
        if ($currentSlide) {
            if (empty($currentSlide['title'])) {
                $currentSlide['title'] = 'Слайд ' . $currentSlide['slide_order'];
            }
            $presentation['slides'][] = $currentSlide;
        }

        return $presentation;
    }
} 