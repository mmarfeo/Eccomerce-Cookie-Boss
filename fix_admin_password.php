<?php
/**
 * Script temporal para corregir las contraseñas de admin en la base de datos.
 * Acceder desde el navegador: http://localhost/02-Eccomerce-Cookies/fix_admin_password.php
 * ELIMINAR este archivo después de ejecutarlo.
 */

// Cargar configuración
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Contraseñas a establecer
    $users = [
        ['email' => 'admin@cookiebakery.com',   'password' => 'Admin123!',   'name' => 'Administrador'],
        ['email' => 'manager@cookiebakery.com', 'password' => 'Manager123!', 'name' => 'Encargado'],
    ];

    $updated = [];
    foreach ($users as $u) {
        $hash = password_hash($u['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE email = ?");
        $rows = $stmt->execute([$hash, $u['email']]);
        if ($stmt->rowCount() > 0) {
            $updated[] = $u;
        }
    }

    echo '<style>body{font-family:sans-serif;max-width:600px;margin:60px auto;padding:20px}</style>';
    echo '<h2>✅ Contraseñas actualizadas</h2>';
    echo '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%">';
    echo '<tr><th>Usuario</th><th>Email</th><th>Contraseña</th><th>Rol</th></tr>';
    foreach ($updated as $u) {
        $role = $u['email'] === 'admin@cookiebakery.com' ? 'Admin' : 'Encargado';
        echo "<tr><td>{$u['name']}</td><td>{$u['email']}</td><td><code>{$u['password']}</code></td><td>{$role}</td></tr>";
    }
    echo '</table>';

    if (empty($updated)) {
        echo '<p style="color:red">⚠️ No se encontraron usuarios con esos emails. ¿Importaste el database.sql?</p>';
    } else {
        echo '<p style="color:green">Ahora podés <a href="' . BASE_URL . '/admin/login">iniciar sesión en el panel</a>.</p>';
        echo '<p style="color:red"><strong>⚠️ Eliminá este archivo (fix_admin_password.php) inmediatamente por seguridad.</strong></p>';
    }

} catch (Exception $e) {
    echo '<p style="color:red">Error de conexión: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Verificá que MySQL esté corriendo y que hayas importado el database.sql.</p>';
}
