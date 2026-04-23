<?php use App\Helpers\Branding; ?>

<!-- Tabs de configuración -->
<ul class="nav nav-tabs mb-4" id="settingsTabs">
  <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-tienda">🏪 Tienda</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-branding">🎨 Apariencia</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-pago">💳 Mercado Pago</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-email">📧 Email</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-envios">🚚 Envíos</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-pass">🔒 Contraseña</a></li>
</ul>

<form method="POST" action="<?= BASE_URL ?>/admin/configuracion/guardar" enctype="multipart/form-data">
  <?= \App\Core\Csrf::field() ?>

  <div class="tab-content">

    <!-- ── TAB TIENDA ──────────────────────────────────────── -->
    <div class="tab-pane fade show active" id="tab-tienda">
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="admin-form-section">
            <h6>Información de la tienda</h6>
            <div class="row g-3">
              <?php foreach ([
                ['store_name',          'Nombre de la tienda', 'Cookie Bakery'],
                ['store_tagline',       'Slogan / tagline',    'New York Style Cookies'],
                ['store_phone',         'Teléfono / WhatsApp', '+54 11 XXXX-XXXX'],
                ['store_email',         'Email de contacto',   'hola@tienda.com'],
                ['store_instagram',     'Instagram (@usuario)', '@mitienda'],
                ['store_address',       'Ciudad / Dirección',  'Buenos Aires, Argentina'],
                ['store_pickup_address','Dirección retiro',    'Consultá por Instagram'],
              ] as [$key, $label, $placeholder]): ?>
              <div class="col-md-6">
                <label class="form-label fw-600 small"><?= $label ?></label>
                <input type="text" name="<?= $key ?>" class="form-control"
                       value="<?= htmlspecialchars($settings[$key] ?? '') ?>"
                       placeholder="<?= $placeholder ?>">
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="admin-card">
            <h6 class="fw-700 mb-3">Estado de la tienda</h6>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <label class="fw-600">Tienda abierta</label>
              <label class="toggle-switch">
                <input type="checkbox" name="store_open" value="1"
                       <?= ($settings['store_open'] ?? '1') === '1' ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
              </label>
            </div>
            <textarea name="store_closed_msg" class="form-control small" rows="3"
                      placeholder="Mensaje cuando está cerrada"><?= htmlspecialchars($settings['store_closed_msg'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- ── TAB BRANDING ────────────────────────────────────── -->
    <div class="tab-pane fade" id="tab-branding">
      <div class="row g-4">
        <!-- Logo -->
        <div class="col-lg-4">
          <div class="admin-form-section text-center">
            <h6>Logo de la tienda</h6>
            <?php $logoUrl = Branding::logoUrl(); ?>
            <div id="logoPreviewContainer" class="mb-3">
              <?php if ($logoUrl): ?>
                <img id="logoPreview" src="<?= htmlspecialchars($logoUrl) ?>"
                     style="max-height:120px;max-width:100%;object-fit:contain;border-radius:8px;border:2px solid var(--border)">
              <?php else: ?>
                <div id="logoPlaceholder" style="height:120px;background:var(--cream);border-radius:8px;border:2px dashed var(--border);display:flex;align-items:center;justify-content:center;font-size:2.5rem">🍪</div>
              <?php endif; ?>
            </div>

            <input type="file" name="store_logo" id="logoInput" accept="image/jpeg,image/png,image/webp,image/gif"
                   class="form-control mb-2" onchange="previewLogo(this)">
            <div class="form-text mb-3">PNG con fondo transparente recomendado. Máx 5MB.</div>

            <?php if ($logoUrl): ?>
              <div class="d-flex gap-2 justify-content-center">
                <button type="button" class="btn btn-sm btn-admin-outline"
                        onclick="document.getElementById('logoInput').click()">
                  Cambiar logo
                </button>
                <button type="button" class="btn btn-sm btn-admin-danger"
                        onclick="removeLogo()">
                  <i class="bi bi-trash"></i> Quitar
                </button>
              </div>
              <input type="hidden" name="remove_logo" id="removeLogoInput" value="0">
            <?php endif; ?>
          </div>
        </div>

        <!-- Colores -->
        <div class="col-lg-5">
          <div class="admin-form-section">
            <h6>Paleta de colores</h6>

            <?php $colorFields = [
              ['brand_color_primary',   'Color primario (dorado)',   '#bf9663', 'Botones, links, detalles'],
              ['brand_color_secondary', 'Color secundario (naranja)','#FF8D08', 'Botón principal, badges'],
              ['brand_color_dark',      'Color oscuro (fondo hero)', '#2d2016', 'Hero, sidebar'],
              ['brand_color_text',      'Color de texto',            '#4a3728', 'Texto principal'],
            ]; ?>

            <?php foreach ($colorFields as [$key, $label, $default, $hint]): ?>
            <div class="mb-3">
              <label class="form-label fw-600 small"><?= $label ?></label>
              <div class="d-flex align-items-center gap-3">
                <input type="color" name="<?= $key ?>" id="<?= $key ?>"
                       value="<?= htmlspecialchars($settings[$key] ?? $default) ?>"
                       style="width:56px;height:40px;border:2px solid var(--border);border-radius:8px;padding:2px;cursor:pointer"
                       oninput="document.getElementById('hex_<?= $key ?>').value=this.value;updatePreview()">
                <input type="text" id="hex_<?= $key ?>"
                       value="<?= htmlspecialchars($settings[$key] ?? $default) ?>"
                       class="form-control form-control-sm font-monospace" style="width:110px"
                       pattern="^#[0-9a-fA-F]{6}$"
                       oninput="syncColor('<?= $key ?>',this.value)">
                <span class="small text-muted"><?= $hint ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Fuente + Preview -->
        <div class="col-lg-3">
          <div class="admin-form-section">
            <h6>Tipografía</h6>
            <select name="brand_font" class="form-select mb-3" id="fontSelect" onchange="updatePreview()">
              <?php foreach (['Poppins','Lato','Montserrat','Raleway','Nunito','Inter','Open Sans','Playfair Display'] as $font): ?>
                <option value="<?= $font ?>" <?= ($settings['brand_font'] ?? 'Poppins') === $font ? 'selected' : '' ?>>
                  <?= $font ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="admin-form-section">
            <h6>Vista previa</h6>
            <div id="colorPreview" style="border-radius:12px;overflow:hidden;border:1px solid #e9ecef">
              <div id="prevHeader" style="background:#2d2016;padding:12px;text-align:center">
                <span id="prevTitle" style="color:#fff;font-weight:800;font-size:1rem">Mi Tienda</span>
              </div>
              <div style="padding:12px;background:#fff">
                <div id="prevBtn" style="background:#bf9663;color:#fff;border-radius:50px;padding:8px 16px;font-size:.8rem;font-weight:700;text-align:center;margin-bottom:8px">
                  Ver cookies
                </div>
                <div id="prevBtnOrange" style="background:#FF8D08;color:#fff;border-radius:50px;padding:8px 16px;font-size:.8rem;font-weight:700;text-align:center">
                  Agregar al carrito
                </div>
                <div id="prevText" style="color:#4a3728;font-size:.8rem;margin-top:8px;text-align:center">
                  Texto de ejemplo
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-3">
        <button type="submit" class="btn btn-admin-primary">
          <i class="bi bi-palette me-2"></i>Guardar apariencia
        </button>
        <button type="button" class="btn btn-admin-outline ms-2" onclick="resetBranding()">
          Restablecer por defecto
        </button>
      </div>
    </div>

    <!-- ── TAB MERCADO PAGO ────────────────────────────────── -->
    <div class="tab-pane fade" id="tab-pago">
      <div class="col-lg-7">
        <div class="admin-form-section">
          <h6><i class="bi bi-credit-card-2-front me-2 text-gold"></i>Credenciales Mercado Pago</h6>
          <div class="alert alert-info py-2 px-3 small mb-3">
            Obtené tus claves en <a href="https://www.mercadopago.com.ar/developers/panel" target="_blank"><strong>mercadopago.com.ar/developers/panel</strong></a>. Usá claves TEST para pruebas, PROD para producción.
          </div>
          <div class="mb-3">
            <label class="form-label fw-600 small">Public Key</label>
            <input type="text" name="mp_public_key" class="form-control font-monospace small"
                   value="<?= htmlspecialchars($settings['mp_public_key'] ?? '') ?>" placeholder="TEST-xxxxx">
          </div>
          <div class="mb-3">
            <label class="form-label fw-600 small">Access Token</label>
            <div class="input-group">
              <input type="password" name="mp_access_token" id="mpTokenInput" class="form-control font-monospace small"
                     value="<?= htmlspecialchars($settings['mp_access_token'] ?? '') ?>" placeholder="TEST-xxxxx">
              <button type="button" class="btn btn-outline-secondary"
                      onclick="togglePass('mpTokenInput')">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-600 small">Webhook Secret (opcional)</label>
            <input type="text" name="mp_webhook_secret" class="form-control font-monospace small"
                   value="<?= htmlspecialchars($settings['mp_webhook_secret'] ?? '') ?>">
            <div class="form-text">Configuralo en el panel de MP → Notificaciones IPN → URL: <code><?= BASE_URL ?>/webhook/mercadopago</code></div>
          </div>
          <button type="submit" class="btn btn-admin-primary">Guardar credenciales</button>
        </div>
      </div>
    </div>

    <!-- ── TAB EMAIL ───────────────────────────────────────── -->
    <div class="tab-pane fade" id="tab-email">
      <div class="col-lg-7">
        <div class="admin-form-section">
          <h6>Configuración de emails</h6>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-600 small">Nombre del remitente</label>
              <input type="text" name="mail_from_name" class="form-control"
                     value="<?= htmlspecialchars($settings['mail_from_name'] ?? '') ?>" placeholder="Mi Tienda">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600 small">Email del remitente</label>
              <input type="email" name="mail_from_email" class="form-control"
                     value="<?= htmlspecialchars($settings['mail_from_email'] ?? '') ?>">
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">SMTP (opcional)</h6>
            <label class="toggle-switch">
              <input type="checkbox" name="mail_smtp_enabled" value="1"
                     id="smtpToggle"
                     <?= ($settings['mail_smtp_enabled'] ?? '0') === '1' ? 'checked' : '' ?>
                     onchange="document.getElementById('smtpFields').style.display=this.checked?'block':'none'">
              <span class="toggle-slider"></span>
            </label>
          </div>
          <div id="smtpFields" style="<?= ($settings['mail_smtp_enabled'] ?? '0') === '1' ? '' : 'display:none' ?>">
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label fw-600 small">Host SMTP</label>
                <input type="text" name="mail_smtp_host" class="form-control"
                       value="<?= htmlspecialchars($settings['mail_smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-600 small">Puerto</label>
                <input type="number" name="mail_smtp_port" class="form-control"
                       value="<?= htmlspecialchars($settings['mail_smtp_port'] ?? '587') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-600 small">Usuario</label>
                <input type="text" name="mail_smtp_user" class="form-control"
                       value="<?= htmlspecialchars($settings['mail_smtp_user'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-600 small">Contraseña / App Password</label>
                <input type="password" name="mail_smtp_pass" class="form-control"
                       value="<?= htmlspecialchars($settings['mail_smtp_pass'] ?? '') ?>">
              </div>
            </div>
            <div class="alert alert-info small mt-3 py-2">
              Para Gmail usá una <strong>App Password</strong> (no tu contraseña normal). Activá 2FA primero.
            </div>
          </div>
          <button type="submit" class="btn btn-admin-primary mt-3">Guardar configuración email</button>
        </div>
      </div>
    </div>

    <!-- ── TAB ENVÍOS ──────────────────────────────────────── -->
    <div class="tab-pane fade" id="tab-envios">
      <div class="col-lg-6">
        <div class="admin-form-section">
          <h6>Precios y condiciones de envío</h6>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-600 small">Costo de envío (ARS)</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" name="shipping_cost" class="form-control" min="0"
                       value="<?= htmlspecialchars($settings['shipping_cost'] ?? '0') ?>">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-600 small">Envío gratis desde (ARS)</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" name="free_shipping_min" class="form-control" min="0"
                       value="<?= htmlspecialchars($settings['free_shipping_min'] ?? '0') ?>">
              </div>
              <div class="form-text">0 = sin envío gratis automático</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-600 small">Pedido mínimo (ARS)</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" name="min_order_amount" class="form-control" min="0"
                       value="<?= htmlspecialchars($settings['min_order_amount'] ?? '0') ?>">
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-admin-primary mt-3">Guardar</button>
        </div>
      </div>
    </div>

    <!-- ── TAB CONTRASEÑA ─────────────────────────────────── -->
    <div class="tab-pane fade" id="tab-pass">
      <div class="col-lg-5">
        <div class="admin-form-section">
          <h6>🔒 Cambiar contraseña</h6>
          <form method="POST" action="<?= BASE_URL ?>/admin/change-password">
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3">
              <label class="form-label fw-600 small">Contraseña actual</label>
              <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-600 small">Nueva contraseña</label>
              <input type="password" name="new_password" class="form-control" required minlength="8"
                     placeholder="Mínimo 8 caracteres">
            </div>
            <div class="mb-4">
              <label class="form-label fw-600 small">Confirmar nueva contraseña</label>
              <input type="password" name="new_password_confirm" class="form-control" required minlength="8">
            </div>
            <button type="submit" class="btn btn-admin-primary">
              <i class="bi bi-key me-2"></i>Cambiar contraseña
            </button>
          </form>
        </div>
      </div>
    </div>

  </div><!-- /.tab-content -->

  <!-- Botón guardar flotante (para todos los tabs excepto contraseña) -->
</form>

<script>
// Sincronizar color picker ↔ input text
function syncColor(key, hex) {
  if (/^#[0-9a-fA-F]{6}$/.test(hex)) {
    document.getElementById(key).value = hex;
    updatePreview();
  }
}

// Preview en vivo
function updatePreview() {
  const primary   = document.getElementById('brand_color_primary')?.value || '#bf9663';
  const secondary = document.getElementById('brand_color_secondary')?.value || '#FF8D08';
  const dark      = document.getElementById('brand_color_dark')?.value || '#2d2016';
  const textColor = document.getElementById('brand_color_text')?.value || '#4a3728';
  const font      = document.getElementById('fontSelect')?.value || 'Poppins';

  document.getElementById('prevHeader').style.background = dark;
  document.getElementById('prevBtn').style.background    = primary;
  document.getElementById('prevBtnOrange').style.background = secondary;
  document.getElementById('prevText').style.color        = textColor;
  document.getElementById('prevTitle').style.fontFamily  = `'${font}', sans-serif`;
  document.getElementById('colorPreview').style.fontFamily = `'${font}', sans-serif`;
}

// Preview de logo antes de subir
function previewLogo(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    let img = document.getElementById('logoPreview');
    const ph  = document.getElementById('logoPlaceholder');
    if (!img) {
      img = document.createElement('img');
      img.id = 'logoPreview';
      img.style.cssText = 'max-height:120px;max-width:100%;object-fit:contain;border-radius:8px;border:2px solid var(--border)';
      document.getElementById('logoPreviewContainer').innerHTML = '';
      document.getElementById('logoPreviewContainer').appendChild(img);
    }
    if (ph) ph.style.display = 'none';
    img.src = e.target.result;
    img.style.display = 'block';
  };
  reader.readAsDataURL(file);
}

function removeLogo() {
  if (!confirm('¿Quitar el logo actual?')) return;
  document.getElementById('removeLogoInput').value = '1';
  document.getElementById('logoPreviewContainer').innerHTML =
    '<div style="height:120px;background:var(--cream);border-radius:8px;border:2px dashed var(--border);display:flex;align-items:center;justify-content:center;font-size:2.5rem">🍪</div>';
  document.getElementById('logoInput').value = '';
  // Auto-submit
  document.querySelector('form').submit();
}

function resetBranding() {
  if (!confirm('¿Restablecer colores y fuente por defecto?')) return;
  const defaults = {
    brand_color_primary: '#bf9663', brand_color_secondary: '#FF8D08',
    brand_color_dark: '#2d2016',    brand_color_text: '#4a3728',
  };
  for (const [k, v] of Object.entries(defaults)) {
    const el   = document.getElementById(k);
    const hexEl= document.getElementById('hex_' + k);
    if (el) el.value = v;
    if (hexEl) hexEl.value = v;
  }
  document.getElementById('fontSelect').value = 'Poppins';
  updatePreview();
}

function togglePass(id) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}

// Activar tab correcto si hay flash
document.addEventListener('DOMContentLoaded', () => {
  updatePreview();
  const hash = location.hash;
  if (hash) {
    const tab = document.querySelector(`[href="${hash}"]`);
    if (tab) new bootstrap.Tab(tab).show();
  }
});
</script>
