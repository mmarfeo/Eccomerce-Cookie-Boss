<?php
namespace App\Controllers;

use App\Core\Csrf;
use App\Core\CustomerAuth;
use App\Core\Session;
use App\Helpers\Loyalty;
use App\Models\Customer;
use App\Models\Order;

class AccountController extends \App\Controllers\BaseController
{
    // ── Registro ──────────────────────────────────────────────

    public function register(): void
    {
        if (CustomerAuth::check()) {
            $this->redirect('cuenta/perfil');
        }

        if ($this->isPost()) {
            Csrf::verifyOrFail();

            $name     = trim($this->input('name', ''));
            $email    = trim($this->input('email', ''));
            $phone    = trim($this->input('phone', ''));
            $password = $this->input('password', '');
            $confirm  = $this->input('password_confirm', '');
            $errors   = [];

            if (strlen($name) < 2)    $errors[] = 'El nombre debe tener al menos 2 caracteres.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
            if (strlen($password) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
            if ($password !== $confirm) $errors[] = 'Las contraseñas no coinciden.';

            $model = new Customer();

            if (empty($errors)) {
                $existing = $model->findByEmail($email);
                if ($existing && $existing['password']) {
                    $errors[] = 'Ya existe una cuenta con ese email. <a href="' . BASE_URL . '/cuenta/login">Iniciá sesión</a>.';
                }
            }

            if (!empty($errors)) {
                $this->view('account/register', [
                    'pageTitle' => 'Crear cuenta',
                    'errors'    => $errors,
                    'old'       => compact('name', 'email', 'phone'),
                ]);
                return;
            }

            // Si ya existe como guest (sin password) → asignar password
            $existing = $model->findByEmail($email);
            if ($existing) {
                $model->updatePassword($existing['id'], $password);
                $model->query("UPDATE customers SET name = ?, phone = COALESCE(?, phone) WHERE id = ?",
                    [$name, $phone ?: null, $existing['id']]);
                $customer = $model->findById($existing['id']);
            } else {
                $id = $model->register(compact('name', 'email', 'phone', 'password'));
                $customer = $model->findById($id);
            }

            $model->updateLastLogin($customer['id']);
            CustomerAuth::login($customer);
            Session::flash('success', '¡Bienvenido/a, ' . htmlspecialchars($name) . '! Tu cuenta fue creada.');
            $this->redirect('cuenta/perfil');
        }

        $this->view('account/register', ['pageTitle' => 'Crear cuenta']);
    }

    // ── Login ─────────────────────────────────────────────────

    public function login(): void
    {
        if (CustomerAuth::check()) {
            $this->redirect('cuenta/perfil');
        }

        if ($this->isPost()) {
            Csrf::verifyOrFail();

            $email    = trim($this->input('email', ''));
            $password = $this->input('password', '');
            $model    = new Customer();
            $customer = $model->findByEmail($email);

            if (!$customer || !$customer['password'] || !password_verify($password, $customer['password'])) {
                $this->view('account/login', [
                    'pageTitle' => 'Iniciar sesión',
                    'error'     => 'Email o contraseña incorrectos.',
                    'old_email' => $email,
                ]);
                return;
            }

            $model->updateLastLogin($customer['id']);
            CustomerAuth::login($customer);

            $redirect = Session::get('redirect_after_login', 'cuenta/perfil');
            Session::remove('redirect_after_login');
            $this->redirect($redirect);
        }

        $this->view('account/login', ['pageTitle' => 'Iniciar sesión']);
    }

    // ── Logout ────────────────────────────────────────────────

    public function logout(): void
    {
        CustomerAuth::logout();
        Session::flash('success', 'Sesión cerrada correctamente.');
        $this->redirect('');
    }

    // ── Perfil ────────────────────────────────────────────────

    public function profile(): void
    {
        CustomerAuth::require();
        $customerId = CustomerAuth::id();
        $model      = new Customer();
        $customer   = $model->findById($customerId);

        if ($this->isPost()) {
            Csrf::verifyOrFail();

            $name    = trim($this->input('name', ''));
            $phone   = trim($this->input('phone', ''));
            $address = trim($this->input('address', ''));
            $city    = trim($this->input('city', ''));

            if (strlen($name) < 2) {
                Session::flash('error', 'El nombre debe tener al menos 2 caracteres.');
            } else {
                $model->updateProfile($customerId, compact('name', 'phone', 'address', 'city'));
                // Refresh session name
                $updated = $model->findById($customerId);
                CustomerAuth::login($updated);
                Session::flash('success', 'Perfil actualizado correctamente.');
            }
            $this->redirect('cuenta/perfil');
        }

        $cfg           = Loyalty::config();
        $points        = (int)($customer['loyalty_points'] ?? 0);
        $pointsValue   = $cfg['redeem_rate'] > 0 ? floor($points / $cfg['redeem_rate']) : 0;
        $history       = $model->getLoyaltyHistory($customerId);
        $orderModel    = new Order();
        $orders        = $orderModel->query(
            "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 20",
            [$customerId]
        )->fetchAll();

        $this->view('account/profile', [
            'pageTitle'   => 'Mi cuenta',
            'customer'    => $customer,
            'points'      => $points,
            'pointsValue' => $pointsValue,
            'tier'        => Loyalty::tierLabel($customer['loyalty_tier'] ?? 'bronze'),
            'tierColor'   => Loyalty::tierColor($customer['loyalty_tier'] ?? 'bronze'),
            'history'     => $history,
            'orders'      => $orders,
            'cfg'         => $cfg,
        ]);
    }

    // ── Cambiar contraseña ────────────────────────────────────

    public function changePassword(): void
    {
        CustomerAuth::require();
        Csrf::verifyOrFail();

        $current = $this->input('current_password', '');
        $new     = $this->input('new_password', '');
        $confirm = $this->input('confirm_password', '');

        $model    = new Customer();
        $customer = $model->findById(CustomerAuth::id());

        if (!$customer['password'] || !password_verify($current, $customer['password'])) {
            Session::flash('error', 'La contraseña actual es incorrecta.');
            $this->redirect('cuenta/perfil');
        }

        if (strlen($new) < 8) {
            Session::flash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
            $this->redirect('cuenta/perfil');
        }

        if ($new !== $confirm) {
            Session::flash('error', 'Las contraseñas no coinciden.');
            $this->redirect('cuenta/perfil');
        }

        $model->updatePassword(CustomerAuth::id(), $new);
        Session::flash('success', 'Contraseña actualizada correctamente.');
        $this->redirect('cuenta/perfil');
    }
}
