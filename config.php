<?php
// ── Paths ───────────────────────────────────────────────────
define('ROOT_PATH',   __DIR__);
define('APP_PATH',    ROOT_PATH . '/app');
define('UPLOAD_PATH', ROOT_PATH . '/uploads/products');
define('UPLOAD_URL',  '/02-Eccomerce-Cookies/uploads/products');
define('BASE_URL',    'http://localhost/02-Eccomerce-Cookies');
define('PUBLIC_URL',  BASE_URL . '/public');

// ── Database ────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'cookie_bakery');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── Mercado Pago ────────────────────────────────────────────
// Obtené tus claves en: https://www.mercadopago.com.ar/developers/panel
define('MP_PUBLIC_KEY',     getenv('MP_PUBLIC_KEY')     ?: 'TEST-YOUR-PUBLIC-KEY');
define('MP_ACCESS_TOKEN',   getenv('MP_ACCESS_TOKEN')   ?: 'TEST-YOUR-ACCESS-TOKEN');
define('MP_WEBHOOK_SECRET', getenv('MP_WEBHOOK_SECRET') ?: '');

// ── App ─────────────────────────────────────────────────────
define('APP_ENV',       'development'); // Cambiar a 'production' al deployar
define('APP_NAME',      'Cookie Bakery');
define('SESSION_NAME',  'cb_sess');
define('CSRF_TOKEN_KEY','_csrf_token');
define('UPLOAD_MAX_MB', 5);

// ── Error handling ──────────────────────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
}
