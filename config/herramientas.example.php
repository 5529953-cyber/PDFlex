<?php
// Copiar este archivo como herramientas.php y ajustar las rutas según donde
// hayan quedado instalados LibreOffice, Ghostscript y Tesseract en tu máquina.
// herramientas.php NO se sube a GitHub (está en .gitignore).
//
// Importante: RUTA_LIBREOFFICE debe apuntar a "soffice.com", no a
// "soffice.exe" — con el .exe, exec() no devuelve el código de salida
// correctamente y ProcesadorPDF no puede saber si la conversión falló.

define('RUTA_LIBREOFFICE', 'C:\\Program Files\\LibreOffice\\program\\soffice.com');
define('RUTA_GHOSTSCRIPT', 'C:\\Program Files\\gs\\gs10.xx.x\\bin\\gswin64c.exe');
define('RUTA_TESSERACT', 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe');

// Opcional: si no está instalado o no se define esta constante, el OCR
// sigue funcionando igual, solo que sin el preprocesamiento que mejora
// fotos con mala luz, algo torcidas o de baja resolución (ver
// ProcesadorPDF::preprocesarImagenParaOcr). Instalar desde
// https://imagemagick.org/script/download.php#windows (la ruta exacta
// depende de la versión instalada, ej. "ImageMagick-7.1.x-Q16-HDRI").
define('RUTA_IMAGEMAGICK', 'C:\\Program Files\\ImageMagick-7.1.1-Q16-HDRI\\magick.exe');