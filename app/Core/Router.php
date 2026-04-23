<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function __construct()
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        // ── Rutas públicas ─────────────────────────────────
        $this->add('',                      'HomeController',     'index');
        $this->add('inicio',               'HomeController',     'index');
        $this->add('nosotros',             'HomeController',     'about');
        $this->add('contacto',             'HomeController',     'contact');

        $this->add('productos',            'ProductController',  'catalog');
        $this->add('productos/categoria',  'ProductController',  'catalog');
        $this->add('producto',             'ProductController',  'detail');

        $this->add('carrito',              'CartController',     'index');
        $this->add('carrito/agregar',      'CartController',     'add');
        $this->add('carrito/actualizar',   'CartController',     'update');
        $this->add('carrito/eliminar',     'CartController',     'remove');
        $this->add('carrito/contar',       'CartController',     'count');

        $this->add('checkout',             'CheckoutController', 'index');
        $this->add('checkout/procesar',    'CheckoutController', 'process');

        $this->add('pedido/success',       'PaymentController',  'success');
        $this->add('pedido/failure',       'PaymentController',  'failure');
        $this->add('pedido/pending',       'PaymentController',  'pending');
        $this->add('pedido/confirmacion',  'PaymentController',  'confirmation');
        $this->add('webhook/mercadopago',  'PaymentController',  'webhook');

        // ── Rutas admin ────────────────────────────────────
        $this->add('admin',                'Admin\AuthController',    'redirectDashboard');
        $this->add('admin/login',          'Admin\AuthController',    'login');
        $this->add('admin/logout',         'Admin\AuthController',    'logout');

        $this->add('admin/dashboard',      'Admin\DashboardController', 'index');

        $this->add('admin/categorias',             'Admin\CategoryController', 'index');
        $this->add('admin/categorias/crear',       'Admin\CategoryController', 'create');
        $this->add('admin/categorias/editar',      'Admin\CategoryController', 'edit');
        $this->add('admin/categorias/eliminar',    'Admin\CategoryController', 'delete');
        $this->add('admin/categorias/reordenar',   'Admin\CategoryController', 'reorder');

        $this->add('admin/productos',              'Admin\ProductAdminController', 'index');
        $this->add('admin/productos/crear',        'Admin\ProductAdminController', 'create');
        $this->add('admin/productos/editar',       'Admin\ProductAdminController', 'edit');
        $this->add('admin/productos/eliminar',     'Admin\ProductAdminController', 'delete');
        $this->add('admin/productos/toggle',       'Admin\ProductAdminController', 'toggle');
        $this->add('admin/productos/imagen-eliminar', 'Admin\ProductAdminController', 'deleteImage');

        $this->add('admin/pedidos',                'Admin\OrderAdminController', 'index');
        $this->add('admin/pedidos/ver',            'Admin\OrderAdminController', 'detail');
        $this->add('admin/pedidos/estado',         'Admin\OrderAdminController', 'updateStatus');

        $this->add('admin/clientes',               'Admin\CustomerController', 'index');
        $this->add('admin/clientes/ver',           'Admin\CustomerController', 'detail');

        $this->add('admin/reportes',               'Admin\ReportController', 'index');
        $this->add('admin/configuracion',          'Admin\SettingController',  'index');
        $this->add('admin/configuracion/guardar',  'Admin\SettingController',  'save');

        // ── Gestión de usuarios admin ──────────────────────────
        $this->add('admin/usuarios',               'Admin\AdminUserController', 'index');
        $this->add('admin/usuarios/crear',         'Admin\AdminUserController', 'create');
        $this->add('admin/usuarios/eliminar',      'Admin\AdminUserController', 'delete');
        $this->add('admin/usuarios/reset-password','Admin\AdminUserController', 'resetPassword');

        // ── Password reset ─────────────────────────────────────
        $this->add('admin/forgot-password',        'Admin\AuthController',    'forgotPassword');
        $this->add('admin/reset-password',         'Admin\AuthController',    'resetPassword');
        $this->add('admin/change-password',        'Admin\AuthController',    'changePassword');

        // ── Puntos de fidelidad ────────────────────────────────
        $this->add('admin/puntos',                 'Admin\LoyaltyController', 'index');
        $this->add('admin/puntos/ajustar',         'Admin\LoyaltyController', 'adjust');
        $this->add('admin/puntos/config',          'Admin\LoyaltyController', 'config');

        // ── Cupones ────────────────────────────────────────────
        $this->add('admin/cupones',                'Admin\CouponController',  'index');
        $this->add('admin/cupones/crear',          'Admin\CouponController',  'create');
        $this->add('admin/cupones/eliminar',       'Admin\CouponController',  'delete');
        $this->add('admin/cupones/toggle',         'Admin\CouponController',  'toggle');

        // ── Checkout con puntos/cupones ────────────────────────
        $this->add('checkout/aplicar-cupon',       'CheckoutController',      'applyCoupon');
        $this->add('checkout/aplicar-puntos',      'CheckoutController',      'applyPoints');

        // ── Cuenta de clientes ─────────────────────────────────
        $this->add('cuenta/registro',              'AccountController',        'register');
        $this->add('cuenta/login',                 'AccountController',        'login');
        $this->add('cuenta/logout',                'AccountController',        'logout');
        $this->add('cuenta/perfil',                'AccountController',        'profile');
        $this->add('cuenta/cambiar-contrasena',    'AccountController',        'changePassword');
    }

    private function add(string $path, string $controller, string $action): void
    {
        $this->routes[$path] = ['controller' => $controller, 'action' => $action];
    }

    public function dispatch(): void
    {
        $url = $_GET['url'] ?? '';
        $url = trim($url, '/');

        // Quitar query string si quedara
        $url = strtok($url, '?');
        $url = $url ?: '';

        if (isset($this->routes[$url])) {
            $route = $this->routes[$url];
        } else {
            // Intentar coincidencia con prefijo (para rutas con ID en query string)
            $route = null;
            foreach ($this->routes as $path => $r) {
                if (str_starts_with($url, $path) && strlen($url) > strlen($path)) {
                    $route = $r;
                    break;
                }
            }
            if (!$route) {
                $this->notFound();
                return;
            }
        }

        $controllerName = 'App\\Controllers\\' . str_replace('\\', '\\', $route['controller']);
        $action         = $route['action'];

        if (!class_exists($controllerName)) {
            $this->notFound();
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $action)) {
            $this->notFound();
            return;
        }

        $controller->$action();
    }

    private function notFound(): void
    {
        http_response_code(404);
        $view = APP_PATH . '/Views/errors/404.php';
        if (file_exists($view)) {
            include $view;
        } else {
            echo '<h1>404 - Página no encontrada</h1>';
        }
    }
}
