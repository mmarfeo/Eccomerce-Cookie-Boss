<?php use App\Helpers\Formatter; ?>

<section class="py-5">
  <div class="container">
    <div class="row align-items-center mb-4">
      <div class="col">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb small text-muted mb-1">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Inicio</a></li>
            <li class="breadcrumb-item active">Cookies</li>
          </ol>
        </nav>
        <h1 class="section-title"><?= htmlspecialchars($pageTitle) ?></h1>
        <p class="section-subtitle"><?= count($products) ?> sabores disponibles</p>
      </div>
    </div>

    <!-- Filtros -->
    <div class="category-filter">
      <a href="<?= BASE_URL ?>/productos"
         class="filter-btn <?= empty($currentCat) ? 'active' : '' ?>">
        Todos
      </a>
      <?php foreach ($categories as $cat): ?>
        <?php if (!$cat['active']) continue; ?>
        <a href="<?= BASE_URL ?>/productos?categoria=<?= urlencode($cat['slug']) ?>"
           class="filter-btn <?= ($currentCat && $currentCat['slug'] === $cat['slug']) ? 'active' : '' ?>">
          <?= htmlspecialchars($cat['name']) ?>
          <span class="ms-1 opacity-60">(<?= $cat['product_count'] ?>)</span>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Grid de productos -->
    <?php if (empty($products)): ?>
      <div class="text-center py-5">
        <div style="font-size:5rem">🍪</div>
        <h4 class="mt-3">No hay cookies en esta categoría</h4>
        <p class="text-muted">Probá con otra categoría o mirá todas nuestras cookies.</p>
        <a href="<?= BASE_URL ?>/productos" class="btn btn-gold mt-2">Ver todas</a>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($products as $product): ?>
        <div class="col-6 col-md-4 col-lg-3 fade-in">
          <div class="product-card">
            <a href="<?= BASE_URL ?>/producto?slug=<?= urlencode($product['slug']) ?>" class="text-decoration-none">
              <div class="product-card-img">
                <?php if ($product['main_image']): ?>
                  <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($product['main_image']) ?>"
                       alt="<?= htmlspecialchars($product['name']) ?>"
                       loading="lazy">
                <?php else: ?>
                  <div class="product-card-placeholder">🍪</div>
                <?php endif; ?>
                <?php if ($product['featured']): ?>
                  <span class="product-badge">⭐ Top</span>
                <?php endif; ?>
                <?php if (!$product['available']): ?>
                  <span class="product-badge" style="background:#6c757d">Agotada</span>
                <?php endif; ?>
              </div>
            </a>
            <div class="product-card-body">
              <?php if (!empty($product['category_name'])): ?>
                <div class="product-card-category"><?= htmlspecialchars($product['category_name']) ?></div>
              <?php endif; ?>
              <a href="<?= BASE_URL ?>/producto?slug=<?= urlencode($product['slug']) ?>" class="text-decoration-none">
                <div class="product-card-title"><?= htmlspecialchars($product['name']) ?></div>
              </a>
              <?php if (!empty($product['short_desc'])): ?>
                <div class="product-card-desc"><?= htmlspecialchars(substr($product['short_desc'], 0, 75)) ?>...</div>
              <?php endif; ?>
              <div class="product-card-footer">
                <div>
                  <div class="product-price"><?= Formatter::price((float)$product['price']) ?></div>
                  <?php if (!empty($product['compare_price'])): ?>
                    <div class="product-price-compare"><?= Formatter::price((float)$product['compare_price']) ?></div>
                  <?php endif; ?>
                </div>
                <?php if ($product['available']): ?>
                  <button class="btn btn-gold btn-sm" data-add-cart="<?= $product['id'] ?>"
                          data-original-text="<i class='bi bi-bag-plus'></i>">
                    <i class="bi bi-bag-plus"></i>
                  </button>
                <?php else: ?>
                  <button class="btn btn-secondary btn-sm" disabled>Agotada</button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
