<section class="order-result py-5">
  <div class="container">
    <div class="text-center" style="max-width:500px;margin:0 auto">
      <div class="order-result-icon">⏳</div>
      <h1 class="mb-3">Pago en proceso</h1>
      <p class="lead text-muted mb-4">
        Tu pago está siendo verificado. Te avisaremos por email cuando se confirme.
      </p>
      <?php if (!empty($order)): ?>
      <p class="text-muted small">Pedido: <strong><?= htmlspecialchars($order['order_number']) ?></strong></p>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/" class="btn btn-gold btn-lg mt-3">
        <i class="bi bi-house me-2"></i>Ir al inicio
      </a>
    </div>
  </div>
</section>
