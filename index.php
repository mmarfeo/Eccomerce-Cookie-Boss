<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Autoload: Composer si existe, fallback manual PSR-4
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Fallback autoloader PSR-4 para App\
    spl_autoload_register(function (string $class): void {
        if (str_starts_with($class, 'App\\')) {
            $path = APP_PATH . '/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
            if (file_exists($path)) {
                require_once $path;
            }
        }
    });
}

use App\Core\Session;
use App\Core\Router;

Session::start();

$router = new Router();
$router->dispatch();
