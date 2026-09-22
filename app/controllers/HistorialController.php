<?php
/**
 * Historial de conversiones del usuario (tabla filtrable).
 * Vista ya diseñada en el wireframe "Historial" (Historial.dc.html).
 *
 * Semana 6 (s6-f1 / s6-b1) — completo. Descarga real agregada después
 * (sin semana asignada en el cronograma original).
 */
class HistorialController extends Controller
{
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

    /**
     * GET /descargar?id=<historial_id> — usado tanto desde el Historial
     * como desde el panel de Estado (los dos enlazan al mismo id de
     * historial). Solo sirve el archivo si el registro es del usuario
     * logueado y quedó completado.
     */
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

        $nombreFisico = basename($registro['nombre_archivo_resultado']); // nunca subcarpetas
        $rutaArchivo = __DIR__ . '/../../storage/processed/' . $nombreFisico;

        if (!file_exists($rutaArchivo)) {
            http_response_code(404);
            echo 'El archivo ya no está disponible (puede haber expirado).';
            exit;
        }

        // Nombre "lindo" para el navegador: el nombre original que subió el
        // usuario, con la extensión real del archivo generado (pueden ser
        // distintas, p. ej. subió tarea.pdf y el resultado es tarea.docx).
        $extension = pathinfo($rutaArchivo, PATHINFO_EXTENSION);
        $nombreBase = pathinfo($registro['nombre_archivo_original'] ?? $nombreFisico, PATHINFO_FILENAME);
        $nombreDescarga = $nombreBase . '.' . $extension;

        $this->descargarArchivo($rutaArchivo, $nombreDescarga);
    }
}