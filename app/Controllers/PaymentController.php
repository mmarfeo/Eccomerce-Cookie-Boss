<?php
namespace App\Controllers;

use App\Helpers\MercadoPago;
use App\Helpers\Mailer;
use App\Helpers\Loyalty;
use App\Models\Order;

class PaymentController extends BaseController
{
    public function success(): void
    {
        $paymentId   = $this->input('payment_id', '');
        $externalRef = $this->input('external_reference', '');
        $demo        = $this->input('demo', '');
        $orderNumber = $this->input('order', $externalRef);

        $orderModel = new Order();
        $order      = null;

        if ($orderNumber) {
            $order = $orderModel->findByOrderNumber($orderNumber);
        }

        // Modo demo
        if ($demo && $order && $order['status'] === 'pending') {
            $orderModel->updateStatus($order['id'], 'paid', 'Pago demo/test', 'system');
            $order = $orderModel->findById($order['id']);
            $items = $orderModel->getItems($order['id']);
            $this->onPaymentSuccess($order, $items, $orderModel);
        }

        // Verificar con MP si hay payment_id
        if ($paymentId && $order) {
            $payment = MercadoPago::getPayment($paymentId);
            if ($payment) {
                $internalStatus = MercadoPago::mapStatus($payment['status']);
                $orderModel->updateMercadoPago($order['id'], [
                    'preference_id'    => $order['mp_preference_id'],
                    'payment_id'       => $payment['id'],
                    'mp_status'        => $payment['status'],
                    'mp_status_detail' => $payment['status_detail'],
                    'status'           => $internalStatus,
                ]);
                $order = $orderModel->findById($order['id']);
                if ($internalStatus === 'paid') {
                    $items = $orderModel->getItems($order['id']);
                    $this->onPaymentSuccess($order, $items, $orderModel);
                }
            }
        }

        $this->view('orders/success', [
            'pageTitle' => '¡Pedido confirmado!',
            'order'     => $order,
            'items'     => $order ? $orderModel->getItems($order['id']) : [],
        ]);
    }

    public function failure(): void
    {
        $externalRef = $this->input('external_reference', '');
        $orderModel  = new Order();
        $order       = $externalRef ? $orderModel->findByOrderNumber($externalRef) : null;

        if ($order && $order['status'] === 'pending') {
            $orderModel->updateStatus($order['id'], 'cancelled', 'Pago rechazado por MP', 'system');
        }

        $this->view('orders/failure', ['pageTitle' => 'Pago no procesado', 'order' => $order]);
    }

    public function pending(): void
    {
        $externalRef = $this->input('external_reference', '');
        $orderModel  = new Order();
        $order       = $externalRef ? $orderModel->findByOrderNumber($externalRef) : null;

        $this->view('orders/pending', ['pageTitle' => 'Pago en proceso', 'order' => $order]);
    }

    public function confirmation(): void
    {
        $orderNumber = $this->input('numero', '');
        $orderModel  = new Order();
        $order       = $orderNumber ? $orderModel->findByOrderNumber($orderNumber) : null;

        if (!$order) { $this->redirect(''); }

        $this->view('orders/success', [
            'pageTitle' => '¡Pedido confirmado!',
            'order'     => $order,
            'items'     => $orderModel->getItems($order['id']),
        ]);
    }

    public function webhook(): void
    {
        $body   = file_get_contents('php://input');
        $data   = json_decode($body, true) ?? [];
        $type   = $data['type'] ?? ($_GET['topic'] ?? '');
        $dataId = $data['data']['id'] ?? ($_GET['id'] ?? '');

        $xSig  = $_SERVER['HTTP_X_SIGNATURE']  ?? '';
        $xReqId= $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
        if ($xSig && !MercadoPago::verifyWebhook($xSig, $xReqId, $dataId)) {
            http_response_code(401); exit('Firma inválida');
        }

        if (!in_array($type, ['payment', 'merchant_order']) || !$dataId) {
            http_response_code(200); exit('OK');
        }

        $payment = MercadoPago::getPayment($dataId);
        if (!$payment) { http_response_code(200); exit('OK'); }

        $orderModel = new Order();
        $order      = $orderModel->findByOrderNumber($payment['external_ref'] ?? '');

        if ($order) {
            $internalStatus = MercadoPago::mapStatus($payment['status']);
            $orderModel->updateMercadoPago($order['id'], [
                'preference_id'    => $order['mp_preference_id'],
                'payment_id'       => $payment['id'],
                'mp_status'        => $payment['status'],
                'mp_status_detail' => $payment['status_detail'],
                'status'           => $internalStatus,
            ]);
            if ($internalStatus === 'paid' && $order['status'] !== 'paid') {
                $order = $orderModel->findById($order['id']);
                $items = $orderModel->getItems($order['id']);
                $this->onPaymentSuccess($order, $items, $orderModel);
            }
        }

        http_response_code(200); echo 'OK';
    }

    // ── Acciones post-pago exitoso ─────────────────────────────
    private function onPaymentSuccess(array $order, array $items, Order $orderModel): void
    {
        // Acumular puntos de fidelidad
        if (class_exists('\App\Helpers\Loyalty')) {
            $points = Loyalty::earnPoints($order);
            if ($points > 0) {
                $orderModel->updatePoints($order['id'], $points);
            }
        }

        // Email de confirmación al cliente
        try {
            $mailer = new Mailer();
            $mailer->orderConfirmation($order, $items);
            $mailer->adminNewOrder($order, $items);
        } catch (\Throwable $e) {
            error_log('[Mailer] Error en confirmación: ' . $e->getMessage());
        }
    }
}
