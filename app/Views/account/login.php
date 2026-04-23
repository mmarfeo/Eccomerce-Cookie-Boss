<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5">

        <div class="text-center mb-4">
          <i class="bi bi-person-circle" style="font-size:3rem;color:var(--gold)"></i>
          <h2 class="mt-2 fw-bold" style="color:var(--dark)">Iniciar sesión</h2>
          <p class="text-muted small">Accedé a tu cuenta para ver tus puntos y pedidos</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm" style="border-radius:1rem">
          <div class="card-body p-4">
            <form method="POST" action="<?= BASE_URL ?>/cuenta/login">
              <?= \App\Core\Csrf::field() ?>

              <div class="mb-3">
                <label class="form-label fw-500">Email</label>
                <input type="email" name="email" class="form-control" required autofocus
                       value="<?= htmlspecialchars($old_email ?? '') ?>">
              </div>
              <div class="mb-4">
                <label class="form-label fw-500">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
              </div>

              <button type="submit" class="btn btn-gold w-100">
                <i class="bi bi-box-arrow-in-right me-1"></i>Ingresar
              </button>
            </form>
          </div>
        </div>

        <div class="text-center mt-4">
          <span class="text-muted small">¿No tenés cuenta?</span>
          <a href="<?= BASE_URL ?>/cuenta/registro" class="ms-1 small fw-600" style="color:var(--gold)">Crear cuenta gratis</a>
        </div>

      </div>
    </div>
  </div>
</section>
