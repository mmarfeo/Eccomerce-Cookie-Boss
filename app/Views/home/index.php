<?php use App\Helpers\Formatter; ?>

<!-- ── HERO ───────────────────────────────────────────────── -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6 hero-content">
        <div class="hero-tag">✦ New York Style Cookies</div>
        <h1>Las cookies<br>que <span>te vuelan</span><br>la cabeza</h1>
        <p>Masa mantecosa, bordes dorados y crocantes, centro súper suave y rellenos que te sorprenden. Hechas a mano con los mejores ingredientes.</p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="<?= BASE_URL ?>/productos" class="btn btn-orange btn-lg">
            <i class="bi bi-bag-heart me-2"></i>Ver cookies
          </a>
          <a href="<?= BASE_URL ?>/nosotros" class="btn btn-gold-outline btn-lg" style="color:#fff;border-color:rgba(255,255,255,0.4)">
            Conocernos
          </a>
        </div>
        <div class="d-flex gap-4 mt-4">
          <div style="color:rgba(255,255,255,0.8)">
            <div style="font-size:1.5rem;font-weight:800;color:#fff">16+</div>
            <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:1px">Sabores</div>
          </div>
          <div style="border-left:1px solid rgba(255,255,255,0.2);padding-left:1.5rem;color:rgba(255,255,255,0.8)">
            <div style="font-size:1.5rem;font-weight:800;color:#fff">4.9 ⭐</div>
            <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:1px">Calificación</div>
          </div>
          <div style="border-left:1px solid rgba(255,255,255,0.2);padding-left:1.5rem;color:rgba(255,255,255,0.8)">
            <div style="font-size:1.5rem;font-weight:800;color:#fff">100%</div>
            <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:1px">Artesanal</div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 hero-image text-center mt-4 mt-lg-0">
        <div style="width:420px;height:420px;margin:0 auto;background:rgba(191,150,99,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12rem;">
          🍪
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── CATEGORÍAS ──────────────────────────────────────────── -->
<?php if (!empty($categories)): ?>
<section class="py-5 bg-cream">
  <div class="container">
    <div class="row align-items-end mb-4">
      <div class="col">
        <h2 class="section-title">Nuestras<br>categorías</h2>
      </div>
      <div class="col-auto">
        <a href="<?= BASE_URL ?>/productos" class="btn btn-gold-outline">Ver todas →</a>
      </div>
    </div>
    <div class="row g-3">
      <?php
      $icons = ['Clásicas' => '🍫', 'Especiales' => '🌟', 'Premium' => '💎', 'Temporada' => '🍂'];
      foreach ($categories as $cat):
        if (!$cat['active']) continue;
        $icon = $icons[$cat['name']] ?? '🍪';
      ?>
      <div class="col-6 col-md-3">
        <a href="<?= BASE_URL ?>/productos?categoria=<?= urlencode($cat['slug']) ?>" class="category-card">
          <div class="category-card-icon"><?= $icon ?></div>
          <div class="category-card-name"><?= htmlspecialchars($cat['name']) ?></div>
          <div class="category-card-count"><?= $cat['product_count'] ?> sabores</div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── PRODUCTOS DESTACADOS ────────────────────────────────── -->
<?php if (!empty($featured)): ?>
<section class="py-5">
  <div class="container">
    <div class="row align-items-end mb-4">
      <div class="col">
        <h2 class="section-title">Nuestras<br>favoritas</h2>
        <p class="section-subtitle">Las más pedidas por nuestros clientes</p>
      </div>
      <div class="col-auto">
        <a href="<?= BASE_URL ?>/productos" class="btn btn-gold-outline">Ver todas →</a>
      </div>
    </div>
    <div class="row g-4">
      <?php foreach ($featured as $product): ?>
      <div class="col-6 col-md-4 col-lg-3">
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
              <div class="product-card-desc"><?= htmlspecialchars(substr($product['short_desc'], 0, 80)) ?>...</div>
            <?php endif; ?>
            <div class="product-card-footer">
              <div>
                <div class="product-price"><?= Formatter::price((float)$product['price']) ?></div>
                <?php if (!empty($product['compare_price'])): ?>
                  <div class="product-price-compare"><?= Formatter::price((float)$product['compare_price']) ?></div>
                <?php endif; ?>
              </div>
              <button class="btn btn-gold btn-sm" data-add-cart="<?= $product['id'] ?>"
                      data-original-text="<i class='bi bi-bag-plus'></i>">
                <i class="bi bi-bag-plus"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── POR QUÉ ELEGIRNOS ───────────────────────────────────── -->
<section class="py-5 bg-cream">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title d-inline-block">¿Por qué elegirnos?</h2>
    </div>
    <div class="row g-4 text-center">
      <div class="col-md-3">
        <div class="p-3">
          <div style="font-size:3rem;margin-bottom:1rem">🧈</div>
          <h5 style="font-weight:700">Ingredientes premium</h5>
          <p class="text-muted small">Solo usamos manteca real, chocolate de calidad y rellenos originales.</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="p-3">
          <div style="font-size:3rem;margin-bottom:1rem">🤲</div>
          <h5 style="font-weight:700">100% artesanal</h5>
          <p class="text-muted small">Cada cookie es hecha a mano con amor y dedicación.</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="p-3">
          <div style="font-size:3rem;margin-bottom:1rem">📦</div>
          <h5 style="font-weight:700">Empaque hermoso</h5>
          <p class="text-muted small">Perfectas para regalar. Presentación cuidada en cada pedido.</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="p-3">
          <div style="font-size:3rem;margin-bottom:1rem">🚀</div>
          <h5 style="font-weight:700">Pago seguro</h5>
          <p class="text-muted small">Procesamos tus pagos con Mercado Pago. 100% seguro.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── CTA FINAL ───────────────────────────────────────────── -->
<section class="py-5" style="background:linear-gradient(135deg,#2d2016,#4a3728)">
  <div class="container text-center">
    <h2 class="fw-800 mb-3" style="color:#fff;font-size:2.5rem;font-weight:800">
      ¿Listo para probarlas? 🍪
    </h2>
    <p style="color:rgba(255,255,255,0.7);font-size:1.1rem;margin-bottom:2rem">
      Elegí tu sabor favorito y lo preparamos especialmente para vos.
    </p>
    <a href="<?= BASE_URL ?>/productos" class="btn btn-orange btn-lg px-5">
      Ver todas las cookies →
    </a>
  </div>
</section>
