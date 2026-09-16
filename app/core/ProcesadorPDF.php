<?php
// ============================================================
// PDFlex - Procesador de archivos PDF
//
// Encapsula las llamadas a herramientas externas (LibreOffice,
// Ghostscript) para conversión y compresión. El controlador
// solo debe llamar a estos métodos, nunca ejecutar comandos
// de consola directamente.
// ============================================================

require_once __DIR__ . '/../../config/herramientas.php';

class ProcesadorPDF
{
    /**
     * Convierte un PDF a Word (.docx) usando LibreOffice.
     *
     * Nota: requiere --infilter="writer_pdf_import" para que LibreOffice
     * abra el PDF como documento de Writer (editable) en vez de Draw
     * (gráficos); sin eso falla con "no export filter for .docx found".
     */
    public static function pdfAWord(string $rutaEntrada, string $carpetaSalida): string
    {
        self::validarArchivo($rutaEntrada);

        $comando = sprintf(
            '"%s" --headless --infilter="writer_pdf_import" --convert-to docx --outdir "%s" "%s"',
            RUTA_LIBREOFFICE,
            $carpetaSalida,
            $rutaEntrada
        );

        self::ejecutar($comando);

        $nombreBase = pathinfo($rutaEntrada, PATHINFO_FILENAME);
        $rutaSalida = $carpetaSalida . DIRECTORY_SEPARATOR . $nombreBase . '.docx';

        if (!file_exists($rutaSalida)) {
            throw new Exception('LibreOffice no generó el archivo esperado: ' . $rutaSalida);
        }

        return $rutaSalida;
    }

    /**
     * Convierte un PDF a imagen (PNG) usando LibreOffice.
     *
     * A diferencia de pdfAWord(), acá NO se fuerza el infiltro de Writer:
     * el PDF se abre como documento de Draw (su comportamiento por
     * defecto) y desde ahí sí existe filtro de exportación a PNG.
     *
     * Limitación conocida: con PDFs de varias páginas, LibreOffice solo
     * genera la imagen de la primera página en esta conversión directa.
     * Si más adelante se necesita una imagen por página, hay que resolver
     * eso aparte (por ejemplo con Ghostscript, que sí soporta multi-página).
     */
    public static function pdfAImagen(string $rutaEntrada, string $carpetaSalida): string
    {
        self::validarArchivo($rutaEntrada);

        $comando = sprintf(
            '"%s" --headless --convert-to png --outdir "%s" "%s"',
            RUTA_LIBREOFFICE,
            $carpetaSalida,
            $rutaEntrada
        );

        self::ejecutar($comando);

        $nombreBase = pathinfo($rutaEntrada, PATHINFO_FILENAME);
        $rutaSalida = $carpetaSalida . DIRECTORY_SEPARATOR . $nombreBase . '.png';

        if (!file_exists($rutaSalida)) {
            throw new Exception('LibreOffice no generó la imagen esperada: ' . $rutaSalida);
        }

        return $rutaSalida;
    }

    /**
     * Comprime un PDF usando Ghostscript.
     *
     * @param string $calidad Nivel de compresión: screen | ebook | printer | prepress
     */
    public static function comprimir(string $rutaEntrada, string $rutaSalida, string $calidad = 'ebook'): string
    {
        self::validarArchivo($rutaEntrada);

        $nivelesValidos = ['screen', 'ebook', 'printer', 'prepress'];
        if (!in_array($calidad, $nivelesValidos, true)) {
            $calidad = 'ebook';
        }

        $comando = sprintf(
            '"%s" -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/%s ' .
            '-dNOPAUSE -dQUIET -dBATCH -sOutputFile="%s" "%s"',
            RUTA_GHOSTSCRIPT,
            $calidad,
            $rutaSalida,
            $rutaEntrada
        );

        self::ejecutar($comando);

        if (!file_exists($rutaSalida)) {
            throw new Exception('Ghostscript no generó el archivo comprimido: ' . $rutaSalida);
        }

        return $rutaSalida;
    }

    private static function ejecutar(string $comando): void
    {
        exec($comando . ' 2>&1', $salida, $codigoSalida);

        if ($codigoSalida !== 0) {
            throw new Exception('Error ejecutando comando externo: ' . implode("\n", $salida));
        }
    }

    private static function validarArchivo(string $ruta): void
    {
        if (!file_exists($ruta)) {
            throw new Exception('El archivo de entrada no existe: ' . $ruta);
        }
    }
}