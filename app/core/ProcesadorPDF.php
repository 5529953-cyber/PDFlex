<?php
// ============================================================
// PDFlex - Procesador de archivos PDF
//
// Encapsula las llamadas a herramientas externas (LibreOffice,
// Ghostscript, Tesseract) para conversión, compresión, unión,
// división y OCR. El controlador solo debe llamar a estos
// métodos, nunca ejecutar comandos de consola directamente.
// ============================================================

require_once __DIR__ . '/../../config/herramientas.php';

class ProcesadorPDF
{
    /**
     * Convierte un PDF a Word (.docx) usando LibreOffice.
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

    /**
     * Une varios PDFs en uno solo, en el orden en que se pasan, usando Ghostscript.
     *
     * @param string[] $rutasEntrada Rutas absolutas de los PDFs a unir, en orden
     */
    public static function unir(array $rutasEntrada, string $rutaSalida): string
    {
        if (count($rutasEntrada) < 2) {
            throw new Exception('Se necesitan al menos 2 archivos para unir.');
        }

        foreach ($rutasEntrada as $ruta) {
            self::validarArchivo($ruta);
        }

        $entradas = implode(' ', array_map(
            fn($ruta) => '"' . $ruta . '"',
            $rutasEntrada
        ));

        $comando = sprintf(
            '"%s" -sDEVICE=pdfwrite -dNOPAUSE -dQUIET -dBATCH -sOutputFile="%s" %s',
            RUTA_GHOSTSCRIPT,
            $rutaSalida,
            $entradas
        );

        self::ejecutar($comando);

        if (!file_exists($rutaSalida)) {
            throw new Exception('Ghostscript no generó el archivo unido: ' . $rutaSalida);
        }

        return $rutaSalida;
    }

    /**
     * Extrae páginas específicas de un PDF y las guarda como un nuevo PDF,
     * usando Ghostscript.
     *
     * @param string $listaPaginas Números de página separados por coma, ej. "1,3,4"
     */
    public static function dividir(string $rutaEntrada, string $listaPaginas, string $rutaSalida): string
    {
        self::validarArchivo($rutaEntrada);

        if ($listaPaginas === '' || !preg_match('/^\d+(,\d+)*$/', $listaPaginas)) {
            throw new Exception('Selección de páginas inválida.');
        }

        $comando = sprintf(
            '"%s" -sDEVICE=pdfwrite -dNOPAUSE -dQUIET -dBATCH -sPageList="%s" -sOutputFile="%s" "%s"',
            RUTA_GHOSTSCRIPT,
            $listaPaginas,
            $rutaSalida,
            $rutaEntrada
        );

        self::ejecutar($comando);

        if (!file_exists($rutaSalida)) {
            throw new Exception('Ghostscript no generó el archivo dividido: ' . $rutaSalida);
        }

        return $rutaSalida;
    }

    // Umbral de confianza de Tesseract (0-100) para aceptar una palabra
    // reconocida. Las palabras de 1-2 letras usan un umbral más exigente
    // porque un logo o ícono en la imagen casi siempre se "lee" como una
    // letra suelta con confianza baja-media (ver extraerTextoConfiable()).
    private const OCR_CONFIANZA_MINIMA = 60;
    private const OCR_CONFIANZA_MINIMA_PALABRA_CORTA = 85;

    /**
     * Aplica OCR a un PDF o a una imagen suelta: extrae el TEXTO real (no
     * solo lo vuelve "seleccionable" sobre la imagen) y lo transcribe a un
     * PDF nuevo, de texto plano — combinando Ghostscript + Tesseract +
     * LibreOffice:
     *
     *   1. Si la entrada es un PDF, Ghostscript rasteriza cada página a una
     *      imagen PNG. Si la entrada YA es una imagen (JPG/PNG — 29 sep,
     *      Marvin: para que el usuario pueda subir la foto directamente sin
     *      tener que meterla antes en un PDF), se usa tal cual y este paso
     *      se salta.
     *   2. Tesseract reconoce el texto de cada imagen (modo "txt": texto
     *      plano, no el modo "pdf" que solo pega la imagen original con una
     *      capa de texto invisible encima — eso no es lo que se pidió; acá
     *      el resultado debe ser el texto de verdad, transcrito).
     *   3. Todo el texto (una página tras otra, separadas con salto de
     *      página) se junta en un único .txt y se convierte a PDF con
     *      LibreOffice — ese PDF final tiene el texto como texto real,
     *      no la foto/escaneo original de fondo.
     *
     * @param string $carpetaTemp Carpeta donde crear una subcarpeta de
     *                            trabajo temporal (imágenes y texto
     *                            intermedios); se borra sola al terminar.
     * @param string $idioma      Código de idioma de Tesseract (ej. "spa", "eng", "spa+eng")
     */
    public static function ocr(string $rutaEntrada, string $carpetaTemp, string $rutaSalida, string $idioma = 'spa'): string
    {
        self::validarArchivo($rutaEntrada);

        $idiomasValidos = ['spa', 'eng', 'spa+eng'];
        if (!in_array($idioma, $idiomasValidos, true)) {
            $idioma = 'spa';
        }

        $carpetaTrabajo = rtrim($carpetaTemp, '/\\') . DIRECTORY_SEPARATOR . uniqid('ocr_', true);

        if (!mkdir($carpetaTrabajo, 0777, true) && !is_dir($carpetaTrabajo)) {
            throw new Exception('No se pudo crear la carpeta temporal para el OCR.');
        }

        try {
            $extensionEntrada = strtolower(pathinfo($rutaEntrada, PATHINFO_EXTENSION));
            $esImagen = in_array($extensionEntrada, ['jpg', 'jpeg', 'png'], true);

            if ($esImagen) {
                // La entrada ya es una imagen: Tesseract la lee directo, sin
                // pasar por Ghostscript (eso solo rasteriza PDFs).
                $imagenes = [$rutaEntrada];
            } else {
                // 1. Rasterizar cada página del PDF a PNG (300 dpi: buen
                // equilibrio entre calidad de reconocimiento y tiempo/peso)
                $patronImagenes = $carpetaTrabajo . DIRECTORY_SEPARATOR . 'pagina_%03d.png';

                $comandoGs = sprintf(
                    '"%s" -sDEVICE=png16m -r300 -dNOPAUSE -dQUIET -dBATCH -o "%s" "%s"',
                    RUTA_GHOSTSCRIPT,
                    $patronImagenes,
                    $rutaEntrada
                );

                self::ejecutar($comandoGs);

                $imagenes = glob($carpetaTrabajo . DIRECTORY_SEPARATOR . 'pagina_*.png');
                sort($imagenes);

                if (empty($imagenes)) {
                    throw new Exception('Ghostscript no generó imágenes para el OCR.');
                }
            }

            // 2. Tesseract sobre cada imagen, en modo "tsv": igual que "txt"
            // pero con la confianza (0-100) de cada palabra reconocida. Con
            // fotos reales (un cartel, un logo, un ícono junto al texto),
            // Tesseract intenta "leer" esos gráficos como si fueran letras
            // sueltas — casi siempre con confianza muy baja. Filtrando esas
            // palabras de baja confianza (extraerTextoConfiable) se limpia
            // ese ruido de la transcripción final.
            $textoPorPagina = [];

            foreach ($imagenes as $imagen) {
                $nombreBase = pathinfo($imagen, PATHINFO_FILENAME);
                $salidaBase = $carpetaTrabajo . DIRECTORY_SEPARATOR . $nombreBase;

                $comandoTesseract = sprintf(
                    '"%s" "%s" "%s" -l %s tsv',
                    RUTA_TESSERACT,
                    $imagen,
                    $salidaBase,
                    $idioma
                );

                self::ejecutar($comandoTesseract);

                $rutaTsvPagina = $salidaBase . '.tsv';

                if (!file_exists($rutaTsvPagina)) {
                    throw new Exception('Tesseract no generó el texto esperado para: ' . $imagen);
                }

                $textoPorPagina[] = self::extraerTextoConfiable($rutaTsvPagina);
            }

            // 3. Todas las páginas en un solo .txt (separadas con salto de
            // página, \x0C — LibreOffice lo respeta como salto de página al
            // convertir), y ese .txt se convierte a PDF con LibreOffice.
            $rutaTxtFinal = $carpetaTrabajo . DIRECTORY_SEPARATOR . 'texto_extraido.txt';
            file_put_contents($rutaTxtFinal, implode("\n\n\x0C\n\n", $textoPorPagina));

            $comandoLibreOffice = sprintf(
                '"%s" --headless --infilter="Text (encoded):UTF8" --convert-to pdf --outdir "%s" "%s"',
                RUTA_LIBREOFFICE,
                $carpetaTrabajo,
                $rutaTxtFinal
            );

            self::ejecutar($comandoLibreOffice);

            $rutaPdfGenerado = $carpetaTrabajo . DIRECTORY_SEPARATOR . 'texto_extraido.pdf';

            if (!file_exists($rutaPdfGenerado)) {
                throw new Exception('LibreOffice no generó el PDF de texto esperado.');
            }

            if (!copy($rutaPdfGenerado, $rutaSalida)) {
                throw new Exception('No se pudo generar el archivo final del OCR.');
            }

            if (!file_exists($rutaSalida)) {
                throw new Exception('El OCR no generó el archivo esperado: ' . $rutaSalida);
            }

            return $rutaSalida;
        } finally {
            // Limpieza de imágenes y PDFs intermedios, pase lo que pase.
            self::limpiarCarpeta($carpetaTrabajo);
        }
    }

    /**
     * Reconstruye el texto de una página a partir del .tsv de Tesseract
     * (una fila por palabra reconocida, con su nivel de confianza 0-100),
     * descartando las palabras de baja confianza — típicamente restos de
     * un logo, ícono o mancha en la imagen que Tesseract intentó "leer"
     * como si fueran letras — y respetando los saltos de línea originales
     * (agrupando por bloque/párrafo/línea, columnas 3, 4 y 5 del .tsv).
     */
    private static function extraerTextoConfiable(string $rutaTsv): string
    {
        $filas = file($rutaTsv, FILE_IGNORE_NEW_LINES);

        if ($filas === false || count($filas) < 2) {
            return '';
        }

        array_shift($filas); // encabezado (level, page_num, block_num, ...)

        $claveLineaActual = null;
        $palabrasLinea = [];
        $lineasTexto = [];

        foreach ($filas as $fila) {
            $columnas = explode("\t", $fila);

            // nivel 5 = palabra (1=página, 2=bloque, 3=párrafo, 4=línea)
            if (count($columnas) < 12 || $columnas[0] !== '5') {
                continue;
            }

            $claveLinea = $columnas[2] . '.' . $columnas[3] . '.' . $columnas[4]; // bloque.párrafo.línea
            $confianza = (float) $columnas[10];
            $texto = trim($columnas[11]);

            if ($texto === '') {
                continue;
            }

            if ($claveLinea !== $claveLineaActual) {
                if (!empty($palabrasLinea)) {
                    $lineasTexto[] = implode(' ', $palabrasLinea);
                }
                $palabrasLinea = [];
                $claveLineaActual = $claveLinea;
            }

            $umbral = mb_strlen($texto) <= 2
                ? self::OCR_CONFIANZA_MINIMA_PALABRA_CORTA
                : self::OCR_CONFIANZA_MINIMA;

            if ($confianza >= $umbral) {
                $palabrasLinea[] = $texto;
            }
        }

        if (!empty($palabrasLinea)) {
            $lineasTexto[] = implode(' ', $palabrasLinea);
        }

        return implode("\n", array_filter($lineasTexto, fn($linea) => $linea !== ''));
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

    private static function limpiarCarpeta(string $carpeta): void
    {
        if (!is_dir($carpeta)) {
            return;
        }

        foreach (glob($carpeta . DIRECTORY_SEPARATOR . '*') as $archivo) {
            @unlink($archivo);
        }

        @rmdir($carpeta);
    }
}