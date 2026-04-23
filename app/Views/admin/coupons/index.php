<?php use App\Helpers\Formatter; ?>

<div class="row g-4">
  <!-- Formulario crear -->
  <div class="col-lg-4">
    <div class="admin-card">
      <h5 class="fw-700 mb-3"><i class="bi bi-plus-circle me-2 text-gold"></i>Nuevo cupón</h5>
      <form method="POST" action="<?= BASE_URL ?>/admin/cupones/crear">
        <?= \App\Core\Csrf::field() ?>
        <div class="mb-3">
          <label class="form-label fw-600 small">Código *</label>
          <input type="text" name="code" class="form-control text-uppercase" required
                 placeholder="PROMO20" maxlength="30"
                 style="text-transform:uppercase;letter-spacing:2px;font-weight:700">
          <div class="form-text">Se guarda en mayúsculas automáticamente.</div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-600 small">Descripción</label>
          <input type="text" name="description" class="form-control" placeholder="Ej: 20% para nuevos clientes">
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label fw-600 small">Tipo</label>
            <select name="type" class="form-select" id="couponType" onchange="updateValueLabel()">
              <option value="percent">Porcentaje (%)</option>
              <option value="fixed">Monto fijo ($)</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label fw-600 small" id="valueLabel">Valor (%)</label>
            <input type="number" name="value" class="form-control" required min="0" step="0.01" placeholder="20">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-600 small">Pedido mínimo (ARS)</label>
          <input type="number" name="min_order" class="form-control" value="0" min="0">
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label fw-600 small">Usos máximos</label>
            <input type="number" name="max_uses" class="form-control" min="1" placeholder="Sin límite">
          </div>
          <div class="col-6">
            <label class="form-label fw-600 small">Vence el</label>
            <input type="datetime-local" name="expires_at" class="form-control">
          </div>
        </div>
        <button type="submit" class="btn btn-admin-primary w-100">
          <i class="bi bi-plus me-1"></i>Crear cupón
        </button>
      </form>
    </div>
  </div>

  <!-- Lista de cupones -->
  <div class="col-lg-8">
    <div class="admin-card">
      <h5 class="fw-700 mb-3">Cupones existentes</h5>
      <?php if (empty($coupons)): ?>
        <p class="text-muted text-center py-4">No hay cupones creados.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr><th>Código</th><th>Descuento</th><th>Mín.</th><th>Usos</th><th>Vence</th><th>Estado</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($coupons as $c): ?>
            <tr>
              <td>
                <div>
                  <code class="fw-700" style="font-size:.95rem;letter-spacing:2px;color:var(--dark)"><?= htmlspecialchars($c['code']) ?></code>
                  <?php if ($c['description']): ?>
                    <div class="small text-muted"><?= htmlspecialchars($c['description']) ?></div>
                  <?php endif; ?>
                </div>
              </td>
              <td class="fw-700 text-gold">
                <?= $c['type'] === 'percent' ? $c['value'] . '%' : Formatter::price((float)$c['value']) ?>
              </td>
              <td class="small"><?= $c['min_order'] > 0 ? Formatter::price((float)$c['min_order']) : '—' ?></td>
              <td class="small">
                <?= $c['used_count'] ?>
                <?= $c['max_uses'] ? '/ ' . $c['max_uses'] : '' ?>
              </td>
              <td class="small text-muted">
                <?= $c['expires_at'] ? Formatter::date($c['expires_at'], 'd/m/y') : '—' ?>
              </td>
              <td>
                <button class="btn btn-sm <?= $c['active'] ? 'btn-success' : 'btn-secondary' ?>"
                        onclick="toggleCoupon(<?= $c['id'] ?>, this)">
                  <?= $c['active'] ? 'Activo' : 'Inactivo' ?>
                </button>
              </td>
              <td>
                <form method="POST" action="<?= BASE_URL ?>/admin/cupones/eliminar"
                      onsubmit="return confirm('¿Eliminar cupón <?= htmlspecialchars($c['code']) ?>?')">
                  <?= \App\Core\Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <button class="btn btn-sm btn-admin-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function updateValueLabel() {
  const type = document.getElementById('couponType').value;
  document.getElementById('valueLabel').textContent = type === 'percent' ? 'Valor (%)' : 'Valor ($)';
}
async function toggleCoupon(id, btn) {
  await fetch('<?= BASE_URL ?>/admin/cupones/toggle', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({id, _csrf_token:'<?= \App\Core\Csrf::token() ?>'})
  });
  const isActive = btn.classList.contains('btn-success');
  btn.className = 'btn btn-sm ' + (isActive ? 'btn-secondary' : 'btn-success');
  btn.textContent = isActive ? 'Inactivo' : 'Activo';
}
</script>
