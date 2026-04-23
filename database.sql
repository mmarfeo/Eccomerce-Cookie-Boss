-- ============================================================
-- E-Commerce Cookie Bakery — Esquema completo de base de datos
-- ENGINE: InnoDB | CHARSET: utf8mb4 | COLLATION: utf8mb4_unicode_ci
-- ============================================================

CREATE DATABASE IF NOT EXISTS cookie_bakery
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cookie_bakery;

SET foreign_key_checks = 0;
DROP TABLE IF EXISTS order_status_history;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS settings;
SET foreign_key_checks = 1;

-- ─── ADMIN USERS ────────────────────────────────────────────
CREATE TABLE admin_users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)            NOT NULL,
    email       VARCHAR(150)            NOT NULL UNIQUE,
    password    VARCHAR(255)            NOT NULL,
    role        ENUM('admin','manager') NOT NULL DEFAULT 'admin',
    last_login  DATETIME                NULL,
    created_at  DATETIME                NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME                NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── CATEGORIES ─────────────────────────────────────────────
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)    NOT NULL,
    slug        VARCHAR(120)    NOT NULL UNIQUE,
    description TEXT            NULL,
    image       VARCHAR(255)    NULL,
    sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    active      TINYINT(1)      NOT NULL DEFAULT 1,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── PRODUCTS ───────────────────────────────────────────────
CREATE TABLE products (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id     INT UNSIGNED     NULL,
    name            VARCHAR(150)     NOT NULL,
    slug            VARCHAR(180)     NOT NULL UNIQUE,
    short_desc      VARCHAR(280)     NULL,
    description     TEXT             NULL,
    ingredients     TEXT             NULL,
    price           DECIMAL(10,2)    NOT NULL,
    compare_price   DECIMAL(10,2)    NULL,
    main_image      VARCHAR(255)     NULL,
    featured        TINYINT(1)       NOT NULL DEFAULT 0,
    available       TINYINT(1)       NOT NULL DEFAULT 1,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_available (available),
    INDEX idx_featured (featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── PRODUCT IMAGES (galería) ───────────────────────────────
CREATE TABLE product_images (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED    NOT NULL,
    filename    VARCHAR(255)    NOT NULL,
    alt_text    VARCHAR(255)    NULL,
    sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── CUSTOMERS ──────────────────────────────────────────────
CREATE TABLE customers (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)    NOT NULL,
    email       VARCHAR(150)    NOT NULL,
    phone       VARCHAR(30)     NULL,
    address     VARCHAR(255)    NULL,
    city        VARCHAR(100)    NULL,
    notes       TEXT            NULL,
    total_orders INT UNSIGNED   NOT NULL DEFAULT 0,
    total_spent  DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── ORDERS ─────────────────────────────────────────────────
CREATE TABLE orders (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number        VARCHAR(25)     NOT NULL UNIQUE,
    customer_id         INT UNSIGNED    NULL,
    customer_name       VARCHAR(150)    NOT NULL,
    customer_email      VARCHAR(150)    NOT NULL,
    customer_phone      VARCHAR(30)     NULL,
    customer_address    VARCHAR(255)    NULL,
    customer_city       VARCHAR(100)    NULL,
    customer_notes      TEXT            NULL,
    subtotal            DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    shipping_cost       DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    discount            DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    total               DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    status              ENUM(
                            'pending',
                            'paid',
                            'processing',
                            'ready',
                            'delivered',
                            'cancelled',
                            'refunded'
                        ) NOT NULL DEFAULT 'pending',
    payment_method      VARCHAR(50)     NULL DEFAULT 'mercadopago',
    mp_preference_id    VARCHAR(255)    NULL,
    mp_payment_id       VARCHAR(255)    NULL,
    mp_status           VARCHAR(50)     NULL,
    mp_status_detail    VARCHAR(100)    NULL,
    delivery_method     ENUM('pickup','delivery') NOT NULL DEFAULT 'pickup',
    admin_notes         TEXT            NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_mp_payment_id (mp_payment_id),
    INDEX idx_mp_preference_id (mp_preference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── ORDER ITEMS ────────────────────────────────────────────
CREATE TABLE order_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED    NOT NULL,
    product_id      INT UNSIGNED    NULL,
    product_name    VARCHAR(150)    NOT NULL,
    product_image   VARCHAR(255)    NULL,
    unit_price      DECIMAL(10,2)   NOT NULL,
    quantity        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    subtotal        DECIMAL(10,2)   NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── ORDER STATUS HISTORY ───────────────────────────────────
CREATE TABLE order_status_history (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED    NOT NULL,
    old_status  VARCHAR(30)     NULL,
    new_status  VARCHAR(30)     NOT NULL,
    notes       TEXT            NULL,
    changed_by  VARCHAR(100)    NULL,
    changed_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SETTINGS ───────────────────────────────────────────────
CREATE TABLE settings (
    setting_key     VARCHAR(100)    PRIMARY KEY,
    setting_value   TEXT            NULL,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin: password = password  (cambiar ejecutando fix_admin_password.php)
INSERT INTO admin_users (name, email, password, role) VALUES
('Administrador', 'admin@cookiebakery.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Categorías
INSERT INTO categories (name, slug, description, sort_order, active) VALUES
('Clásicas',   'clasicas',   'Las cookies de siempre, perfectas siempre',        1, 1),
('Especiales', 'especiales', 'Combinaciones únicas con ingredientes seleccionados', 2, 1),
('Premium',    'premium',    'Nuestras creaciones más exclusivas',                3, 1),
('Temporada',  'temporada',  'Ediciones limitadas de temporada',                  4, 1);

-- Productos (16 variedades)
INSERT INTO products (category_id, name, slug, short_desc, description, price, featured, available, sort_order) VALUES
(1, 'Classic Chocolate Chip', 'classic-chocolate-chip',
 'La cookie de siempre, perfecta siempre',
 'Nuestra cookie insignia al estilo Nueva York. Masa mantecosa con chips de chocolate premium, bordes crocantes y centro súper suave. La referencia con la que lo medimos todo.',
 3600.00, 1, 1, 1),

(1, 'Marshmallow', 'marshmallow',
 'Chocolate Hershey''s con marshmallow derretido',
 'Masa de chocolate intenso con chips Hershey''s y marshmallows tostados que se derriten con el calor. Una experiencia que te recuerda a una fogata.',
 3800.00, 1, 1, 2),

(1, 'Chocotorta', 'chocotorta',
 'Inspirada en el postre argentino más querido',
 'La magia de la chocotorta en formato cookie. Masa con Chocolinas trituradas, rellena de crema de queso y chocolate. Argentina en cada mordida.',
 3800.00, 0, 1, 3),

(2, 'Lotus Cookie', 'lotus-cookie',
 'Masa de Biscoff con crema Lotus en el centro',
 'Para los fanáticos del Biscoff. Masa especiada con galletas Lotus, rellena de crema Lotus caramelizada que fluye al romperla. Irresistible.',
 4200.00, 1, 1, 4),

(2, 'Lemon & Blueberries', 'lemon-blueberries',
 'Limón, arándanos y cream cheese',
 'Masa cítrica con zeste de limón, arándanos frescos y un corazón de cream cheese. Fresca, frutal y perfectamente equilibrada.',
 4000.00, 0, 1, 5),

(2, 'Bon o Bon', 'bon-o-bon',
 'Masa de maní con corazón de Bon o Bon',
 'Masa de maní artesanal con trozos de chocolate, rellena con la crema de Bon o Bon original. Una fusión argentina de primer nivel.',
 4200.00, 1, 1, 6),

(2, 'Oreo', 'oreo',
 'Masa de Oreo con corazón de Nutella',
 'Masa negra de Oreo triturado con chips de chocolate blanco y oscuro, rellena de Nutella fundida. El favorito de los chicos (y de los grandes).',
 3800.00, 0, 1, 7),

(2, 'Tiramisu', 'tiramisu',
 'Café, mascarpone y cacao espolvoreado',
 'Inspirada en el clásico italiano. Masa con esencia de café y cacao, rellena de crema de mascarpone y espolvoreada con cacao amargo. Sofisticada y deliciosa.',
 4500.00, 0, 1, 8),

(2, 'Red Velvet', 'red-velvet',
 'Terciopelo rojo con crema de queso',
 'La elegancia del red velvet en formato cookie. Masa roja aterciopelada con cacao suave, rellena de crema de queso philadelphia. Perfecta para regalar.',
 4200.00, 0, 1, 9),

(3, 'Brookie Crumble', 'brookie-crumble',
 'Brownie + cookie + crumble en una sola pieza',
 'El mejor de los tres mundos. Base de brownie de chocolate intenso, masa de cookie clásica y crumble mantecoso por encima. Para los que no pueden elegir.',
 5000.00, 1, 1, 10),

(3, 'Pistachio', 'pistachio',
 'Masa de pistacho puro, cremosa y delicada',
 'Para los paladares más exigentes. Masa con pasta de pistacho 100% natural y trozos de pistacho tostado. Verde intenso, sabor único.',
 5200.00, 0, 1, 11),

(3, 'Dubai', 'dubai',
 'Kadaif crujiente con crema de pistacho',
 'La cookie del momento. Masa de mantequilla dorada, rellena de kadaif (pasta de trigo frita) y crema de pistacho al estilo chocolate Dubai. Textura y sabor únicos.',
 6000.00, 1, 1, 12),

(3, 'Kinder Cookie', 'kinder-cookie',
 'Rellena de crema Kinder fundido',
 'Para todos los que aman Kinder. Masa dorada con leche, rellena de ganache de chocolate Kinder. Dulce, cremosa y adictiva.',
 4800.00, 0, 1, 13),

(2, 'Carrot Cookie', 'carrot-cookie',
 'Zanahoria, canela y nueces con glaseado',
 'Una cookie inspirada en el carrot cake. Masa con zanahoria rallada, canela, jengibre y nueces, con un glaseado de cream cheese por encima.',
 4000.00, 0, 1, 14),

(3, 'Cacao 70%', 'cacao-70',
 'Ganache de chocolate negro 70% en el centro',
 'Para los amantes del chocolate puro. Masa oscura e intensa con ganache de chocolate negro 70% que fluye al abrirla. Sin aditivos, solo chocolate premium.',
 5500.00, 0, 1, 15),

(2, 'M&M Marshmallow', 'mm-marshmallow',
 'M&Ms coloridos con marshmallow fundido',
 'Colorida, divertida y deliciosa. Masa de vainilla con M&Ms de colores y marshmallows tostados. La más fotogénica de nuestra carta.',
 4200.00, 0, 1, 16);

-- Configuración general
INSERT INTO settings (setting_key, setting_value) VALUES
('store_name',           'Cookie Bakery'),
('store_tagline',        'New York Style Cookies'),
('store_phone',          '+54 11 XXXX-XXXX'),
('store_email',          'hola@cookiebakery.com'),
('store_instagram',      '@cookiebakery'),
('store_address',        'Buenos Aires, Argentina'),
('store_pickup_address', 'Consultá dirección por Instagram'),
('mp_public_key',        ''),
('mp_access_token',      ''),
('mp_webhook_secret',    ''),
('shipping_cost',        '1500'),
('free_shipping_min',    '10000'),
('min_order_amount',     '3600'),
('order_number_prefix',  'CB'),
('store_open',           '1'),
('store_closed_msg',     'Estamos cerrados en este momento. Horario: Martes a Domingo 14:00 - 19:30');
