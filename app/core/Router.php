<?php
/**
 * Router muy simple: empareja método HTTP + ruta con [Controlador, método].
 * Suficiente para el alcance de PDFlex; se puede crecer más adelante
 * (parámetros dinámicos, middlewares) si el proyecto lo pide.
 */
class Router
{
    private array $rutas = ['GET' => [], 'POST' => []];

    public function get(string $ruta, array $accion): void
    {
        $this->rutas['GET'][$ruta] = $accion;
    }

    public function post(string $ruta, array $accion): void
    {
        $this->rutas['POST'][$ruta] = $accion;
    }

    public function resolver(): void
    {
        $metodo = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Quita la carpeta base (p.ej. "/PDFlex/public") ya que la app
        // vive en una subcarpeta de www, no en la raíz del servidor.
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        if ($base !== '' && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }

        $uri = rtrim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }

        $accion = $this->rutas[$metodo][$uri] ?? null;

        if (!$accion) {
            http_response_code(404);
            echo "404 — Página no encontrada: {$uri}";
            return;
        }

        [$controlador, $metodoControlador] = $accion;
        $instancia = new $controlador();
        $instancia->$metodoControlador();
    }
}
