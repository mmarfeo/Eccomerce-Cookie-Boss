<?php
namespace App\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;

class HomeController extends BaseController
{
    public function index(): void
    {
        $productModel  = new Product();
        $categoryModel = new Category();
        $settingModel  = new Setting();

        $this->view('home/index', [
            'featured'   => $productModel->featured(8),
            'categories' => $categoryModel->withProductCount(),
            'storeName'  => $settingModel->get('store_name', 'Cookie Bakery'),
            'tagline'    => $settingModel->get('store_tagline', 'New York Style Cookies'),
        ]);
    }

    public function about(): void
    {
        $this->view('home/about', ['pageTitle' => 'Nosotros']);
    }

    public function contact(): void
    {
        $settingModel = new Setting();
        $this->view('home/contact', [
            'pageTitle' => 'Contacto',
            'phone'     => $settingModel->get('store_phone'),
            'email'     => $settingModel->get('store_email'),
            'instagram' => $settingModel->get('store_instagram'),
            'address'   => $settingModel->get('store_address'),
        ]);
    }
}
