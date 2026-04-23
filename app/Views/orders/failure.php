<section class="order-result py-5">
  <div class="container">
    <div class="text-center" style="max-width:500px;margin:0 auto">
      <div class="order-result-icon">😔</div>
      <h1 class="mb-3">Pago no procesado</h1>
      <p class="lead text-muted mb-4">
        Hubo un problema al procesar tu pago. No se realizó ningún cargo.
      </p>
      <?php if (!empty($order)): ?>
      <p class="text-muted small">Pedido de referencia: <strong><?= htmlspecialchars($order['order_number']) ?></strong></p>
      <?php endif; ?>
      <div class="d-flex gap-3 justify-content-center mt-4">
        <a href="<?= BASE_URL ?>/carrito" class="btn btn-orange btn-lg">
          <i class="bi bi-arrow-repeat me-2"></i>Intentar de nuevo
        </a>
        <a href="<?= BASE_URL ?>/contacto" class="btn btn-gold-outline btn-lg">Contactarnos</a>
      </div>
    </div>
  </div>
</section>
