<?php
namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Session;
use App\Models\AdminUser;

class AdminUserController extends AdminBaseController
{
    public function index(): void
    {
        $this->requireAdmin();
        $model = new AdminUser();
        $users = $model->findAll('id ASC');

        $this->view('admin_users/index', [
            'pageTitle' => 'Usuarios del panel',
            'users'     => $users,
        ]);
    }

    public function create(): void
    {
        $this->requireAdmin();

        if ($this->isPost()) {
            Csrf::verifyOrFail();

            $name     = trim($this->input('name', ''));
            $email    = trim($this->input('email', ''));
            $role     = $this->input('role', 'manager');
            $password = $this->input('password', '');
            $confirm  = $this->input('password_confirm', '');
            $errors   = [];

            if (strlen($name) < 2)               $errors[] = 'El nombre debe tener al menos 2 caracteres.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
            if (!in_array($role, ['admin','manager'])) $errors[] = 'Rol inválido.';
            if (strlen($password) < 8)           $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
            if ($password !== $confirm)          $errors[] = 'Las contraseñas no coinciden.';

            $model = new AdminUser();
            if (empty($errors)) {
                $existing = $model->findByEmail($email);
                if ($existing) $errors[] = 'Ya existe un usuario con ese email.';
            }

            if (!empty($errors)) {
                $this->view('admin_users/index', [
                    'pageTitle' => 'Usuarios del panel',
                    'users'     => $model->findAll('id ASC'),
                    'errors'    => $errors,
                    'old'       => compact('name', 'email', 'role'),
                ]);
                return;
            }

            $model->create($name, $email, $role, $password);

            Session::flash('success', "Usuario {$name} creado correctamente.");
            $this->redirect('admin/usuarios');
        }

        $this->redirect('admin/usuarios');
    }

    public function delete(): void
    {
        $this->requireAdmin();
        Csrf::verifyOrFail();

        $id        = (int)$this->input('id');
        $currentId = \App\Core\Auth::user()['id'];

        if ($id === $currentId) {
            Session::flash('error', 'No podés eliminar tu propio usuario.');
            $this->redirect('admin/usuarios');
        }

        $model = new AdminUser();
        $model->delete($id);
        Session::flash('success', 'Usuario eliminado.');
        $this->redirect('admin/usuarios');
    }

    public function resetPassword(): void
    {
        $this->requireAdmin();
        Csrf::verifyOrFail();

        $id       = (int)$this->input('id');
        $password = $this->input('new_password', '');

        if (strlen($password) < 8) {
            Session::flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            $this->redirect('admin/usuarios');
        }

        $model = new AdminUser();
        $model->updatePassword($id, $password);

        Session::flash('success', 'Contraseña actualizada.');
        $this->redirect('admin/usuarios');
    }
}
