<?php
/**
 * Controlador base: todos los controladores heredan de aquí
 * para tener una forma común de cargar vistas dentro del layout.
 */
class Controller
{
    protected function vista(string $ruta, array $datos = []): void
    {
        extract($datos);
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . "/../views/{$ruta}.php";
        require __DIR__ . '/../views/layouts/footer.php';
    }

    protected function redirigir(string $ruta): void
    {
        header("Location: {$ruta}");
        exit;
    }

    protected function json(array $datos): void
    {
        header('Content-Type: application/json');
        echo json_encode($datos);
        exit;
    }
}
