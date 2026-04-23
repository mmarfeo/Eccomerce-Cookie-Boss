<?php use App\Helpers\Formatter; ?>

<div class="d-flex gap-2 mb-4 flex-wrap">
  <a href="<?= BASE_URL ?>/admin/pedidos" class="btn btn-sm <?= !$current ? 'btn-admin-primary' : 'btn-admin-outline' ?>">Todos</a>
  <?php foreach ($statuses as $s): ?>
    <a href="<?= BASE_URL ?>/admin/pedidos?estado=<?= $s ?>"
       class="btn btn-sm <?= $current === $s ? 'btn-admin-primary' : 'btn-admin-outline' ?>">
      <?= Formatter::statusLabel($s) ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="admin-card">
  <?php if (empty($orders)): ?>
    <p class="text-center text-muted py-5">No hay pedidos en esta categoría.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr><th>Pedido</th><th>Cliente</th><th>Items</th><th>Total</th><th>Entrega</th><th>Estado</th><th>Fecha</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
        <tr>
          <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
          <td>
            <div class="fw-semibold small"><?= htmlspecialchars($order['customer_name']) ?></div>
            <div class="text-muted small"><?= htmlspecialchars($order['customer_email']) ?></div>
          </td>
          <td class="text-center"><?= $order['item_count'] ?></td>
          <td class="fw-700"><?= Formatter::price((float)$order['total']) ?></td>
          <td>
            <span class="small">
              <?= $order['delivery_method'] === 'pickup' ? '🏪 Retiro' : '🚚 Envío' ?>
            </span>
          </td>
          <td><?= Formatter::statusBadge($order['status']) ?></td>
          <td class="small text-muted"><?= Formatter::date($order['created_at'], 'd/m/y H:i') ?></td>
          <td>
            <a href="<?= BASE_URL ?>/admin/pedidos/ver?id=<?= $order['id'] ?>"
               class="btn btn-sm btn-admin-outline">Ver</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
