-- ============================================================
-- Actualizaciones v3: Login de clientes + gestión de admins
-- Ejecutar después de database.sql y database_updates.sql
-- ============================================================

-- ─── PASSWORD para clientes (nullable = guest sigue funcionando) ──
ALTER TABLE customers
    ADD COLUMN IF NOT EXISTS password      VARCHAR(255) NULL        AFTER notes,
    ADD COLUMN IF NOT EXISTS email_verified TINYINT(1)  NOT NULL DEFAULT 0 AFTER password,
    ADD COLUMN IF NOT EXISTS last_login    DATETIME     NULL        AFTER email_verified;

-- Hacer email único (los guests pueden tener el mismo email repetido;
-- para login lo resolvemos con UNIQUE + NULL password para guests)
-- NOTA: Si ya hay emails duplicados en customers, primero deduplicar.
-- ALTER TABLE customers ADD UNIQUE INDEX idx_email_unique (email);
-- (Comentado por seguridad — ejecutar manualmente si no hay duplicados)

-- ─── PASSWORD RESETS para clientes ──────────────────────────
CREATE TABLE IF NOT EXISTS customer_password_resets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(150)    NOT NULL,
    token       VARCHAR(64)     NOT NULL UNIQUE,
    used        TINYINT(1)      NOT NULL DEFAULT 0,
    expires_at  DATETIME        NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Segundo usuario admin de ejemplo (encargado) ──────────
-- Contraseña para ambos usuarios: Admin123!
-- Para cambiarla desde el panel: Admin → Usuarios panel → ícono llave
INSERT IGNORE INTO admin_users (name, email, password, role) VALUES
('Encargado', 'manager@cookiebakery.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager');
