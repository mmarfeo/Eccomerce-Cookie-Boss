<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>Admin | Cookie Bakery</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= PUBLIC_URL ?>/css/admin.css">
  <?= \App\Helpers\Branding::injectStyles() ?>
</head>
<body>

<!-- Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-brand">
    <?php $logoUrl = \App\Helpers\Branding::logoUrl(); ?>
    <?php if ($logoUrl): ?>
      <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" style="height:40px;width:auto;object-fit:contain;margin-bottom:.5rem;display:block">
    <?php else: ?>
      <div class="sidebar-brand-text"><?= \App\Helpers\Branding::storeName() ?></div>
    <?php endif; ?>
    <div class="sidebar-subtitle">Panel Admin</div>
  </div>

  <nav class="sidebar-menu">
    <div class="sidebar-section">General</div>
    <a href="<?= BASE_URL ?>/admin/dashboard"
       class="sidebar-item <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : '' ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="sidebar-section">Catálogo</div>
    <a href="<?= BASE_URL ?>/admin/categorias"
       class="sidebar-item <?= strpos($_SERVER['REQUEST_URI'], 'categorias') !== false ? 'active' : '' ?>">
      <i class="bi bi-grid"></i> Categorías
    </a>
    <a href="<?= BASE_URL ?>/admin/productos"
       class="sidebar-item <?= strpos($_SERVER['REQUEST_URI'], 'productos') !== false ? 'active' : '' ?>">
      <i class="bi bi-bag"></i> Productos
    </a>

    <div class="sidebar-section">Ventas</div>
    <a href="<?= BASE_URL ?>/admin/pedidos"
       class="sidebar-item <?= strpos($_SERVER['REQUEST_URI'], 'pedidos') !== false ? 'active' : '' ?>">
      <i class="bi bi-receipt"></i> Pedidos
    </a>
    <a href="<?= BASE_URL ?>/admin/clientes"
       class="sidebar-item <?= strpos($_SERVER['REQUEST_URI'], 'clientes') !== false ? 'active' : '' ?>">
      <i class="bi bi-people"></i> Clientes
    </a>
    <a href="<?= BASE_URL ?>/admin/puntos"
       class="sidebar-item <?= strpos($_SERVER['REQUEST_URI'], 'puntos') !== false ? 'active' : '' ?>">
      <i class="bi bi-star"></i> Puntos fidelidad
    </a>
    <a href="<?= BASE_URL ?>/admin/cupones"
       class="sidebar-item <?= strpos($_SERVER['REQUEST_URI'], 'cupones') !== false ? 'active' : '' ?>">
      <i class="bi bi-ticket-perforated"></i> Cupones
    </a>
    <a href="<?= BASE_URL ?>/admin/reportes"
       class="sidebar-item <?= strpos($_SERVER['REQUEST_URI'], 'reportes') !== false ? 'active' : '' ?>">
      <i class="bi bi-bar-chart"></i> Reportes
    </a>

    <div class="sidebar-section">Sistema</div>
    <?php if (($authUser['role'] ?? '') === 'admin'): ?>
    <a href="<?= BASE_URL ?>/admin/configuracion"
       class="sidebar-item <?= strpos($_SERVER['REQUEST_URI'], 'configuracion') !== false ? 'active' : '' ?>">
      <i class="bi bi-gear"></i> Configuración
    </a>
    <a href="<?= BASE_URL ?>/admin/usuarios"
       class="sidebar-item <?= strpos($_SERVER['REQUEST_URI'], 'usuarios') !== false ? 'active' : '' ?>">
      <i class="bi bi-people-fill"></i> Usuarios panel
    </a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/" target="_blank" class="sidebar-item">
      <i class="bi bi-box-arrow-up-right"></i> Ver tienda
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar">
        <?= strtoupper(substr($authUser['name'] ?? 'A', 0, 1)) ?>
      </div>
      <div>
        <div style="font-size:.8rem;font-weight:600"><?= htmlspecialchars($authUser['name'] ?? '') ?></div>
        <div style="font-size:.7rem;opacity:.7">
          <?php if (($authUser['role'] ?? '') === 'admin'): ?>
            <i class="bi bi-shield-fill-check me-1" style="color:var(--gold)"></i>Admin
          <?php else: ?>
            <i class="bi bi-person-badge me-1"></i>Encargado
          <?php endif; ?>
        </div>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/admin/logout" class="btn btn-sm btn-outline-light mt-3 w-100" style="font-size:.75rem">
      <i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión
    </a>
  </div>
</aside>

<!-- Topbar -->
<header class="admin-topbar">
  <div class="d-flex align-items-center gap-3">
    <button class="btn btn-sm d-lg-none" id="sidebarToggle"
            style="background:none;border:none;font-size:1.3rem">
      <i class="bi bi-list"></i>
    </button>
    <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>
  </div>
  <div class="d-flex align-items-center gap-2">
    <a href="<?= BASE_URL ?>/admin/pedidos?estado=pending"
       class="btn btn-sm" style="background:var(--cream);border:1px solid var(--border);font-size:.8rem">
      <i class="bi bi-bell me-1"></i>Pedidos pendientes
    </a>
  </div>
</header>

<!-- Main -->
<main class="admin-main">
  <div class="admin-content">

    <!-- Flash messages -->
    <?php if (!empty($flash['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flash['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (!empty($flash['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($flash['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php require $viewFile; ?>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
  document.getElementById('adminSidebar').classList.toggle('open');
});

// Auto-slug desde nombre
const nameInput = document.getElementById('nameInput');
const slugInput = document.getElementById('slugInput');
if (nameInput && slugInput) {
  nameInput.addEventListener('input', () => {
    if (!slugInput.dataset.manual) {
      slugInput.value = nameInput.value.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
        .replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-').trim();
    }
  });
  slugInput.addEventListener('input', () => { slugInput.dataset.manual = '1'; });
}

// Image preview
document.getElementById('mainImageInput')?.addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    let preview = document.getElementById('mainImagePreview');
    if (!preview) { preview = document.createElement('img'); preview.id = 'mainImagePreview'; preview.className = 'img-preview mt-2'; this.parentElement.appendChild(preview); }
    preview.src = e.target.result;
    preview.style.display = 'block';
  };
  reader.readAsDataURL(file);
});
</script>
</body>
</html>
