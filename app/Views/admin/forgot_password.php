<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Recuperar contraseña — Cookie Bakery Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#2d2016,#4a3728);min-height:100vh;display:flex;align-items:center}
    .card{background:#fff;border-radius:20px;padding:2.5rem;box-shadow:0 25px 60px rgba(0,0,0,.3);max-width:420px;width:100%}
    .btn-gold{background:#bf9663;color:#fff;border:none;border-radius:50px;font-weight:700;padding:.75rem 2rem;width:100%;font-family:'Poppins',sans-serif}
    .btn-gold:hover{background:#a07840;color:#fff}
    .form-control{border:2px solid #e8ddd0;border-radius:10px;padding:.65rem 1rem;font-family:'Poppins',sans-serif}
    .form-control:focus{border-color:#bf9663;box-shadow:0 0 0 .2rem rgba(191,150,99,.15)}
  </style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card">
        <div class="text-center mb-4">
          <div style="font-size:3rem">🔑</div>
          <h4 class="fw-800 mt-2">Recuperar contraseña</h4>
          <p class="text-muted small">Ingresá tu email de administrador</p>
        </div>

        <?php if (!empty($message)): ?>
          <div class="alert alert-success py-2 px-3 small"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 px-3 small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
          <?= \App\Core\Csrf::field() ?>
          <div class="mb-4">
            <label class="form-label fw-600 small">Email de administrador</label>
            <input type="email" name="email" class="form-control" required autofocus
                   placeholder="admin@cookiebakery.com">
          </div>
          <button type="submit" class="btn-gold mb-3">Enviar link de recuperación</button>
        </form>
        <div class="text-center small">
          <a href="<?= BASE_URL ?>/admin/login" style="color:#bf9663">← Volver al login</a>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
