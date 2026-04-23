<?php use App\Helpers\Formatter; ?>

<div class="mb-3">
  <a href="<?= BASE_URL ?>/admin/clientes" class="btn btn-sm btn-admin-outline">
    <i class="bi bi-arrow-left me-1"></i>Volver
  </a>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="admin-card text-center">
      <div style="width:80px;height:80px;background:var(--gold);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;color:#fff;font-weight:800;margin:0 auto 1rem">
        <?= strtoupper(substr($customer['name'], 0, 1)) ?>
      </div>
      <h5 class="fw-700"><?= htmlspecialchars($customer['name']) ?></h5>
      <div class="text-muted small mb-3"><?= htmlspecialchars($customer['email']) ?></div>
      <?php if ($customer['phone']): ?>
        <a href="https://wa.me/<?= preg_replace('/\D/','',$customer['phone']) ?>" class="btn btn-sm btn-admin-primary">
          <i class="bi bi-whatsapp me-1"></i>WhatsApp
        </a>
      <?php endif; ?>
      <div class="row g-3 mt-3 text-center">
        <div class="col-6">
          <div class="fw-800 text-gold" style="font-size:1.5rem"><?= $customer['total_orders'] ?></div>
          <div class="small text-muted">Pedidos</div>
        </div>
        <div class="col-6">
          <div class="fw-800 text-gold" style="font-size:1.3rem"><?= Formatter::price((float)$customer['total_spent']) ?></div>
          <div class="small text-muted">Total gastado</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="admin-card">
      <h5 class="fw-700 mb-3">Historial de pedidos</h5>
      <?php if (empty($orders)): ?>
        <p class="text-muted text-center py-3">Sin pedidos todavía.</p>
      <?php else: ?>
      <table class="admin-table">
        <thead><tr><th>Pedido</th><th>Total</th><th>Estado</th><th>Fecha</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td><strong><?= htmlspecialchars($o['order_number']) ?></strong></td>
            <td class="fw-700"><?= Formatter::price((float)$o['total']) ?></td>
            <td><?= Formatter::statusBadge($o['status']) ?></td>
            <td class="small text-muted"><?= Formatter::date($o['created_at'], 'd/m/Y') ?></td>
            <td><a href="<?= BASE_URL ?>/admin/pedidos/ver?id=<?= $o['id'] ?>" class="btn btn-sm btn-admin-outline">Ver</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>
