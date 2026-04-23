<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Cookie Bakery</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { font-family:'Poppins',sans-serif; background:linear-gradient(135deg,#2d2016,#4a3728); min-height:100vh; display:flex; align-items:center; }
    .login-card { background:#fff; border-radius:20px; padding:2.5rem; box-shadow:0 25px 60px rgba(0,0,0,0.3); max-width:420px; width:100%; }
    .login-brand { font-size:1.8rem; font-weight:800; text-align:center; margin-bottom:0.3rem; }
    .login-brand span { color:#bf9663; }
    .btn-gold { background:#bf9663;color:#fff;border:none;border-radius:50px;font-weight:700;padding:.75rem 2rem;width:100%;font-family:'Poppins',sans-serif;font-size:1rem;transition:all .25s; }
    .btn-gold:hover { background:#a07840;color:#fff; }
    .form-control { border:2px solid #e8ddd0;border-radius:10px;padding:.65rem 1rem;font-family:'Poppins',sans-serif; }
    .form-control:focus { border-color:#bf9663;box-shadow:0 0 0 .2rem rgba(191,150,99,.15); }
  </style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="login-card">
        <div class="login-brand">Cookie<span>Bakery</span></div>
        <p class="text-center text-muted small mb-4">Panel de administración</p>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger alert-sm py-2 px-3 small">
            <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="">
          <?= \App\Core\Csrf::field() ?>
          <div class="mb-3">
            <label class="form-label fw-600 small">Email</label>
            <div class="input-group">
              <span class="input-group-text" style="border:2px solid #e8ddd0;border-right:none;border-radius:10px 0 0 10px">
                <i class="bi bi-envelope"></i>
              </span>
              <input type="email" name="email" class="form-control" style="border-left:none;border-radius:0 10px 10px 0"
                     placeholder="admin@cookiebakery.com" required autofocus
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-600 small">Contraseña</label>
            <div class="input-group">
              <span class="input-group-text" style="border:2px solid #e8ddd0;border-right:none;border-radius:10px 0 0 10px">
                <i class="bi bi-lock"></i>
              </span>
              <input type="password" name="password" class="form-control" style="border-left:none;border-radius:0 10px 10px 0"
                     placeholder="••••••••" required>
            </div>
          </div>
          <button type="submit" class="btn-gold">
            <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
          </button>
        </form>

        <div class="text-center mt-3">
          <a href="<?= BASE_URL ?>/admin/forgot-password" class="small" style="color:#bf9663">
            ¿Olvidaste tu contraseña?
          </a>
        </div>
        <div class="text-center mt-2">
          <a href="<?= BASE_URL ?>/" class="text-muted small">
            <i class="bi bi-arrow-left me-1"></i>Volver a la tienda
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
