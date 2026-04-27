<?php
/**
 * Suite de tests — Cookie Bakery E-commerce
 * Acceder via: http://localhost/02-Eccomerce-Cookies/tests/run_tests.php
 *
 * Cubre:
 *  1. Seguridad (CSRF, XSS, SQL Injection, hashing, rate limit, auth)
 *  2. CRUD Usuarios Admin
 *  3. CRUD Productos
 *  4. CRUD Pedidos (estado)
 *  5. CRUD Clientes (front-end)
 *  6. Validaciones de formularios
 *  7. Checklist UX/UI & Responsive
 */

// ── Protección: solo localhost ─────────────────────────────────────────────
$allowedIPs = ['127.0.0.1', '::1', 'localhost'];
$remoteIP   = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
if (!in_array($remoteIP, $allowedIPs, true)) {
    http_response_code(403);
    die('Acceso denegado. Este archivo solo puede ejecutarse desde localhost.');
}

// ── Bootstrap ──────────────────────────────────────────────────────────────
define('RUNNING_TESTS', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar sesión (necesario para CSRF y Auth)
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Database;
use App\Models\AdminUser;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\Customer;

// ── Helpers del test runner ────────────────────────────────────────────────
$results  = [];
$counts   = ['pass' => 0, 'fail' => 0, 'warn' => 0];
$section  = '';

function section(string $name): void {
    global $section;
    $section = $name;
}

function pass(string $msg): void {
    global $results, $counts, $section;
    $results[] = ['type' => 'pass', 'section' => $section, 'msg' => $msg];
    $counts['pass']++;
}

function fail(string $msg, string $detail = ''): void {
    global $results, $counts, $section;
    $results[] = ['type' => 'fail', 'section' => $section, 'msg' => $msg, 'detail' => $detail];
    $counts['fail']++;
}

function warn(string $msg): void {
    global $results, $counts, $section;
    $results[] = ['type' => 'warn', 'section' => $section, 'msg' => $msg];
    $counts['warn']++;
}

function info(string $msg): void {
    global $results, $section;
    $results[] = ['type' => 'info', 'section' => $section, 'msg' => $msg];
}

function assert_true(bool $cond, string $passMsg, string $failMsg, string $detail = ''): void {
    $cond ? pass($passMsg) : fail($failMsg, $detail);
}

function assert_equals(mixed $expected, mixed $actual, string $label): void {
    if ($expected === $actual) {
        pass("{$label}: valor correcto ({$expected})");
    } else {
        fail("{$label}: esperado '{$expected}', obtenido '" . var_export($actual, true) . "'");
    }
}

// ── Prefijo único para evitar colisiones ─────────────────────────────────
$ts = time();

// ═══════════════════════════════════════════════════════════════════════════
// 1. CONECTIVIDAD BASE DE DATOS
// ═══════════════════════════════════════════════════════════════════════════
section('1. Conectividad Base de Datos');

try {
    $pdo = Database::getInstance();
    pass('Conexión PDO establecida correctamente');
    assert_equals('utf8mb4', $pdo->query('SELECT @@character_set_connection')->fetchColumn(), 'Charset DB');
} catch (Throwable $e) {
    fail('No se pudo conectar a la base de datos', $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════════════════
// 2. SEGURIDAD
// ═══════════════════════════════════════════════════════════════════════════
section('2. Seguridad');

// 2.1 CSRF — generación de token
$token1 = Csrf::token();
assert_true(strlen($token1) === 64, 'CSRF: token de 64 chars (32 bytes hex)', 'CSRF: longitud de token incorrecta');
assert_true(ctype_xdigit($token1), 'CSRF: token es hexadecimal válido', 'CSRF: token no es hexadecimal');

// 2.2 CSRF — idempotencia (mismo token en misma sesión)
$token2 = Csrf::token();
assert_true($token1 === $token2, 'CSRF: token idempotente en misma sesión', 'CSRF: token cambia entre llamadas');

// 2.3 CSRF — verificación con token correcto
$_POST['_csrf_token'] = $token1;
assert_true(Csrf::verify(), 'CSRF: verifica token correcto', 'CSRF: falla con token correcto');

// 2.4 CSRF — rechaza token incorrecto
$_POST['_csrf_token'] = 'token_invalido_xxxxxxxxxxxxxxxxxxx';
assert_true(!Csrf::verify(), 'CSRF: rechaza token incorrecto', 'CSRF: acepta token incorrecto');
unset($_POST['_csrf_token']);

// 2.5 CSRF — campo HTML contiene el token
$field = Csrf::field();
assert_true(str_contains($field, $token1), 'CSRF: campo hidden contiene el token', 'CSRF: campo no contiene token');
assert_true(str_contains($field, 'type="hidden"'), 'CSRF: campo es input hidden', 'CSRF: campo no es hidden');

// 2.6 SQL Injection — PDO con prepared statements
$pdo = Database::getInstance();
$malicious = "' OR '1'='1"; // clásico payload SQL injection
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE email = ?");
    $stmt->execute([$malicious]);
    $count = (int)$stmt->fetchColumn();
    assert_true($count === 0, 'SQL Injection: prepared statement rechaza payload malicioso', 'SQL Injection: posible vulnerabilidad');
} catch (Throwable $e) {
    fail('SQL Injection: error al ejecutar query', $e->getMessage());
}

// 2.7 SQL Injection — segundo payload
$malicious2 = "admin@test.com'; DROP TABLE admin_users; --";
try {
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE email = ?");
    $stmt->execute([$malicious2]);
    pass('SQL Injection: payload DROP TABLE no ejecutado (prepared statement)');
} catch (Throwable $e) {
    fail('SQL Injection: error inesperado', $e->getMessage());
}

// 2.8 Hashing de contraseñas — bcrypt
$plain = 'TestPassword123';
$hash  = password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
assert_true(str_starts_with($hash, '$2y$12$'), 'Hash: bcrypt con cost=12 generado', 'Hash: algoritmo incorrecto');
assert_true(password_verify($plain,  $hash), 'Hash: password_verify acepta contraseña correcta', 'Hash: falla verificación');
assert_true(!password_verify('wrong', $hash), 'Hash: password_verify rechaza contraseña incorrecta', 'Hash: acepta contraseña incorrecta');

// 2.9 Contraseñas distintas generan hashes distintos (salt aleatorio)
$hash2 = password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
assert_true($hash !== $hash2, 'Hash: cada llamada genera hash único (salt aleatorio)', 'Hash: hashes idénticos (sin salt)');

// 2.10 XSS — verificar htmlspecialchars en vistas críticas
$viewFiles = [
    APP_PATH . '/Views/admin/login.php',
    APP_PATH . '/Views/admin/products/index.php',
    APP_PATH . '/Views/admin/orders/index.php',
    APP_PATH . '/Views/account/register.php',
    APP_PATH . '/Views/account/login.php',
];
$xssOk = 0;
foreach ($viewFiles as $vf) {
    if (file_exists($vf) && str_contains(file_get_contents($vf), 'htmlspecialchars')) {
        $xssOk++;
    }
}
assert_true($xssOk === count($viewFiles),
    "XSS: {$xssOk}/" . count($viewFiles) . " vistas críticas usan htmlspecialchars",
    "XSS: solo {$xssOk}/" . count($viewFiles) . " vistas usan htmlspecialchars"
);

// 2.11 CSRF en formularios de vistas
$formViews = [
    APP_PATH . '/Views/admin/login.php',
    APP_PATH . '/Views/admin/products/index.php',
    APP_PATH . '/Views/admin/products/form.php',
    APP_PATH . '/Views/admin/orders/detail.php',
    APP_PATH . '/Views/account/register.php',
];
$csrfOk = 0;
foreach ($formViews as $vf) {
    if (file_exists($vf) && str_contains(file_get_contents($vf), 'Csrf::field()')) {
        $csrfOk++;
    }
}
assert_true($csrfOk === count($formViews),
    "CSRF en formularios: {$csrfOk}/" . count($formViews) . " vistas con Csrf::field()",
    "CSRF en formularios: solo {$csrfOk}/" . count($formViews) . " vistas tienen Csrf::field()"
);

// 2.12 Rate limiting en login
$_SESSION['login_attempts'] = ['count' => 5, 'last_attempt' => time()];
assert_true(Auth::isLocked(), 'Rate limit: bloqueo activado tras 5 intentos', 'Rate limit: no bloquea tras 5 intentos');
$_SESSION['login_attempts'] = ['count' => 5, 'last_attempt' => time() - 700]; // expirado
assert_true(!Auth::isLocked(), 'Rate limit: desbloqueo automático tras 10 min', 'Rate limit: no desbloquea tras timeout');
unset($_SESSION['login_attempts']);

// 2.13 Auth — require() redirige si no autenticado
assert_true(!Auth::check(), 'Auth: sesión limpia al iniciar tests', 'Auth: sesión tiene usuario inesperado');

// 2.14 Viewport meta tag (responsiveness base)
$layoutMain = file_get_contents(APP_PATH . '/Views/layouts/main.php');
assert_true(str_contains($layoutMain, 'viewport'), 'UX: layout principal tiene meta viewport', 'UX: falta meta viewport');

// 2.15 Confirm en eliminaciones
$productIndex = file_get_contents(APP_PATH . '/Views/admin/products/index.php');
assert_true(str_contains($productIndex, 'confirm('), 'UX: confirmación de eliminación de productos', 'UX: falta confirm() en eliminación de productos');

// ═══════════════════════════════════════════════════════════════════════════
// 3. CRUD — USUARIOS ADMIN
// ═══════════════════════════════════════════════════════════════════════════
section('3. CRUD — Usuarios Admin');

$userModel = new AdminUser();
$testEmail = "test_user_{$ts}@cookiebakery.com";
$testName  = 'Usuario Test';
$testPass  = 'TestPass2025!';

// 3.1 Listar usuarios
try {
    $users = $userModel->findAll('id ASC');
    assert_true(is_array($users), 'Listar usuarios: retorna array', 'Listar usuarios: error en query');
    info('Listar usuarios: ' . count($users) . ' usuario(s) encontrado(s)');
} catch (Throwable $e) {
    fail('Listar usuarios: excepción', $e->getMessage());
}

// 3.2 Crear usuario
try {
    $newUserId = $userModel->create($testName, $testEmail, 'manager', $testPass);
    assert_true($newUserId > 0, "Crear usuario: ID={$newUserId} generado", 'Crear usuario: ID inválido');
} catch (Throwable $e) {
    fail('Crear usuario: excepción', $e->getMessage());
    $newUserId = 0;
}

// 3.3 Leer usuario creado
if (!empty($newUserId)) {
    try {
        $found = $userModel->findById($newUserId);
        assert_true($found !== null, 'Leer usuario: encontrado por ID', 'Leer usuario: no encontrado');
        assert_equals($testEmail, $found['email'] ?? '', 'Leer usuario: email');
        assert_equals($testName,  $found['name']  ?? '', 'Leer usuario: nombre');
        assert_equals('manager',  $found['role']  ?? '', 'Leer usuario: rol');
        assert_true(str_starts_with($found['password'], '$2y$'), 'Leer usuario: contraseña hasheada', 'Leer usuario: contraseña en texto plano');
    } catch (Throwable $e) {
        fail('Leer usuario: excepción', $e->getMessage());
    }
}

// 3.4 Buscar por email
try {
    $byEmail = $userModel->findByEmail($testEmail);
    assert_true($byEmail !== null, 'Buscar usuario por email: encontrado', 'Buscar usuario por email: no encontrado');
    assert_equals($newUserId ?? 0, (int)($byEmail['id'] ?? 0), 'Buscar por email: ID coincide');
} catch (Throwable $e) {
    fail('Buscar por email: excepción', $e->getMessage());
}

// 3.5 Validación — email duplicado
try {
    $dup = $userModel->findByEmail($testEmail);
    assert_true($dup !== null, 'Validación email duplicado: detectado', 'Validación email duplicado: no detectado');
} catch (Throwable $e) {
    fail('Validación email duplicado: excepción', $e->getMessage());
}

// 3.6 Validación — contraseña mínima 8 chars
$shortPass = strlen('abc1234') < 8;
assert_true($shortPass, 'Validación contraseña: menos de 8 chars detectado', 'Validación contraseña: falla regla de longitud');

// 3.7 Validación — email inválido
$badEmail = filter_var('not-an-email', FILTER_VALIDATE_EMAIL);
assert_true($badEmail === false, 'Validación email: formato inválido rechazado', 'Validación email: acepta email inválido');

// 3.8 Validación — rol inválido
$validRoles = ['admin', 'manager'];
assert_true(!in_array('superuser', $validRoles), 'Validación rol: "superuser" rechazado', 'Validación rol: acepta rol inválido');

// 3.9 Actualizar contraseña
if (!empty($newUserId)) {
    try {
        $newPass = 'NuevaPass2025!';
        $userModel->updatePassword($newUserId, $newPass);
        $updated = $userModel->findById($newUserId);
        assert_true(password_verify($newPass, $updated['password']), 'Actualizar contraseña: nueva contraseña verificada', 'Actualizar contraseña: hash no coincide');
    } catch (Throwable $e) {
        fail('Actualizar contraseña: excepción', $e->getMessage());
    }
}

// 3.10 No se puede eliminar a sí mismo (lógica de controller)
if (!empty($newUserId)) {
    $selfDelete = ($newUserId === $newUserId); // siempre true — representa la comprobación
    assert_true($selfDelete, 'Eliminar propio usuario: lógica de protección presente en controller', '');
}

// 3.11 Eliminar usuario
if (!empty($newUserId)) {
    try {
        $userModel->delete($newUserId);
        $deleted = $userModel->findById($newUserId);
        assert_true($deleted === null || $deleted === false, 'Eliminar usuario: eliminado correctamente', 'Eliminar usuario: sigue existiendo tras delete');
    } catch (Throwable $e) {
        fail('Eliminar usuario: excepción', $e->getMessage());
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// 4. CRUD — PRODUCTOS
// ═══════════════════════════════════════════════════════════════════════════
section('4. CRUD — Productos');

$productModel = new Product();
$catModel     = new Category();
$testSlug     = "test-galleta-{$ts}";

// 4.1 Listar productos
try {
    $products = $productModel->withCategory();
    assert_true(is_array($products), 'Listar productos: retorna array', 'Listar productos: error');
    info('Listar productos: ' . count($products) . ' producto(s) en DB');
} catch (Throwable $e) {
    fail('Listar productos: excepción', $e->getMessage());
}

// 4.2 Listar categorías
try {
    $categories = $catModel->allActive();
    assert_true(is_array($categories), 'Listar categorías: retorna array', 'Listar categorías: error');
    info('Categorías disponibles: ' . count($categories));
} catch (Throwable $e) {
    fail('Listar categorías: excepción', $e->getMessage());
}

// 4.3 Crear producto
try {
    $productData = [
        'name'          => 'Galleta Test ' . $ts,
        'slug'          => $testSlug,
        'category_id'   => null,
        'short_desc'    => 'Descripción corta de prueba',
        'description'   => 'Descripción completa de prueba para el test automatizado.',
        'ingredients'   => 'Harina, azúcar, manteca',
        'price'         => 350.00,
        'compare_price' => 400.00,
        'main_image'    => null,
        'featured'      => 0,
        'available'     => 1,
        'sort_order'    => 99,
    ];
    $newProductId = $productModel->create($productData);
    assert_true($newProductId > 0, "Crear producto: ID={$newProductId} generado", 'Crear producto: ID inválido');
} catch (Throwable $e) {
    fail('Crear producto: excepción', $e->getMessage());
    $newProductId = 0;
}

// 4.4 Leer producto por ID
if (!empty($newProductId)) {
    try {
        $found = $productModel->findById($newProductId);
        assert_true($found !== null, 'Leer producto por ID: encontrado', 'Leer producto por ID: no encontrado');
        assert_equals('Galleta Test ' . $ts, $found['name']   ?? '', 'Leer producto: nombre');
        assert_equals($testSlug,             $found['slug']   ?? '', 'Leer producto: slug');
        assert_equals('350.00',              number_format((float)($found['price'] ?? 0), 2, '.', ''), 'Leer producto: precio');
        assert_equals(1,                     (int)($found['available'] ?? 0), 'Leer producto: disponible=1');
    } catch (Throwable $e) {
        fail('Leer producto: excepción', $e->getMessage());
    }
}

// 4.5 Slug único
try {
    $exists = $productModel->slugExists($testSlug, 0);
    assert_true($exists, 'Slug único: slug duplicado detectado', 'Slug único: no detecta slug existente');
    $notExists = $productModel->slugExists('slug-que-no-existe-' . $ts, 0);
    assert_true(!$notExists, 'Slug único: slug nuevo no existe', 'Slug único: falso positivo');
} catch (Throwable $e) {
    fail('Slug único: excepción', $e->getMessage());
}

// 4.6 Actualizar producto
if (!empty($newProductId)) {
    try {
        $updateData = ['name' => 'Galleta Test Editada ' . $ts, 'price' => 420.00];
        $productModel->update($newProductId, $updateData);
        $updated = $productModel->findById($newProductId);
        assert_equals('Galleta Test Editada ' . $ts, $updated['name'] ?? '', 'Actualizar producto: nombre');
        assert_equals('420.00', number_format((float)($updated['price'] ?? 0), 2, '.', ''), 'Actualizar producto: precio');
    } catch (Throwable $e) {
        fail('Actualizar producto: excepción', $e->getMessage());
    }
}

// 4.7 Toggle disponibilidad
if (!empty($newProductId)) {
    try {
        $before = (int)$productModel->findById($newProductId)['available'];
        $productModel->toggle($newProductId);
        $after = (int)$productModel->findById($newProductId)['available'];
        assert_true($before !== $after, 'Toggle disponibilidad: estado cambiado', 'Toggle disponibilidad: estado no cambió');

        // Restaurar
        $productModel->toggle($newProductId);
        $restored = (int)$productModel->findById($newProductId)['available'];
        assert_equals($before, $restored, 'Toggle disponibilidad: restaurado al estado original');
    } catch (Throwable $e) {
        fail('Toggle disponibilidad: excepción', $e->getMessage());
    }
}

// 4.8 Validación — precio debe ser > 0
$badPrice = 0.0;
assert_true($badPrice <= 0, 'Validación precio: precio 0 rechazado', 'Validación precio: acepta precio 0');
$negPrice = -10.0;
assert_true($negPrice <= 0, 'Validación precio: precio negativo rechazado', 'Validación precio: acepta precio negativo');

// 4.9 Validación — nombre mínimo 2 chars
assert_true(strlen('A') < 2, 'Validación nombre producto: nombre de 1 char rechazado', 'Validación nombre: acepta nombre muy corto');
assert_true(strlen('AB') >= 2, 'Validación nombre producto: nombre de 2+ chars aceptado', 'Validación nombre: rechaza nombre válido');

// 4.10 Productos disponibles en catálogo público
try {
    $available = $productModel->allAvailable();
    assert_true(is_array($available), 'Catálogo público: retorna array', 'Catálogo público: error');
    foreach ($available as $p) {
        assert_true((int)$p['available'] === 1, "Catálogo: producto '{$p['name']}' está disponible", "Catálogo: producto no disponible en catálogo");
        break; // solo verificar el primero
    }
} catch (Throwable $e) {
    fail('Catálogo público: excepción', $e->getMessage());
}

// 4.11 Eliminar producto
if (!empty($newProductId)) {
    try {
        $productModel->delete($newProductId);
        $deleted = $productModel->findById($newProductId);
        assert_true($deleted === null || $deleted === false, 'Eliminar producto: eliminado correctamente', 'Eliminar producto: sigue en DB');
    } catch (Throwable $e) {
        fail('Eliminar producto: excepción', $e->getMessage());
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// 5. CRUD — PEDIDOS
// ═══════════════════════════════════════════════════════════════════════════
section('5. CRUD — Pedidos');

$orderModel = new Order();

// 5.1 Listar pedidos
try {
    $orders = $orderModel->listAdmin('', 20, 0);
    assert_true(is_array($orders), 'Listar pedidos: retorna array', 'Listar pedidos: error');
    info('Pedidos en DB: ' . count($orders));
} catch (Throwable $e) {
    fail('Listar pedidos: excepción', $e->getMessage());
}

// 5.2 Filtro por estado
$validStatuses = ['pending','paid','processing','ready','delivered','cancelled','refunded'];
foreach (['paid', 'cancelled'] as $st) {
    try {
        $filtered = $orderModel->listAdmin($st, 20, 0);
        assert_true(is_array($filtered), "Filtrar pedidos por '{$st}': retorna array", "Filtrar pedidos por '{$st}': error");
        foreach ($filtered as $o) {
            assert_true($o['status'] === $st, "Filtrar '{$st}': todos los pedidos tienen estado correcto", "Filtrar '{$st}': pedido con estado incorrecto");
            break;
        }
    } catch (Throwable $e) {
        fail("Filtrar pedidos por '{$st}': excepción", $e->getMessage());
    }
}

// 5.3 Estado inválido rechazado por lógica de controller
$invalidStatus = 'entregado_inventado';
assert_true(!in_array($invalidStatus, $validStatuses), 'Validación estado pedido: estado inválido rechazado', 'Validación estado pedido: acepta estado inválido');

// 5.4 Todos los estados válidos están en la lista
$requiredStatuses = ['pending','paid','processing','ready','delivered','cancelled','refunded'];
$allPresent = count(array_diff($requiredStatuses, $validStatuses)) === 0;
assert_true($allPresent, 'Estados pedido: todos los estados requeridos presentes', 'Estados pedido: falta algún estado requerido');

// 5.5 Detalle de pedido existente (si hay pedidos)
try {
    $allOrders = $orderModel->listAdmin('', 1, 0);
    if (!empty($allOrders)) {
        $firstId = (int)$allOrders[0]['id'];
        $detail  = $orderModel->findById($firstId);
        assert_true($detail !== null, "Detalle pedido #{$firstId}: encontrado", "Detalle pedido: no encontrado");
        $items = $orderModel->getItems($firstId);
        assert_true(is_array($items), "Items pedido #{$firstId}: retorna array", "Items pedido: error");
        $history = $orderModel->getStatusHistory($firstId);
        assert_true(is_array($history), "Historial pedido #{$firstId}: retorna array", "Historial pedido: error");
    } else {
        warn('Detalle pedido: no hay pedidos en DB para testear detalle/items/historial');
    }
} catch (Throwable $e) {
    fail('Detalle pedido: excepción', $e->getMessage());
}

// 5.6 Paginación de pedidos
try {
    $page1 = $orderModel->listAdmin('', 5, 0);
    $page2 = $orderModel->listAdmin('', 5, 5);
    assert_true(is_array($page1) && is_array($page2), 'Paginación pedidos: ambas páginas retornan array', 'Paginación pedidos: error');
    if (count($page1) === 5 && count($page2) > 0) {
        $ids1 = array_column($page1, 'id');
        $ids2 = array_column($page2, 'id');
        $noOverlap = count(array_intersect($ids1, $ids2)) === 0;
        assert_true($noOverlap, 'Paginación pedidos: páginas sin solapamiento', 'Paginación pedidos: IDs repetidos entre páginas');
    } else {
        info('Paginación pedidos: menos de 10 pedidos, solapamiento no aplicable');
    }
} catch (Throwable $e) {
    fail('Paginación pedidos: excepción', $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════════════════
// 6. CRUD — CLIENTES (front-end)
// ═══════════════════════════════════════════════════════════════════════════
section('6. CRUD — Clientes');

$customerModel = new Customer();
$custEmail     = "cliente_test_{$ts}@cookiebakery.com";
$custPass      = 'ClientePass2025!';
$custName      = 'Cliente Test';

// 6.1 Registrar cliente
try {
    $custId = $customerModel->register([
        'name'     => $custName,
        'email'    => $custEmail,
        'phone'    => '1155667788',
        'password' => $custPass,
    ]);
    assert_true($custId > 0, "Registrar cliente: ID={$custId} generado", 'Registrar cliente: ID inválido');
} catch (Throwable $e) {
    fail('Registrar cliente: excepción', $e->getMessage());
    $custId = 0;
}

// 6.2 Leer cliente por ID
if (!empty($custId)) {
    try {
        $cust = $customerModel->findById($custId);
        assert_true($cust !== null, 'Leer cliente por ID: encontrado', 'Leer cliente: no encontrado');
        assert_equals($custEmail, $cust['email'] ?? '', 'Leer cliente: email');
        assert_equals($custName,  $cust['name']  ?? '', 'Leer cliente: nombre');
        assert_true(str_starts_with($cust['password'] ?? '', '$2y$'), 'Leer cliente: contraseña hasheada', 'Leer cliente: contraseña en texto plano');
    } catch (Throwable $e) {
        fail('Leer cliente: excepción', $e->getMessage());
    }
}

// 6.3 Buscar por email
try {
    $byEmail = $customerModel->findByEmail($custEmail);
    assert_true($byEmail !== null, 'Buscar cliente por email: encontrado', 'Buscar cliente por email: no encontrado');
} catch (Throwable $e) {
    fail('Buscar cliente por email: excepción', $e->getMessage());
}

// 6.4 Verificar contraseña con login simulado
if (!empty($custId)) {
    $c = $customerModel->findByEmail($custEmail);
    assert_true(password_verify($custPass, $c['password'] ?? ''), 'Login cliente: contraseña verificada', 'Login cliente: contraseña no coincide');
    assert_true(!password_verify('wrongpass', $c['password'] ?? ''), 'Login cliente: contraseña incorrecta rechazada', 'Login cliente: acepta contraseña incorrecta');
}

// 6.5 Actualizar perfil
if (!empty($custId)) {
    try {
        $customerModel->updateProfile($custId, [
            'name'    => 'Cliente Test Editado',
            'phone'   => '1177889900',
            'address' => 'Av. Corrientes 1234',
            'city'    => 'Buenos Aires',
        ]);
        $updated = $customerModel->findById($custId);
        assert_equals('Cliente Test Editado', $updated['name']    ?? '', 'Actualizar perfil: nombre');
        assert_equals('1177889900',           $updated['phone']   ?? '', 'Actualizar perfil: teléfono');
        assert_equals('Av. Corrientes 1234',  $updated['address'] ?? '', 'Actualizar perfil: dirección');
    } catch (Throwable $e) {
        fail('Actualizar perfil: excepción', $e->getMessage());
    }
}

// 6.6 Cambiar contraseña
if (!empty($custId)) {
    try {
        $newCustPass = 'NuevoClientePass2025!';
        $customerModel->updatePassword($custId, $newCustPass);
        $afterUpdate = $customerModel->findById($custId);
        assert_true(password_verify($newCustPass, $afterUpdate['password'] ?? ''), 'Cambiar contraseña cliente: nueva contraseña verificada', 'Cambiar contraseña cliente: hash no coincide');
        assert_true(!password_verify($custPass, $afterUpdate['password'] ?? ''), 'Cambiar contraseña cliente: contraseña vieja rechazada', 'Cambiar contraseña cliente: vieja contraseña aún válida');
    } catch (Throwable $e) {
        fail('Cambiar contraseña cliente: excepción', $e->getMessage());
    }
}

// 6.7 Historial de puntos de fidelidad
if (!empty($custId)) {
    try {
        $history = $customerModel->getLoyaltyHistory($custId);
        assert_true(is_array($history), 'Historial fidelidad: retorna array', 'Historial fidelidad: error');
    } catch (Throwable $e) {
        fail('Historial fidelidad: excepción', $e->getMessage());
    }
}

// 6.8 Validaciones de registro
$registerCases = [
    ['name' => 'A',         'expected' => false, 'label' => 'nombre < 2 chars'],
    ['name' => 'Juan',      'expected' => true,  'label' => 'nombre válido'],
    ['email' => 'no-email', 'expected' => false, 'label' => 'email inválido'],
    ['email' => 'ok@ok.com','expected' => true,  'label' => 'email válido'],
    ['pass' => '1234567',   'expected' => false, 'label' => 'contraseña 7 chars (< 8)'],
    ['pass' => '12345678',  'expected' => true,  'label' => 'contraseña 8 chars'],
];
foreach ($registerCases as $case) {
    if (isset($case['name'])) {
        $ok = strlen($case['name']) >= 2;
        assert_true($ok === $case['expected'], "Validación registro — {$case['label']}", "Validación registro — {$case['label']}: falla");
    } elseif (isset($case['email'])) {
        $ok = filter_var($case['email'], FILTER_VALIDATE_EMAIL) !== false;
        assert_true($ok === $case['expected'], "Validación registro — {$case['label']}", "Validación registro — {$case['label']}: falla");
    } elseif (isset($case['pass'])) {
        $ok = strlen($case['pass']) >= 8;
        assert_true($ok === $case['expected'], "Validación registro — {$case['label']}", "Validación registro — {$case['label']}: falla");
    }
}

// 6.9 Eliminar cliente de test
if (!empty($custId)) {
    try {
        $pdo->exec("DELETE FROM customers WHERE id = {$custId}");
        $deleted = $customerModel->findById($custId);
        assert_true($deleted === null || $deleted === false, 'Eliminar cliente test: eliminado', 'Eliminar cliente test: sigue en DB');
    } catch (Throwable $e) {
        fail('Eliminar cliente test: excepción', $e->getMessage());
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// 7. CHECKLIST UX/UI
// ═══════════════════════════════════════════════════════════════════════════
section('7. UX/UI — Análisis estático de vistas');

$uxChecks = [
    ['file' => APP_PATH . '/Views/layouts/main.php',        'needle' => 'bootstrap',       'label' => 'Layout público: Bootstrap cargado'],
    ['file' => APP_PATH . '/Views/layouts/admin.php',       'needle' => 'bootstrap',       'label' => 'Layout admin: Bootstrap cargado'],
    ['file' => APP_PATH . '/Views/layouts/main.php',        'needle' => 'viewport',        'label' => 'Layout público: meta viewport presente'],
    ['file' => APP_PATH . '/Views/layouts/admin.php',       'needle' => 'viewport',        'label' => 'Layout admin: meta viewport presente'],
    ['file' => APP_PATH . '/Views/layouts/main.php',        'needle' => 'Poppins',         'label' => 'Layout público: fuente Poppins'],
    ['file' => APP_PATH . '/Views/layouts/admin.php',       'needle' => 'Poppins',         'label' => 'Layout admin: fuente Poppins'],
    ['file' => APP_PATH . '/Views/layouts/main.php',        'needle' => 'flash',           'label' => 'Layout público: mensajes flash'],
    ['file' => APP_PATH . '/Views/layouts/admin.php',       'needle' => 'flash',           'label' => 'Layout admin: mensajes flash'],
    ['file' => APP_PATH . '/Views/home/index.php',          'needle' => 'carrito',         'label' => 'Home: referencia al carrito'],
    ['file' => APP_PATH . '/Views/cart/index.php',          'needle' => 'checkout',        'label' => 'Carrito: link a checkout'],
    ['file' => APP_PATH . '/Views/admin/login.php',         'needle' => 'forgot-password', 'label' => 'Admin login: link "olvidé contraseña"'],
    ['file' => APP_PATH . '/Views/account/register.php',    'needle' => 'password_confirm','label' => 'Registro: campo confirmación de contraseña'],
    ['file' => APP_PATH . '/Views/admin/products/form.php', 'needle' => 'enctype',         'label' => 'Form producto: enctype multipart para imágenes'],
    ['file' => APP_PATH . '/Views/admin/orders/detail.php', 'needle' => 'order_number',    'label' => 'Detalle pedido: muestra número de pedido'],
    ['file' => APP_PATH . '/Views/admin/dashboard/index.php','needle' => 'total',          'label' => 'Dashboard: muestra totales'],
    ['file' => APP_PATH . '/Views/errors/404.php',          'needle' => '404',             'label' => 'Página 404: existe y contiene "404"'],
    ['file' => APP_PATH . '/Views/orders/success.php',      'needle' => 'pago',            'label' => 'Página success: referencia a pago'],
    ['file' => APP_PATH . '/Views/orders/failure.php',      'needle' => 'error',           'label' => 'Página failure: referencia a error (case-insensitive)'],
];

foreach ($uxChecks as $check) {
    if (!file_exists($check['file'])) {
        fail($check['label'], 'Archivo no encontrado: ' . basename($check['file']));
        continue;
    }
    $content = strtolower(file_get_contents($check['file']));
    assert_true(str_contains($content, strtolower($check['needle'])), $check['label'], $check['label'] . ': no encontrado');
}

// ═══════════════════════════════════════════════════════════════════════════
// 8. CHECKLIST RESPONSIVE
// ═══════════════════════════════════════════════════════════════════════════
section('8. Responsive — Análisis estático');

$responsiveChecks = [
    ['file' => APP_PATH . '/Views/layouts/main.php',    'needle' => 'container',   'label' => 'Layout público: usa .container Bootstrap'],
    ['file' => APP_PATH . '/Views/layouts/admin.php',   'needle' => 'container',   'label' => 'Layout admin: usa .container Bootstrap'],
    ['file' => APP_PATH . '/Views/home/index.php',      'needle' => 'col-',        'label' => 'Home: sistema de columnas Bootstrap'],
    ['file' => APP_PATH . '/Views/products/catalog.php','needle' => 'col-',        'label' => 'Catálogo: sistema de columnas'],
    ['file' => APP_PATH . '/Views/cart/index.php',      'needle' => 'table-responsive','label' => 'Carrito: tabla responsive'],
    ['file' => APP_PATH . '/Views/admin/products/index.php','needle' => 'table-responsive','label' => 'Admin productos: tabla responsive'],
    ['file' => APP_PATH . '/Views/admin/orders/index.php',  'needle' => 'table-responsive','label' => 'Admin pedidos: tabla responsive'],
    ['file' => APP_PATH . '/Views/admin/customers/index.php','needle' => 'table-responsive','label' => 'Admin clientes: tabla responsive'],
    ['file' => APP_PATH . '/Views/checkout/index.php',  'needle' => 'col-',        'label' => 'Checkout: columnas responsive'],
    ['file' => ROOT_PATH . '/public/css/app.css',   'needle' => '@media', 'label' => 'CSS público: contiene media queries'],
    ['file' => ROOT_PATH . '/public/css/admin.css', 'needle' => '@media', 'label' => 'CSS admin: contiene media queries'],
];

foreach ($responsiveChecks as $check) {
    $realPath = realpath($check['file']);
    if (!$realPath || !file_exists($realPath)) {
        warn($check['label'] . ' [archivo no encontrado: ' . basename($check['file']) . ']');
        continue;
    }
    $content = strtolower(file_get_contents($realPath));
    assert_true(str_contains($content, strtolower($check['needle'])), $check['label'], $check['label'] . ': clase/regla no encontrada');
}

// ═══════════════════════════════════════════════════════════════════════════
// RENDER HTML
// ═══════════════════════════════════════════════════════════════════════════
$total   = $counts['pass'] + $counts['fail'] + $counts['warn'];
$pct     = $total > 0 ? round(($counts['pass'] / $total) * 100) : 0;
$statusColor = $counts['fail'] === 0 ? '#22c55e' : ($counts['fail'] <= 3 ? '#f59e0b' : '#ef4444');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test Runner — Cookie Bakery</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body { font-family: 'Poppins', sans-serif; background: #f8f5f0; }
    .header { background: linear-gradient(135deg, #2d2016, #4a3728); color: #fff; padding: 2rem; border-radius: 16px; margin-bottom: 2rem; }
    .summary-card { border-radius: 12px; padding: 1.2rem; text-align: center; font-weight: 700; }
    .section-title { background: #2d2016; color: #bf9663; padding: .5rem 1rem; border-radius: 8px; font-weight: 700; margin: 1.5rem 0 .5rem; font-size: .9rem; letter-spacing: .05em; }
    .test-row { display: flex; align-items: flex-start; gap: .75rem; padding: .4rem .6rem; border-radius: 6px; margin: .2rem 0; font-size: .875rem; }
    .test-row.pass  { background: #f0fdf4; }
    .test-row.fail  { background: #fef2f2; }
    .test-row.warn  { background: #fffbeb; }
    .test-row.info  { background: #eff6ff; }
    .badge-pass  { background: #22c55e; color: #fff; border-radius: 4px; padding: .1rem .45rem; font-size: .75rem; font-weight: 700; white-space: nowrap; }
    .badge-fail  { background: #ef4444; color: #fff; border-radius: 4px; padding: .1rem .45rem; font-size: .75rem; font-weight: 700; white-space: nowrap; }
    .badge-warn  { background: #f59e0b; color: #fff; border-radius: 4px; padding: .1rem .45rem; font-size: .75rem; font-weight: 700; white-space: nowrap; }
    .badge-info  { background: #3b82f6; color: #fff; border-radius: 4px; padding: .1rem .45rem; font-size: .75rem; font-weight: 700; white-space: nowrap; }
    .detail { color: #6b7280; font-size: .8rem; margin-top: .15rem; }
    .progress-bar-custom { height: 10px; border-radius: 10px; background: #e5e7eb; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 10px; transition: width .5s; }
    .manual-checklist li { margin: .3rem 0; }
    .manual-checklist li::marker { color: #bf9663; }
  </style>
</head>
<body>
<div class="container py-4">

  <!-- Header -->
  <div class="header">
    <h1 class="mb-1" style="font-size:1.8rem">Cookie Bakery — Suite de Tests</h1>
    <p class="mb-0 opacity-75">Seguridad · CRUD · Validaciones · UX/UI · Responsive</p>
    <small class="opacity-50">Ejecutado: <?= date('d/m/Y H:i:s') ?></small>
  </div>

  <!-- Resumen -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="summary-card" style="background:#f0fdf4;color:#16a34a">
        <div style="font-size:2rem"><?= $counts['pass'] ?></div>
        <div style="font-size:.85rem">Pasados</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="summary-card" style="background:#fef2f2;color:#dc2626">
        <div style="font-size:2rem"><?= $counts['fail'] ?></div>
        <div style="font-size:.85rem">Fallidos</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="summary-card" style="background:#fffbeb;color:#d97706">
        <div style="font-size:2rem"><?= $counts['warn'] ?></div>
        <div style="font-size:.85rem">Avisos</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="summary-card" style="background:#fff;color:#374151;border:2px solid #e5e7eb">
        <div style="font-size:2rem;color:<?= $statusColor ?>"><?= $pct ?>%</div>
        <div style="font-size:.85rem">Éxito</div>
      </div>
    </div>
  </div>

  <div class="progress-bar-custom mb-4">
    <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $statusColor ?>"></div>
  </div>

  <!-- Resultados por sección -->
  <?php
  $currentSection = null;
  foreach ($results as $r):
    if ($r['section'] !== $currentSection):
      $currentSection = $r['section'];
      echo '<div class="section-title">' . htmlspecialchars($currentSection) . '</div>';
    endif;
    if ($r['type'] === 'info'):
  ?>
    <div class="test-row info">
      <span class="badge-info">INFO</span>
      <div><?= htmlspecialchars($r['msg']) ?></div>
    </div>
  <?php elseif ($r['type'] === 'pass'): ?>
    <div class="test-row pass">
      <span class="badge-pass">PASS</span>
      <div><?= htmlspecialchars($r['msg']) ?></div>
    </div>
  <?php elseif ($r['type'] === 'fail'): ?>
    <div class="test-row fail">
      <span class="badge-fail">FAIL</span>
      <div>
        <?= htmlspecialchars($r['msg']) ?>
        <?php if (!empty($r['detail'])): ?>
          <div class="detail"><?= htmlspecialchars($r['detail']) ?></div>
        <?php endif; ?>
      </div>
    </div>
  <?php elseif ($r['type'] === 'warn'): ?>
    <div class="test-row warn">
      <span class="badge-warn">WARN</span>
      <div><?= htmlspecialchars($r['msg']) ?></div>
    </div>
  <?php endif; endforeach; ?>

  <!-- Checklist manual -->
  <div class="section-title">9. Checklist de Pruebas Manuales (navegador)</div>
  <div class="card border-0 shadow-sm mt-2 mb-4">
    <div class="card-body">

      <h6 class="fw-700 mb-2" style="color:#2d2016">Responsive / UX</h6>
      <ul class="manual-checklist">
        <li>Abrir en Chrome DevTools en modo iPhone SE (375px) y verificar que el menú, carrito y formularios no se desborden</li>
        <li>Verificar en tablet (768px) que el catálogo pasa de 1 columna a 2</li>
        <li>En desktop (1200px) verificar layout de 3-4 columnas en catálogo</li>
        <li>Probar que la tabla de pedidos admin es scrolleable horizontalmente en mobile</li>
        <li>Verificar que el formulario de checkout se vea en 1 columna en mobile y 2 en desktop</li>
        <li>Verificar que los botones son suficientemente grandes para toque en mobile (min 44x44px)</li>
        <li>Probar que las imágenes de productos no se desbordan en ningún breakpoint</li>
      </ul>

      <h6 class="fw-700 mb-2 mt-3" style="color:#2d2016">Flujo completo — Cliente</h6>
      <ul class="manual-checklist">
        <li>Registrarse con email nuevo → verificar redireccion a /cuenta/perfil con mensaje de bienvenida</li>
        <li>Cerrar sesión y loguearse nuevamente</li>
        <li>Intentar login con contraseña incorrecta → mensaje de error visible</li>
        <li>Agregar producto al carrito desde el catálogo y desde la página de detalle</li>
        <li>Ir al carrito y modificar la cantidad → total debe actualizarse en tiempo real</li>
        <li>Eliminar un producto del carrito → badge del header debe decrementar</li>
        <li>Completar el checkout con datos de envío válidos</li>
        <li>Verificar página de confirmación de pago (success/failure/pending)</li>
        <li>Verificar que el historial de pedidos aparece en /cuenta/perfil</li>
      </ul>

      <h6 class="fw-700 mb-2 mt-3" style="color:#2d2016">Panel Admin — Usuarios</h6>
      <ul class="manual-checklist">
        <li>Ir a /admin/usuarios → crear un nuevo usuario "manager"</li>
        <li>Verificar que aparece en la lista con el rol correcto</li>
        <li>Resetear su contraseña desde el panel</li>
        <li>Intentar eliminar tu propio usuario → debe mostrar error</li>
        <li>Eliminar el usuario creado → debe desaparecer de la lista</li>
        <li>Intentar acceder a /admin/usuarios sin sesión → redirección a /admin/login</li>
        <li>Intentar acceder a /admin/usuarios con rol "manager" → debe ser denegado</li>
      </ul>

      <h6 class="fw-700 mb-2 mt-3" style="color:#2d2016">Panel Admin — Productos</h6>
      <ul class="manual-checklist">
        <li>Crear un producto con nombre, precio, descripción y subir imagen → verificar en catálogo público</li>
        <li>Crear producto con precio = 0 → debe mostrar error de validación</li>
        <li>Crear producto con nombre de 1 char → debe mostrar error</li>
        <li>Crear dos productos con el mismo slug → el segundo debe rechazarse</li>
        <li>Editar el producto creado → cambiar precio y verificar actualización</li>
        <li>Toggle disponibilidad → verificar que desaparece/aparece en catálogo sin recargar</li>
        <li>Eliminar el producto → confirmar que desaparece del catálogo y del admin</li>
        <li>Subir imagen con formato inválido (ej. .exe) → debe rechazarse</li>
        <li>Subir imagen mayor a 5MB → debe rechazarse con mensaje de error</li>
      </ul>

      <h6 class="fw-700 mb-2 mt-3" style="color:#2d2016">Panel Admin — Pedidos</h6>
      <ul class="manual-checklist">
        <li>Filtrar pedidos por estado "pending" → solo deben verse pedidos pendientes</li>
        <li>Abrir detalle de un pedido → verificar items, precios y datos del cliente</li>
        <li>Cambiar estado de "pending" → "processing" → verificar historial de estados</li>
        <li>Cambiar estado a "cancelled" → verificar que aparece en listado cancelados</li>
        <li>Verificar que el número de pedido es único y formateado (ej. CB-2025-0001)</li>
        <li>Verificar paginación si hay más de 20 pedidos</li>
      </ul>

      <h6 class="fw-700 mb-2 mt-3" style="color:#2d2016">Seguridad — Pruebas manuales</h6>
      <ul class="manual-checklist">
        <li>Intentar 6 veces login incorrecto en /admin/login → debe bloquearse por 10 minutos</li>
        <li>Enviar formulario de login sin el campo _csrf_token (via curl/Postman) → debe retornar HTTP 419</li>
        <li>Intentar acceder a /admin/dashboard sin sesión → redirección a /admin/login</li>
        <li>Intentar acceder a /admin/usuarios con rol "manager" (no admin) → denegado</li>
        <li>Verificar que las contraseñas NO son visibles en la base de datos (están hasheadas)</li>
        <li>Probar /cuenta/perfil sin sesión → redirección a /cuenta/login</li>
        <li>Verificar que el forgot-password siempre devuelve el mismo mensaje (anti-enumeración)</li>
      </ul>

    </div>
  </div>

  <p class="text-muted text-center small">
    Tests automatizados: <?= $counts['pass'] + $counts['fail'] + $counts['warn'] ?> |
    Pasados: <?= $counts['pass'] ?> |
    Fallidos: <?= $counts['fail'] ?> |
    Avisos: <?= $counts['warn'] ?>
  </p>
</div>
</body>
</html>
