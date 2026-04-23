<?php
use App\Core\Csrf;
use App\Helpers\Formatter;
?>

<section class="py-5">
  <div class="container">
    <h1 class="section-title mb-4">Finalizar pedido</h1>

    <div class="row g-4">
      <!-- Formulario -->
      <div class="col-lg-8">
        <form method="POST" action="<?= BASE_URL ?>/checkout/procesar" id="checkoutForm">
          <?= Csrf::field() ?>

          <!-- Datos personales -->
          <div class="checkout-card mb-4">
            <h5 class="fw-700 mb-4"><i class="bi bi-person-circle me-2 text-gold"></i>Tus datos</h5>

            <?php if (!empty($loggedCustomer)): ?>
              <!-- Cliente logueado: datos pre-llenados -->
              <div class="alert alert-success py-2 mb-3 d-flex align-items-center gap-2" style="font-size:.85rem">
                <i class="bi bi-person-check-fill" style="color:var(--gold)"></i>
                Comprando como <strong><?= htmlspecialchars($loggedCustomer['name']) ?></strong> —
                <a href="<?= BASE_URL ?>/cuenta/logout" class="text-muted ms-1">No soy yo</a>
              </div>
            <?php elseif (!\App\Core\CustomerAuth::check()): ?>
              <div class="alert alert-light py-2 mb-3 d-flex align-items-center gap-2" style="font-size:.85rem;border:1px solid var(--border)">
                <i class="bi bi-star" style="color:var(--gold)"></i>
                <a href="<?= BASE_URL ?>/cuenta/login" style="color:var(--gold)">Iniciá sesión</a>
                para acumular puntos y completar más rápido
              </div>
            <?php endif; ?>

            <div class="row g-3">
              <?php $lc = $loggedCustomer ?? []; ?>
              <div class="col-md-6">
                <label class="form-label">Nombre completo *</label>
                <input type="text" name="name" class="form-control" required
                       placeholder="Ej: María García"
                       value="<?= htmlspecialchars($_POST['name'] ?? $lc['name'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" required
                       placeholder="tu@email.com"
                       <?= !empty($lc) ? 'readonly' : '' ?>
                       value="<?= htmlspecialchars($_POST['email'] ?? $lc['email'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">WhatsApp / Teléfono *</label>
                <input type="tel" name="phone" class="form-control" required
                       placeholder="+54 11 1234-5678"
                       value="<?= htmlspecialchars($_POST['phone'] ?? $lc['phone'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Barrio / Ciudad</label>
                <input type="text" name="city" class="form-control"
                       placeholder="Ej: Palermo, CABA"
                       value="<?= htmlspecialchars($_POST['city'] ?? $lc['city'] ?? '') ?>">
              </div>
            </div>
          </div>

          <!-- Método de entrega -->
          <div class="checkout-card mb-4">
            <h5 class="fw-700 mb-4"><i class="bi bi-truck me-2 text-gold"></i>Método de entrega</h5>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="border rounded p-3 d-flex gap-3 align-items-start cursor-pointer" style="cursor:pointer;border-color:var(--border)!important">
                  <input type="radio" name="delivery_method" value="pickup" checked
                         onchange="toggleDelivery(this.value)">
                  <div>
                    <div class="fw-700">🏪 Retiro en local</div>
                    <div class="small text-muted">Gratis · Consultá dirección por Instagram</div>
                  </div>
                </label>
              </div>
              <div class="col-md-6">
                <label class="border rounded p-3 d-flex gap-3 align-items-start cursor-pointer" style="cursor:pointer;border-color:var(--border)!important">
                  <input type="radio" name="delivery_method" value="delivery"
                         onchange="toggleDelivery(this.value)">
                  <div>
                    <div class="fw-700">🚚 Envío a domicilio</div>
                    <div class="small text-muted">Costo: <?= Formatter::price((float)($shipping ?? 0)) ?></div>
                  </div>
                </label>
              </div>
            </div>

            <div id="addressSection" class="mt-3" style="display:none">
              <label class="form-label">Dirección de entrega *</label>
              <input type="text" name="address" class="form-control"
                     placeholder="Calle, número, piso, depto"
                     value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
            </div>
          </div>

          <!-- Notas -->
          <div class="checkout-card mb-4">
            <h5 class="fw-700 mb-3"><i class="bi bi-chat me-2 text-gold"></i>Notas adicionales</h5>
            <textarea name="notes" class="form-control" rows="3"
                      placeholder="¿Alguna aclaración especial para tu pedido?"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
          </div>

          <!-- Cupón de descuento -->
          <div class="checkout-card mb-4">
            <h5 class="fw-700 mb-3"><i class="bi bi-ticket-perforated me-2 text-gold"></i>Cupón de descuento</h5>
            <?php if (!empty($coupon)): ?>
              <div class="alert py-2 px-3" style="background:rgba(40,167,69,.1);border:1px solid #28a745;border-radius:8px">
                <div class="d-flex justify-content-between align-items-center">
                  <span>✅ Cupón <strong><?= htmlspecialchars($coupon['code']) ?></strong> aplicado</span>
                  <strong class="text-success">-<?= Formatter::price((float)$coupon['discount']) ?></strong>
                </div>
              </div>
            <?php else: ?>
              <div class="input-group">
                <input type="text" id="couponInput" class="form-control" placeholder="Ej: PROMO20"
                       style="text-transform:uppercase;letter-spacing:1px;font-weight:600">
                <button type="button" class="btn btn-gold" onclick="applyCoupon()">Aplicar</button>
              </div>
              <div id="couponMsg" class="form-text mt-1"></div>
            <?php endif; ?>
          </div>

          <!-- Puntos de fidelidad -->
          <?php if (!empty($loyaltyConfig['enabled']) && $customerPoints >= ($loyaltyConfig['min_redeem'] ?? 500)): ?>
          <div class="checkout-card mb-4">
            <h5 class="fw-700 mb-3"><i class="bi bi-star-fill me-2 text-gold"></i>Mis puntos de fidelidad</h5>
            <?php if (!empty($pointsRedeem)): ?>
              <div class="alert py-2 px-3" style="background:rgba(191,150,99,.1);border:1px solid var(--gold);border-radius:8px">
                ⭐ <strong><?= number_format($pointsRedeem) ?> puntos</strong> canjeados
                = -<?= Formatter::price($pointsRedeem / ($loyaltyConfig['redeem_rate'] ?? 100)) ?> de descuento
              </div>
            <?php else: ?>
              <div class="alert py-2 px-3 small" style="background:rgba(191,150,99,.08);border:1px solid var(--border);border-radius:8px;margin-bottom:.75rem">
                Tenés <strong class="text-gold"><?= number_format($customerPoints) ?> puntos</strong>
                (≈ <?= Formatter::price(intdiv($customerPoints, $loyaltyConfig['redeem_rate'] ?? 100)) ?> de descuento)
              </div>
              <div class="input-group">
                <input type="number" id="pointsInput" class="form-control"
                       placeholder="Puntos a canjear (mín. <?= $loyaltyConfig['min_redeem'] ?>)"
                       min="<?= $loyaltyConfig['min_redeem'] ?>" max="<?= $customerPoints ?>"
                       step="<?= $loyaltyConfig['redeem_rate'] ?>">
                <button type="button" class="btn btn-gold" onclick="applyPoints()">Canjear</button>
              </div>
              <div id="pointsMsg" class="form-text mt-1"></div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <button type="submit" class="btn btn-orange btn-lg w-100" id="submitBtn">
            <i class="bi bi-credit-card me-2"></i>Pagar con Mercado Pago
          </button>
          <p class="text-center small text-muted mt-2">
            <i class="bi bi-shield-lock me-1"></i>Tus datos están seguros. Procesamos pagos con Mercado Pago.
          </p>
        </form>
      </div>

      <!-- Resumen del pedido -->
      <div class="col-lg-4">
        <div class="order-summary">
          <h5 class="fw-700 mb-3">Tu pedido</h5>

          <?php foreach ($cart['items'] as $item): ?>
          <div class="d-flex gap-2 align-items-center mb-2 pb-2" style="border-bottom:1px solid var(--border)">
            <?php if ($item['image']): ?>
              <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($item['image']) ?>"
                   alt="" width="45" height="45" class="rounded" style="object-fit:cover">
            <?php else: ?>
              <div style="width:45px;height:45px;background:var(--cream);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.5rem">🍪</div>
            <?php endif; ?>
            <div class="flex-grow-1">
              <div class="small fw-semibold"><?= htmlspecialchars($item['name']) ?></div>
              <div class="small text-muted">x<?= $item['quantity'] ?></div>
            </div>
            <div class="small fw-700"><?= Formatter::price($item['price'] * $item['quantity']) ?></div>
          </div>
          <?php endforeach; ?>

          <div class="order-summary-row mt-2">
            <span>Subtotal</span>
            <span><?= Formatter::price($cart['subtotal']) ?></span>
          </div>
          <div class="order-summary-row" id="shippingRow">
            <span>Envío</span>
            <span id="shippingDisplay">
              <?= $shipping > 0 ? Formatter::price($shipping) : '<span class="text-success fw-semibold">¡Gratis!</span>' ?>
            </span>
          </div>
          <div class="order-summary-total">
            <span>Total</span>
            <span id="totalDisplay"><?= Formatter::price($cart['total']) ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
const shippingCost = <?= $shipping ?? 0 ?>;
const subtotal = <?= $cart['subtotal'] ?>;

function toggleDelivery(method) {
  const addrSection = document.getElementById('addressSection');
  const shippingDisplay = document.getElementById('shippingDisplay');
  const totalDisplay = document.getElementById('totalDisplay');

  const fmt = n => '$' + Number(n).toLocaleString('es-AR', { maximumFractionDigits: 0 });

  if (method === 'delivery') {
    addrSection.style.display = 'block';
    shippingDisplay.innerHTML = fmt(shippingCost);
    totalDisplay.textContent = fmt(subtotal + shippingCost);
  } else {
    addrSection.style.display = 'none';
    shippingDisplay.innerHTML = '<span class="text-success fw-semibold">¡Gratis!</span>';
    totalDisplay.textContent = fmt(subtotal);
  }
}

async function applyCoupon() {
  const code = document.getElementById('couponInput')?.value;
  if (!code) return;
  const res  = await fetch(`<?= BASE_URL ?>/checkout/aplicar-cupon`, {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
    body: new URLSearchParams({ coupon_code: code, _csrf_token: '<?= \App\Core\Csrf::token() ?>' }),
  });
  const data = await res.json();
  const msg  = document.getElementById('couponMsg');
  msg.textContent = data.message;
  msg.style.color = data.success ? '#28a745' : '#dc3545';
  if (data.success) location.reload();
}

async function applyPoints() {
  const pts   = document.getElementById('pointsInput')?.value;
  const email = document.querySelector('[name="email"]')?.value;
  if (!pts || !email) { alert('Ingresá tu email y los puntos a canjear.'); return; }
  const res   = await fetch(`<?= BASE_URL ?>/checkout/aplicar-puntos`, {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
    body: new URLSearchParams({ email, points: pts, _csrf_token: '<?= \App\Core\Csrf::token() ?>' }),
  });
  const data  = await res.json();
  const msg   = document.getElementById('pointsMsg');
  msg.textContent = data.message;
  msg.style.color = data.success ? '#28a745' : '#dc3545';
  if (data.success) location.reload();
}

document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Redirigiendo a Mercado Pago...';
});
</script>
