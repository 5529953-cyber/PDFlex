<?php
/**
 * Controlador base: todos los controladores heredan de aquí
 * para tener una forma común de cargar vistas dentro del layout.
 */
class Controller
{
    protected function vista(string $ruta, array $datos = [], string $layout = 'app'): void
    {
        extract($datos);
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . "/../views/{$ruta}.php";
        require __DIR__ . '/../views/layouts/footer.php';
    }

    protected function redirigir(string $ruta): void
    {
        // BASE_URL es obligatorio aquí: la app vive en /PDFlex/public, no en la
        // raíz del servidor. Un "Location: /login" a secas manda al navegador a
        // http://localhost/login (404) en vez de http://localhost/PDFlex/public/login.
        header('Location: ' . BASE_URL . $ruta);
        exit;
    }

    protected function json(array $datos): void
    {
        header('Content-Type: application/json');
        echo json_encode($datos);
        exit;
    }

     protected function requiereSesion(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            $this->redirigir('/login');
        }
    }
}
