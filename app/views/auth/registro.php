<!-- Vista: Crear cuenta — misma tarjeta "carpeta" que login.php, pero
     arrancando ya volteada hacia la cara de "Crear cuenta" (así /registro
     como URL directa muestra el formulario correcto sin depender de JS).
     Markup compartido en _carpeta.php.
     La lógica real de registro (procesarRegistro) es Backend, ya conectada. -->
<?php
$flipInicial = true; // esta ruta siempre arranca mostrando "Crear cuenta"
$errorLogin = null;
$errorRegistro = $error ?? null;
require __DIR__ . '/_carpeta.php';
