<?php use App\Helpers\Formatter; ?>

<section class="py-5">
  <div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb small text-muted">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Inicio</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/productos">Cookies</a></li>
        <?php if (!empty($product['category_name'])): ?>
          <li class="breadcrumb-item">
            <a href="<?= BASE_URL ?>/productos?categoria=<?= urlencode($product['category_slug']) ?>">
              <?= htmlspecialchars($product['category_name']) ?>
            </a>
          </li>
        <?php endif; ?>
        <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
      </ol>
    </nav>

    <div class="row g-5">
      <!-- Galería -->
      <div class="col-lg-6">
        <div class="product-gallery-main mb-3">
          <?php
          $mainImg = $product['main_image'];
          $mainSrc = $mainImg ? UPLOAD_URL . '/' . htmlspecialchars($mainImg) : null;
          ?>
          <?php if ($mainSrc): ?>
            <img src="<?= $mainSrc ?>" id="galleryMain" alt="<?= htmlspecialchars($product['name']) ?>">
          <?php else: ?>
            <div style="height:400px;display:flex;align-items:center;justify-content:center;font-size:8rem;background:var(--cream)">🍪</div>
          <?php endif; ?>
        </div>

        <?php if (!empty($images)): ?>
        <div class="product-thumbnails">
          <?php if ($mainSrc): ?>
          <div class="product-thumb active">
            <img src="<?= $mainSrc ?>" alt="Principal">
          </div>
          <?php endif; ?>
          <?php foreach ($images as $img): ?>
          <div class="product-thumb">
            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($img['filename']) ?>"
                 alt="<?= htmlspecialchars($img['alt_text'] ?? $product['name']) ?>">
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Info del producto -->
      <div class="col-lg-6">
        <?php if (!empty($product['category_name'])): ?>
          <a href="<?= BASE_URL ?>/productos?categoria=<?= urlencode($product['category_slug']) ?>"
             class="text-gold fw-semibold small text-uppercase"
             style="letter-spacing:1px">
            <?= htmlspecialchars($product['category_name']) ?>
          </a>
        <?php endif; ?>

        <h1 class="mt-1 mb-2" style="font-size:2rem;font-weight:800">
          <?= htmlspecialchars($product['name']) ?>
        </h1>

        <!-- Precio -->
        <div class="d-flex align-items-baseline gap-3 mb-3">
          <span class="product-detail-price"><?= Formatter::price((float)$product['price']) ?></span>
          <?php if (!empty($product['compare_price'])): ?>
            <span class="product-detail-compare"><?= Formatter::price((float)$product['compare_price']) ?></span>
            <?php
              $discount = round((1 - $product['price'] / $product['compare_price']) * 100);
            ?>
            <span class="badge" style="background:var(--orange)">-<?= $discount ?>%</span>
          <?php endif; ?>
        </div>

        <?php if (!empty($product['short_desc'])): ?>
          <p class="lead" style="font-size:1rem;color:var(--text);line-height:1.7;margin-bottom:1.5rem">
            <?= htmlspecialchars($product['short_desc']) ?>
          </p>
        <?php endif; ?>

        <?php if (!empty($product['description'])): ?>
          <p style="color:var(--gray);line-height:1.8;margin-bottom:1.5rem">
            <?= nl2br(htmlspecialchars($product['description'])) ?>
          </p>
        <?php endif; ?>

        <?php if (!empty($product['ingredients'])): ?>
        <div class="mb-3">
          <strong class="small text-uppercase" style="letter-spacing:1px;color:var(--gold)">
            <i class="bi bi-list-ul me-1"></i>Ingredientes
          </strong>
          <p class="small text-muted mt-1"><?= htmlspecialchars($product['ingredients']) ?></p>
        </div>
        <?php endif; ?>

        <hr style="border-color:var(--border)">

        <?php if ($product['available']): ?>
          <!-- Cantidad y agregar -->
          <div class="d-flex align-items-center gap-3 mb-3">
            <label class="fw-semibold small">Cantidad:</label>
            <div class="qty-control">
              <button class="qty-btn qty-minus" type="button">−</button>
              <input type="number" class="qty-input" id="qtyInput" value="1" min="1" max="99">
              <button class="qty-btn qty-plus" type="button">+</button>
            </div>
          </div>
          <button class="btn btn-orange btn-lg w-100 mb-3"
                  data-add-cart="<?= $product['id'] ?>"
                  data-original-text="<i class='bi bi-bag-plus me-2'></i>Agregar al carrito">
            <i class="bi bi-bag-plus me-2"></i>Agregar al carrito
          </button>
          <a href="<?= BASE_URL ?>/carrito" class="btn btn-gold-outline btn-lg w-100">
            <i class="bi bi-bag-check me-2"></i>Ver carrito
          </a>
        <?php else: ?>
          <div class="alert alert-secondary">
            <i class="bi bi-clock me-2"></i>Este sabor está temporalmente agotado.
            <a href="<?= BASE_URL ?>/productos" class="alert-link ms-2">Ver otros sabores</a>
          </div>
        <?php endif; ?>

        <!-- Garantías -->
        <div class="row g-2 mt-3">
          <div class="col-4 text-center">
            <div class="small text-muted"><i class="bi bi-shield-check text-gold"></i><br>Pago seguro</div>
          </div>
          <div class="col-4 text-center">
            <div class="small text-muted"><i class="bi bi-clock text-gold"></i><br>Listas el mismo día</div>
          </div>
          <div class="col-4 text-center">
            <div class="small text-muted"><i class="bi bi-star text-gold"></i><br>Calidad garantizada</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
