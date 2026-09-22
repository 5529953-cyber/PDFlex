<?php
/**
 * Pantalla de subida de archivos y elección de operación
 * (convertir, comprimir, unir/dividir, OCR).
 */
require_once __DIR__ . '/../core/ProcesadorPDF.php';

class UploadController extends Controller
{
    private const TIPO_MIME_PERMITIDO = 'application/pdf';
    private const EXTENSION_PERMITIDA = 'pdf';
    private const TAMANO_MAXIMO_BYTES = 20 * 1024 * 1024; // 20 MB por archivo

    private const OPERACIONES_VALIDAS = [
        'conversion_pdf_word',
        'conversion_pdf_imagen',
        'compresion',
        'union',
        'division',
        'ocr',
    ];

    public function index(): void
    {
        $this->requiereSesion();
        $this->vista('upload/subida', ['activo' => 'subir']);
    }

    public function procesar(): void
    {
        $this->requiereSesion();

        // 1. Validar la operación elegida primero: define qué campo de
        // $_FILES leer (Marvin, s5-f1: "archivos" para union, "archivo"
        // para el resto, ver comentario en app/views/upload/subida.php).
        $tipoOperacion = $_POST['operacion'] ?? '';

        if (!in_array($tipoOperacion, self::OPERACIONES_VALIDAS, true)) {
            $this->vista('upload/subida', ['error' => 'Elegí una operación válida.']);
            return;
        }

        $campoArchivo = $tipoOperacion === 'union' ? 'archivos' : 'archivo';
        $archivos = $this->normalizarArchivos($_FILES[$campoArchivo] ?? null);

        if (empty($archivos)) {
            $this->vista('upload/subida', ['error' => 'No se pudo subir el archivo. Intentá de nuevo.']);
            return;
        }

        // 2. Reglas de cantidad según la operación
        if ($tipoOperacion === 'union') {
            if (count($archivos) < 2) {
                $this->vista('upload/subida', ['error' => 'Para unir necesitás subir al menos 2 archivos.']);
                return;
            }
        } elseif (count($archivos) > 1) {
            $this->vista('upload/subida', ['error' => 'Esta operación admite un solo archivo.']);
            return;
        }

        // 3. Validar cada archivo individualmente
        foreach ($archivos as $archivo) {
            if ($archivo['error'] !== UPLOAD_ERR_OK) {
                $this->vista('upload/subida', ['error' => 'No se pudo subir el archivo. Intentá de nuevo.']);
                return;
            }

            if ($archivo['size'] > self::TAMANO_MAXIMO_BYTES) {
                $this->vista('upload/subida', ['error' => 'Cada archivo debe pesar hasta 20 MB.']);
                return;
            }

            $tipoMime = mime_content_type($archivo['tmp_name']);
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

            if ($tipoMime !== self::TIPO_MIME_PERMITIDO || $extension !== self::EXTENSION_PERMITIDA) {
                $this->vista('upload/subida', ['error' => 'Solo se permiten archivos PDF.']);
                return;
            }
        }

        // 4. Para "división", validar que llegaron páginas seleccionadas
        // ANTES de guardar nada (evita mover archivos si esto va a fallar).
        $paginasSeleccionadas = trim($_POST['paginas_seleccionadas'] ?? '');
        if ($tipoOperacion === 'division') {
            if ($paginasSeleccionadas === '' || !preg_match('/^\d+(,\d+)*$/', $paginasSeleccionadas)) {
                $this->vista('upload/subida', ['error' => 'Elegí al menos una página para dividir.']);
                return;
            }
        }

        // 5. Guardar todos los archivos con nombre único
        $rutasGuardadas = [];
        $nombresOriginales = [];

        foreach ($archivos as $archivo) {
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            $nombreFisico = uniqid('pdf_', true) . '.' . $extension;
            $rutaDestino = __DIR__ . '/../../storage/uploads/' . $nombreFisico;

            if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                $this->vista('upload/subida', ['error' => 'Error interno al guardar el archivo.']);
                return;
            }

            $rutasGuardadas[] = $rutaDestino;
            $nombresOriginales[] = $archivo['name'];
        }

        // 6. Registrar la operación en el historial (tabla `historial`, estado "pendiente")
        $nombreParaHistorial = count($nombresOriginales) > 1
            ? $nombresOriginales[0] . ' (+' . (count($nombresOriginales) - 1) . ' más)'
            : $nombresOriginales[0];

        $historialId = Historial::registrar($_SESSION['usuario_id'], $tipoOperacion, $nombreParaHistorial);

        // 7. Rastrear los archivos físicos originales para el borrado automático (expira en 24h)
        $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));
        foreach ($rutasGuardadas as $ruta) {
            ArchivoTemporal::registrar($historialId, $ruta, $fechaExpiracion);
        }

        // 8. Disparar el procesamiento real según la operación elegida.
        $carpetaProcessed = __DIR__ . '/../../storage/processed/';
        $tamanoOriginalKb = (int) round(array_sum(array_map('filesize', $rutasGuardadas)) / 1024);

        try {
            Historial::actualizarEstado($historialId, 'procesando');

            $rutaResultado = null;

            switch ($tipoOperacion) {
                case 'conversion_pdf_word':
                    $rutaResultado = ProcesadorPDF::pdfAWord($rutasGuardadas[0], $carpetaProcessed);
                    break;

                case 'conversion_pdf_imagen':
                    $rutaResultado = ProcesadorPDF::pdfAImagen($rutasGuardadas[0], $carpetaProcessed);
                    break;

                case 'compresion':
                    $nombreComprimido = uniqid('comprimido_', true) . '.pdf';
                    $rutaResultado = ProcesadorPDF::comprimir($rutasGuardadas[0], $carpetaProcessed . $nombreComprimido);
                    break;

                case 'union':
                    $nombreUnido = uniqid('unido_', true) . '.pdf';
                    $rutaResultado = ProcesadorPDF::unir($rutasGuardadas, $carpetaProcessed . $nombreUnido);
                    break;

                                case 'division':
                    $nombreDividido = uniqid('dividido_', true) . '.pdf';
                    $rutaResultado = ProcesadorPDF::dividir($rutasGuardadas[0], $paginasSeleccionadas, $carpetaProcessed . $nombreDividido);
                    break;

                case 'ocr':
                    $nombreOcr = uniqid('ocr_', true) . '.pdf';
                    $carpetaTemp = __DIR__ . '/../../storage/temp/';
                    $rutaResultado = ProcesadorPDF::ocr($rutasGuardadas[0], $carpetaTemp, $carpetaProcessed . $nombreOcr);
                    break;

                default:
                    // ocr: todavía no implementado.
                    $this->redirigir('/estado');
                    return;
            }

            // 9. Guardar el resultado y marcar como completado
            $tamanoResultadoKb = (int) round(filesize($rutaResultado) / 1024);

            Historial::guardarResultado($historialId, basename($rutaResultado), $tamanoOriginalKb, $tamanoResultadoKb);
            Historial::actualizarEstado($historialId, 'completado');

            // 10. Rastrear también el archivo generado
            ArchivoTemporal::registrar($historialId, $rutaResultado, $fechaExpiracion);
        } catch (Exception $e) {
            Historial::actualizarEstado($historialId, 'error', $e->getMessage());
        }

        $this->redirigir('/estado');
    }

    /**
     * Normaliza un campo de $_FILES a una lista de archivos individuales,
     * sin importar si vino como archivo único ("archivo") o como arreglo
     * ("archivos[]", usado para Unir).
     */
    private function normalizarArchivos(?array $campoArchivo): array
    {
        if ($campoArchivo === null || empty($campoArchivo['name'])) {
            return [];
        }

        if (is_array($campoArchivo['name'])) {
            $archivos = [];
            $cantidad = count($campoArchivo['name']);

            for ($i = 0; $i < $cantidad; $i++) {
                if ($campoArchivo['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $archivos[] = [
                    'name'     => $campoArchivo['name'][$i],
                    'type'     => $campoArchivo['type'][$i],
                    'tmp_name' => $campoArchivo['tmp_name'][$i],
                    'error'    => $campoArchivo['error'][$i],
                    'size'     => $campoArchivo['size'][$i],
                ];
            }

            return $archivos;
        }

        if ($campoArchivo['error'] === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        return [$campoArchivo];
    }
}