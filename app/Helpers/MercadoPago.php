<?php
namespace App\Helpers;

/**
 * Wrapper para Mercado Pago SDK v2 (compatible con PHP 8.0)
 * SDK: mercadopago/dx-php ^2.5
 */
class MercadoPago
{
    private static bool $initialized = false;

    public static function init(): void
    {
        if (self::$initialized) return;

        if (!class_exists('\MercadoPago\SDK')) {
            return; // SDK no disponible
        }

        \MercadoPago\SDK::setAccessToken(MP_ACCESS_TOKEN);
        self::$initialized = true;
    }

    /**
     * Crear preferencia de pago en MP.
     * Retorna ['init_point' => url, 'preference_id' => id]
     */
    public static function createPreference(array $order, array $items): array
    {
        self::init();

        if (!self::$initialized || empty(MP_ACCESS_TOKEN) || str_contains(MP_ACCESS_TOKEN, 'YOUR')) {
            // Modo demo sin SDK o sin credenciales
            return [
                'init_point'    => BASE_URL . '/pedido/success?external_reference=' . urlencode($order['order_number']) . '&demo=1&order=' . urlencode($order['order_number']),
                'preference_id' => 'DEMO-' . uniqid(),
            ];
        }

        $preference = new \MercadoPago\Preference();

        $mpItems = [];
        foreach ($items as $item) {
            $mpItem              = new \MercadoPago\Item();
            $mpItem->id          = (string)($item['product_id'] ?? 'prod');
            $mpItem->title       = $item['product_name'];
            $mpItem->quantity    = (int)$item['quantity'];
            $mpItem->unit_price  = (float)$item['unit_price'];
            $mpItem->currency_id = 'ARS';
            $mpItems[]           = $mpItem;
        }

        // Envío como ítem separado
        if ((float)$order['shipping_cost'] > 0) {
            $shipping             = new \MercadoPago\Item();
            $shipping->id         = 'shipping';
            $shipping->title      = 'Envío a domicilio';
            $shipping->quantity   = 1;
            $shipping->unit_price = (float)$order['shipping_cost'];
            $shipping->currency_id= 'ARS';
            $mpItems[]            = $shipping;
        }

        $preference->items = $mpItems;

        // Pagador
        $payer         = new \MercadoPago\Payer();
        $payer->name   = $order['customer_name'];
        $payer->email  = $order['customer_email'];
        $payer->phone  = ['number' => $order['customer_phone'] ?? ''];
        $preference->payer = $payer;

        // URLs de retorno
        $preference->back_urls = [
            'success' => BASE_URL . '/pedido/success',
            'failure' => BASE_URL . '/pedido/failure',
            'pending' => BASE_URL . '/pedido/pending',
        ];
        $preference->auto_return         = 'approved';
        $preference->notification_url    = BASE_URL . '/webhook/mercadopago';
        $preference->external_reference  = $order['order_number'];
        $preference->statement_descriptor= 'COOKIE BAKERY';

        $preference->save();

        return [
            'init_point'    => $preference->init_point,
            'preference_id' => $preference->id,
        ];
    }

    /**
     * Obtener info de un pago por ID
     */
    public static function getPayment(string $paymentId): ?array
    {
        self::init();
        if (!self::$initialized) return null;

        try {
            $payment = \MercadoPago\Payment::find_by_id((int)$paymentId);
            if (!$payment) return null;

            return [
                'id'             => $payment->id,
                'status'         => $payment->status,
                'status_detail'  => $payment->status_detail,
                'amount'         => $payment->transaction_amount,
                'external_ref'   => $payment->external_reference,
                'payer_email'    => $payment->payer->email ?? null,
            ];
        } catch (\Exception $e) {
            error_log('MP getPayment error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar firma del webhook
     */
    public static function verifyWebhook(string $xSignature, string $xRequestId, string $dataId): bool
    {
        if (!MP_WEBHOOK_SECRET) return true;

        $manifest = "id:{$dataId};request-id:{$xRequestId};";
        $hash     = hash_hmac('sha256', $manifest, MP_WEBHOOK_SECRET);
        $parts    = [];
        foreach (explode(',', $xSignature) as $part) {
            $kv = array_map('trim', explode('=', trim($part), 2));
            if (count($kv) === 2) $parts[$kv[0]] = $kv[1];
        }
        return isset($parts['v1']) && hash_equals($hash, $parts['v1']);
    }

    /**
     * Mapear status de MP a status interno
     */
    public static function mapStatus(string $mpStatus): string
    {
        return match($mpStatus) {
            'approved'     => 'paid',
            'rejected'     => 'cancelled',
            'refunded'     => 'refunded',
            'charged_back' => 'refunded',
            default        => 'pending',
        };
    }
}
