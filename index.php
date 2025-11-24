<?php
/**
 * Main Application Router
 * Uses AltoRouter for clean URL routing
 */

// Start session
session_start();

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Load database configuration
require_once __DIR__ . '/app/config/database.php';

// Disable error display in production (can be enabled via .env)
if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'false') {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Initialize router
$router = new AltoRouter();

// Set base path if application is in a subdirectory
// $router->setBasePath('/subdirectory');

/**
 * Define routes
 * Format: $router->map('HTTP_METHOD', 'ROUTE', 'CONTROLLER#METHOD', 'ROUTE_NAME');
 */

// Public routes (no authentication required)
$router->map('GET', '/', function() {
    // Landing page
    require __DIR__ . '/landing.php';
}, 'home');

$router->map('GET', '/login', function() {
    // Login page
    require __DIR__ . '/public/page_connexion.php';
}, 'login_page');

$router->map('POST', '/login', 'AuthController#handleLogin', 'login_action');

$router->map('GET', '/logout', 'AuthController#logout', 'logout');

// Protected routes (authentication required)
$router->map('GET', '/dashboard', 'DashboardController#index', 'dashboard');

// Layout route - for backward compatibility during migration
$router->map('GET', '/layout', function() {
    require __DIR__ . '/public/layout.php';
}, 'layout');

// Match current request
$match = $router->match();

if ($match) {
    // Check if route target is a callable (closure)
    if (is_callable($match['target'])) {
        call_user_func_array($match['target'], $match['params']);
    } else {
        // Route target is a controller#method string
        list($controller, $method) = explode('#', $match['target']);
        
        // Build controller path
        $controllerFile = __DIR__ . '/app/controllers/' . $controller . '.php';
        
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            
            if (class_exists($controller)) {
                $db = Database::getConnection();
                $controllerInstance = new $controller($db);
                
                if (method_exists($controllerInstance, $method)) {
                    call_user_func_array([$controllerInstance, $method], $match['params']);
                } else {
                    // Method not found
                    http_response_code(500);
                    echo "Error: Method '$method' not found in controller '$controller'";
                }
            } else {
                // Class not found
                http_response_code(500);
                echo "Error: Controller class '$controller' not found";
            }
        } else {
            // Controller file not found
            http_response_code(500);
            echo "Error: Controller file not found: $controllerFile";
        }
    }
} else {
    // No route matched - 404
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 - Page non trouvée</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 flex items-center justify-center min-h-screen">
        <div class="text-center">
            <h1 class="text-6xl font-bold text-gray-800 mb-4">404</h1>
            <p class="text-xl text-gray-600 mb-8">Page non trouvée</p>
            <a href="/" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                Retour à l'accueil
            </a>
        </div>
    </body>
    </html>
    <?php
}
