<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6">

        <div class="text-center mb-4">
          <i class="bi bi-person-plus" style="font-size:3rem;color:var(--gold)"></i>
          <h2 class="mt-2 fw-bold" style="color:var(--dark)">Crear cuenta</h2>
          <p class="text-muted small">Acumulá puntos en cada compra y accedé a descuentos exclusivos</p>
        </div>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
              <?php foreach ($errors as $e): ?>
                <li><?= $e ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm" style="border-radius:1rem">
          <div class="card-body p-4">
            <form method="POST" action="<?= BASE_URL ?>/cuenta/registro">
              <?= \App\Core\Csrf::field() ?>

              <div class="mb-3">
                <label class="form-label fw-500">Nombre completo *</label>
                <input type="text" name="name" class="form-control" required
                       value="<?= htmlspecialchars($old['name'] ?? '') ?>">
              </div>
              <div class="mb-3">
                <label class="form-label fw-500">Email *</label>
                <input type="email" name="email" class="form-control" required
                       value="<?= htmlspecialchars($old['email'] ?? '') ?>">
              </div>
              <div class="mb-3">
                <label class="form-label fw-500">Teléfono <span class="text-muted">(opcional)</span></label>
                <input type="tel" name="phone" class="form-control"
                       value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
              </div>
              <div class="mb-3">
                <label class="form-label fw-500">Contraseña *</label>
                <input type="password" name="password" class="form-control" id="regPass" required minlength="8">
                <div class="form-text">Mínimo 8 caracteres</div>
              </div>
              <div class="mb-4">
                <label class="form-label fw-500">Confirmar contraseña *</label>
                <input type="password" name="password_confirm" class="form-control" id="regPassConfirm" required>
                <div id="matchMsg" class="form-text" style="display:none"></div>
              </div>

              <!-- Beneficios -->
              <div class="p-3 rounded mb-4" style="background:var(--cream)">
                <div class="fw-600 mb-2" style="color:var(--dark);font-size:.9rem">
                  <i class="bi bi-star-fill me-1" style="color:var(--gold)"></i>Beneficios de tu cuenta
                </div>
                <ul class="mb-0 ps-3 small text-muted">
                  <li>Acumulá puntos en cada compra</li>
                  <li>Canjealos por descuentos reales</li>
                  <li>Historial de todos tus pedidos</li>
                  <li>Completar checkout más rápido</li>
                </ul>
              </div>

              <button type="submit" class="btn btn-gold w-100">
                <i class="bi bi-person-check me-1"></i>Crear mi cuenta
              </button>
            </form>
          </div>
        </div>

        <div class="text-center mt-4">
          <span class="text-muted small">¿Ya tenés cuenta?</span>
          <a href="<?= BASE_URL ?>/cuenta/login" class="ms-1 small fw-600" style="color:var(--gold)">Iniciá sesión</a>
        </div>

      </div>
    </div>
  </div>
</section>

<script>
const p = document.getElementById('regPass');
const c = document.getElementById('regPassConfirm');
const m = document.getElementById('matchMsg');
c.addEventListener('input', () => {
  if (!c.value) { m.style.display='none'; return; }
  m.style.display='block';
  if (p.value === c.value) {
    m.textContent = '✓ Las contraseñas coinciden';
    m.style.color = 'green';
  } else {
    m.textContent = '✗ Las contraseñas no coinciden';
    m.style.color = 'red';
  }
});
</script>
