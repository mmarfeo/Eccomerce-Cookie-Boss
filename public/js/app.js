/**
 * Cookie Bakery — App principal JS
 */

const BASE_URL = document.querySelector('meta[name="base-url"]')?.content ?? '';

// ── CSRF token para AJAX ───────────────────────────────────
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// ── Carrito AJAX ───────────────────────────────────────────
const Cart = {
  offcanvas: null,

  init() {
    this.offcanvas = document.getElementById('cartOffcanvas')
      ? new bootstrap.Offcanvas(document.getElementById('cartOffcanvas'))
      : null;

    // Botones "Agregar al carrito"
    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('[data-add-cart]');
      if (!btn) return;

      e.preventDefault();
      const productId = btn.dataset.addCart;
      const qty = parseInt(document.getElementById('qtyInput')?.value ?? 1);
      await Cart.add(productId, qty, btn);
    });

    // Actualizar cantidad en carrito
    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('[data-cart-update]');
      if (!btn) return;
      const productId = btn.dataset.cartUpdate;
      const input = document.querySelector(`[data-qty-input="${productId}"]`);
      if (!input) return;
      const qty = parseInt(input.value);
      await Cart.update(productId, qty);
    });

    // Eliminar del carrito
    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('[data-cart-remove]');
      if (!btn) return;
      const productId = btn.dataset.cartRemove;
      await Cart.remove(productId);
    });

    // Controles de cantidad
    document.addEventListener('click', (e) => {
      if (e.target.closest('.qty-minus')) {
        const inp = e.target.closest('.qty-control').querySelector('.qty-input');
        if (inp) inp.value = Math.max(1, parseInt(inp.value) - 1);
      }
      if (e.target.closest('.qty-plus')) {
        const inp = e.target.closest('.qty-control').querySelector('.qty-input');
        if (inp) inp.value = Math.min(99, parseInt(inp.value) + 1);
      }
    });
  },

  async add(productId, qty = 1, btn = null) {
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }
    try {
      const res = await fetch(`${BASE_URL}/carrito/agregar`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF_TOKEN,
        },
        body: new URLSearchParams({ product_id: productId, quantity: qty, _csrf_token: CSRF_TOKEN }),
      });
      const data = await res.json();
      if (data.success) {
        Cart.updateBadge(data.cartCount);
        Toast.show(data.message ?? '¡Agregado!', 'success');
        if (Cart.offcanvas) setTimeout(() => Cart.offcanvas.show(), 400);
      } else {
        Toast.show(data.message ?? 'Error al agregar', 'error');
      }
    } catch {
      Toast.show('Error de conexión', 'error');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.originalText ?? 'Agregar al carrito';
      }
    }
  },

  async update(productId, qty) {
    const res = await fetch(`${BASE_URL}/carrito/actualizar`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: new URLSearchParams({ product_id: productId, quantity: qty, _csrf_token: CSRF_TOKEN }),
    });
    const data = await res.json();
    if (data.success) {
      Cart.updateBadge(data.cartCount);
      Cart.updateTotals(data);
      if (qty <= 0) {
        document.querySelector(`[data-cart-row="${productId}"]`)?.remove();
      }
    }
  },

  async remove(productId) {
    const row = document.querySelector(`[data-cart-row="${productId}"]`);
    if (row) row.style.opacity = '0.4';

    const res = await fetch(`${BASE_URL}/carrito/eliminar`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: new URLSearchParams({ product_id: productId, _csrf_token: CSRF_TOKEN }),
    });
    const data = await res.json();
    if (data.success) {
      row?.remove();
      Cart.updateBadge(data.cartCount);
      Cart.updateTotals(data);
      if (data.cartCount === 0) {
        document.getElementById('cartEmpty')?.classList.remove('d-none');
        document.getElementById('cartItems')?.classList.add('d-none');
      }
    }
  },

  updateBadge(count) {
    document.querySelectorAll('.cart-badge').forEach(el => {
      el.textContent = count;
      el.style.display = count > 0 ? 'flex' : 'none';
    });
  },

  updateTotals(data) {
    const fmt = (n) => '$' + Number(n).toLocaleString('es-AR', { maximumFractionDigits: 0 });
    document.querySelectorAll('[data-cart-subtotal]').forEach(el => el.textContent = fmt(data.subtotal));
    document.querySelectorAll('[data-cart-total]').forEach(el => el.textContent = fmt(data.total));
    document.querySelectorAll('[data-cart-shipping]').forEach(el => el.textContent = fmt(data.shipping ?? 0));
  },
};

// ── Toast notifications ────────────────────────────────────
const Toast = {
  show(message, type = 'success') {
    const colors = {
      success: 'bg-success',
      error:   'bg-danger',
      info:    'bg-info',
      warning: 'bg-warning',
    };
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const id   = 'toast-' + Date.now();
    const html = `
      <div id="${id}" class="toast align-items-center text-white ${colors[type] ?? 'bg-secondary'} border-0" role="alert" aria-live="assertive">
        <div class="d-flex">
          <div class="toast-body fw-semibold">${message}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>`;
    container.insertAdjacentHTML('beforeend', html);
    const toastEl = document.getElementById(id);
    const toast   = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
  },
};

// ── Galería de producto ────────────────────────────────────
const Gallery = {
  init() {
    document.querySelectorAll('.product-thumb').forEach(thumb => {
      thumb.addEventListener('click', () => {
        const src = thumb.querySelector('img')?.src;
        const main = document.getElementById('galleryMain');
        if (main && src) main.src = src;
        document.querySelectorAll('.product-thumb').forEach(t => t.classList.remove('active'));
        thumb.classList.add('active');
      });
    });
  },
};

// ── Delivery method toggle (checkout) ─────────────────────
function initDeliveryToggle() {
  const radios = document.querySelectorAll('[name="delivery_method"]');
  const addressSection = document.getElementById('addressSection');
  if (!radios.length) return;

  const toggle = () => {
    const val = document.querySelector('[name="delivery_method"]:checked')?.value;
    if (addressSection) {
      addressSection.style.display = val === 'delivery' ? 'block' : 'none';
    }
  };
  radios.forEach(r => r.addEventListener('change', toggle));
  toggle();
}

// ── Auto-ocultar alertas flash ─────────────────────────────
function initFlashAlerts() {
  document.querySelectorAll('.flash-alert').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 4000);
  });
}

// ── Slug automático desde nombre (admin) ───────────────────
function initAutoSlug() {
  const nameInput = document.getElementById('nameInput');
  const slugInput = document.getElementById('slugInput');
  if (!nameInput || !slugInput) return;

  nameInput.addEventListener('input', () => {
    if (!slugInput.dataset.manual) {
      slugInput.value = nameInput.value
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/[\s]+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-+|-+$/g, '');
    }
  });
  slugInput.addEventListener('input', () => { slugInput.dataset.manual = '1'; });
}

// ── Init ───────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  Cart.init();
  Gallery.init();
  initDeliveryToggle();
  initFlashAlerts();
  initAutoSlug();

  // Mostrar flash messages como toast si existen
  document.querySelectorAll('[data-flash]').forEach(el => {
    Toast.show(el.dataset.flash, el.dataset.flashType ?? 'success');
  });
});
