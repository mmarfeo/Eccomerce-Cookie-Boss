<section class="py-5">
  <div class="container" style="max-width:700px">
    <h1 class="section-title mb-4">Contacto</h1>
    <p class="lead text-muted mb-5">¿Tenés alguna consulta? Escribinos y te respondemos a la brevedad.</p>
    <div class="row g-4">
      <div class="col-md-6">
        <div class="checkout-card text-center">
          <div style="font-size:2.5rem;margin-bottom:1rem">📱</div>
          <h5 class="fw-700">WhatsApp</h5>
          <?php if (!empty($phone)): ?>
            <a href="https://wa.me/<?= preg_replace('/\D/', '', $phone) ?>" class="btn btn-gold mt-2">
              Escribirnos
            </a>
          <?php else: ?>
            <p class="text-muted small">Consultá por Instagram</p>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="checkout-card text-center">
          <div style="font-size:2.5rem;margin-bottom:1rem">📸</div>
          <h5 class="fw-700">Instagram</h5>
          <?php if (!empty($instagram)): ?>
            <a href="https://instagram.com/<?= ltrim($instagram, '@') ?>" target="_blank" class="btn btn-gold mt-2">
              <?= htmlspecialchars($instagram) ?>
            </a>
          <?php else: ?>
            <p class="text-muted small">Próximamente</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
