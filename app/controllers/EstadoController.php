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
 */
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
}