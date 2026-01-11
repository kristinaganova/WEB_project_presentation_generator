<?php
require_once __DIR__ . '/../helpers/Logger.php';

class App
{
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];

    public function __construct()
    {
        error_log('[App] REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
        $url = $this->parseUrl();
        error_log('[App] Парсиран URL: ' . print_r($url, true));
        Logger::log("App::__construct - REQUEST_URI: " . $_SERVER['REQUEST_URI']);
        Logger::log("App::__construct - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        Logger::log("App::__construct - SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME']);
        Logger::log("App::__construct - PHP_SELF: " . $_SERVER['PHP_SELF']);

        // Handle API routes
        if (isset($url[0]) && $url[0] === 'api') {
            array_shift($url);
            $this->controller = 'ApiController';
            
            // Map API routes to controller methods
            // /api/presentations/{id} -> ApiController->presentation($id)
            // /api/slides/{id} -> ApiController->slide($id)
            // /api/workspaces -> ApiController->workspaces()
            // /api/health -> ApiController->health()
            
            if (isset($url[0])) {
                $this->method = $url[0]; // presentations, slides, workspaces, health
                unset($url[0]);
                
                if (isset($url[1])) {
                    $this->params = [$url[1]]; // id parameter
                }
            }
            
            Logger::log("App::__construct - API route detected: controller={$this->controller}, method={$this->method}, params=" . print_r($this->params, true));
            
            require_once "../app/controllers/{$this->controller}.php";
            $this->controller = new $this->controller;
            call_user_func_array([$this->controller, $this->method], $this->params);
            return;
        }

        if (isset($url[0])) {
            $controllerName = rtrim($url[0], 's');
            if (file_exists("../app/controllers/" . ucfirst($controllerName) . "Controller.php")) {
                $this->controller = ucfirst($controllerName) . 'Controller';
            unset($url[0]);
                Logger::log("App::__construct - Selected controller: " . $this->controller);
            }
        }

        require_once "../app/controllers/{$this->controller}.php";
        $this->controller = new $this->controller;

        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
            $this->method = $url[1];
            unset($url[1]);
                Logger::log("App::__construct - Selected method: " . $this->method);
            } else {
                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            }
        }

        $this->params = $url ? array_values($url) : [];
        Logger::log("App::__construct - Parameters: " . print_r($this->params, true));

        Logger::log("App::__construct - Calling controller method with parameters");
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    protected function parseUrl()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        
        if ($basePath !== '/' && strpos($requestUri, $basePath) === 0) {
            $requestUri = substr($requestUri, strlen($basePath));
        }
        
        if (($pos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $pos);
        }
        
        $url = explode('/', trim($requestUri, '/'));
        Logger::log("App::parseUrl - Parsed URL from REQUEST_URI: " . print_r($url, true));
        
        if (empty($url[0])) {
        return ['home'];
        }
        
        if ($url[0] === 'public') {
            array_shift($url);
        }
        
        if ($url[0] === 'web-project') {
            array_shift($url);
        }
        
        if ($url[0] === 'WEB_project_presentation_generator') {
            array_shift($url);
        }
        
        Logger::log("App::parseUrl - Final URL segments: " . print_r($url, true));
        return $url;
    }
}
