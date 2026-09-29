<?php
/**
 * Front controller — único punto de entrada de la aplicación.
 * Todas las peticiones pasan por aquí gracias a .htaccess.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zona horaria (29 sep, Marvin): sin esto PHP usa UTC por defecto, mientras
// que MySQL (NOW(), usado por Historial::actualizarEstado() para poner
// fecha_fin) devuelve la hora local del servidor — Honduras, UTC-6. Ese
// desfase de 6 horas era la causa de que "Terminó hace X" mostrara
// "hace 6 horas" en un proceso recién completado (EstadoController::tiempoRelativo()
// hace time() - strtotime(fecha_fin), y sin esta línea time() venía adelantado
// 6 horas respecto a fecha_fin).
date_default_timezone_set('America/Tegucigalpa');

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
$router->get('/reintentar', ['UploadController', 'reintentar']); // NUEVO — reintentar operación fallida

$router->get('/historial', ['HistorialController', 'index']);
$router->get('/descargar', ['HistorialController', 'descargar']); // NUEVO — descarga real de archivos procesados
$router->get('/previsualizar', ['HistorialController', 'previsualizar']); // NUEVO — vista previa embebida

$router->get('/estado', ['EstadoController', 'index']);
$router->get('/estado/consultar', ['EstadoController', 'consultar']); // endpoint AJAX para refrescar progreso
$router->get('/estado/editar-ocr', ['EstadoController', 'editarOcr']); // NUEVO — corregir texto de un OCR completado
$router->post('/estado/editar-ocr', ['EstadoController', 'guardarOcr']);

$router->resolver();
