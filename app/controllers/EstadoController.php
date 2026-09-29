<?php
/**
 * Panel de estado de procesos en curso (en curso / completado / error).
 * Vista ya diseñada en el wireframe "Estado" (Estado.dc.html).
 *
 * Semana 5-6 (s5-f2 progreso de OCR, s6-f2 panel de estado, s6-b2 lógica real) — completo.
 *
 * Nota importante: UploadController::procesar() es síncrono (exec() espera
 * a que LibreOffice/Ghostscript/Tesseract terminen dentro de la misma
 * petición), así que no existe un porcentaje de avance real ni un tiempo
 * restante medible — solo los estados discretos de `historial`
 * (pendiente/procesando/completado/error). "en_curso" en pantalla cubre
 * tanto "pendiente" como "procesando".
 *
 * "Editar texto" (30 sep, Marvin): para un OCR ya completado, editarOcr()/
 * guardarOcr() dejan corregir a mano el texto que reconoció Tesseract y
 * regenerar el mismo PDF de resultado — sin volver a correr Ghostscript/
 * Tesseract, reutilizando ProcesadorPDF::generarPdfDesdeTexto() sobre el
 * .txt "hermano" que ProcesadorPDF::ocr() deja guardado junto al PDF.
 */
require_once __DIR__ . '/../core/ProcesadorPDF.php';

class EstadoController extends Controller
{
    private const ETIQUETAS_OPERACION = [
        'conversion_pdf_word'   => 'Convertir a Word',
        'conversion_pdf_imagen' => 'Convertir a imagen',
        'compresion'            => 'Comprimir',
        'union'                 => 'Unir / Dividir',
        'division'               => 'Unir / Dividir',
        'ocr'                    => 'OCR',
    ];

    public function index(): void
    {
        $this->requiereSesion();

        $registros = Historial::recientes($_SESSION['usuario_id'], 10);
        $procesos = array_map([$this, 'mapearProceso'], $registros);

        $this->vista('estado/estado', ['activo' => 'estado', 'procesos' => $procesos]);
    }

    public function consultar(): void
    {
        $this->requiereSesion();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['error' => 'Falta el id del proceso a consultar.']);
            return;
        }

        $registro = Historial::porId($id, $_SESSION['usuario_id']);

        if ($registro === null) {
            $this->json(['error' => 'Proceso no encontrado.']);
            return;
        }

        $this->json($this->mapearProceso($registro));
    }

    /**
     * Convierte una fila de `historial` al formato que espera la vista
     * estado/estado.php.
     */
    private function mapearProceso(array $registro): array
    {
        $estadoDb = $registro['estado'];
        $estadoVista = in_array($estadoDb, ['pendiente', 'procesando'], true) ? 'en_curso' : $estadoDb;

        $proceso = [
            'id'        => (int) $registro['id'],
            'archivo'   => $registro['nombre_archivo_original'],
            'operacion' => self::ETIQUETAS_OPERACION[$registro['tipo_operacion']] ?? $registro['tipo_operacion'],
            'tipo'      => $registro['tipo_operacion'], // sin traducir — para el botón "Editar texto" (solo OCR)
            'estado'    => $estadoVista,
        ];

        if ($estadoVista === 'completado') {
            $proceso['terminado_hace'] = self::tiempoRelativo($registro['fecha_fin'] ?? null);
        } elseif ($estadoVista === 'error') {
            $proceso['mensaje'] = $registro['mensaje_error'] ?? 'Ocurrió un error al procesar el archivo.';
        }
        // 'en_curso': sin 'progreso'/'restante' — ver nota arriba, no hay
        // forma honesta de calcularlos con las herramientas actuales.

        return $proceso;
    }

    private static function tiempoRelativo(?string $fecha): string
    {
        if ($fecha === null) {
            return '';
        }

        $segundos = time() - strtotime($fecha);

        if ($segundos < 60) {
            return 'hace un momento';
        }
        if ($segundos < 3600) {
            $minutos = (int) floor($segundos / 60);
            return 'hace ' . $minutos . ' minuto' . ($minutos === 1 ? '' : 's');
        }
        if ($segundos < 86400) {
            $horas = (int) floor($segundos / 3600);
            return 'hace ' . $horas . ' hora' . ($horas === 1 ? '' : 's');
        }
        $dias = (int) floor($segundos / 86400);
        return 'hace ' . $dias . ' día' . ($dias === 1 ? '' : 's');
    }

    /**
     * GET /estado/editar-ocr?id=<historial_id> — muestra el texto que
     * reconoció Tesseract (guardado en el .txt "hermano" del PDF, ver
     * ProcesadorPDF::rutaSidecarTexto()) en un formulario editable.
     */
    public function editarOcr(): void
    {
        $this->requiereSesion();

        $id = (int) ($_GET['id'] ?? 0);
        $registro = Historial::porId($id, $_SESSION['usuario_id']);

        if ($registro === null || $registro['tipo_operacion'] !== 'ocr' || $registro['estado'] !== 'completado'
            || empty($registro['nombre_archivo_resultado'])) {
            $this->redirigir('/estado');
            return;
        }

        $rutaPdf = __DIR__ . '/../../storage/processed/' . basename($registro['nombre_archivo_resultado']);
        $rutaTxt = ProcesadorPDF::rutaSidecarTexto($rutaPdf);

        if (!file_exists($rutaTxt)) {
            http_response_code(404);
            echo 'El texto de este OCR ya no está disponible (puede haber expirado). '
                . 'Volvé a subir el archivo desde "Subir archivo" para procesarlo de nuevo.';
            exit;
        }

        $this->vista('estado/editar_ocr', [
            'activo'  => 'estado',
            'id'      => $id,
            'archivo' => $registro['nombre_archivo_original'],
            'texto'   => file_get_contents($rutaTxt),
        ]);
    }

    /**
     * POST /estado/editar-ocr — guarda el texto corregido: regenera el
     * MISMO archivo PDF (mismo nombre físico, así que `historial` no
     * necesita tocarse) con ProcesadorPDF::generarPdfDesdeTexto(), y
     * actualiza el .txt "hermano" para que una próxima edición parta del
     * texto ya corregido.
     */
    public function guardarOcr(): void
    {
        $this->requiereSesion();

        $id = (int) ($_POST['id'] ?? 0);
        $registro = Historial::porId($id, $_SESSION['usuario_id']);

        if ($registro === null || $registro['tipo_operacion'] !== 'ocr' || $registro['estado'] !== 'completado'
            || empty($registro['nombre_archivo_resultado'])) {
            $this->redirigir('/estado');
            return;
        }

        $textoEditado = (string) ($_POST['texto'] ?? '');
        $rutaPdf = __DIR__ . '/../../storage/processed/' . basename($registro['nombre_archivo_resultado']);
        $carpetaTemp = __DIR__ . '/../../storage/temp/';

        try {
            ProcesadorPDF::generarPdfDesdeTexto($textoEditado, $carpetaTemp, $rutaPdf);
            file_put_contents(ProcesadorPDF::rutaSidecarTexto($rutaPdf), $textoEditado);

            $tamanoResultadoKb = (int) round(filesize($rutaPdf) / 1024);
            Historial::guardarResultado(
                $id,
                basename($rutaPdf),
                (int) ($registro['tamano_original_kb'] ?? 0),
                $tamanoResultadoKb
            );
            // Refresca fecha_fin a ahora, para que "Terminó hace..." refleje la edición.
            Historial::actualizarEstado($id, 'completado');
        } catch (Exception $e) {
            http_response_code(500);
            echo 'No se pudo guardar el cambio: ' . htmlspecialchars($e->getMessage());
            exit;
        }

        $this->redirigir('/estado');
    }
}