<div class="d-flex justify-content-between align-items-center mb-4">
  <div></div>
  <a href="<?= BASE_URL ?>/admin/categorias/crear" class="btn btn-admin-primary">
    <i class="bi bi-plus-circle me-2"></i>Nueva categoría
  </a>
</div>

<div class="admin-card">
  <?php if (empty($categories)): ?>
    <p class="text-center text-muted py-5">No hay categorías todavía.</p>
  <?php else: ?>
  <table class="admin-table">
    <thead>
      <tr><th>Nombre</th><th>Slug</th><th>Productos</th><th>Orden</th><th>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody>
      <?php foreach ($categories as $cat): ?>
      <tr>
        <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
        <td><code class="small"><?= htmlspecialchars($cat['slug']) ?></code></td>
        <td><?= $cat['product_count'] ?></td>
        <td><?= $cat['sort_order'] ?></td>
        <td>
          <span class="badge <?= $cat['active'] ? 'bg-success' : 'bg-secondary' ?>">
            <?= $cat['active'] ? 'Activa' : 'Inactiva' ?>
          </span>
        </td>
        <td>
          <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/categorias/editar?id=<?= $cat['id'] ?>"
               class="btn btn-sm btn-admin-outline">
              <i class="bi bi-pencil"></i>
            </a>
            <form method="POST" action="<?= BASE_URL ?>/admin/categorias/eliminar"
                  onsubmit="return confirm('¿Eliminar categoría <?= htmlspecialchars($cat['name']) ?>?')">
              <?= \App\Core\Csrf::field() ?>
              <input type="hidden" name="id" value="<?= $cat['id'] ?>">
              <button type="submit" class="btn btn-sm btn-admin-danger">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
