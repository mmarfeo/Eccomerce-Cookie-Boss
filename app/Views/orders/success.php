<?php use App\Helpers\Formatter; ?>

<section class="order-result py-5">
  <div class="container">
    <div class="text-center" style="max-width:600px;margin:0 auto">
      <div class="order-result-icon">🎉</div>
      <h1 class="section-title d-inline-block mb-3">¡Pedido confirmado!</h1>
      <p class="lead text-muted mb-4">
        Gracias por tu compra. ¡Ya estamos preparando tus cookies!
      </p>

      <?php if (!empty($order)): ?>
      <div class="checkout-card mb-4" style="text-align:left">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-700 mb-0">Pedido #<?= htmlspecialchars($order['order_number']) ?></h5>
          <span class="badge" style="background:var(--orange);font-size:0.8rem">
            <?= $order['status'] === 'paid' ? '✓ Pagado' : 'Pendiente de pago' ?>
          </span>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-sm-6">
            <div class="small text-muted">Cliente</div>
            <div class="fw-semibold"><?= htmlspecialchars($order['customer_name']) ?></div>
          </div>
          <div class="col-sm-6">
            <div class="small text-muted">Email</div>
            <div class="fw-semibold"><?= htmlspecialchars($order['customer_email']) ?></div>
          </div>
          <div class="col-sm-6">
            <div class="small text-muted">Entrega</div>
            <div class="fw-semibold">
              <?= $order['delivery_method'] === 'pickup' ? '🏪 Retiro en local' : '🚚 Envío a domicilio' ?>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="small text-muted">Total</div>
            <div class="fw-700 text-gold" style="font-size:1.1rem">
              <?= Formatter::price((float)$order['total']) ?>
            </div>
          </div>
        </div>

        <?php if (!empty($items)): ?>
        <hr style="border-color:var(--border)">
        <h6 class="fw-700 mb-3">Items del pedido</h6>
        <?php foreach ($items as $item): ?>
        <div class="d-flex justify-content-between align-items-center mb-2 small">
          <span><?= htmlspecialchars($item['product_name']) ?> × <?= $item['quantity'] ?></span>
          <span class="fw-semibold"><?= Formatter::price((float)$item['subtotal']) ?></span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="alert" style="background:rgba(191,150,99,0.1);border:1px solid var(--gold);border-radius:12px">
        <i class="bi bi-whatsapp me-2 text-gold" style="font-size:1.2rem"></i>
        <strong>¿Dudas?</strong> Escribinos por WhatsApp o Instagram para consultar sobre tu pedido.
      </div>

      <div class="d-flex gap-3 justify-content-center mt-4">
        <a href="<?= BASE_URL ?>/productos" class="btn btn-gold btn-lg">
          <i class="bi bi-bag-heart me-2"></i>Seguir comprando
        </a>
        <a href="<?= BASE_URL ?>/" class="btn btn-gold-outline btn-lg">Ir al inicio</a>
      </div>
    </div>
  </div>
</section>
