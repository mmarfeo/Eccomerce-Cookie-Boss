<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Helpers\Upload;
use App\Models\Setting;

class SettingController extends AdminBaseController
{
    public function index(): void
    {
        $this->requireAdmin();
        $model = new Setting();
        $this->view('settings/index', [
            'pageTitle' => 'Configuración',
            'settings'  => $model->all(),
        ]);
    }

    public function save(): void
    {
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('admin/configuracion'); }
        Csrf::verifyOrFail();

        $model = new Setting();

        $allowed = [
            // Tienda
            'store_name', 'store_tagline', 'store_phone', 'store_email',
            'store_instagram', 'store_address', 'store_pickup_address',
            // MP
            'mp_public_key', 'mp_access_token', 'mp_webhook_secret',
            // Envíos
            'shipping_cost', 'free_shipping_min', 'min_order_amount',
            // Estado
            'store_open', 'store_closed_msg',
            // Branding colors/font
            'brand_color_primary', 'brand_color_secondary',
            'brand_color_dark', 'brand_color_text', 'brand_font',
            // Email
            'mail_from_name', 'mail_from_email',
            'mail_smtp_host', 'mail_smtp_port',
            'mail_smtp_user', 'mail_smtp_pass', 'mail_smtp_enabled',
        ];

        $data = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $_POST)) {
                $data[$key] = trim($_POST[$key]);
            }
        }

        // Checkboxes (no vienen si están desmarcados)
        $data['store_open']       = isset($_POST['store_open'])       ? '1' : '0';
        $data['mail_smtp_enabled']= isset($_POST['mail_smtp_enabled']) ? '1' : '0';

        // Upload de logo
        if (!empty($_FILES['store_logo']['name'])) {
            $up = Upload::image($_FILES['store_logo'], 'logo');
            if ($up['success']) {
                // Borrar logo anterior
                $old = $model->get('store_logo');
                if ($old) Upload::delete($old);
                $data['store_logo'] = $up['filename'];
            } else {
                Session::flash('error', 'Error al subir el logo: ' . $up['error']);
                $this->redirect('admin/configuracion');
            }
        }

        // Eliminar logo
        if ($this->input('remove_logo') === '1') {
            $old = $model->get('store_logo');
            if ($old) Upload::delete($old);
            $data['store_logo'] = '';
        }

        $model->saveMany($data);
        Session::flash('success', 'Configuración guardada correctamente.');
        $this->redirect('admin/configuracion');
    }
}
