<?php
namespace App\Controllers;

use App\Helpers\Cart;
use App\Models\Product;
use App\Models\Setting;

class CartController extends BaseController
{
    public function index(): void
    {
        $settingModel = new Setting();
        $shipping     = (float)$settingModel->get('shipping_cost', 0);
        $freeMin      = (float)$settingModel->get('free_shipping_min', 0);
        $subtotal     = Cart::subtotal();

        if ($freeMin > 0 && $subtotal >= $freeMin) {
            $shipping = 0;
        }

        $this->view('cart/index', [
            'pageTitle'   => 'Tu carrito',
            'cart'        => Cart::toArray($shipping),
            'shipping'    => $shipping,
            'freeShipMin' => $freeMin,
        ]);
    }

    public function add(): void
    {
        $productId = (int)$this->input('product_id');
        $quantity  = max(1, (int)$this->input('quantity', 1));

        if (!$productId) {
            $this->json(['success' => false, 'message' => 'Producto inválido.'], 400);
        }

        $productModel = new Product();
        $product      = $productModel->findById($productId);

        if (!$product || !$product['available']) {
            $this->json(['success' => false, 'message' => 'Producto no disponible.'], 404);
        }

        Cart::add($product, $quantity);

        $this->json([
            'success'   => true,
            'message'   => '¡Agregado al carrito!',
            'cartCount' => Cart::count(),
            'subtotal'  => Cart::subtotal(),
        ]);
    }

    public function update(): void
    {
        $productId = (int)$this->input('product_id');
        $quantity  = (int)$this->input('quantity', 0);

        Cart::update($productId, $quantity);

        $settingModel = new Setting();
        $shipping     = (float)$settingModel->get('shipping_cost', 0);
        $freeMin      = (float)$settingModel->get('free_shipping_min', 0);
        $subtotal     = Cart::subtotal();

        if ($freeMin > 0 && $subtotal >= $freeMin) {
            $shipping = 0;
        }

        $this->json([
            'success'   => true,
            'cartCount' => Cart::count(),
            'subtotal'  => $subtotal,
            'shipping'  => $shipping,
            'total'     => $subtotal + $shipping,
        ]);
    }

    public function remove(): void
    {
        $productId = (int)$this->input('product_id');
        Cart::remove($productId);

        $this->json([
            'success'   => true,
            'cartCount' => Cart::count(),
            'subtotal'  => Cart::subtotal(),
        ]);
    }

    public function count(): void
    {
        $this->json(['count' => Cart::count()]);
    }
}
