<?php
/**
 * Pantalla de subida de archivos y elección de operación
 * (convertir, comprimir, unir/dividir, OCR).
 */
class UploadController extends Controller
{
    private const TIPO_MIME_PERMITIDO = 'application/pdf';
    private const EXTENSION_PERMITIDA = 'pdf';
    private const TAMANO_MAXIMO_BYTES = 20 * 1024 * 1024; // 20 MB (requisito no funcional del anteproyecto)

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

        // 1. ¿Llegó el archivo sin errores de subida?
        if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $this->vista('upload/subida', ['error' => 'No se pudo subir el archivo. Intentá de nuevo.']);
            return;
        }

        $archivo = $_FILES['archivo'];

        // 2. Validar tamaño (hasta 20 MB)
        if ($archivo['size'] > self::TAMANO_MAXIMO_BYTES) {
            $this->vista('upload/subida', ['error' => 'El archivo supera el límite de 20 MB.']);
            return;
        }

        // 3. Validar tipo real del archivo (no solo la extensión del nombre)
        $tipoMime = mime_content_type($archivo['tmp_name']);
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if ($tipoMime !== self::TIPO_MIME_PERMITIDO || $extension !== self::EXTENSION_PERMITIDA) {
            $this->vista('upload/subida', ['error' => 'Solo se permiten archivos PDF.']);
            return;
        }

        // 4. Validar la operación elegida
        $tipoOperacion = $_POST['operacion'] ?? '';

        if (!in_array($tipoOperacion, self::OPERACIONES_VALIDAS, true)) {
            $this->vista('upload/subida', ['error' => 'Elegí una operación válida.']);
            return;
        }

        // Nota: "union" hoy solo se registra con el único archivo que este
        // formulario permite subir. Cuando en Semana 5 se resuelva la subida
        // de múltiples archivos para "Unir", este mismo flujo se extiende.

        // 5. Guardar el archivo con nombre único (para no pisar archivos de otros usuarios)
        $nombreFisico = uniqid('pdf_', true) . '.' . $extension;
        $rutaDestino = __DIR__ . '/../../storage/uploads/' . $nombreFisico;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            $this->vista('upload/subida', ['error' => 'Error interno al guardar el archivo.']);
            return;
        }

        // 6. Registrar la operación en el historial (tabla `historial`, estado "pendiente")
        $historialId = Historial::registrar($_SESSION['usuario_id'], $tipoOperacion, $archivo['name']);

        // 7. Rastrear el archivo físico para el borrado automático (expira en 24h)
        $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));
        ArchivoTemporal::registrar($historialId, $rutaDestino, $fechaExpiracion);

        // TODO (Backend, Semana 4/5): disparar el módulo real según $tipoOperacion
        // (conversión, compresión, unión/división, OCR) y actualizar el estado
        // con Historial::actualizarEstado() cuando termine.

        $this->redirigir('/estado');
    }
}