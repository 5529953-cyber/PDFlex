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

    /**
     * Envía un archivo real como descarga (headers de attachment).
     */
    protected function descargarArchivo(string $rutaCompleta, string $nombreDescarga): void
    {
        $this->enviarArchivo($rutaCompleta, 'attachment', $nombreDescarga);
    }

    /**
     * Envía un archivo para mostrarlo EMBEBIDO en el navegador (p. ej.
     * dentro de un <iframe>, para "Vista previa"), en vez de descargarlo.
     * Solo tiene sentido para tipos que el navegador puede mostrar solo
     * (PDF, imágenes) — el controlador que lo llama es responsable de
     * decidir si el tipo de archivo se puede previsualizar o no.
     */
    protected function mostrarArchivo(string $rutaCompleta): void
    {
        $this->enviarArchivo($rutaCompleta, 'inline');
    }

    private function enviarArchivo(string $rutaCompleta, string $disposicion, ?string $nombreDescarga = null): void
    {
        $mime = mime_content_type($rutaCompleta) ?: 'application/octet-stream';
        $nombre = $nombreDescarga !== null ? str_replace(['"', '\\'], '', $nombreDescarga) : basename($rutaCompleta);

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposicion . '; filename="' . $nombre . '"');
        header('Content-Length: ' . filesize($rutaCompleta));
        header('Cache-Control: no-cache, must-revalidate');

        readfile($rutaCompleta);
        exit;
    }
}