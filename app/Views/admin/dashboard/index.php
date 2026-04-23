<?php use App\Helpers\Formatter; ?>

<!-- Stats cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-value"><?= Formatter::price((float)($todaySales['revenue'] ?? 0)) ?></div>
          <div class="stat-label">Ventas hoy</div>
          <div class="small text-muted mt-1"><?= $todaySales['count'] ?? 0 ?> pedidos</div>
        </div>
        <div class="stat-icon text-gold"><i class="bi bi-currency-dollar"></i></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card orange">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-value"><?= Formatter::price((float)($monthSales['revenue'] ?? 0)) ?></div>
          <div class="stat-label">Ventas este mes</div>
          <div class="small text-muted mt-1"><?= $monthSales['count'] ?? 0 ?> pedidos</div>
        </div>
        <div class="stat-icon" style="color:var(--orange)"><i class="bi bi-graph-up"></i></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card green">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-value"><?= $pendingCount ?? 0 ?></div>
          <div class="stat-label">Pedidos pendientes</div>
          <div class="small mt-1">
            <a href="<?= BASE_URL ?>/admin/pedidos?estado=pending" class="text-success">Ver todos →</a>
          </div>
        </div>
        <div class="stat-icon text-success"><i class="bi bi-clock-history"></i></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card blue">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-value"><?= $totalCustomers ?? 0 ?></div>
          <div class="stat-label">Clientes totales</div>
          <div class="small mt-1">
            <a href="<?= BASE_URL ?>/admin/clientes" class="text-info">Ver todos →</a>
          </div>
        </div>
        <div class="stat-icon text-info"><i class="bi bi-people"></i></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Últimos pedidos -->
  <div class="col-lg-8">
    <div class="admin-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-700 mb-0">Últimos pedidos</h5>
        <a href="<?= BASE_URL ?>/admin/pedidos" class="btn btn-sm btn-admin-outline">Ver todos</a>
      </div>
      <?php if (empty($latestOrders)): ?>
        <p class="text-muted text-center py-4">No hay pedidos todavía.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Pedido</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Fecha</th><th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($latestOrders as $order): ?>
            <tr>
              <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
              <td>
                <div><?= htmlspecialchars($order['customer_name']) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($order['customer_email']) ?></div>
              </td>
              <td class="fw-700"><?= Formatter::price((float)$order['total']) ?></td>
              <td><?= Formatter::statusBadge($order['status']) ?></td>
              <td class="small text-muted"><?= Formatter::date($order['created_at'], 'd/m H:i') ?></td>
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
  </div>

  <!-- Más vendidos -->
  <div class="col-lg-4">
    <div class="admin-card">
      <h5 class="fw-700 mb-3">Más vendidos</h5>
      <?php if (empty($bestSellers)): ?>
        <p class="text-muted text-center py-4">Sin datos todavía.</p>
      <?php else: ?>
        <?php foreach ($bestSellers as $i => $prod): ?>
        <div class="d-flex align-items-center gap-3 mb-3">
          <div style="width:28px;height:28px;background:var(--cream);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:var(--gold)">
            <?= $i + 1 ?>
          </div>
          <?php if ($prod['main_image']): ?>
            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($prod['main_image']) ?>"
                 width="40" height="40" class="rounded" style="object-fit:cover">
          <?php else: ?>
            <div style="width:40px;height:40px;background:var(--cream);border-radius:6px;display:flex;align-items:center;justify-content:center">🍪</div>
          <?php endif; ?>
          <div class="flex-grow-1">
            <div class="small fw-semibold"><?= htmlspecialchars($prod['name']) ?></div>
            <div class="small text-muted"><?= $prod['total_sold'] ?> vendidas</div>
          </div>
          <div class="small fw-700 text-gold"><?= Formatter::price((float)$prod['total_revenue']) ?></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Accesos rápidos -->
    <div class="admin-card mt-4">
      <h5 class="fw-700 mb-3">Accesos rápidos</h5>
      <div class="d-grid gap-2">
        <a href="<?= BASE_URL ?>/admin/productos/crear" class="btn btn-admin-primary">
          <i class="bi bi-plus-circle me-2"></i>Nuevo producto
        </a>
        <a href="<?= BASE_URL ?>/admin/categorias/crear" class="btn btn-admin-outline">
          <i class="bi bi-plus me-2"></i>Nueva categoría
        </a>
        <a href="<?= BASE_URL ?>/admin/pedidos" class="btn btn-admin-outline">
          <i class="bi bi-receipt me-2"></i>Ver pedidos
        </a>
      </div>
    </div>
  </div>
</div>
