<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Session;

abstract class AdminBaseController
{
    public function __construct()
    {
        Auth::require();
    }

    /**
     * Requiere rol 'admin'. Los managers ven un mensaje de acceso denegado.
     */
    protected function requireAdmin(): void
    {
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'admin') {
            Session::flash('error', 'No tenés permisos para acceder a esta sección.');
            $this->redirect('admin/dashboard');
        }
    }

    protected function isAdmin(): bool
    {
        return (Auth::user()['role'] ?? '') === 'admin';
    }

    protected function view(string $viewPath, array $data = []): void
    {
        $data['authUser'] = Auth::user();
        $data['flash']    = [
            'success' => Session::getFlash('success'),
            'error'   => Session::getFlash('error'),
            'info'    => Session::getFlash('info'),
        ];

        extract($data);

        $layoutFile = APP_PATH . '/Views/layouts/admin.php';
        $viewFile   = APP_PATH . '/Views/admin/' . $viewPath . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            die("Vista admin no encontrada: {$viewPath}");
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

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
        exit;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        $val = $_POST[$key] ?? $_GET[$key] ?? $default;
        if (is_string($val)) $val = trim($val);
        return $val;
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
