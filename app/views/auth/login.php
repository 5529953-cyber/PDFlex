<!-- Vista: Login — diseño "carpeta" (artifact "PDFlex — Login carpeta",
     11 sep 2026): tarjeta que se voltea entre Iniciar sesión y Crear
     cuenta, con silueta de carpeta dorada detrás. Reemplaza el tratamiento
     anterior (tarjeta con pestañas planas) que se le había dado antes.
     Markup compartido con registro.php en _carpeta.php.
     La lógica real de autenticación (procesarLogin) es Backend, ya conectada. -->
<?php
$flipInicial = false; // esta ruta siempre arranca mostrando "Iniciar sesión"
$errorLogin = $error ?? null;
$errorRegistro = null;
require __DIR__ . '/_carpeta.php';
