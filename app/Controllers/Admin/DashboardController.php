<?php
namespace App\Controllers\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;

class DashboardController extends AdminBaseController
{
    public function index(): void
    {
        $orderModel    = new Order();
        $productModel  = new Product();
        $customerModel = new Customer();

        $this->view('dashboard/index', [
            'pageTitle'      => 'Dashboard',
            'todaySales'     => $orderModel->todaySales(),
            'monthSales'     => $orderModel->monthSales(),
            'pendingCount'   => $orderModel->pendingCount(),
            'totalProducts'  => $productModel->count(),
            'totalCustomers' => $customerModel->count(),
            'latestOrders'   => $orderModel->listAdmin('', 10, 0),
            'bestSellers'    => $productModel->getBestSellers(5),
            'salesChart'     => $orderModel->salesByDay(30),
        ]);
    }
}
