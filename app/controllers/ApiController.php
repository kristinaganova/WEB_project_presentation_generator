<?php

require_once __DIR__ . '/../core/AuthMiddleware.php';

class ApiController extends Controller
{
    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function authenticate() {
        // Handle OPTIONS request for CORS
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            exit;
        }

        // Check for Bearer token
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        // For development: accept both Bearer token and session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // If we have a valid session, use it
        if (isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }
        
        // Otherwise require Bearer token
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Unauthorized - Bearer token required'
            ], 401);
        }
        
        // For demo purposes, accept any token and use session
        // In production, validate token against database
        if (isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }
        
        $this->jsonResponse([
            'success' => false,
            'error' => 'Unauthorized - Invalid token'
        ], 401);
    }

    // GET /api/presentations/{id}
    public function presentation($id = null) {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $id) {
            $this->getPresentation($id);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$id) {
            $this->createPresentation();
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Invalid request'
            ], 400);
        }
    }

    // GET /api/presentations/{id}
    private function getPresentation($id) {
        $userId = $this->authenticate();
        
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        $slideModel = $this->model('Slide');
        
        $presentation = $presentationModel->getById($id);
        
        if (!$presentation) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Presentation not found'
            ], 404);
        }
        
        if (!$workspaceModel->hasAccess($userId, $presentation['workspace_id'])) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Access denied'
            ], 403);
        }
        
        $slides = $slideModel->getByPresentationId($id);
        
        $this->jsonResponse([
            'success' => true,
            'data' => [
                'presentation' => $presentation,
                'slides' => $slides,
                'slide_count' => count($slides)
            ]
        ]);
    }

    // POST /api/presentations
    private function createPresentation() {
        $userId = $this->authenticate();
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Invalid JSON: ' . json_last_error_msg()
            ], 400);
        }
        
        if (!isset($data['workspace_id']) || !isset($data['title'])) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Missing required fields: workspace_id, title'
            ], 400);
        }
        
        $workspaceModel = $this->model('Workspace');
        if (!$workspaceModel->isOwner($userId, $data['workspace_id'])) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Access denied - Only workspace owner can create presentations'
            ], 403);
        }
        
        $presentationModel = $this->model('Presentation');
        $presentationId = $presentationModel->create(
            $data['workspace_id'],
            $data['title'],
            $data['language'] ?? 'bg',
            $data['theme'] ?? 'light'
        );
        
        if ($presentationId) {
            $presentation = $presentationModel->getById($presentationId);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Presentation created successfully',
                'data' => [
                    'id' => $presentationId,
                    'presentation' => $presentation
                ]
            ], 201);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to create presentation'
            ], 500);
        }
    }

    // PUT /api/slides/{id}
    // DELETE /api/slides/{id}
    public function slide($id = null) {
        if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $id) {
            $this->updateSlide($id);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $id) {
            $this->deleteSlide($id);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$id) {
            $this->createSlide();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $id) {
            $this->getSlide($id);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Invalid request'
            ], 400);
        }
    }

    // GET /api/slides/{id}
    private function getSlide($id) {
        $userId = $this->authenticate();
        
        $slideModel = $this->model('Slide');
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        
        $slide = $slideModel->getById($id);
        
        if (!$slide) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Slide not found'
            ], 404);
        }
        
        $presentation = $presentationModel->getById($slide['presentation_id']);
        if (!$workspaceModel->hasAccess($userId, $presentation['workspace_id'])) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Access denied'
            ], 403);
        }
        
        $this->jsonResponse([
            'success' => true,
            'data' => $slide
        ]);
    }

    // POST /api/slides
    private function createSlide() {
        $userId = $this->authenticate();
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Invalid JSON: ' . json_last_error_msg()
            ], 400);
        }
        
        if (!isset($data['presentation_id']) || !isset($data['title']) || !isset($data['layout'])) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Missing required fields: presentation_id, title, layout'
            ], 400);
        }
        
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        
        $presentation = $presentationModel->getById($data['presentation_id']);
        if (!$presentation) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Presentation not found'
            ], 404);
        }
        
        if (!$workspaceModel->isOwner($userId, $presentation['workspace_id'])) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Access denied - Only workspace owner can create slides'
            ], 403);
        }
        
        $slideModel = $this->model('Slide');
        $slideId = $slideModel->create($data);
        
        if ($slideId) {
            $slide = $slideModel->getById($slideId);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Slide created successfully',
                'data' => [
                    'id' => $slideId,
                    'slide' => $slide
                ]
            ], 201);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to create slide'
            ], 500);
        }
    }

    // PUT /api/slides/{id}
    private function updateSlide($id) {
        $userId = $this->authenticate();
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Invalid JSON: ' . json_last_error_msg()
            ], 400);
        }
        
        $slideModel = $this->model('Slide');
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        
        $slide = $slideModel->getById($id);
        if (!$slide) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Slide not found'
            ], 404);
        }
        
        $presentation = $presentationModel->getById($slide['presentation_id']);
        if (!$workspaceModel->isOwner($userId, $presentation['workspace_id'])) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Access denied - Only workspace owner can update slides'
            ], 403);
        }
        
        // Update slide data
        $updateData = [
            'title' => $data['title'] ?? $slide['title'],
            'layout' => $data['layout'] ?? $slide['layout']
        ];
        
        // If elements provided, update them
        if (isset($data['elements'])) {
            $updateData['elements'] = $data['elements'];
        }
        
        $success = $slideModel->update($id, $updateData);
        
        if ($success) {
            $updatedSlide = $slideModel->getById($id);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Slide updated successfully',
                'data' => $updatedSlide
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to update slide'
            ], 500);
        }
    }

    // DELETE /api/slides/{id}
    private function deleteSlide($id) {
        $userId = $this->authenticate();
        
        $slideModel = $this->model('Slide');
        $presentationModel = $this->model('Presentation');
        $workspaceModel = $this->model('Workspace');
        
        $slide = $slideModel->getById($id);
        if (!$slide) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Slide not found'
            ], 404);
        }
        
        $presentation = $presentationModel->getById($slide['presentation_id']);
        if (!$workspaceModel->isOwner($userId, $presentation['workspace_id'])) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Access denied - Only workspace owner can delete slides'
            ], 403);
        }
        
        $success = $slideModel->delete($id);
        
        if ($success) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Slide deleted successfully'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to delete slide'
            ], 500);
        }
    }

    // GET /api/workspaces - Bonus endpoint
    public function workspaces() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Method not allowed'
            ], 405);
        }
        
        $userId = $this->authenticate();
        
        $workspaceModel = $this->model('Workspace');
        $workspaces = $workspaceModel->getUserWorkspaces($userId);
        
        $this->jsonResponse([
            'success' => true,
            'data' => [
                'workspaces' => $workspaces,
                'count' => count($workspaces)
            ]
        ]);
    }

    // GET /api/health - Health check endpoint
    public function health() {
        $this->jsonResponse([
            'success' => true,
            'status' => 'OK',
            'service' => 'Presentation Generator API',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
