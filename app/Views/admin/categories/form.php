<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="admin-card">
      <form method="POST" enctype="multipart/form-data">
        <?= \App\Core\Csrf::field() ?>

        <div class="mb-3">
          <label class="form-label fw-600">Nombre *</label>
          <input type="text" name="name" id="nameInput" class="form-control" required
                 value="<?= htmlspecialchars($category['name'] ?? '') ?>"
                 placeholder="Ej: Clásicas">
        </div>

        <div class="mb-3">
          <label class="form-label fw-600">Slug (URL) *</label>
          <div class="input-group">
            <span class="input-group-text text-muted small">/productos?categoria=</span>
            <input type="text" name="slug" id="slugInput" class="form-control"
                   value="<?= htmlspecialchars($category['slug'] ?? '') ?>"
                   placeholder="clasicas" required>
          </div>
          <div class="form-text">Se genera automáticamente. Solo letras, números y guiones.</div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-600">Descripción</label>
          <textarea name="description" class="form-control" rows="3"
                    placeholder="Descripción breve de la categoría..."><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-600">Orden</label>
            <input type="number" name="sort_order" class="form-control" min="0"
                   value="<?= htmlspecialchars($category['sort_order'] ?? '0') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-600">Estado</label>
            <select name="active" class="form-select">
              <option value="1" <?= ($category['active'] ?? 1) == 1 ? 'selected' : '' ?>>Activa</option>
              <option value="0" <?= ($category['active'] ?? 1) == 0 ? 'selected' : '' ?>>Inactiva</option>
            </select>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-600">Imagen (opcional)</label>
          <?php if (!empty($category['image'])): ?>
            <div class="mb-2">
              <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($category['image']) ?>"
                   width="100" height="100" class="rounded" style="object-fit:cover">
            </div>
          <?php endif; ?>
          <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
          <div class="form-text">JPG, PNG o WebP. Máximo 5 MB.</div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-admin-primary">
            <i class="bi bi-check-lg me-2"></i><?= $category ? 'Guardar cambios' : 'Crear categoría' ?>
          </button>
          <a href="<?= BASE_URL ?>/admin/categorias" class="btn btn-admin-outline">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</div>
