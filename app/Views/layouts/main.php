<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="base-url" content="<?= BASE_URL ?>">
  <meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?><?= APP_NAME ?></title>

  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- App CSS -->
  <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/app.css">
  <!-- Branding dinámico (colores + fuente desde admin) -->
  <?= \App\Helpers\Branding::injectStyles() ?>
</head>
<body>

<!-- Shipping banner -->
<div class="shipping-banner">
  🍪 Envío gratis en pedidos mayores a $10.000 &nbsp;|&nbsp; Retiro en local disponible
</div>

<!-- Toast container -->
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/">
      <?php $logoUrl = \App\Helpers\Branding::logoUrl(); ?>
      <?php if ($logoUrl): ?>
        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" style="height:48px;width:auto;object-fit:contain">
      <?php else: ?>
        <span class="navbar-brand-text"><?= \App\Helpers\Branding::storeName() ?></span>
      <?php endif; ?>
    </a>

    <div class="d-flex align-items-center gap-3 d-lg-none">
      <!-- Carrito mobile -->
      <a href="<?= BASE_URL ?>/carrito" class="cart-icon position-relative">
        <i class="bi bi-bag"></i>
        <span class="cart-badge" style="<?= ($cartCount ?? 0) > 0 ? '' : 'display:none' ?>">
          <?= $cartCount ?? 0 ?>
        </span>
      </a>
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
        <i class="bi bi-list fs-4"></i>
      </button>
    </div>

    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav mx-auto gap-1">
        <li class="nav-item">
          <a class="nav-link <?= ($_SERVER['REQUEST_URI'] === '/02-Eccomerce-Cookies/' || $_SERVER['REQUEST_URI'] === '/02-Eccomerce-Cookies') ? 'active' : '' ?>" href="<?= BASE_URL ?>/">Inicio</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/productos') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>/productos">Cookies</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/nosotros">Nosotros</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/contacto">Contacto</a>
        </li>
        <?php $cUser = \App\Core\CustomerAuth::user(); ?>
        <?php if ($cUser): ?>
          <li class="nav-item d-lg-none">
            <a class="nav-link" href="<?= BASE_URL ?>/cuenta/perfil"><i class="bi bi-person me-1"></i>Mi cuenta</a>
          </li>
          <li class="nav-item d-lg-none">
            <a class="nav-link text-danger" href="<?= BASE_URL ?>/cuenta/logout"><i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión</a>
          </li>
        <?php else: ?>
          <li class="nav-item d-lg-none">
            <a class="nav-link" href="<?= BASE_URL ?>/cuenta/login"><i class="bi bi-person me-1"></i>Ingresar / Registrarse</a>
          </li>
        <?php endif; ?>
      </ul>

      <div class="d-flex align-items-center gap-3">
        <!-- Cuenta cliente desktop -->
        <?php $cUser = \App\Core\CustomerAuth::user(); ?>
        <?php if ($cUser): ?>
          <div class="dropdown d-none d-lg-block">
            <button class="btn btn-sm dropdown-toggle d-flex align-items-center gap-2"
                    style="background:var(--cream);border:1px solid var(--border);color:var(--dark)"
                    data-bs-toggle="dropdown">
              <i class="bi bi-person-circle" style="color:var(--gold)"></i>
              <?= htmlspecialchars(explode(' ', $cUser['name'])[0]) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/cuenta/perfil"><i class="bi bi-person me-2"></i>Mi cuenta</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/cuenta/perfil#puntos"><i class="bi bi-star me-2" style="color:var(--gold)"></i>Mis puntos</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/cuenta/logout"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/cuenta/login" class="btn btn-sm d-none d-lg-inline-flex align-items-center gap-1"
             style="background:var(--cream);border:1px solid var(--border);color:var(--dark)">
            <i class="bi bi-person"></i> Ingresar
          </a>
        <?php endif; ?>
        <!-- Carrito desktop -->
        <a href="<?= BASE_URL ?>/carrito" class="cart-icon position-relative d-none d-lg-flex">
          <i class="bi bi-bag fs-5"></i>
          <span class="cart-badge" style="<?= ($cartCount ?? 0) > 0 ? '' : 'display:none' ?>">
            <?= $cartCount ?? 0 ?>
          </span>
        </a>
        <a href="<?= BASE_URL ?>/productos" class="btn btn-gold btn-sm d-none d-lg-inline-flex">
          <i class="bi bi-bag-plus me-1"></i> Pedir ahora
        </a>
      </div>
    </div>
  </div>
</nav>

<!-- Flash messages -->
<?php if (!empty($flash['success'])): ?>
  <div class="container mt-3">
    <div class="alert alert-success alert-dismissible fade show flash-alert" role="alert">
      <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flash['success']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  </div>
<?php endif; ?>
<?php if (!empty($flash['error'])): ?>
  <div class="container mt-3">
    <div class="alert alert-danger alert-dismissible fade show flash-alert" role="alert">
      <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($flash['error']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  </div>
<?php endif; ?>

<!-- CONTENIDO DE LA VISTA -->
<?php require $viewFile; ?>

<!-- Footer -->
<footer class="mt-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <?php $logoUrl = \App\Helpers\Branding::logoUrl(); ?>
        <?php if ($logoUrl): ?>
          <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" style="height:56px;width:auto;object-fit:contain;margin-bottom:.75rem;display:block">
        <?php else: ?>
          <div class="footer-brand"><?= \App\Helpers\Branding::storeName() ?></div>
        <?php endif; ?>
        <p class="text-white-50 small">New York Style Cookies en Buenos Aires. Masa mantecosa, bordes crocantes y centro súper suave. 🍪</p>
        <div class="d-flex gap-2 mt-2">
          <a href="#" class="social-icon"><i class="bi bi-instagram"></i></a>
          <a href="#" class="social-icon"><i class="bi bi-tiktok"></i></a>
          <a href="#" class="social-icon"><i class="bi bi-whatsapp"></i></a>
        </div>
      </div>
      <div class="col-lg-2">
        <h5>Links</h5>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="<?= BASE_URL ?>/">Inicio</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/productos">Cookies</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/nosotros">Nosotros</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/contacto">Contacto</a></li>
        </ul>
      </div>
      <div class="col-lg-3">
        <h5>Horarios</h5>
        <ul class="list-unstyled small text-white-50">
          <li class="mb-1">Lunes: Cerrado</li>
          <li class="mb-1">Martes a Jueves: 14:00 – 19:00</li>
          <li class="mb-1">Viernes a Domingo: 14:00 – 19:30</li>
        </ul>
      </div>
      <div class="col-lg-3">
        <h5>Contacto</h5>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="https://wa.me/5491130730496"><i class="bi bi-whatsapp me-2"></i>WhatsApp</a></li>
          <li class="mb-2"><a href="https://instagram.com"><i class="bi bi-instagram me-2"></i>Instagram</a></li>
          <li class="mb-2 text-white-50"><i class="bi bi-geo-alt me-2"></i>Buenos Aires, Argentina</li>
        </ul>
      </div>
    </div>

    <hr class="footer-divider">

    <div class="d-flex flex-wrap justify-content-between align-items-center small text-white-50">
      <span>© <?= date('Y') ?> Cookie Bakery. Todos los derechos reservados.</span>
      <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
        <img src="https://www.mercadopago.com/org-img/MP3/home/logomp.gif" height="20" alt="Mercado Pago">
        <span>· Pagos seguros</span>
      </div>
    </div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- App JS -->
<script src="<?= PUBLIC_URL ?>/js/app.js"></script>
</body>
</html>
