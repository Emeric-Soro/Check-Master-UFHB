<?php
session_start();

// Load Composer autoloader and initialize
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/utils/RouterHelper.php';

// Initialize AltoRouter
$router = new AltoRouter();

// Load all routes from configuration file
require_once __DIR__ . '/app/config/routes.php';

// Initialize RouterHelper with router instance
RouterHelper::init($router);

// Match current request
$match = $router->match();

if ($match) {
    // Parse controller and method from target
    list($controller, $method) = explode('#', $match['target']);
    
    // Build controller file path
    $controllerFile = __DIR__ . '/app/controllers/' . $controller . '.php';
    
    // Check if controller file exists
    if (file_exists($controllerFile)) {
        // Include the controller file dynamically
        require_once $controllerFile;
        
        // Check if class exists
        if (class_exists($controller)) {
            // Instantiate the class
            // Handle different constructor signatures based on controller type
            try {
                if ($controller === 'AuthController') {
                    // AuthController needs database connection
                    $controllerInstance = new $controller(Database::getConnection());
                } else {
                    // Try to instantiate with no parameters (most controllers)
                    $controllerInstance = new $controller();
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo "500 - Error instantiating controller: " . $e->getMessage();
                exit;
            }
            
            // Check if method exists
            if (method_exists($controllerInstance, $method)) {
                // Special handling for AuthController login
                if ($controller === 'AuthController' && $method === 'login') {
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        // Handle POST login
                        $result = $controllerInstance->login($_POST['login'] ?? '', $_POST['password'] ?? '');
                        if ($result) {
                            // Login successful - redirect to dashboard
                            header('Location: /dashboard');
                            exit;
                        } else {
                            // Login failed - redirect back to login
                            $_SESSION['error'] = 'Login ou mot de passe incorrect';
                            header('Location: /');
                            exit;
                        }
                    } else {
                        // GET request - show login page
                        require_once __DIR__ . '/page_connexion.php';
                        exit;
                    }
                } elseif ($controller === 'AuthController' && $method === 'logout') {
                    // Handle logout
                    $controllerInstance->logout();
                    header('Location: /');
                    exit;
                } else {
                    // Call controller method with route params
                    call_user_func_array([$controllerInstance, $method], $match['params']);
                }
            } else {
                // Method not found
                http_response_code(404);
                echo "404 - Method not found: $method in controller $controller";
            }
        } else {
            // Class not found
            http_response_code(404);
            echo "404 - Controller class not found: $controller";
        }
    } else {
        // Controller file not found
        http_response_code(404);
        echo "404 - Controller file not found: $controllerFile";
    }
} else {
    // No route matched - 404
    http_response_code(404);
    echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page non trouvée</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        .container { background: white; padding: 40px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { font-size: 72px; margin: 0; color: #1a5276; }
        h2 { font-size: 24px; color: #333; margin: 20px 0; }
        p { font-size: 16px; color: #666; }
        a { color: #1a5276; text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <h2>Page non trouvée</h2>
        <p>La page que vous recherchez n\'existe pas.</p>
        <p><a href="/">← Retour à l\'accueil</a></p>
    </div>
</body>
</html>';
}
