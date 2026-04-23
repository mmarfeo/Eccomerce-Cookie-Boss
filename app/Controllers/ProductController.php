<?php
namespace App\Controllers;

use App\Models\Product;
use App\Models\Category;

class ProductController extends BaseController
{
    public function catalog(): void
    {
        $productModel  = new Product();
        $categoryModel = new Category();

        $categorySlug  = $this->input('categoria', '');
        $categoryId    = 0;
        $currentCat    = null;

        if ($categorySlug) {
            $currentCat = $categoryModel->findBySlug($categorySlug);
            $categoryId = $currentCat ? (int)$currentCat['id'] : 0;
        }

        $products   = $productModel->allAvailable($categoryId);
        $categories = $categoryModel->withProductCount();

        $this->view('products/catalog', [
            'pageTitle'  => $currentCat ? $currentCat['name'] : 'Nuestras Cookies',
            'products'   => $products,
            'categories' => $categories,
            'currentCat' => $currentCat,
        ]);
    }

    public function detail(): void
    {
        $slug         = $this->input('slug', '');
        $productModel = new Product();

        if (!$slug) {
            $this->redirect('productos');
        }

        $product = $productModel->findBySlug($slug);

        if (!$product) {
            http_response_code(404);
            $this->view('errors/404', ['pageTitle' => 'Producto no encontrado']);
            return;
        }

        $images = $productModel->getImages((int)$product['id']);

        $this->view('products/detail', [
            'pageTitle' => $product['name'],
            'product'   => $product,
            'images'    => $images,
        ]);
    }
}
