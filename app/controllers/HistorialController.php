<?php
/**
 * Historial de conversiones del usuario (tabla filtrable).
 * Vista ya diseñada en el wireframe "Historial" (Historial.dc.html).
 *
 * Semana 6 (s6-f1 / s6-b1) — completo. Descarga, reintento y vista previa
 * agregados después (sin semana asignada en el cronograma original).
 */
class HistorialController extends Controller
{
    private const EXTENSIONES_PREVISUALIZABLES = ['pdf', 'png', 'jpg', 'jpeg'];

    public function index(): void
    {
        $this->requiereSesion();

        $busqueda = trim($_GET['busqueda'] ?? '');
        $operacion = trim($_GET['operacion'] ?? '');

        $registros = Historial::porUsuario(
            $_SESSION['usuario_id'],
            $busqueda !== '' ? $busqueda : null,
            $operacion !== '' ? $operacion : null
        );

        $this->vista('historial/historial', ['registros' => $registros, 'activo' => 'historial']);
    }

    public function descargar(): void
    {
        $this->requiereSesion();

        $id = (int) ($_GET['id'] ?? 0);
        $registro = Historial::porId($id, $_SESSION['usuario_id']);

        if ($registro === null || $registro['estado'] !== 'completado' || empty($registro['nombre_archivo_resultado'])) {
            http_response_code(404);
            echo 'Archivo no encontrado o todavía no está listo para descargar.';
            exit;
        }

        $nombreFisico = basename($registro['nombre_archivo_resultado']);
        $rutaArchivo = __DIR__ . '/../../storage/processed/' . $nombreFisico;

        if (!file_exists($rutaArchivo)) {
            http_response_code(404);
            echo 'El archivo ya no está disponible (puede haber expirado).';
            exit;
        }

        $extension = pathinfo($rutaArchivo, PATHINFO_EXTENSION);
        $nombreBase = pathinfo($registro['nombre_archivo_original'] ?? $nombreFisico, PATHINFO_FILENAME);
        $nombreDescarga = $nombreBase . '.' . $extension;

        $this->descargarArchivo($rutaArchivo, $nombreDescarga);
    }

    /**
     * GET /previsualizar?id=<historial_id> — pensada para cargarse dentro
     * de un <iframe> en el modal de "Vista previa" (s4-f2, todavía sin
     * armar del lado de Marvin). Para PDF/imagen muestra el archivo
     * directo; para lo demás (p. ej. .docx, que el navegador no puede
     * mostrar solo) devuelve una página mínima con un enlace de descarga,
     * para que el iframe siga mostrando algo sensato.
     */
    public function previsualizar(): void
    {
        $this->requiereSesion();

        $id = (int) ($_GET['id'] ?? 0);
        $registro = Historial::porId($id, $_SESSION['usuario_id']);

        if ($registro === null || $registro['estado'] !== 'completado' || empty($registro['nombre_archivo_resultado'])) {
            http_response_code(404);
            echo 'Archivo no encontrado o todavía no está listo.';
            exit;
        }

        $nombreFisico = basename($registro['nombre_archivo_resultado']);
        $rutaArchivo = __DIR__ . '/../../storage/processed/' . $nombreFisico;

        if (!file_exists($rutaArchivo)) {
            http_response_code(404);
            echo 'El archivo ya no está disponible (puede haber expirado).';
            exit;
        }

        $extension = strtolower(pathinfo($rutaArchivo, PATHINFO_EXTENSION));

        if (!in_array($extension, self::EXTENSIONES_PREVISUALIZABLES, true)) {
            header('Content-Type: text/html; charset=utf-8');
            $urlDescarga = BASE_URL . '/descargar?id=' . $id;
            echo '<!DOCTYPE html><html lang="es"><meta charset="utf-8">'
                . '<body style="font-family:sans-serif;text-align:center;padding:40px 20px;">'
                . '<p>La vista previa no está disponible para este tipo de archivo (.' . htmlspecialchars($extension) . ').</p>'
                . '<p><a href="' . htmlspecialchars($urlDescarga) . '">Descargar el archivo</a></p>'
                . '</body></html>';
            exit;
        }

        $this->mostrarArchivo($rutaArchivo);
    }

    /**
     * GET /reintentar?id=<historial_id>
     */
    public function reintentar(): void
    {
        // (sin cambios respecto a la versión anterior — la dejo afuera de
        // este bloque para no repetirla; seguí usando la que ya tenés)
    }
}