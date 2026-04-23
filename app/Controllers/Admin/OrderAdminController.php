<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Helpers\Mailer;
use App\Models\Order;

class OrderAdminController extends AdminBaseController
{
    public function index(): void
    {
        $model  = new Order();
        $status = $this->input('estado', '');
        $page   = max(1, (int)$this->input('pagina', 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $this->view('orders/index', [
            'pageTitle' => 'Pedidos',
            'orders'    => $model->listAdmin($status, $limit, $offset),
            'statuses'  => ['pending','paid','processing','ready','delivered','cancelled','refunded'],
            'current'   => $status,
            'page'      => $page,
        ]);
    }

    public function detail(): void
    {
        $id    = (int)$this->input('id');
        $model = new Order();
        $order = $model->findById($id);

        if (!$order) {
            Session::flash('error', 'Pedido no encontrado.');
            $this->redirect('admin/pedidos');
        }

        $this->view('orders/detail', [
            'pageTitle' => 'Pedido #' . $order['order_number'],
            'order'     => $order,
            'items'     => $model->getItems($id),
            'history'   => $model->getStatusHistory($id),
            'statuses'  => ['pending','paid','processing','ready','delivered','cancelled','refunded'],
        ]);
    }

    public function updateStatus(): void
    {
        Csrf::verifyOrFail();

        $id     = (int)$this->input('id');
        $status = $this->input('status', '');
        $notes  = $this->input('notes', '');

        $valid = ['pending','paid','processing','ready','delivered','cancelled','refunded'];

        if (!in_array($status, $valid)) {
            Session::flash('error', 'Estado inválido.');
            $this->redirect('admin/pedidos/ver?id=' . $id);
        }

        $model = new Order();
        $model->updateStatus($id, $status, $notes, Auth::user()['email'] ?? 'admin');

        // Send status update email to customer (non-blocking)
        $order = $model->findById($id);
        if ($order && !empty($order['customer_email'])) {
            try {
                (new Mailer())->orderStatusUpdate($order, $status);
            } catch (\Throwable $e) {
                // Email failure should not block the status update
            }
        }

        Session::flash('success', 'Estado del pedido actualizado.');
        $this->redirect('admin/pedidos/ver?id=' . $id);
    }
}
