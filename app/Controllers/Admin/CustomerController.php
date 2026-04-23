<?php
namespace App\Controllers\Admin;

use App\Core\Session;
use App\Models\Customer;

class CustomerController extends AdminBaseController
{
    public function index(): void
    {
        $model = new Customer();
        $this->view('customers/index', [
            'pageTitle' => 'Clientes',
            'customers' => $model->listWithStats(),
        ]);
    }

    public function detail(): void
    {
        $id    = (int)$this->input('id');
        $model = new Customer();
        $customer = $model->findById($id);

        if (!$customer) {
            Session::flash('error', 'Cliente no encontrado.');
            $this->redirect('admin/clientes');
        }

        $this->view('customers/detail', [
            'pageTitle' => 'Cliente: ' . $customer['name'],
            'customer'  => $customer,
            'orders'    => $model->getOrders($id),
        ]);
    }
}
