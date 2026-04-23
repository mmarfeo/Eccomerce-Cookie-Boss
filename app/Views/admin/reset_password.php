<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Nueva contraseña — Cookie Bakery Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#2d2016,#4a3728);min-height:100vh;display:flex;align-items:center}
    .card{background:#fff;border-radius:20px;padding:2.5rem;box-shadow:0 25px 60px rgba(0,0,0,.3);max-width:420px;width:100%}
    .btn-gold{background:#bf9663;color:#fff;border:none;border-radius:50px;font-weight:700;padding:.75rem 2rem;width:100%;font-family:'Poppins',sans-serif}
    .btn-gold:hover{background:#a07840;color:#fff}
    .form-control{border:2px solid #e8ddd0;border-radius:10px;padding:.65rem 1rem;font-family:'Poppins',sans-serif}
    .form-control:focus{border-color:#bf9663;box-shadow:0 0 0 .2rem rgba(191,150,99,.15)}
    .strength{height:4px;border-radius:2px;margin-top:6px;transition:all .3s}
  </style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card">
        <div class="text-center mb-4">
          <div style="font-size:3rem">🔒</div>
          <h4 class="fw-800 mt-2">Nueva contraseña</h4>
          <p class="text-muted small">Ingresá tu nueva contraseña de acceso al panel</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 px-3 small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="resetForm">
          <?= \App\Core\Csrf::field() ?>
          <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

          <div class="mb-3">
            <label class="form-label fw-600 small">Nueva contraseña</label>
            <input type="password" name="password" id="newPass" class="form-control" required
                   minlength="8" placeholder="Mínimo 8 caracteres" autofocus>
            <div class="strength" id="strengthBar" style="background:#eee"></div>
            <div id="strengthText" class="form-text small"></div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-600 small">Confirmar contraseña</label>
            <input type="password" name="password_confirm" id="confirmPass" class="form-control" required
                   placeholder="Repetir contraseña">
            <div id="matchText" class="form-text small"></div>
          </div>

          <button type="submit" class="btn-gold">Restablecer contraseña</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
const newPass = document.getElementById('newPass');
const confirmPass = document.getElementById('confirmPass');
const bar = document.getElementById('strengthBar');
const txt = document.getElementById('strengthText');
const matchTxt = document.getElementById('matchText');

newPass.addEventListener('input', () => {
  const v = newPass.value;
  let score = 0;
  if (v.length >= 8) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;
  const colors = ['#dc3545','#ffc107','#17a2b8','#28a745'];
  const labels = ['Muy débil','Regular','Buena','Fuerte'];
  bar.style.width = (score * 25) + '%';
  bar.style.background = colors[score-1] || '#eee';
  txt.textContent = score > 0 ? labels[score-1] : '';
  txt.style.color = colors[score-1] || '#999';
});

confirmPass.addEventListener('input', () => {
  if (confirmPass.value === newPass.value) {
    matchTxt.textContent = '✓ Las contraseñas coinciden';
    matchTxt.style.color = '#28a745';
  } else {
    matchTxt.textContent = '✗ No coinciden';
    matchTxt.style.color = '#dc3545';
  }
});
</script>
</body>
</html>
