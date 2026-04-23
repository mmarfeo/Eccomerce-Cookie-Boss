<section class="py-5">
  <div class="container">

    <!-- Header -->
    <div class="d-flex align-items-center gap-3 mb-4">
      <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
           style="width:56px;height:56px;font-size:1.4rem;background:var(--gold)">
        <?= strtoupper(substr($customer['name'], 0, 1)) ?>
      </div>
      <div>
        <h4 class="mb-0 fw-bold" style="color:var(--dark)"><?= htmlspecialchars($customer['name']) ?></h4>
        <span class="small text-muted"><?= htmlspecialchars($customer['email']) ?></span>
      </div>
      <a href="<?= BASE_URL ?>/cuenta/logout" class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión
      </a>
    </div>

    <!-- Puntos card -->
    <div class="card border-0 mb-4" style="background:var(--dark);border-radius:1.25rem;color:#fff">
      <div class="card-body p-4">
        <div class="row align-items-center g-3">
          <div class="col-sm-6">
            <div class="small opacity-75 mb-1">Tus puntos acumulados</div>
            <div class="fw-bold" style="font-size:2.5rem;color:var(--gold)"><?= number_format($points) ?></div>
            <div class="small opacity-75">≈ $<?= number_format($pointsValue) ?> en descuentos</div>
          </div>
          <div class="col-sm-3 text-center">
            <div class="small opacity-75 mb-1">Nivel</div>
            <span class="badge px-3 py-2" style="background:<?= htmlspecialchars($tierColor) ?>;font-size:.95rem">
              <?= htmlspecialchars($tier) ?>
            </span>
          </div>
          <div class="col-sm-3 text-center">
            <div class="small opacity-75 mb-1">Pedidos realizados</div>
            <div class="fw-bold fs-4"><?= (int)$customer['total_orders'] ?></div>
          </div>
        </div>
        <?php if ($cfg['enabled']): ?>
          <div class="mt-3 small opacity-75">
            <i class="bi bi-info-circle me-1"></i>
            Ganás <?= $cfg['per_peso'] ?> punto<?= $cfg['per_peso'] != 1 ? 's' : '' ?> por cada $1 gastado.
            Cada <?= number_format($cfg['redeem_rate']) ?> puntos = $1 de descuento. Mínimo para canjear: <?= number_format($cfg['min_redeem']) ?> pts.
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="row g-4">

      <!-- Columna izquierda: perfil + contraseña -->
      <div class="col-lg-4">

        <!-- Editar perfil -->
        <div class="card border-0 shadow-sm mb-3" style="border-radius:1rem">
          <div class="card-body p-4">
            <h6 class="fw-bold mb-3" style="color:var(--dark)"><i class="bi bi-person me-2" style="color:var(--gold)"></i>Mis datos</h6>
            <form method="POST" action="<?= BASE_URL ?>/cuenta/perfil">
              <?= \App\Core\Csrf::field() ?>
              <div class="mb-3">
                <label class="form-label small fw-500">Nombre</label>
                <input type="text" name="name" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($customer['name']) ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-500">Teléfono</label>
                <input type="tel" name="phone" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-500">Dirección</label>
                <input type="text" name="address" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($customer['address'] ?? '') ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-500">Ciudad</label>
                <input type="text" name="city" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($customer['city'] ?? '') ?>">
              </div>
              <button type="submit" class="btn btn-gold btn-sm w-100">Guardar cambios</button>
            </form>
          </div>
        </div>

        <!-- Cambiar contraseña -->
        <?php if ($customer['password']): ?>
        <div class="card border-0 shadow-sm" style="border-radius:1rem">
          <div class="card-body p-4">
            <h6 class="fw-bold mb-3" style="color:var(--dark)"><i class="bi bi-lock me-2" style="color:var(--gold)"></i>Cambiar contraseña</h6>
            <form method="POST" action="<?= BASE_URL ?>/cuenta/cambiar-contrasena">
              <?= \App\Core\Csrf::field() ?>
              <div class="mb-2">
                <label class="form-label small fw-500">Contraseña actual</label>
                <input type="password" name="current_password" class="form-control form-control-sm" required>
              </div>
              <div class="mb-2">
                <label class="form-label small fw-500">Nueva contraseña</label>
                <input type="password" name="new_password" class="form-control form-control-sm" required minlength="8">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-500">Confirmar nueva</label>
                <input type="password" name="confirm_password" class="form-control form-control-sm" required minlength="8">
              </div>
              <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Actualizar contraseña</button>
            </form>
          </div>
        </div>
        <?php endif; ?>

      </div>

      <!-- Columna derecha: pedidos + historial puntos -->
      <div class="col-lg-8">

        <!-- Historial de pedidos -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius:1rem">
          <div class="card-body p-4">
            <h6 class="fw-bold mb-3" style="color:var(--dark)"><i class="bi bi-receipt me-2" style="color:var(--gold)"></i>Mis pedidos</h6>
            <?php if (empty($orders)): ?>
              <p class="text-muted small mb-0">Todavía no realizaste ningún pedido.
                <a href="<?= BASE_URL ?>/productos" style="color:var(--gold)">¡Empezá a comprar!</a>
              </p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Pedido</th>
                      <th>Fecha</th>
                      <th>Total</th>
                      <th>Estado</th>
                      <th>Puntos</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                      <td class="fw-600">#<?= htmlspecialchars($o['order_number']) ?></td>
                      <td class="text-muted small"><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                      <td class="fw-600">$<?= number_format((float)$o['total'], 0, ',', '.') ?></td>
                      <td><?= \App\Helpers\Formatter::statusBadge($o['status']) ?></td>
                      <td class="small">
                        <?php if ($o['points_earned'] > 0): ?>
                          <span style="color:var(--gold)">+<?= $o['points_earned'] ?> pts</span>
                        <?php elseif ($o['points_redeemed'] > 0): ?>
                          <span class="text-muted">-<?= $o['points_redeemed'] ?> pts</span>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Historial de puntos -->
        <?php if (!empty($history)): ?>
        <div class="card border-0 shadow-sm" style="border-radius:1rem">
          <div class="card-body p-4">
            <h6 class="fw-bold mb-3" style="color:var(--dark)"><i class="bi bi-star me-2" style="color:var(--gold)"></i>Movimientos de puntos</h6>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr><th>Fecha</th><th>Descripción</th><th>Puntos</th><th>Saldo</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($history as $h): ?>
                  <tr>
                    <td class="text-muted small"><?= date('d/m/Y', strtotime($h['created_at'])) ?></td>
                    <td class="small"><?= htmlspecialchars($h['description'] ?? '') ?></td>
                    <td class="fw-600 <?= $h['points'] >= 0 ? 'text-success' : 'text-danger' ?>">
                      <?= $h['points'] >= 0 ? '+' : '' ?><?= $h['points'] ?>
                    </td>
                    <td class="small text-muted"><?= number_format($h['balance_after']) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div><!-- /row -->

  </div>
</section>
