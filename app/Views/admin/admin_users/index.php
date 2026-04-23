<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="fw-bold mb-0" style="color:var(--dark)">Usuarios del panel</h5>
    <small class="text-muted">Administradores y encargados con acceso al panel</small>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0 ps-3">
      <?php foreach ($errors as $e): echo '<li>'.htmlspecialchars($e).'</li>'; endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="row g-4">

  <!-- Lista de usuarios -->
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm" style="border-radius:1rem">
      <div class="card-body p-0">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">Nombre</th>
              <th>Email</th>
              <th>Rol</th>
              <th>Último acceso</th>
              <th class="pe-4"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td class="ps-4">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                       style="width:34px;height:34px;font-size:.85rem;background:var(--gold)">
                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                  </div>
                  <span class="fw-600"><?= htmlspecialchars($u['name']) ?></span>
                  <?php if ($u['id'] === $authUser['id']): ?>
                    <span class="badge bg-secondary" style="font-size:.65rem">Vos</span>
                  <?php endif; ?>
                </div>
              </td>
              <td class="small text-muted"><?= htmlspecialchars($u['email']) ?></td>
              <td>
                <?php if ($u['role'] === 'admin'): ?>
                  <span class="badge" style="background:var(--gold)">Admin</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Encargado</span>
                <?php endif; ?>
              </td>
              <td class="small text-muted">
                <?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : '—' ?>
              </td>
              <td class="pe-4">
                <?php if ($u['id'] !== $authUser['id']): ?>
                <div class="d-flex gap-1 justify-content-end">
                  <!-- Reset password -->
                  <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                          data-bs-target="#resetModal<?= $u['id'] ?>">
                    <i class="bi bi-key"></i>
                  </button>
                  <!-- Delete -->
                  <form method="POST" action="<?= BASE_URL ?>/admin/usuarios/eliminar"
                        onsubmit="return confirm('¿Eliminar a <?= htmlspecialchars(addslashes($u['name'])) ?>?')">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
                <?php endif; ?>
              </td>
            </tr>

            <!-- Reset password modal -->
            <div class="modal fade" id="resetModal<?= $u['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-sm">
                <div class="modal-content" style="border-radius:1rem">
                  <div class="modal-header border-0">
                    <h6 class="modal-title fw-bold">Cambiar contraseña</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="POST" action="<?= BASE_URL ?>/admin/usuarios/reset-password">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <div class="modal-body">
                      <div class="mb-2 small fw-600"><?= htmlspecialchars($u['name']) ?></div>
                      <input type="password" name="new_password" class="form-control form-control-sm"
                             placeholder="Nueva contraseña (mín. 8 car.)" required minlength="8">
                    </div>
                    <div class="modal-footer border-0 pt-0">
                      <button type="submit" class="btn btn-gold btn-sm w-100">Guardar</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Crear nuevo usuario -->
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm" style="border-radius:1rem">
      <div class="card-header border-0 pt-4 pb-0 px-4">
        <h6 class="fw-bold mb-0" style="color:var(--dark)">
          <i class="bi bi-person-plus me-2" style="color:var(--gold)"></i>Agregar usuario
        </h6>
      </div>
      <div class="card-body p-4">
        <form method="POST" action="<?= BASE_URL ?>/admin/usuarios/crear">
          <?= \App\Core\Csrf::field() ?>

          <div class="mb-3">
            <label class="form-label small fw-500">Nombre completo</label>
            <input type="text" name="name" class="form-control" required
                   value="<?= htmlspecialchars($old['name'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-500">Email</label>
            <input type="email" name="email" class="form-control" required
                   value="<?= htmlspecialchars($old['email'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-500">Rol</label>
            <select name="role" class="form-select">
              <option value="manager" <?= ($old['role'] ?? '') === 'manager' ? 'selected' : '' ?>>
                Encargado — gestiona productos y pedidos
              </option>
              <option value="admin" <?= ($old['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                Admin — acceso completo incluyendo configuración
              </option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-500">Contraseña</label>
            <input type="password" name="password" class="form-control" required minlength="8">
          </div>
          <div class="mb-4">
            <label class="form-label small fw-500">Confirmar contraseña</label>
            <input type="password" name="password_confirm" class="form-control" required minlength="8">
          </div>

          <!-- Permisos por rol -->
          <div class="p-3 rounded mb-4" style="background:var(--cream);font-size:.82rem">
            <div class="fw-600 mb-2" style="color:var(--dark)">Permisos por rol</div>
            <table class="table table-sm table-borderless mb-0" style="font-size:.8rem">
              <thead><tr><th>Sección</th><th class="text-center">Admin</th><th class="text-center">Encargado</th></tr></thead>
              <tbody>
                <tr><td>Productos / Categorías</td><td class="text-center text-success">✓</td><td class="text-center text-success">✓</td></tr>
                <tr><td>Pedidos / Clientes</td><td class="text-center text-success">✓</td><td class="text-center text-success">✓</td></tr>
                <tr><td>Cupones / Puntos</td><td class="text-center text-success">✓</td><td class="text-center text-success">✓</td></tr>
                <tr><td>Reportes</td><td class="text-center text-success">✓</td><td class="text-center text-success">✓</td></tr>
                <tr><td>Configuración general</td><td class="text-center text-success">✓</td><td class="text-center text-danger">✗</td></tr>
                <tr><td>Gestión de usuarios panel</td><td class="text-center text-success">✓</td><td class="text-center text-danger">✗</td></tr>
              </tbody>
            </table>
          </div>

          <button type="submit" class="btn btn-gold w-100">
            <i class="bi bi-person-check me-1"></i>Crear usuario
          </button>
        </form>
      </div>
    </div>
  </div>

</div>
