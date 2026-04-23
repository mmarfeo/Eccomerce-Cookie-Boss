-- ============================================================
-- Actualizaciones v2: Puntos, Password Reset, Personalización
-- Ejecutar después de database.sql
-- ============================================================

USE cookie_bakery;

-- ─── PASSWORD RESETS ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS password_resets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(150)    NOT NULL,
    token       VARCHAR(64)     NOT NULL UNIQUE,
    used        TINYINT(1)      NOT NULL DEFAULT 0,
    expires_at  DATETIME        NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── LOYALTY POINTS ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS loyalty_points (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED    NOT NULL,
    order_id    INT UNSIGNED    NULL,
    type        ENUM('earn','redeem','adjust','expire') NOT NULL,
    points      INT             NOT NULL,  -- positivo: acumulan, negativo: descuentan
    balance_after INT           NOT NULL DEFAULT 0,
    description VARCHAR(255)    NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id)    REFERENCES orders(id)    ON DELETE SET NULL,
    INDEX idx_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columna de puntos a customers
ALTER TABLE customers
    ADD COLUMN IF NOT EXISTS loyalty_points INT UNSIGNED NOT NULL DEFAULT 0 AFTER total_spent,
    ADD COLUMN IF NOT EXISTS loyalty_tier   ENUM('bronze','silver','gold','platinum') NOT NULL DEFAULT 'bronze' AFTER loyalty_points;

-- Agregar columnas de puntos/descuento a orders
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS points_earned   INT UNSIGNED NOT NULL DEFAULT 0 AFTER admin_notes,
    ADD COLUMN IF NOT EXISTS points_redeemed INT UNSIGNED NOT NULL DEFAULT 0 AFTER points_earned,
    ADD COLUMN IF NOT EXISTS coupon_code     VARCHAR(30)  NULL       AFTER points_redeemed,
    ADD COLUMN IF NOT EXISTS coupon_discount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER coupon_code;

-- ─── COUPONS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS coupons (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(30)     NOT NULL UNIQUE,
    description     VARCHAR(255)    NULL,
    type            ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    value           DECIMAL(10,2)   NOT NULL,
    min_order       DECIMAL(10,2)   NOT NULL DEFAULT 0,
    max_uses        INT UNSIGNED    NULL,
    used_count      INT UNSIGNED    NOT NULL DEFAULT 0,
    active          TINYINT(1)      NOT NULL DEFAULT 1,
    expires_at      DATETIME        NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SETTINGS adicionales ───────────────────────────────────
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
-- Branding
('store_logo',           ''),
('brand_color_primary',  '#bf9663'),
('brand_color_secondary','#FF8D08'),
('brand_color_dark',     '#2d2016'),
('brand_color_text',     '#4a3728'),
('brand_font',           'Poppins'),
-- Email
('mail_from_name',       'Cookie Bakery'),
('mail_from_email',      'noreply@cookiebakery.com'),
('mail_smtp_host',       ''),
('mail_smtp_port',       '587'),
('mail_smtp_user',       ''),
('mail_smtp_pass',       ''),
('mail_smtp_enabled',    '0'),
-- Puntos
('loyalty_enabled',      '1'),
('loyalty_points_per_peso', '1'),   -- 1 punto por cada $1 gastado
('loyalty_redeem_rate',  '100'),    -- 100 puntos = $1 de descuento
('loyalty_min_redeem',   '500'),    -- mínimo 500 puntos para canjear
('loyalty_silver_min',   '5000'),   -- puntos para tier silver
('loyalty_gold_min',     '15000'),  -- puntos para tier gold
('loyalty_plat_min',     '40000');  -- puntos para tier platinum
