<?php use App\Helpers\Formatter; ?>

<section class="py-5">
  <div class="container">
    <h1 class="section-title mb-4">Tu carrito</h1>

    <?php if (empty($cart['items'])): ?>
      <div class="text-center py-5">
        <div style="font-size:6rem;margin-bottom:1rem">🛒</div>
        <h3>Tu carrito está vacío</h3>
        <p class="text-muted mb-4">Todavía no agregaste ninguna cookie. ¡Elegí la tuya!</p>
        <a href="<?= BASE_URL ?>/productos" class="btn btn-orange btn-lg">
          <i class="bi bi-bag-heart me-2"></i>Ver cookies
        </a>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <!-- Items -->
        <div class="col-lg-8" id="cartItems">
          <div class="admin-card" style="border-radius:12px;border:1px solid var(--border);padding:1.5rem">
            <?php foreach ($cart['items'] as $item): ?>
            <div class="cart-item" data-cart-row="<?= $item['id'] ?>">
              <?php if ($item['image']): ?>
                <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($item['image']) ?>"
                     alt="<?= htmlspecialchars($item['name']) ?>"
                     class="cart-item-img">
              <?php else: ?>
                <div class="cart-item-img d-flex align-items-center justify-content-center bg-cream rounded"
                     style="font-size:2rem;width:70px;height:70px;flex-shrink:0">🍪</div>
              <?php endif; ?>

              <div class="flex-grow-1">
                <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                <div class="cart-item-price"><?= Formatter::price($item['price']) ?> c/u</div>
              </div>

              <div class="qty-control" style="transform:scale(0.9)">
                <button class="qty-btn" type="button"
                        onclick="updateQty(<?= $item['id'] ?>, -1)">−</button>
                <input type="number"
                       class="qty-input"
                       value="<?= $item['quantity'] ?>"
                       min="1" max="99"
                       id="qty-<?= $item['id'] ?>"
                       data-qty-input="<?= $item['id'] ?>">
                <button class="qty-btn" type="button"
                        onclick="updateQty(<?= $item['id'] ?>, 1)">+</button>
              </div>

              <div class="fw-700" style="min-width:90px;text-align:right;font-weight:700">
                <?= Formatter::price($item['price'] * $item['quantity']) ?>
              </div>

              <button class="btn btn-sm text-danger"
                      data-cart-remove="<?= $item['id'] ?>"
                      title="Eliminar">
                <i class="bi bi-trash"></i>
              </button>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="mt-3 d-flex gap-2">
            <a href="<?= BASE_URL ?>/productos" class="btn btn-gold-outline">
              <i class="bi bi-arrow-left me-1"></i>Seguir comprando
            </a>
          </div>
        </div>

        <!-- Resumen -->
        <div class="col-lg-4">
          <div class="order-summary">
            <h5 class="fw-700 mb-3">Resumen del pedido</h5>

            <div class="order-summary-row">
              <span>Subtotal (<?= $cart['count'] ?> items)</span>
              <span data-cart-subtotal><?= Formatter::price($cart['subtotal']) ?></span>
            </div>

            <div class="order-summary-row">
              <span>Envío</span>
              <?php if ($shipping > 0): ?>
                <span data-cart-shipping><?= Formatter::price($shipping) ?></span>
              <?php else: ?>
                <span class="text-success fw-semibold" data-cart-shipping>¡Gratis!</span>
              <?php endif; ?>
            </div>

            <?php if ($freeShipMin > 0 && $cart['subtotal'] < $freeShipMin): ?>
            <div class="alert py-2 px-3 mt-2" style="background:rgba(191,150,99,0.1);border:1px solid var(--gold);border-radius:8px;font-size:0.8rem">
              <i class="bi bi-truck me-1 text-gold"></i>
              Te faltan <strong><?= Formatter::price($freeShipMin - $cart['subtotal']) ?></strong> para envío gratis
            </div>
            <?php endif; ?>

            <div class="order-summary-total mt-3">
              <span>Total</span>
              <span data-cart-total><?= Formatter::price($cart['total']) ?></span>
            </div>

            <a href="<?= BASE_URL ?>/checkout" class="btn btn-orange w-100 mt-3">
              <i class="bi bi-lock me-2"></i>Finalizar pedido
            </a>

            <div class="text-center mt-3">
              <img src="https://www.mercadopago.com/org-img/MP3/home/logomp.gif" height="20" alt="Mercado Pago">
              <div class="small text-muted mt-1">Pagos 100% seguros</div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
function updateQty(productId, delta) {
  const input = document.getElementById('qty-' + productId);
  if (!input) return;
  const newVal = Math.max(1, Math.min(99, parseInt(input.value) + delta));
  input.value = newVal;

  fetch(`<?= BASE_URL ?>/carrito/actualizar`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
    body: new URLSearchParams({ product_id: productId, quantity: newVal, _csrf_token: '<?= \App\Core\Csrf::token() ?>' }),
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) Cart.updateBadge(data.cartCount), Cart.updateTotals(data);
  });
}
</script>
