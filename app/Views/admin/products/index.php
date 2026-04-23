<?php use App\Helpers\Formatter; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div class="d-flex gap-2">
    <a href="<?= BASE_URL ?>/admin/productos" class="filter-btn <?= !$filterCat ? 'active' : '' ?>"
       style="<?= !$filterCat ? 'background:var(--gold);color:#fff;border-color:var(--gold)' : '' ?>;padding:.4rem 1rem;border-radius:50px;border:2px solid #e8ddd0;font-size:.85rem;font-weight:600;text-decoration:none;color:var(--text)">
      Todos
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="<?= BASE_URL ?>/admin/productos?categoria=<?= $cat['id'] ?>"
       class="filter-btn"
       style="<?= $filterCat == $cat['id'] ? 'background:var(--gold);color:#fff;border-color:var(--gold)' : '' ?>;padding:.4rem 1rem;border-radius:50px;border:2px solid #e8ddd0;font-size:.85rem;font-weight:600;text-decoration:none;color:var(--text)">
      <?= htmlspecialchars($cat['name']) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <a href="<?= BASE_URL ?>/admin/productos/crear" class="btn btn-admin-primary">
    <i class="bi bi-plus-circle me-2"></i>Nuevo producto
  </a>
</div>

<div class="admin-card">
  <?php if (empty($products)): ?>
    <p class="text-center text-muted py-5">No hay productos.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr><th>Producto</th><th>Categoría</th><th>Precio</th><th>Destacado</th><th>Disponible</th><th>Acciones</th></tr>
      </thead>
      <tbody>
        <?php foreach ($products as $prod):
          if ($filterCat && $prod['category_id'] != $filterCat) continue;
        ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-3">
              <?php if ($prod['main_image']): ?>
                <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($prod['main_image']) ?>"
                     width="50" height="50" class="rounded" style="object-fit:cover">
              <?php else: ?>
                <div style="width:50px;height:50px;background:var(--cream);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.5rem">🍪</div>
              <?php endif; ?>
              <div>
                <div class="fw-semibold"><?= htmlspecialchars($prod['name']) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($prod['slug']) ?></div>
              </div>
            </div>
          </td>
          <td><?= htmlspecialchars($prod['category_name'] ?? '—') ?></td>
          <td class="fw-700"><?= Formatter::price((float)$prod['price']) ?></td>
          <td>
            <?php if ($prod['featured']): ?>
              <span class="badge" style="background:var(--orange)">⭐ Sí</span>
            <?php else: ?>
              <span class="text-muted small">No</span>
            <?php endif; ?>
          </td>
          <td>
            <button class="btn btn-sm <?= $prod['available'] ? 'btn-success' : 'btn-secondary' ?>"
                    onclick="toggleProduct(<?= $prod['id'] ?>, this)"
                    title="Click para cambiar">
              <?= $prod['available'] ? 'Disponible' : 'No disponible' ?>
            </button>
          </td>
          <td>
            <div class="d-flex gap-2">
              <a href="<?= BASE_URL ?>/admin/productos/editar?id=<?= $prod['id'] ?>"
                 class="btn btn-sm btn-admin-outline">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="POST" action="<?= BASE_URL ?>/admin/productos/eliminar"
                    onsubmit="return confirm('¿Eliminar «<?= htmlspecialchars($prod['name']) ?>»? Esta acción no se puede deshacer.')">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                <button type="submit" class="btn btn-sm btn-admin-danger"><i class="bi bi-trash"></i></button>
              </form>
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
async function toggleProduct(id, btn) {
  const res = await fetch(`<?= BASE_URL ?>/admin/productos/toggle`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ id, _csrf_token: '<?= \App\Core\Csrf::token() ?>' }),
  });
  const data = await res.json();
  if (data.success) {
    btn.textContent = data.label;
    btn.className = 'btn btn-sm ' + (data.available ? 'btn-success' : 'btn-secondary');
  }
}
</script>
