<?php
namespace App\Controllers;

use App\Core\Csrf;
use App\Core\CustomerAuth;
use App\Core\Session;
use App\Core\Database;
use App\Helpers\Cart;
use App\Helpers\Loyalty;
use App\Helpers\MercadoPago;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Setting;

class CheckoutController extends BaseController
{
    public function index(): void
    {
        if (Cart::isEmpty()) {
            Session::flash('error', 'Tu carrito está vacío.');
            $this->redirect('carrito');
        }

        $settingModel = new Setting();
        $shipping     = $this->getShipping($settingModel);
        $subtotal     = Cart::subtotal();
        $coupon       = Session::get('checkout_coupon');
        $pointsRedeem = (int)Session::get('checkout_points_redeem', 0);
        $loyaltyConfig= Loyalty::config();

        // Si el cliente está logueado, usar sus datos; si no, usar sesión previa
        $loggedCustomer = null;
        $customerPoints = 0;
        if (CustomerAuth::check()) {
            $cModel         = new Customer();
            $loggedCustomer = $cModel->findById(CustomerAuth::id());
            $customerPoints = (int)($loggedCustomer['loyalty_points'] ?? 0);
        } elseif ($email = Session::get('checkout_email', '')) {
            $db = Database::getInstance();
            $c  = $db->prepare("SELECT loyalty_points FROM customers WHERE email = ?");
            $c->execute([$email]);
            $row = $c->fetch();
            $customerPoints = (int)($row['loyalty_points'] ?? 0);
        }

        $this->view('checkout/index', [
            'pageTitle'      => 'Finalizar pedido',
            'cart'           => Cart::toArray($shipping),
            'shipping'       => $shipping,
            'freeShipMin'    => (float)$settingModel->get('free_shipping_min', 0),
            'coupon'         => $coupon,
            'pointsRedeem'   => $pointsRedeem,
            'customerPoints' => $customerPoints,
            'loyaltyConfig'  => $loyaltyConfig,
            'loggedCustomer' => $loggedCustomer,
        ]);
    }

    public function applyCoupon(): void
    {
        $code   = strtoupper(trim($this->input('coupon_code', '')));
        $model  = new Coupon();
        $coupon = $model->findByCode($code);

        if (!$coupon) {
            $this->json(['success' => false, 'message' => 'Cupón inválido o vencido.']);
        }

        $subtotal = Cart::subtotal();
        $discount = $model->calculateDiscount($coupon, $subtotal);

        if ($discount <= 0) {
            $this->json(['success' => false, 'message' => "Pedido mínimo para este cupón: $" . number_format($coupon['min_order'], 0, ',', '.')]);
        }

        Session::set('checkout_coupon', [
            'id'       => $coupon['id'],
            'code'     => $coupon['code'],
            'discount' => $discount,
            'type'     => $coupon['type'],
            'value'    => $coupon['value'],
        ]);
        Session::remove('checkout_points_redeem'); // no combinar

        $this->json([
            'success'  => true,
            'message'  => "¡Cupón aplicado! Descuento: $" . number_format($discount, 0, ',', '.'),
            'discount' => $discount,
            'code'     => $coupon['code'],
        ]);
    }

    public function applyPoints(): void
    {
        $email  = trim($this->input('email', ''));
        $points = (int)$this->input('points', 0);

        if (!$email || !$points) {
            $this->json(['success' => false, 'message' => 'Datos incompletos.']);
        }

        $db       = Database::getInstance();
        $customer = $db->query("SELECT * FROM customers WHERE email = ?", [$email])->fetch();

        if (!$customer || (int)$customer['loyalty_points'] < $points) {
            $this->json(['success' => false, 'message' => 'No tenés suficientes puntos.']);
        }

        $redeem = Loyalty::calculateRedeem((int)$customer['id'], $points);

        if (!$redeem['valid']) {
            $cfg = Loyalty::config();
            $this->json(['success' => false, 'message' => "Mínimo {$cfg['min_redeem']} puntos para canjear."]);
        }

        Session::set('checkout_points_redeem', $redeem['points']);
        Session::set('checkout_points_customer_id', $customer['id']);
        Session::set('checkout_email', $email);
        Session::remove('checkout_coupon'); // no combinar

        $this->json([
            'success'  => true,
            'message'  => "¡{$redeem['points']} puntos canjeados! Descuento: $" . number_format($redeem['discount'], 0, ',', '.'),
            'discount' => $redeem['discount'],
            'points'   => $redeem['points'],
        ]);
    }

    public function process(): void
    {
        if (!$this->isPost()) { $this->redirect('checkout'); }
        Csrf::verifyOrFail();

        if (Cart::isEmpty()) {
            Session::flash('error', 'Tu carrito está vacío.');
            $this->redirect('carrito');
        }

        // Datos del cliente
        $name           = trim($this->input('name', ''));
        $email          = trim($this->input('email', ''));
        $phone          = trim($this->input('phone', ''));
        $address        = trim($this->input('address', ''));
        $city           = trim($this->input('city', ''));
        $notes          = trim($this->input('notes', ''));
        $deliveryMethod = $this->input('delivery_method', 'pickup');

        $errors = [];
        if (strlen($name) < 2)                              $errors[] = 'El nombre es obligatorio.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = 'El email no es válido.';
        if (strlen($phone) < 6)                             $errors[] = 'El teléfono es obligatorio.';
        if ($deliveryMethod === 'delivery' && strlen($address) < 5)
                                                             $errors[] = 'La dirección es obligatoria para envío.';

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect('checkout');
        }

        // Calcular totales
        $settingModel  = new Setting();
        $shippingCost  = $this->getShipping($settingModel);
        $subtotal      = Cart::subtotal();

        if ($deliveryMethod === 'pickup') $shippingCost = 0;

        $discount         = 0.0;
        $couponDiscount   = 0.0;
        $couponCode       = null;
        $pointsRedeemed   = 0;
        $couponData       = Session::get('checkout_coupon');
        $pointsRedeem     = (int)Session::get('checkout_points_redeem', 0);
        $pointsCustomerId = (int)Session::get('checkout_points_customer_id', 0);

        if ($couponData) {
            $couponDiscount = (float)$couponData['discount'];
            $couponCode     = $couponData['code'];
            $discount       = $couponDiscount;
        } elseif ($pointsRedeem > 0 && $pointsCustomerId) {
            $redeem   = Loyalty::calculateRedeem($pointsCustomerId, $pointsRedeem);
            if ($redeem['valid']) {
                $discount       = $redeem['discount'];
                $pointsRedeemed = $redeem['points'];
            }
        }

        $total = max(0, $subtotal + $shippingCost - $discount);

        // Crear/actualizar cliente
        $customerModel = new Customer();
        $customerId    = $customerModel->findOrCreate([
            'name'        => $name,
            'email'       => $email,
            'phone'       => $phone,
            'address'     => $address,
            'city'        => $city,
            'order_total' => $total,
        ]);

        // Crear pedido
        $orderModel  = new Order();
        $orderNumber = $orderModel->generateOrderNumber();
        $orderId     = $orderModel->create([
            'order_number'     => $orderNumber,
            'customer_id'      => $customerId,
            'customer_name'    => $name,
            'customer_email'   => $email,
            'customer_phone'   => $phone,
            'customer_address' => $address,
            'customer_city'    => $city,
            'customer_notes'   => $notes,
            'subtotal'         => $subtotal,
            'shipping_cost'    => $shippingCost,
            'discount'         => $discount,
            'total'            => $total,
            'status'           => 'pending',
            'delivery_method'  => $deliveryMethod,
        ]);

        // Guardar cupón si aplica
        if ($couponCode) {
            $orderModel->updateCoupon($orderId, $couponCode, $couponDiscount);
            $couponModel = new Coupon();
            $couponModel->incrementUsage($couponData['id']);
        }

        // Registrar canje de puntos
        if ($pointsRedeemed > 0 && $pointsCustomerId) {
            Loyalty::redeemPoints($pointsCustomerId, $orderId, $pointsRedeemed, $discount);
        }

        // Guardar items
        foreach (Cart::get() as $item) {
            $orderModel->addItem($orderId, [
                'product_id' => $item['id'],
                'name'       => $item['name'],
                'image'      => $item['image'],
                'price'      => $item['price'],
                'quantity'   => $item['quantity'],
            ]);
        }

        // Limpiar sesión de checkout
        Session::remove('checkout_coupon');
        Session::remove('checkout_points_redeem');
        Session::remove('checkout_points_customer_id');

        // Crear preferencia MP
        $order = $orderModel->findById($orderId);
        $items = $orderModel->getItems($orderId);

        try {
            $mp = MercadoPago::createPreference($order, $items);
            $orderModel->updateMercadoPago($orderId, [
                'preference_id'    => $mp['preference_id'],
                'payment_id'       => null,
                'mp_status'        => null,
                'mp_status_detail' => null,
                'status'           => 'pending',
            ]);
            Cart::clear();
            header('Location: ' . $mp['init_point']);
            exit;
        } catch (\Exception $e) {
            error_log('MP error: ' . $e->getMessage());
            Session::flash('error', 'Error al procesar el pago. Intentá nuevamente.');
            $this->redirect('checkout');
        }
    }

    private function getShipping(Setting $settingModel): float
    {
        $cost    = (float)$settingModel->get('shipping_cost', 0);
        $freeMin = (float)$settingModel->get('free_shipping_min', 0);
        if ($freeMin > 0 && Cart::subtotal() >= $freeMin) return 0;
        return $cost;
    }
}
