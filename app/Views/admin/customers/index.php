<?php use App\Helpers\Formatter; ?>

<div class="admin-card">
  <?php if (empty($customers)): ?>
    <p class="text-center text-muted py-5">No hay clientes todavía.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr><th>Cliente</th><th>Teléfono</th><th>Pedidos</th><th>Total gastado</th><th>Fecha</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $c): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= htmlspecialchars($c['name']) ?></div>
            <div class="small text-muted"><?= htmlspecialchars($c['email']) ?></div>
          </td>
          <td class="small"><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
          <td><span class="badge bg-light text-dark"><?= $c['total_orders'] ?></span></td>
          <td class="fw-700 text-gold"><?= Formatter::price((float)$c['total_spent']) ?></td>
          <td class="small text-muted"><?= Formatter::date($c['created_at'], 'd/m/Y') ?></td>
          <td>
            <a href="<?= BASE_URL ?>/admin/clientes/ver?id=<?= $c['id'] ?>"
               class="btn btn-sm btn-admin-outline">Ver</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
