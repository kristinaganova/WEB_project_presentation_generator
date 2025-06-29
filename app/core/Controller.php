<?php

class Controller {
    public function model($model) {
        $modelFile = __DIR__ . '/../models/' . $model . '.php';
        if (file_exists($modelFile)) {
            require_once $modelFile;
        return new $model();
        }
        throw new Exception("Model file not found: $modelFile");
    }

    public function view($view, $data = []) {
        error_log("[Controller] Опит за зареждане на изглед: " . print_r($view, true));
        if (is_numeric($view)) {
            throw new Exception("Опит за зареждане на изглед с числово име: $view. Провери пренасочванията и подадените параметри.");
        }
        if (empty($view) || !is_string($view)) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        error_log("Trying to load view file: " . $viewFile);
        
        if (file_exists($viewFile)) {
            extract($data);
            
            ob_start();
            require_once $viewFile;
            $content = ob_get_clean();
            
            require_once __DIR__ . '/../views/layouts/main.php';
        } else {
            error_log("View file not found: " . $viewFile);
            throw new Exception("View file not found: $viewFile");
        }
    }

    protected function redirect($path, $params = []) {
        $url = BASE_URL . '/' . $path;
        
        if (!empty($params)) {
            $query = http_build_query($params);
            $url .= '?' . $query;
        }
        
        header('Location: ' . $url);
        exit;
    }
}
