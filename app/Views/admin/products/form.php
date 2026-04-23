<form method="POST" enctype="multipart/form-data">
  <?= \App\Core\Csrf::field() ?>
  <div class="row g-4">
    <!-- Columna principal -->
    <div class="col-lg-8">
      <div class="admin-form-section">
        <h6>Información básica</h6>
        <div class="mb-3">
          <label class="form-label fw-600">Nombre del producto *</label>
          <input type="text" name="name" id="nameInput" class="form-control" required
                 value="<?= htmlspecialchars($product['name'] ?? '') ?>"
                 placeholder="Ej: Classic Chocolate Chip">
        </div>
        <div class="mb-3">
          <label class="form-label fw-600">Slug (URL) *</label>
          <div class="input-group">
            <span class="input-group-text text-muted small">/producto?slug=</span>
            <input type="text" name="slug" id="slugInput" class="form-control"
                   value="<?= htmlspecialchars($product['slug'] ?? '') ?>" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-600">Descripción corta (tagline)</label>
          <input type="text" name="short_desc" class="form-control" maxlength="280"
                 value="<?= htmlspecialchars($product['short_desc'] ?? '') ?>"
                 placeholder="Aparece en la tarjeta del catálogo">
        </div>
        <div class="mb-3">
          <label class="form-label fw-600">Descripción completa</label>
          <textarea name="description" class="form-control" rows="5"
                    placeholder="Descripción detallada del producto..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label fw-600">Ingredientes</label>
          <textarea name="ingredients" class="form-control" rows="2"
                    placeholder="Lista de ingredientes..."><?= htmlspecialchars($product['ingredients'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- Galería -->
      <div class="admin-form-section">
        <h6>Galería de imágenes</h6>
        <?php if (!empty($images)): ?>
        <div class="d-flex flex-wrap gap-3 mb-3">
          <?php foreach ($images as $img): ?>
          <div class="img-preview-wrapper">
            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($img['filename']) ?>"
                 class="img-preview" alt="">
            <button type="button" class="img-delete-btn"
                    onclick="deleteImage(<?= $img['id'] ?>, this)" title="Eliminar">
              <i class="bi bi-x"></i>
            </button>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <label class="form-label fw-600">Agregar imágenes a la galería</label>
        <input type="file" name="gallery[]" class="form-control" accept="image/*" multiple>
        <div class="form-text">Podés seleccionar múltiples imágenes. JPG, PNG o WebP, máx 5 MB cada una.</div>
      </div>
    </div>

    <!-- Columna lateral -->
    <div class="col-lg-4">
      <!-- Imagen principal -->
      <div class="admin-form-section">
        <h6>Imagen principal</h6>
        <?php if (!empty($product['main_image'])): ?>
          <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($product['main_image']) ?>"
               class="img-preview w-100 mb-3" style="height:200px;width:100%!important">
        <?php endif; ?>
        <input type="file" name="main_image" id="mainImageInput"
               class="form-control" accept="image/jpeg,image/png,image/webp">
        <img id="mainImagePreview" style="display:none" class="img-preview mt-2 w-100">
        <div class="form-text">JPG, PNG o WebP. Se redimensiona automáticamente a 1200px.</div>
      </div>

      <!-- Precio y categoría -->
      <div class="admin-form-section">
        <h6>Precio y categoría</h6>
        <div class="mb-3">
          <label class="form-label fw-600">Precio (ARS) *</label>
          <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="price" class="form-control" step="0.01" min="0" required
                   value="<?= htmlspecialchars($product['price'] ?? '') ?>"
                   placeholder="3600.00">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-600">Precio tachado (opcional)</label>
          <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="compare_price" class="form-control" step="0.01" min="0"
                   value="<?= htmlspecialchars($product['compare_price'] ?? '') ?>"
                   placeholder="0.00">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-600">Categoría</label>
          <select name="category_id" class="form-select">
            <option value="">Sin categoría</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"
                      <?= ($product['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-600">Orden de aparición</label>
          <input type="number" name="sort_order" class="form-control" min="0"
                 value="<?= htmlspecialchars($product['sort_order'] ?? '0') ?>">
        </div>
      </div>

      <!-- Estado -->
      <div class="admin-form-section">
        <h6>Estado</h6>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-600">Disponible</label>
          <label class="toggle-switch">
            <input type="checkbox" name="available" value="1"
                   <?= ($product['available'] ?? 1) ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
          </label>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <label class="fw-600">Destacado en home</label>
          <label class="toggle-switch">
            <input type="checkbox" name="featured" value="1"
                   <?= !empty($product['featured']) ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
          </label>
        </div>
      </div>

      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-admin-primary">
          <i class="bi bi-check-lg me-2"></i><?= $product ? 'Guardar cambios' : 'Crear producto' ?>
        </button>
        <a href="<?= BASE_URL ?>/admin/productos" class="btn btn-admin-outline text-center">Cancelar</a>
      </div>
    </div>
  </div>
</form>

<script>
async function deleteImage(imageId, btn) {
  if (!confirm('¿Eliminar esta imagen?')) return;
  const res = await fetch(`<?= BASE_URL ?>/admin/productos/imagen-eliminar`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ id: imageId, _csrf_token: '<?= \App\Core\Csrf::token() ?>' }),
  });
  const data = await res.json();
  if (data.success) {
    btn.closest('.img-preview-wrapper').remove();
  }
}
</script>
