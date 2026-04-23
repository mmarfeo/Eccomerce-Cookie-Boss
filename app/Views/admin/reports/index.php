<?php use App\Helpers\Formatter; ?>

<div class="d-flex gap-2 mb-4">
  <?php foreach ([7,15,30,90] as $p): ?>
    <a href="<?= BASE_URL ?>/admin/reportes?periodo=<?= $p ?>"
       class="btn btn-sm <?= $period == $p ? 'btn-admin-primary' : 'btn-admin-outline' ?>">
      Últimos <?= $p ?> días
    </a>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-value"><?= Formatter::price((float)($monthSales['revenue'] ?? 0)) ?></div>
      <div class="stat-label">Este mes</div>
      <div class="small text-muted"><?= $monthSales['count'] ?? 0 ?> pedidos</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card orange">
      <div class="stat-value"><?= Formatter::price((float)($todaySales['revenue'] ?? 0)) ?></div>
      <div class="stat-label">Hoy</div>
      <div class="small text-muted"><?= $todaySales['count'] ?? 0 ?> pedidos</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card green">
      <?php
        $totalRevenue = array_sum(array_column($salesByDay, 'revenue'));
        $totalOrders  = array_sum(array_column($salesByDay, 'count'));
      ?>
      <div class="stat-value"><?= Formatter::price((float)$totalRevenue) ?></div>
      <div class="stat-label">Últimos <?= $period ?> días</div>
      <div class="small text-muted"><?= $totalOrders ?> pedidos</div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Gráfico de ventas -->
  <div class="col-lg-8">
    <div class="admin-card">
      <h5 class="fw-700 mb-3">Ventas por día</h5>
      <?php if (empty($salesByDay)): ?>
        <p class="text-muted text-center py-5">Sin datos de ventas para este período.</p>
      <?php else: ?>
        <canvas id="salesChart" height="200"></canvas>
        <script>
          document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('salesChart').getContext('2d');
            const labels = <?= json_encode(array_column($salesByDay, 'day')) ?>;
            const revenues = <?= json_encode(array_map(fn($r) => round((float)$r['revenue']), $salesByDay)) ?>;

            new Chart(ctx, {
              type: 'bar',
              data: {
                labels,
                datasets: [{
                  label: 'Ventas (ARS)',
                  data: revenues,
                  backgroundColor: 'rgba(191,150,99,0.6)',
                  borderColor: '#bf9663',
                  borderWidth: 2,
                  borderRadius: 6,
                }]
              },
              options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                  y: { beginAtZero: true, ticks: { callback: v => '$' + v.toLocaleString('es-AR') } }
                }
              }
            });
          });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
      <?php endif; ?>
    </div>
  </div>

  <!-- Productos más vendidos -->
  <div class="col-lg-4">
    <div class="admin-card">
      <h5 class="fw-700 mb-3">Top 10 productos</h5>
      <?php if (empty($bestSellers)): ?>
        <p class="text-muted text-center py-3">Sin datos.</p>
      <?php else: ?>
        <?php foreach ($bestSellers as $i => $prod): ?>
        <div class="d-flex align-items-center gap-2 mb-3">
          <div style="width:22px;font-weight:800;color:var(--gold);font-size:.85rem"><?= $i + 1 ?></div>
          <div class="flex-grow-1">
            <div class="small fw-semibold"><?= htmlspecialchars($prod['name']) ?></div>
            <div class="small text-muted"><?= $prod['total_sold'] ?> uds · <?= Formatter::price((float)$prod['total_revenue']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
