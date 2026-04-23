<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Helpers\Mailer;
use App\Models\AdminUser;
use App\Models\PasswordReset;

class AuthController
{
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

    // ── Login ──────────────────────────────────────────────────
    public function redirectDashboard(): void
    {
        Auth::check() ? $this->redirect('admin/dashboard') : $this->redirect('admin/login');
    }

    public function login(): void
    {
        if (Auth::check()) { $this->redirect('admin/dashboard'); }

        $error = null;

        if ($this->isPost()) {
            Csrf::verifyOrFail();

            if (Auth::isLocked()) {
                $min   = ceil(Auth::remainingLockout() / 60);
                $error = "Demasiados intentos. Esperá {$min} minuto(s).";
            } else {
                $email    = $this->input('email', '');
                $password = $this->input('password', '');
                $model    = new AdminUser();
                $user     = $model->findByEmail($email);

                if ($user && password_verify($password, $user['password'])) {
                    Auth::login($user);
                    $model->updateLastLogin($user['id']);
                    $this->redirect('admin/dashboard');
                } else {
                    Auth::recordFailedAttempt();
                    $error = 'Email o contraseña incorrectos.';
                }
            }
        }

        $error = $error ?? Session::getFlash('error');
        require APP_PATH . '/Views/admin/login.php';
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('success', 'Sesión cerrada correctamente.');
        $this->redirect('admin/login');
    }

    // ── Forgot password ────────────────────────────────────────
    public function forgotPassword(): void
    {
        $message = null;
        $type    = 'success';

        if ($this->isPost()) {
            Csrf::verifyOrFail();
            $email = $this->input('email', '');

            $model = new AdminUser();
            $user  = $model->findByEmail($email);

            // Respuesta idéntica siempre (anti-enumeration)
            $message = 'Si el email existe, recibirás un link para restablecer tu contraseña en los próximos minutos.';

            if ($user) {
                $reset  = new PasswordReset();
                $token  = $reset->create($email);
                $url    = BASE_URL . '/admin/reset-password?token=' . urlencode($token);

                try {
                    $mailer = new Mailer();
                    $mailer->passwordReset($email, $user['name'], $url);
                } catch (\Throwable $e) {
                    error_log('[Mailer] Password reset error: ' . $e->getMessage());
                }
            }
        }

        require APP_PATH . '/Views/admin/forgot_password.php';
    }

    // ── Reset password ─────────────────────────────────────────
    public function resetPassword(): void
    {
        $token = $this->input('token', '');
        $model = new PasswordReset();
        $reset = $model->findValid($token);

        if (!$reset) {
            Session::flash('error', 'El link expiró o ya fue utilizado. Solicitá uno nuevo.');
            $this->redirect('admin/forgot-password');
        }

        $error = null;

        if ($this->isPost()) {
            Csrf::verifyOrFail();

            $password = $this->input('password', '');
            $confirm  = $this->input('password_confirm', '');

            if (strlen($password) < 8) {
                $error = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif ($password !== $confirm) {
                $error = 'Las contraseñas no coinciden.';
            } else {
                $hash      = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $userModel = new AdminUser();
                $userModel->query(
                    "UPDATE admin_users SET password = ? WHERE email = ?",
                    [$hash, $reset['email']]
                );
                $model->markUsed($token);

                Session::flash('success', '¡Contraseña actualizada! Ya podés iniciar sesión.');
                $this->redirect('admin/login');
            }
        }

        require APP_PATH . '/Views/admin/reset_password.php';
    }

    // ── Change password (desde panel, usuario autenticado) ─────
    public function changePassword(): void
    {
        Auth::require();

        if (!$this->isPost()) {
            $this->redirect('admin/configuracion');
        }

        Csrf::verifyOrFail();

        $current  = $this->input('current_password', '');
        $new      = $this->input('new_password', '');
        $confirm  = $this->input('new_password_confirm', '');
        $authUser = Auth::user();

        $model = new AdminUser();
        $user  = $model->findById($authUser['id']);

        if (!$user || !password_verify($current, $user['password'])) {
            Session::flash('error', 'La contraseña actual es incorrecta.');
            $this->redirect('admin/configuracion');
        }

        if (strlen($new) < 8) {
            Session::flash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
            $this->redirect('admin/configuracion');
        }

        if ($new !== $confirm) {
            Session::flash('error', 'Las contraseñas nuevas no coinciden.');
            $this->redirect('admin/configuracion');
        }

        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        $model->query("UPDATE admin_users SET password = ? WHERE id = ?", [$hash, $authUser['id']]);

        Session::flash('success', 'Contraseña cambiada correctamente.');
        $this->redirect('admin/configuracion');
    }
}
