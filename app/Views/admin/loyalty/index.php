<?php use App\Helpers\{Formatter, Loyalty}; ?>

<!-- Config panel -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="admin-card">
      <h5 class="fw-700 mb-3"><i class="bi bi-star-fill text-gold me-2"></i>Configuración del programa</h5>
      <form method="POST" action="<?= BASE_URL ?>/admin/puntos/config">
        <?= \App\Core\Csrf::field() ?>
        <div class="row g-3">
          <div class="col-md-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="fw-600 small">Programa activo</label>
              <label class="toggle-switch">
                <input type="checkbox" name="loyalty_enabled" value="1"
                       <?= $cfg['enabled'] ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-600 small">Puntos por $1 gastado</label>
            <input type="number" name="loyalty_points_per_peso" class="form-control form-control-sm"
                   value="<?= $cfg['per_peso'] ?>" min="0.1" step="0.1">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-600 small">Pts para canjear $1</label>
            <input type="number" name="loyalty_redeem_rate" class="form-control form-control-sm"
                   value="<?= $cfg['redeem_rate'] ?>" min="1">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-600 small">Mínimo para canjear</label>
            <input type="number" name="loyalty_min_redeem" class="form-control form-control-sm"
                   value="<?= $cfg['min_redeem'] ?>" min="0">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-600 small">Silver desde (pts)</label>
            <input type="number" name="loyalty_silver_min" class="form-control form-control-sm"
                   value="<?= $cfg['silver_min'] ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-600 small">Gold desde (pts)</label>
            <input type="number" name="loyalty_gold_min" class="form-control form-control-sm"
                   value="<?= $cfg['gold_min'] ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-600 small">Platinum desde (pts)</label>
            <input type="number" name="loyalty_plat_min" class="form-control form-control-sm"
                   value="<?= $cfg['plat_min'] ?>">
          </div>
        </div>
        <button type="submit" class="btn btn-admin-primary mt-3">
          <i class="bi bi-save me-1"></i>Guardar configuración
        </button>
      </form>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="admin-card" style="background:linear-gradient(135deg,#2d2016,#4a3728);color:#fff">
      <h5 class="fw-700 mb-3" style="color:var(--gold)">Equivalencias actuales</h5>
      <div class="mb-2 small">
        <i class="bi bi-arrow-right-circle me-2" style="color:var(--gold)"></i>
        Cada $1 comprado = <strong><?= $cfg['per_peso'] ?> pto<?= $cfg['per_peso'] != 1 ? 's' : '' ?></strong>
      </div>
      <div class="mb-2 small">
        <i class="bi bi-arrow-right-circle me-2" style="color:var(--gold)"></i>
        <?= $cfg['redeem_rate'] ?> puntos = <strong>$1 de descuento</strong>
      </div>
      <div class="mb-2 small">
        <i class="bi bi-info-circle me-2" style="color:var(--gold)"></i>
        Mínimo <?= number_format($cfg['min_redeem']) ?> puntos para canjear
      </div>
      <hr style="border-color:rgba(255,255,255,.2)">
      <div class="d-flex justify-content-between small">
        <span>🥉 Bronze</span><span>&lt; <?= number_format($cfg['silver_min']) ?> pts</span>
      </div>
      <div class="d-flex justify-content-between small">
        <span>🥈 Silver</span><span>≥ <?= number_format($cfg['silver_min']) ?> pts</span>
      </div>
      <div class="d-flex justify-content-between small">
        <span>🥇 Gold</span><span>≥ <?= number_format($cfg['gold_min']) ?> pts</span>
      </div>
      <div class="d-flex justify-content-between small">
        <span>💎 Platinum</span><span>≥ <?= number_format($cfg['plat_min']) ?> pts</span>
      </div>
    </div>
  </div>
</div>

<!-- Listado de clientes con puntos -->
<div class="admin-card">
  <h5 class="fw-700 mb-3">Clientes y puntos</h5>
  <?php if (empty($customers)): ?>
    <p class="text-muted text-center py-4">No hay clientes todavía.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr><th>Cliente</th><th>Tier</th><th>Puntos</th><th>Equivale a</th><th>Ajustar</th></tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $c):
          $tier = $c['loyalty_tier'] ?? 'bronze';
          $pts  = (int)$c['loyalty_points'];
          $equiv = $pts > 0 && $cfg['redeem_rate'] > 0 ? intdiv($pts, $cfg['redeem_rate']) : 0;
        ?>
        <tr id="row-<?= $c['id'] ?>">
          <td>
            <div class="fw-semibold"><?= htmlspecialchars($c['name']) ?></div>
            <div class="small text-muted"><?= htmlspecialchars($c['email']) ?></div>
          </td>
          <td>
            <span class="badge" style="background:<?= Loyalty::tierColor($tier) ?>">
              <?= Loyalty::tierLabel($tier) ?>
            </span>
          </td>
          <td>
            <strong id="pts-<?= $c['id'] ?>"><?= number_format($pts) ?></strong> pts
          </td>
          <td class="text-gold fw-700">
            <span id="equiv-<?= $c['id'] ?>"><?= Formatter::price($equiv) ?></span>
          </td>
          <td>
            <div class="d-flex gap-1 align-items-center">
              <input type="number" class="form-control form-control-sm" style="width:80px"
                     id="adj-<?= $c['id'] ?>" placeholder="±500">
              <input type="text" class="form-control form-control-sm" style="width:120px"
                     id="reason-<?= $c['id'] ?>" placeholder="Motivo">
              <button class="btn btn-sm btn-admin-primary"
                      onclick="adjustPoints(<?= $c['id'] ?>)">
                <i class="bi bi-plus-minus"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script>
const REDEEM_RATE = <?= (int)$cfg['redeem_rate'] ?>;
async function adjustPoints(customerId) {
  const pts    = parseInt(document.getElementById('adj-' + customerId).value);
  const reason = document.getElementById('reason-' + customerId).value;
  if (!pts || !reason) return alert('Ingresá puntos y motivo.');

  const res = await fetch('<?= BASE_URL ?>/admin/puntos/ajustar', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ customer_id: customerId, points: pts, reason, _csrf_token: '<?= \App\Core\Csrf::token() ?>' }),
  });
  const data = await res.json();
  if (data.success) {
    document.getElementById('pts-' + customerId).textContent = data.balance.toLocaleString('es-AR');
    const equiv = REDEEM_RATE > 0 ? Math.floor(data.balance / REDEEM_RATE) : 0;
    document.getElementById('equiv-' + customerId).textContent = '$' + equiv.toLocaleString('es-AR', {maximumFractionDigits:0});
    document.getElementById('adj-' + customerId).value = '';
    document.getElementById('reason-' + customerId).value = '';
  } else {
    alert(data.message || 'Error al ajustar puntos.');
  }
}
</script>
