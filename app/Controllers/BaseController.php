<?php
namespace App\Controllers;

use App\Core\Session;
use App\Helpers\Cart;

abstract class BaseController
{
    protected function view(string $viewPath, array $data = []): void
    {
        // Datos globales disponibles en todas las vistas
        $data['cartCount'] = Cart::count();
        $data['flash']     = [
            'success' => Session::getFlash('success'),
            'error'   => Session::getFlash('error'),
            'info'    => Session::getFlash('info'),
        ];

        extract($data);

        $layoutFile = APP_PATH . '/Views/layouts/main.php';
        $viewFile   = APP_PATH . '/Views/' . $viewPath . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            die("Vista no encontrada: {$viewPath}");
        }

        require $layoutFile;
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $path, bool $absolute = false): void
    {
        $url = $absolute ? $path : BASE_URL . '/' . ltrim($path, '/');
        header('Location: ' . $url);
        exit;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        $val = $_POST[$key] ?? $_GET[$key] ?? $default;
        if (is_string($val)) {
            $val = trim($val);
        }
        return $val;
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
}
