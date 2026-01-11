<?php

require_once __DIR__ . '/../models/Slide.php';
require_once __DIR__ . '/../models/Presentation.php';
require_once __DIR__ . '/../core/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Logger.php';

class SlideController extends Controller
{
    private $slideModel;
    private $presentationModel;

    public function __construct()
    {
        AuthMiddleware::requireLogin();
        
        $this->slideModel = new Slide();
        $this->presentationModel = new Presentation();
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

    public function create($presentationId = null)
    {
        Logger::log("SlideController::create called with presentationId: " . $presentationId);
        
        if ($presentationId === null) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $presentation = $this->presentationModel->getById($presentationId);
        if (!$presentation) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $workspaceModel = $this->model('Workspace');
        $userId = AuthMiddleware::currentUserId();
        
        if (!$workspaceModel->hasAccess($userId, $presentation['workspace_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        if (!$workspaceModel->isOwner($userId, $presentation['workspace_id'])) {
            header('Location: ' . BASE_URL . '/presentation/viewPresentation/' . $presentationId);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Logger::log("Received POST request for slide creation");
            Logger::log("POST data: " . print_r($_POST, true));
            
            if (isset($_POST['cancel'])) {
                $presentation_id = $_POST['presentation_id'] ?? null;
                Logger::log("Отказ от създаване на слайд. Пренасочване към: " . ($presentation_id ? (BASE_URL . '/presentation/viewPresentation/' . $presentation_id) : (BASE_URL . '/dashboard')));
                if ($presentation_id) {
                    header('Location: ' . BASE_URL . '/presentation/viewPresentation/' . $presentation_id);
                    return;
                } else {
                    header('Location: ' . BASE_URL . '/dashboard');
                    return;
                }
            }

            $presentationId = $_POST['presentation_id'] ?? null;
            $title = $_POST['title'] ?? '';
            $layout = $_POST['layout'] ?? 'full';
            $noRedirect = isset($_POST['no_redirect']) && $_POST['no_redirect'] === '1';
            
            Logger::log("Processing slide creation with data:");
            Logger::log("presentation_id: " . $presentationId);
            Logger::log("title: " . $title);
            Logger::log("layout: " . $layout);
            Logger::log("no_redirect: " . ($noRedirect ? 'true' : 'false'));
            
            if (empty($title)) {
                Logger::log("Error: Title is required");
                $_SESSION['error'] = 'Заглавието е задължително';
                $this->view('slides/create', [
                    'presentation' => $this->presentationModel->getById($presentationId),
                    'error' => 'Заглавието е задължително'
                ]);
                return;
            }
            
            try {
                $elements = [];
                if (isset($_POST['elements']) && is_array($_POST['elements'])) {
                foreach ($_POST['elements'] as $index => $element) {
                        Logger::log("Processing element $index: " . print_r($element, true));
                    $elements[] = [
                        'type' => $element['type'],
                        'title' => $element['title'] ?? null,
                        'content' => $element['content'] ?? null,
                        'text' => $element['text'] ?? null,
                        'style' => json_decode($element['style'] ?? '{}', true)
                    ];
                    }
                }
                
                Logger::log("Prepared elements data: " . print_r($elements, true));
                
                $slideData = [
                    'presentation_id' => $presentationId,
                    'title' => $title,
                    'layout' => $layout,
                    'elements' => $elements
                ];
                
                Logger::log("Final slide data: " . print_r($slideData, true));
                
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
                
                Logger::log("Is AJAX request: " . ($isAjax ? 'true' : 'false'));
                
                try {
                $slideId = $this->slideModel->create($slideData);
                    Logger::log("Successfully created slide with ID: " . $slideId);
                
                // Broadcast slide creation via WebSocket
                require_once __DIR__ . '/../helpers/WebSocketNotifier.php';
                $wsNotifier = new WebSocketNotifier();
                $wsNotifier->notifySlideCreate(
                    $presentationId,
                    $slideId,
                    $_SESSION['user_id'] ?? 0,
                    $_SESSION['username'] ?? 'Anonymous'
                );
                
                $_SESSION['success'] = 'Слайдът е създаден успешно';
                    
                    if ($isAjax) {
                        Logger::log("Sending JSON response for AJAX request");
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'message' => 'Слайдът е създаден успешно',
                            'slide_id' => $slideId
                        ]);
                        return;
                    } else if ($noRedirect) {
                        Logger::log("Showing success message without redirect");
                        $this->view('slides/create', [
                            'presentation' => $this->presentationModel->getById($presentationId),
                            'success' => 'Слайдът е създаден успешно'
                        ]);
                    } else {
                        Logger::log("Redirecting to presentation: " . BASE_URL . '/presentation/viewPresentation/' . $presentationId);
                        header('Location: ' . BASE_URL . '/presentation/viewPresentation/' . $presentationId);
                    }
                    return;
                } catch (Exception $e) {
                    Logger::log("Error creating slide: " . $e->getMessage());
                    Logger::log("Stack trace: " . $e->getTraceAsString());
                    
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => 'Възникна грешка при създаването на слайда: ' . $e->getMessage()
                        ]);
                        return;
                    }
                    
                    throw $e;
                }
                
            } catch (Exception $e) {
                Logger::log("Error in SlideController::create: " . $e->getMessage());
                Logger::log("Stack trace: " . $e->getTraceAsString());
                $_SESSION['error'] = 'Възникна грешка при създаването на слайда: ' . $e->getMessage();
                
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Възникна грешка при създаването на слайда: ' . $e->getMessage()
                    ]);
                    return;
                }
                
                $this->view('slides/create', [
                    'presentation' => $this->presentationModel->getById($presentationId),
                    'error' => 'Възникна грешка при създаването на слайда: ' . $e->getMessage()
                ]);
                return;
            }
        } else {
            if (!$presentationId) {
                Logger::log("Error: No presentation ID provided");
                $_SESSION['error'] = 'Не е посочена презентация';
                $this->view('slides/create', [
                    'error' => 'Не е посочена презентация'
                ]);
                return;
            }

            $presentation = $this->presentationModel->getById($presentationId);
            if (!$presentation) {
                Logger::log("Error: Presentation not found with ID: " . $presentationId);
                $_SESSION['error'] = 'Презентацията не е намерена';
                $this->view('slides/create', [
                    'error' => 'Презентацията не е намерена'
                ]);
                return;
            }

            $this->view('slides/create', [
                'presentation' => $presentation
            ]);
        }
    }

    public function edit($slideId)
    {
        $slideModel = $this->model('Slide');
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        $userId = AuthMiddleware::currentUserId();
        
        $slide = $slideModel->getById($slideId);
        if (!$slide) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $presentation = $presentationModel->getById($slide['presentation_id']);
        if (!$presentation || !$workspaceModel->hasAccess($userId, $presentation['workspace_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $this->checkOwnerAccess($presentation['workspace_id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $presentation_id = $_POST['presentation_id'] ?? null;
            $title = $_POST['title'] ?? '';
            $layout = $_POST['layout'] ?? 'full';
            
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            $elements = [];
            if (isset($_POST['elements']) && is_array($_POST['elements'])) {
                foreach ($_POST['elements'] as $element) {
                $elements[] = [
                        'type' => $element['type'] ?? 'text',
                        'title' => $element['title'] ?? '',
                        'content' => $element['content'] ?? '',
                        'text' => $element['text'] ?? '',
                        'style' => isset($element['style']) ? json_decode($element['style'], true) : null
                ];
                }
            }
            
            if (empty($title)) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Заглавието е задължително'
                    ]);
                    return;
                }
                $_SESSION['error'] = 'Заглавието е задължително';
                header("Location: " . BASE_URL . "/slides/edit/" . $id);
                exit;
            }
            
            try {
                $slide = new Slide();
                $slide->update($id, [
                    'title' => $title,
                    'layout' => $layout,
                    'elements' => $elements
                ]);
                
                // Broadcast slide update via WebSocket
                require_once __DIR__ . '/../helpers/WebSocketNotifier.php';
                $wsNotifier = new WebSocketNotifier();
                $wsNotifier->notifySlideUpdate(
                    $presentation_id,
                    $id,
                    $_SESSION['user_id'] ?? 0,
                    $_SESSION['username'] ?? 'Anonymous'
                );
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Слайдът е редактиран успешно'
                    ]);
                    return;
                }
                
                $_SESSION['success'] = 'Слайдът е редактиран успешно';
                header("Location: " . BASE_URL . "/presentation/viewPresentation/" . $presentation_id);
                exit;
            } catch (Exception $e) {
                error_log("Грешка при редактиране на слайд: " . $e->getMessage());
                error_log("Данни: " . print_r($_POST, true));
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Грешка при редактиране на слайда: ' . $e->getMessage()
                    ]);
                    return;
                }
                
                $_SESSION['error'] = 'Грешка при редактиране на слайда: ' . $e->getMessage();
                header("Location: " . BASE_URL . "/slides/edit/" . $id);
                exit;
            }
        } else {
            $this->view('slides/edit', [
                'slide' => $slide
            ]);
        }
    }

    public function delete($id)
    {
        $slideModel = $this->model('Slide');
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        $userId = AuthMiddleware::currentUserId();
        
        $slide = $slideModel->getById($id);
        if (!$slide) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $presentation = $presentationModel->getById($slide['presentation_id']);
        if (!$presentation || !$workspaceModel->hasAccess($userId, $presentation['workspace_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $this->checkOwnerAccess($presentation['workspace_id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($slideModel->delete($id)) {
                // Broadcast slide deletion via WebSocket
                require_once __DIR__ . '/../helpers/WebSocketNotifier.php';
                $wsNotifier = new WebSocketNotifier();
                $wsNotifier->notifySlideDelete(
                    $slide['presentation_id'],
                    $id,
                    $_SESSION['user_id'] ?? 0,
                    $_SESSION['username'] ?? 'Anonymous'
                );
                
                header('Location: ' . BASE_URL . '/presentation/viewPresentation/' . $slide['presentation_id']);
            exit;
        } else {
            $error = 'Възникна грешка при изтриването на слайда.';
            $this->view('slides/delete', [
                'title' => 'Изтриване на слайд',
                'slide' => $slide,
                'error' => $error
            ]);
                return;
            }
        }
        
        $this->view('slides/delete', [
            'title' => 'Изтриване на слайд',
            'slide' => $slide
        ]);
    }

    public function updateOrder()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Трябва да сте влезли в системата']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Невалиден метод на заявката']);
            return;
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!isset($data['slides']) || !is_array($data['slides'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Невалидни данни']);
            return;
        }

        $slideModel = $this->model('Slide');
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        $userId = AuthMiddleware::currentUserId();

        $firstSlide = $slideModel->getById($data['slides'][0]['id']);
        if (!$firstSlide) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Слайдът не е намерен']);
            return;
        }

        $presentation = $presentationModel->getById($firstSlide['presentation_id']);
        if (!$presentation || !$workspaceModel->hasAccess($userId, $presentation['workspace_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Нямате достъп до тази презентация']);
            return;
        }

        if (!$workspaceModel->isOwner($userId, $presentation['workspace_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Нямате права за промяна на реда на слайдовете']);
            return;
        }

        $success = true;
        foreach ($data['slides'] as $slide) {
            if (!isset($slide['id']) || !isset($slide['order'])) {
                continue;
            }

            if (!$slideModel->updateOrder($slide['id'], $slide['order'])) {
                $success = false;
                break;
            }
        }
        
        // Broadcast slide order update via WebSocket
        if ($success && isset($firstSlide['presentation_id'])) {
            require_once __DIR__ . '/../helpers/WebSocketNotifier.php';
            $wsNotifier = new WebSocketNotifier();
            $wsNotifier->notifyPresentationUpdate(
                $firstSlide['presentation_id'],
                $_SESSION['user_id'] ?? 0,
                $_SESSION['username'] ?? 'Anonymous'
            );
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Редът на слайдовете е обновен успешно' : 'Възникна грешка при обновяването на реда'
        ]);
    }

    public function updateSlideOrder()
    {
        $slideModel = $this->model('Slide');
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Трябва да сте влезли в системата';
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        
        $slide = $slideModel->getById($slideId);
        
        if (!$slide) {
            header('Location: ' . BASE_URL . '/presentation');
            exit;
        }

        $presentation = $presentationModel->getById($slide['presentation_id']);
        
        if (!$presentation || !$workspaceModel->hasAccess($userId, $presentation['workspace_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $this->checkOwnerAccess($presentation['workspace_id']);

    }
} 