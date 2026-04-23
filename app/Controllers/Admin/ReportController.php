<?php
namespace App\Controllers\Admin;

use App\Models\Order;
use App\Models\Product;

class ReportController extends AdminBaseController
{
    public function index(): void
    {
        $orderModel   = new Order();
        $productModel = new Product();

        $period = $this->input('periodo', '30');

        $this->view('reports/index', [
            'pageTitle'   => 'Reportes',
            'period'      => $period,
            'salesByDay'  => $orderModel->salesByDay((int)$period),
            'monthSales'  => $orderModel->monthSales(),
            'todaySales'  => $orderModel->todaySales(),
            'bestSellers' => $productModel->getBestSellers(10),
        ]);
    }
}
