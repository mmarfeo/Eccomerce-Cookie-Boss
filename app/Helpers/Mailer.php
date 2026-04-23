<?php
namespace App\Helpers;

use App\Models\Setting;

/**
 * Mailer — Envío de emails con soporte SMTP opcional (PHPMailer-compatible via mail())
 * Para producción: instalar phpmailer/phpmailer y activar SMTP en config de admin.
 */
class Mailer
{
    private string $fromName;
    private string $fromEmail;
    private bool   $smtpEnabled;
    private array  $settings;

    public function __construct()
    {
        $m = new Setting();
        $this->settings    = $m->all();
        $this->fromName    = $this->settings['mail_from_name']  ?? APP_NAME;
        $this->fromEmail   = $this->settings['mail_from_email'] ?? 'noreply@cookiebakery.com';
        $this->smtpEnabled = ($this->settings['mail_smtp_enabled'] ?? '0') === '1';
    }

    // ── API pública ────────────────────────────────────────────

    public function orderConfirmation(array $order, array $items): bool
    {
        $subject = "✅ Pedido #{$order['order_number']} confirmado — " . ($this->settings['store_name'] ?? APP_NAME);
        $html    = $this->buildOrderEmail($order, $items);
        return $this->send($order['customer_email'], $order['customer_name'], $subject, $html);
    }

    public function orderStatusUpdate(array $order, string $newStatus): bool
    {
        $label   = Formatter::statusLabel($newStatus);
        $subject = "📦 Tu pedido #{$order['order_number']} está: {$label}";
        $html    = $this->buildStatusEmail($order, $newStatus, $label);
        return $this->send($order['customer_email'], $order['customer_name'], $subject, $html);
    }

    public function passwordReset(string $email, string $name, string $resetUrl): bool
    {
        $subject = "🔑 Recuperar contraseña — " . ($this->settings['store_name'] ?? APP_NAME);
        $html    = $this->buildPasswordResetEmail($name, $resetUrl);
        return $this->send($email, $name, $subject, $html);
    }

    public function adminNewOrder(array $order, array $items): bool
    {
        $adminEmail = $this->settings['store_email'] ?? $this->fromEmail;
        $subject    = "🛒 Nuevo pedido #{$order['order_number']} — \${$order['total']}";
        $html       = $this->buildOrderEmail($order, $items, isAdmin: true);
        return $this->send($adminEmail, $this->fromName, $subject, $html);
    }

    // ── Core send ─────────────────────────────────────────────

    public function send(string $toEmail, string $toName, string $subject, string $html): bool
    {
        try {
            $headers  = $this->buildHeaders($toEmail, $toName);
            $plainText = strip_tags(preg_replace('/<br\s*\/?>/', "\n", $html));

            // Intentar SMTP via PHPMailer si está instalado y habilitado
            if ($this->smtpEnabled && class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
                return $this->sendSmtp($toEmail, $toName, $subject, $html, $plainText);
            }

            // Fallback: mail() nativo de PHP
            $to      = $this->encodeRFC2047($toName) . " <{$toEmail}>";
            $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            return mail($to, $subject, $html, $headers);

        } catch (\Throwable $e) {
            error_log("[Mailer] Error enviando a {$toEmail}: " . $e->getMessage());
            return false;
        }
    }

    // ── Templates ─────────────────────────────────────────────

    private function buildOrderEmail(array $order, array $items, bool $isAdmin = false): string
    {
        $storeName   = htmlspecialchars($this->settings['store_name'] ?? APP_NAME);
        $primaryColor = $this->settings['brand_color_primary'] ?? '#bf9663';
        $orderUrl    = BASE_URL . '/pedido/confirmacion?numero=' . urlencode($order['order_number']);
        $adminUrl    = BASE_URL . '/admin/pedidos/ver?id=' . $order['id'];

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= "
            <tr>
              <td style='padding:8px 0;border-bottom:1px solid #f0f0f0'>
                <strong>{$item['product_name']}</strong>
                <span style='color:#888;margin-left:8px'>x{$item['quantity']}</span>
              </td>
              <td style='padding:8px 0;border-bottom:1px solid #f0f0f0;text-align:right;font-weight:700'>
                " . Formatter::price((float)$item['subtotal']) . "
              </td>
            </tr>";
        }

        $delivery = $order['delivery_method'] === 'pickup' ? '🏪 Retiro en local' : '🚚 Envío a domicilio';
        $greeting = $isAdmin
            ? "Nuevo pedido de <strong>{$order['customer_name']}</strong>"
            : "¡Hola, <strong>" . htmlspecialchars($order['customer_name']) . "</strong>!";

        $ctaUrl  = $isAdmin ? $adminUrl  : $orderUrl;
        $ctaText = $isAdmin ? 'Ver pedido en admin →' : 'Ver mi pedido →';

        ob_start(); ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>body{margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;background:#f5f5f5;color:#333}</style>
</head><body>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:30px 0">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;max-width:600px;width:100%">
      <!-- Header -->
      <tr><td style="background:<?= $primaryColor ?>;padding:32px 40px;text-align:center">
        <h1 style="margin:0;color:#fff;font-size:28px;font-weight:800;letter-spacing:-0.5px">
          <?= $storeName ?>
        </h1>
        <p style="margin:8px 0 0;color:rgba(255,255,255,0.85);font-size:14px">New York Style Cookies</p>
      </td></tr>

      <!-- Body -->
      <tr><td style="padding:40px">
        <p style="font-size:16px;margin:0 0 24px"><?= $greeting ?></p>
        <p style="color:#555;margin:0 0 24px;line-height:1.6">
          <?= $isAdmin
            ? "Se registró un nuevo pedido en la tienda."
            : "Tu pedido fue confirmado. ¡Ya estamos preparando tus cookies! 🍪" ?>
        </p>

        <!-- Detalles del pedido -->
        <div style="background:#fdf8f2;border:1px solid #e8ddd0;border-radius:12px;padding:24px;margin-bottom:24px">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td><strong style="font-size:13px;text-transform:uppercase;letter-spacing:1px;color:<?= $primaryColor ?>">Pedido</strong><br>
                <span style="font-size:18px;font-weight:800">#<?= htmlspecialchars($order['order_number']) ?></span></td>
              <td align="right"><strong style="font-size:13px;text-transform:uppercase;letter-spacing:1px;color:<?= $primaryColor ?>">Entrega</strong><br>
                <span><?= $delivery ?></span></td>
            </tr>
          </table>
        </div>

        <!-- Items -->
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px">
          <?= $itemsHtml ?>
        </table>

        <!-- Totales -->
        <table width="100%" cellpadding="0" cellspacing="0" style="border-top:2px solid #e8ddd0;padding-top:16px">
          <?php if ((float)$order['shipping_cost'] > 0): ?>
          <tr>
            <td style="padding:4px 0;color:#888">Envío</td>
            <td style="padding:4px 0;text-align:right;color:#888"><?= Formatter::price((float)$order['shipping_cost']) ?></td>
          </tr>
          <?php endif; ?>
          <?php if ((float)($order['discount'] ?? 0) > 0): ?>
          <tr>
            <td style="padding:4px 0;color:#28a745">Descuento</td>
            <td style="padding:4px 0;text-align:right;color:#28a745">-<?= Formatter::price((float)$order['discount']) ?></td>
          </tr>
          <?php endif; ?>
          <?php if ((float)($order['coupon_discount'] ?? 0) > 0): ?>
          <tr>
            <td style="padding:4px 0;color:#28a745">Cupón (<?= htmlspecialchars($order['coupon_code'] ?? '') ?>)</td>
            <td style="padding:4px 0;text-align:right;color:#28a745">-<?= Formatter::price((float)$order['coupon_discount']) ?></td>
          </tr>
          <?php endif; ?>
          <tr>
            <td style="padding:12px 0 0;font-size:18px;font-weight:800">TOTAL</td>
            <td style="padding:12px 0 0;text-align:right;font-size:20px;font-weight:800;color:<?= $primaryColor ?>"><?= Formatter::price((float)$order['total']) ?></td>
          </tr>
          <?php if (!empty($order['points_earned'])): ?>
          <tr>
            <td colspan="2" style="padding:8px 0 0">
              <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:10px;font-size:13px;color:#856404">
                ⭐ Ganaste <strong><?= $order['points_earned'] ?> puntos</strong> de fidelidad con este pedido.
              </div>
            </td>
          </tr>
          <?php endif; ?>
        </table>

        <!-- CTA -->
        <div style="text-align:center;margin-top:32px">
          <a href="<?= $ctaUrl ?>" style="display:inline-block;background:<?= $primaryColor ?>;color:#fff;text-decoration:none;padding:14px 32px;border-radius:50px;font-weight:700;font-size:15px">
            <?= $ctaText ?>
          </a>
        </div>
      </td></tr>

      <!-- Footer -->
      <tr><td style="background:#f5f5f5;padding:24px 40px;text-align:center;border-top:1px solid #e9ecef">
        <p style="margin:0;font-size:13px;color:#888">
          <?= $storeName ?> · Buenos Aires, Argentina
        </p>
        <p style="margin:8px 0 0;font-size:12px;color:#aaa">
          Este email fue enviado automáticamente. Por favor no respondas directamente.
        </p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
        <?php
        return ob_get_clean();
    }

    private function buildStatusEmail(array $order, string $status, string $label): string
    {
        $storeName    = htmlspecialchars($this->settings['store_name'] ?? APP_NAME);
        $primaryColor = $this->settings['brand_color_primary'] ?? '#bf9663';

        $icons = [
            'processing' => '👨‍🍳',
            'ready'      => '✅',
            'delivered'  => '🎉',
            'cancelled'  => '❌',
        ];
        $icon = $icons[$status] ?? '📦';

        $messages = [
            'processing' => '¡Estamos preparando tus cookies con mucho amor!',
            'ready'      => 'Tu pedido está listo. ¡Podés pasar a buscarlo!',
            'delivered'  => '¡Esperamos que lo hayas disfrutado! Dejanos tu reseña.',
            'cancelled'  => 'Tu pedido fue cancelado. Contactanos si tenés dudas.',
        ];
        $message = $messages[$status] ?? 'Tu pedido fue actualizado.';

        return "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='font-family:Arial,sans-serif;background:#f5f5f5;padding:30px 0'>
<table width='100%' cellpadding='0' cellspacing='0'><tr><td align='center'>
<table width='560' style='background:#fff;border-radius:16px;overflow:hidden;max-width:560px'>
  <tr><td style='background:{$primaryColor};padding:28px 32px;text-align:center'>
    <h2 style='margin:0;color:#fff;font-size:22px'>{$storeName}</h2>
  </td></tr>
  <tr><td style='padding:40px;text-align:center'>
    <div style='font-size:56px;margin-bottom:16px'>{$icon}</div>
    <h2 style='font-size:22px;font-weight:800;margin:0 0 12px'>Pedido #{$order['order_number']}</h2>
    <div style='display:inline-block;background:#fdf8f2;border:2px solid {$primaryColor};border-radius:50px;padding:8px 24px;font-weight:700;color:{$primaryColor};font-size:16px;margin-bottom:20px'>{$label}</div>
    <p style='color:#555;font-size:15px;line-height:1.6'>{$message}</p>
    <a href='" . BASE_URL . "/pedido/confirmacion?numero={$order['order_number']}' style='display:inline-block;background:{$primaryColor};color:#fff;text-decoration:none;padding:12px 28px;border-radius:50px;font-weight:700;font-size:14px;margin-top:16px'>Ver mi pedido</a>
  </td></tr>
  <tr><td style='background:#f5f5f5;padding:20px;text-align:center;font-size:12px;color:#aaa'>{$storeName} · Buenos Aires, Argentina</td></tr>
</table></td></tr></table>
</body></html>";
    }

    private function buildPasswordResetEmail(string $name, string $resetUrl): string
    {
        $storeName    = htmlspecialchars($this->settings['store_name'] ?? APP_NAME);
        $primaryColor = $this->settings['brand_color_primary'] ?? '#bf9663';

        return "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='font-family:Arial,sans-serif;background:#f5f5f5;padding:30px 0'>
<table width='100%' cellpadding='0' cellspacing='0'><tr><td align='center'>
<table width='520' style='background:#fff;border-radius:16px;overflow:hidden;max-width:520px'>
  <tr><td style='background:{$primaryColor};padding:28px;text-align:center'>
    <h2 style='margin:0;color:#fff;font-size:22px'>{$storeName}</h2>
  </td></tr>
  <tr><td style='padding:40px'>
    <div style='font-size:56px;text-align:center;margin-bottom:20px'>🔑</div>
    <p style='font-size:16px;margin-bottom:16px'>Hola, <strong>" . htmlspecialchars($name) . "</strong></p>
    <p style='color:#555;line-height:1.6;margin-bottom:24px'>
      Recibimos una solicitud para restablecer tu contraseña del panel admin.
      Hacé click en el botón de abajo. El link expira en <strong>1 hora</strong>.
    </p>
    <div style='text-align:center;margin:24px 0'>
      <a href='{$resetUrl}' style='display:inline-block;background:{$primaryColor};color:#fff;text-decoration:none;padding:14px 32px;border-radius:50px;font-weight:700;font-size:15px'>
        Restablecer contraseña →
      </a>
    </div>
    <p style='font-size:12px;color:#999;border-top:1px solid #f0f0f0;padding-top:16px;margin-top:24px'>
      Si no solicitaste este cambio, ignorá este email. Tu contraseña no será modificada.
      <br><br>Link directo: {$resetUrl}
    </p>
  </td></tr>
  <tr><td style='background:#f5f5f5;padding:20px;text-align:center;font-size:12px;color:#aaa'>{$storeName} · Panel Admin</td></tr>
</table></td></tr></table>
</body></html>";
    }

    // ── Helpers internos ──────────────────────────────────────

    private function buildHeaders(string $toEmail, string $toName): string
    {
        $from = $this->encodeRFC2047($this->fromName) . " <{$this->fromEmail}>";
        return implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            "From: {$from}",
            "Reply-To: {$this->fromEmail}",
            'X-Mailer: CookieBakery/1.0',
        ]);
    }

    private function encodeRFC2047(string $name): string
    {
        return '=?UTF-8?B?' . base64_encode($name) . '?=';
    }

    private function sendSmtp(string $toEmail, string $toName, string $subject, string $html, string $plain): bool
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $this->settings['mail_smtp_host'] ?? '';
        $mail->Port       = (int)($this->settings['mail_smtp_port'] ?? 587);
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->settings['mail_smtp_user'] ?? '';
        $mail->Password   = $this->settings['mail_smtp_pass'] ?? '';
        $mail->SMTPSecure = 'tls';
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($this->fromEmail, $this->fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $plain;
        return $mail->send();
    }
}
