<?php use App\Helpers\Formatter; ?>

<div class="mb-3">
  <a href="<?= BASE_URL ?>/admin/pedidos" class="btn btn-sm btn-admin-outline">
    <i class="bi bi-arrow-left me-1"></i>Volver
  </a>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <!-- Items del pedido -->
    <div class="admin-card mb-4">
      <h5 class="fw-700 mb-3">Items del pedido</h5>
      <table class="admin-table">
        <thead><tr><th>Producto</th><th>Precio unit.</th><th>Cantidad</th><th>Subtotal</th></tr></thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <?php if ($item['product_image']): ?>
                  <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($item['product_image']) ?>"
                       width="40" height="40" class="rounded" style="object-fit:cover">
                <?php else: ?>
                  <div style="width:40px;height:40px;background:var(--cream);border-radius:6px;display:flex;align-items:center;justify-content:center">🍪</div>
                <?php endif; ?>
                <span class="fw-semibold"><?= htmlspecialchars($item['product_name']) ?></span>
              </div>
            </td>
            <td><?= Formatter::price((float)$item['unit_price']) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td class="fw-700"><?= Formatter::price((float)$item['subtotal']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="mt-3 pt-3 border-top">
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">Subtotal</span>
          <span><?= Formatter::price((float)$order['subtotal']) ?></span>
        </div>
        <?php if ($order['shipping_cost'] > 0): ?>
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">Envío</span>
          <span><?= Formatter::price((float)$order['shipping_cost']) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($order['discount'] > 0): ?>
        <div class="d-flex justify-content-between mb-1 text-success">
          <span>Descuento</span>
          <span>-<?= Formatter::price((float)$order['discount']) ?></span>
        </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between mt-2 pt-2 border-top fw-800" style="font-size:1.1rem">
          <span>Total</span>
          <span class="text-gold"><?= Formatter::price((float)$order['total']) ?></span>
        </div>
      </div>
    </div>

    <!-- Historial de estados -->
    <?php if (!empty($history)): ?>
    <div class="admin-card">
      <h5 class="fw-700 mb-3">Historial de estados</h5>
      <div class="timeline">
        <?php foreach (array_reverse($history) as $h): ?>
        <div class="d-flex gap-3 mb-3">
          <div style="width:10px;height:10px;background:var(--gold);border-radius:50%;margin-top:.35rem;flex-shrink:0"></div>
          <div>
            <div class="small">
              <strong><?= Formatter::statusLabel($h['new_status']) ?></strong>
              <?php if ($h['old_status']): ?><span class="text-muted"> (antes: <?= Formatter::statusLabel($h['old_status']) ?>)</span><?php endif; ?>
            </div>
            <?php if ($h['notes']): ?>
              <div class="small text-muted"><?= htmlspecialchars($h['notes']) ?></div>
            <?php endif; ?>
            <div class="small text-muted"><?= Formatter::date($h['changed_at']) ?> · <?= htmlspecialchars($h['changed_by'] ?? 'sistema') ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <!-- Info del pedido -->
    <div class="admin-card mb-4">
      <h5 class="fw-700 mb-3">Información del pedido</h5>
      <div class="mb-2"><span class="text-muted small">Número</span><br><strong><?= htmlspecialchars($order['order_number']) ?></strong></div>
      <div class="mb-2"><span class="text-muted small">Estado</span><br><?= Formatter::statusBadge($order['status']) ?></div>
      <div class="mb-2"><span class="text-muted small">Entrega</span><br><strong><?= $order['delivery_method'] === 'pickup' ? '🏪 Retiro en local' : '🚚 Envío a domicilio' ?></strong></div>
      <div class="mb-2"><span class="text-muted small">Pago</span><br>
        <strong><?= $order['payment_method'] ?? 'Mercado Pago' ?></strong>
        <?php if ($order['mp_payment_id']): ?>
          <div class="small text-muted">ID: <?= htmlspecialchars($order['mp_payment_id']) ?></div>
        <?php endif; ?>
      </div>
      <div class="mb-2"><span class="text-muted small">Fecha</span><br><?= Formatter::date($order['created_at']) ?></div>
    </div>

    <!-- Info cliente -->
    <div class="admin-card mb-4">
      <h5 class="fw-700 mb-3">Cliente</h5>
      <div class="mb-1 fw-semibold"><?= htmlspecialchars($order['customer_name']) ?></div>
      <div class="small text-muted mb-1"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($order['customer_email']) ?></div>
      <?php if ($order['customer_phone']): ?>
        <div class="small text-muted mb-1"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($order['customer_phone']) ?></div>
      <?php endif; ?>
      <?php if ($order['customer_address']): ?>
        <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($order['customer_address']) ?>, <?= htmlspecialchars($order['customer_city'] ?? '') ?></div>
      <?php endif; ?>
      <?php if ($order['customer_notes']): ?>
        <div class="mt-2 p-2 bg-light rounded small"><i class="bi bi-chat me-1"></i><?= htmlspecialchars($order['customer_notes']) ?></div>
      <?php endif; ?>
    </div>

    <!-- Cambiar estado -->
    <div class="admin-card">
      <h5 class="fw-700 mb-3">Actualizar estado</h5>
      <form method="POST" action="<?= BASE_URL ?>/admin/pedidos/estado">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="id" value="<?= $order['id'] ?>">
        <div class="mb-3">
          <label class="form-label fw-600 small">Nuevo estado</label>
          <select name="status" class="form-select">
            <?php foreach ($statuses as $s): ?>
              <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
                <?= Formatter::statusLabel($s) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-600 small">Nota interna (opcional)</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Ej: Avisado al cliente por WhatsApp"></textarea>
        </div>
        <button type="submit" class="btn btn-admin-primary w-100">
          <i class="bi bi-check-lg me-2"></i>Actualizar estado
        </button>
      </form>
    </div>
  </div>
</div>
