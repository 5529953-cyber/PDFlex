<?php
// Copiar este archivo como herramientas.php y ajustar las rutas según donde
// haya quedado instalado LibreOffice y Ghostscript en tu máquina.
// herramientas.php NO se sube a GitHub (está en .gitignore).
//
// Importante: RUTA_LIBREOFFICE debe apuntar a "soffice.com", no a
// "soffice.exe" — con el .exe, exec() no devuelve el código de salida
// correctamente y ProcesadorPDF no puede saber si la conversión falló.

define('RUTA_LIBREOFFICE', 'C:\\Program Files\\LibreOffice\\program\\soffice.com');
define('RUTA_GHOSTSCRIPT', 'C:\\Program Files\\gs\\gs10.xx.x\\bin\\gswin64c.exe');
