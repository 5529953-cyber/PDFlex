<?php
/**
 * Front controller — único punto de entrada de la aplicación.
 * Todas las peticiones pasan por aquí gracias a .htaccess.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Router.php';

// Autoload simple para controladores y modelos (sin Composer por ahora)
spl_autoload_register(function ($clase) {
    $rutas = [
        __DIR__ . "/../app/controllers/{$clase}.php",
        __DIR__ . "/../app/models/{$clase}.php",
    ];
    foreach ($rutas as $ruta) {
        if (file_exists($ruta)) {
            require_once $ruta;
            return;
        }
    }
});

$router = new Router();

// --- Rutas (URL => [Controlador, método]) ---
// Cada una corresponde a una de las pantallas ya diseñadas en los wireframes.
$router->get('/', ['AuthController', 'login']);
$router->get('/login', ['AuthController', 'login']);
$router->post('/login', ['AuthController', 'procesarLogin']);
$router->get('/registro', ['AuthController', 'registro']);
$router->post('/registro', ['AuthController', 'procesarRegistro']);
$router->get('/logout', ['AuthController', 'logout']);

$router->get('/subir', ['UploadController', 'index']);
$router->post('/subir', ['UploadController', 'procesar']);

$router->get('/historial', ['HistorialController', 'index']);

$router->get('/estado', ['EstadoController', 'index']);
$router->get('/estado/consultar', ['EstadoController', 'consultar']); // endpoint AJAX para refrescar progreso

$router->resolver();
