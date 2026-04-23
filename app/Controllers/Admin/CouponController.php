<?php
namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Session;
use App\Models\Coupon;

class CouponController extends AdminBaseController
{
    public function index(): void
    {
        $model = new Coupon();
        $this->view('coupons/index', [
            'pageTitle' => 'Cupones de descuento',
            'coupons'   => $model->findAll('created_at DESC'),
        ]);
    }

    public function create(): void
    {
        if (!$this->isPost()) { $this->redirect('admin/cupones'); }
        Csrf::verifyOrFail();

        $model = new Coupon();
        $code  = strtoupper(trim($this->input('code', '')));
        $value = (float)$this->input('value', 0);

        if (!$code || $value <= 0) {
            Session::flash('error', 'Código y valor son obligatorios.');
            $this->redirect('admin/cupones');
        }

        $model->create([
            'code'        => $code,
            'description' => $this->input('description', ''),
            'type'        => $this->input('type', 'percent'),
            'value'       => $value,
            'min_order'   => (float)$this->input('min_order', 0),
            'max_uses'    => $this->input('max_uses', ''),
            'expires_at'  => $this->input('expires_at', ''),
            'active'      => 1,
        ]);

        Session::flash('success', "Cupón {$code} creado.");
        $this->redirect('admin/cupones');
    }

    public function delete(): void
    {
        Csrf::verifyOrFail();
        $model = new Coupon();
        $model->delete((int)$this->input('id'));
        Session::flash('success', 'Cupón eliminado.');
        $this->redirect('admin/cupones');
    }

    public function toggle(): void
    {
        $model = new Coupon();
        $model->toggle((int)$this->input('id'));
        $this->json(['success' => true]);
    }
}
