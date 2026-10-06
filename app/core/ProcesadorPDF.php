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
     * Convierte las páginas elegidas de un PDF a imagen (PNG), usando
     * Ghostscript (-sDEVICE=png16m, el mismo que ya usa ocr() para
     * rasterizar — no se agrega ninguna herramienta nueva al proyecto).
     *
     * NUEVO (oct 2026, selector visual de páginas — mismo patrón que
     * dividir()): antes esto convertía el PDF entero con LibreOffice, pero
     * "--convert-to png" de LibreOffice solo genera UNA imagen (la primera
     * página) sin importar cuántas tenga el PDF — por eso ahora se pide la
     * selección de páginas, igual que en "Dividir". Si se elige una sola
     * página, el resultado es un .png directo; si se eligen varias, se
     * devuelve un .zip con una imagen por página (nombrada según el número
     * de página real, no el orden en que Ghostscript las generó).
     *
     * @param string $listaPaginas Números de página separados por coma, EN
     *                              ORDEN ASCENDENTE (ej. "1,3,4") — Ghostscript
     *                              exige ese orden o falla con "Bad PageList".
     *                              El selector visual (ver app.js,
     *                              paginasInput) ya los entrega ordenados.
     * @param string $carpetaTemp  Carpeta para la subcarpeta de trabajo
     *                              temporal (igual que ocr()); se borra
     *                              sola al terminar, incluso si algo falla.
     */
    public static function pdfAImagen(string $rutaEntrada, string $listaPaginas, string $carpetaTemp, string $carpetaSalida): string
    {
        self::validarArchivo($rutaEntrada);

        if ($listaPaginas === '' || !preg_match('/^\d+(,\d+)*$/', $listaPaginas)) {
            throw new Exception('Selección de páginas inválida.');
        }

        $numerosPagina = array_map('intval', explode(',', $listaPaginas));
        $carpetaTrabajo = rtrim($carpetaTemp, '/\\') . DIRECTORY_SEPARATOR . uniqid('imagen_', true);

        if (!mkdir($carpetaTrabajo, 0777, true) && !is_dir($carpetaTrabajo)) {
            throw new Exception('No se pudo crear la carpeta temporal para la conversión a imagen.');
        }

        try {
            $patronImagenes = $carpetaTrabajo . DIRECTORY_SEPARATOR . 'pagina_%03d.png';

            $comandoGs = sprintf(
                '"%s" -sDEVICE=png16m -r200 -dNOPAUSE -dQUIET -dBATCH -sPageList="%s" -o "%s" "%s"',
                RUTA_GHOSTSCRIPT,
                $listaPaginas,
                $patronImagenes,
                $rutaEntrada
            );

            self::ejecutar($comandoGs);

            $imagenesGeneradas = glob($carpetaTrabajo . DIRECTORY_SEPARATOR . 'pagina_*.png');
            sort($imagenesGeneradas);

            if (empty($imagenesGeneradas) || count($imagenesGeneradas) !== count($numerosPagina)) {
                throw new Exception('Ghostscript no generó las imágenes esperadas.');
            }

            $nombreBase = pathinfo($rutaEntrada, PATHINFO_FILENAME);

            // Una sola página elegida: se devuelve el .png directo, igual
            // que el comportamiento de siempre (sin zip de por medio).
            if (count($imagenesGeneradas) === 1) {
                $rutaSalida = $carpetaSalida . DIRECTORY_SEPARATOR . $nombreBase . '_pagina' . $numerosPagina[0] . '.png';

                if (!rename($imagenesGeneradas[0], $rutaSalida)) {
                    throw new Exception('No se pudo mover la imagen generada.');
                }

                return $rutaSalida;
            }

            // Varias páginas: se empaquetan en un .zip, una imagen por
            // página, nombradas con el número de página real (Ghostscript
            // las numera 001, 002... en orden de salida, no con el número
            // de página real, así que se renombran acá al agregarlas).
            $rutaZip = $carpetaSalida . DIRECTORY_SEPARATOR . $nombreBase . '_imagenes.zip';
            $zip = new ZipArchive();

            if ($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('No se pudo crear el archivo ZIP con las imágenes.');
            }

            foreach ($imagenesGeneradas as $indice => $rutaImagen) {
                $zip->addFile($rutaImagen, 'pagina_' . $numerosPagina[$indice] . '.png');
            }

            $zip->close();

            if (!file_exists($rutaZip)) {
                throw new Exception('No se generó el archivo ZIP esperado.');
            }

            return $rutaZip;
        } finally {
            self::limpiarCarpeta($carpetaTrabajo);
        }
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

            // 2. Tesseract sobre cada imagen — y, si ImageMagick está
            // disponible, TAMBIÉN sobre una versión preprocesada de esa
            // misma imagen (ver preprocesarImagenParaOcr más abajo) — y nos
            // quedamos con la que reconozca MÁS texto con confianza
            // (comparando la longitud del texto ya filtrado por
            // extraerTextoConfiable). Esto es así, y no "siempre usar la
            // preprocesada", porque el preprocesamiento (sobre todo el
            // umbral adaptativo que ayuda con fotos oscuras) puede meterle
            // ruido a una imagen que ya era nítida y empeorarla — probado
            // 2 oct, Marvin: un recorte de diario ya limpio se leía casi
            // perfecto sin preprocesar, y preprocesado salía hecho pedazos.
            // Comparando los dos resultados y quedándonos con el mejor, una
            // foto con mala luz se beneficia del preprocesamiento sin
            // arriesgar que un documento que ya andaba bien empeore. Si
            // RUTA_IMAGEMAGICK no está configurado en esta máquina (p. ej.
            // la de un compañero que todavía no lo instaló), simplemente no
            // se prueba la versión preprocesada y el OCR tarda la mitad.
            $textoPorPagina = [];

            foreach ($imagenes as $imagen) {
                $textoOriginal = self::reconocerTextoDeImagen($imagen, $carpetaTrabajo, $idioma);

                if (defined('RUTA_IMAGEMAGICK')) {
                    $imagenPreprocesada = self::preprocesarImagenParaOcr($imagen, $carpetaTrabajo);
                    $textoPreprocesado = self::reconocerTextoDeImagen($imagenPreprocesada, $carpetaTrabajo, $idioma);

                    $textoPorPagina[] = mb_strlen($textoPreprocesado) > mb_strlen($textoOriginal)
                        ? $textoPreprocesado
                        : $textoOriginal;
                } else {
                    $textoPorPagina[] = $textoOriginal;
                }
            }

            // 3. Todas las páginas en un solo texto (separadas con salto de
            // página, \x0C) y ese texto se convierte a PDF con LibreOffice
            // (generarPdfDesdeTexto(), reutilizado también al editar el
            // texto reconocido después — ver EstadoController::guardarOcr()).
            $textoCompleto = implode("\n\n\x0C\n\n", $textoPorPagina);

            self::generarPdfDesdeTexto($textoCompleto, $carpetaTemp, $rutaSalida);

            // 4. Guarda el texto reconocido en un .txt "hermano" del PDF (30
            // sep, Marvin: para poder reabrirlo y corregirlo después, desde
            // "Editar texto" en /estado, sin tener que volver a correr todo
            // el OCR sobre la imagen/PDF original).
            file_put_contents(self::rutaSidecarTexto($rutaSalida), $textoCompleto);

            return $rutaSalida;
        } finally {
            // Limpieza de imágenes y PDFs intermedios, pase lo que pase.
            self::limpiarCarpeta($carpetaTrabajo);
        }
    }

    /**
     * Corre Tesseract sobre una imagen (modo "tsv": texto + confianza 0-100
     * por palabra) y devuelve ya el texto filtrado por confianza
     * (extraerTextoConfiable) — reutilizado por ocr() tanto para la imagen
     * original como, si corresponde, para su versión preprocesada.
     */
    private static function reconocerTextoDeImagen(string $rutaImagen, string $carpetaTrabajo, string $idioma): string
    {
        $nombreBase = pathinfo($rutaImagen, PATHINFO_FILENAME);
        $salidaBase = $carpetaTrabajo . DIRECTORY_SEPARATOR . $nombreBase;

        $comandoTesseract = sprintf(
            '"%s" "%s" "%s" -l %s tsv',
            RUTA_TESSERACT,
            $rutaImagen,
            $salidaBase,
            $idioma
        );

        self::ejecutar($comandoTesseract);

        $rutaTsv = $salidaBase . '.tsv';

        if (!file_exists($rutaTsv)) {
            throw new Exception('Tesseract no generó el texto esperado para: ' . $rutaImagen);
        }

        return self::extraerTextoConfiable($rutaTsv);
    }

    /**
     * Preprocesa una imagen con ImageMagick antes de mandarla a Tesseract,
     * para mejorar el reconocimiento en fotos de mala calidad: endereza una
     * inclinación leve (-deskew), pasa a escala de grises y estira el
     * contraste al rango completo (-auto-level + -gamma, aclara sombras y
     * zonas con poca luz), separa el texto del fondo con un umbral
     * adaptativo LOCAL (-lat — a diferencia de un umbral fijo, funciona
     * aunque la iluminación no sea pareja en toda la foto) y agranda la
     * imagen al doble (-resize, ayuda con fotos de baja resolución).
     *
     * Probado empíricamente (1 oct, Marvin) contra una foto real: subió la
     * confianza de Tesseract en la palabra "WAITING?" de 54% a 96%, y el
     * resto de palabras reales por encima de 89% (antes algunas quedaban
     * por debajo del umbral de extraerTextoConfiable() y se perdían). En
     * una foto muy oscura/con ruido pasó de no reconocer NADA a transcribir
     * una parte del texto de forma confiable.
     *
     * OJO — esto puede EMPEORAR una imagen que ya era nítida (el umbral
     * adaptativo le mete ruido a un escaneo limpio; probado 2 oct, Marvin,
     * con un recorte de diario). Por eso ocr() nunca usa este resultado a
     * ciegas: siempre lo compara contra el de la imagen original y se queda
     * con el que reconozca más texto (ver reconocerTextoDeImagen() y el
     * comentario en ocr()).
     *
     * Tampoco "entrena" a Tesseract ni lo convierte en un lector de
     * letra cursiva o manuscrita unida: sigue siendo un motor pensado para
     * texto IMPRESO. Lo que mejora acá es la calidad de la foto (luz,
     * inclinación, resolución), no el tipo de letra — una foto de un
     * cuaderno escrito a mano con letra unida va a seguir sin transcribirse
     * de forma confiable, por más que se la preprocese.
     *
     * Si el comando de ImageMagick falla por cualquier motivo, se sigue con
     * la imagen ORIGINAL sin preprocesar en vez de romper todo el OCR.
     */
    private static function preprocesarImagenParaOcr(string $rutaImagenEntrada, string $carpetaTrabajo): string
    {
        $rutaImagenPreprocesada = $carpetaTrabajo . DIRECTORY_SEPARATOR
            . pathinfo($rutaImagenEntrada, PATHINFO_FILENAME) . '_preprocesada.png';

        $comandoImageMagick = sprintf(
            '"%s" "%s" -auto-orient -colorspace Gray -auto-level -gamma 2.2 -deskew 40%% -lat 25x25+5%% -resize 200%% "%s"',
            RUTA_IMAGEMAGICK,
            $rutaImagenEntrada,
            $rutaImagenPreprocesada
        );

        try {
            self::ejecutar($comandoImageMagick);
        } catch (Exception $e) {
            return $rutaImagenEntrada;
        }

        if (!file_exists($rutaImagenPreprocesada)) {
            return $rutaImagenEntrada;
        }

        return $rutaImagenPreprocesada;
    }

    /**
     * Convierte texto plano en un PDF nuevo, vía LibreOffice — el mismo
     * paso final que usa ocr(), reutilizado también cuando se edita a mano
     * el texto ya reconocido (EstadoController::guardarOcr()), sin tener
     * que volver a correr Ghostscript/Tesseract.
     */
    public static function generarPdfDesdeTexto(string $texto, string $carpetaTemp, string $rutaSalida): string
    {
        $carpetaTrabajo = rtrim($carpetaTemp, '/\\') . DIRECTORY_SEPARATOR . uniqid('txt2pdf_', true);

        if (!mkdir($carpetaTrabajo, 0777, true) && !is_dir($carpetaTrabajo)) {
            throw new Exception('No se pudo crear la carpeta temporal para generar el PDF.');
        }

        try {
            $rutaTxtFinal = $carpetaTrabajo . DIRECTORY_SEPARATOR . 'texto.txt';
            file_put_contents($rutaTxtFinal, $texto);

            $comandoLibreOffice = sprintf(
                '"%s" --headless --infilter="Text (encoded):UTF8" --convert-to pdf --outdir "%s" "%s"',
                RUTA_LIBREOFFICE,
                $carpetaTrabajo,
                $rutaTxtFinal
            );

            self::ejecutar($comandoLibreOffice);

            $rutaPdfGenerado = $carpetaTrabajo . DIRECTORY_SEPARATOR . 'texto.pdf';

            if (!file_exists($rutaPdfGenerado)) {
                throw new Exception('LibreOffice no generó el PDF de texto esperado.');
            }

            if (!copy($rutaPdfGenerado, $rutaSalida)) {
                throw new Exception('No se pudo generar el archivo final.');
            }

            if (!file_exists($rutaSalida)) {
                throw new Exception('No se generó el archivo esperado: ' . $rutaSalida);
            }

            return $rutaSalida;
        } finally {
            self::limpiarCarpeta($carpetaTrabajo);
        }
    }

    /**
     * Ruta del .txt "hermano" de un PDF de resultado de OCR: mismo nombre,
     * misma carpeta, extensión .txt. Ahí vive el texto reconocido, para
     * poder reabrirlo y corregirlo después sin volver a correr el OCR.
     */
    public static function rutaSidecarTexto(string $rutaPdf): string
    {
        $directorio = pathinfo($rutaPdf, PATHINFO_DIRNAME);
        $nombreBase = pathinfo($rutaPdf, PATHINFO_FILENAME);

        return $directorio . DIRECTORY_SEPARATOR . $nombreBase . '.txt';
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