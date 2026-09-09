<?php
// Prueba rápida de conexión a la base de datos (creado originalmente por Xavier
// como public/index.php; se mueve aquí para que index.php pueda ser el front
// controller). Visita /PDFlex/public/test_db.php para verificar la conexión.

require_once __DIR__ . '/../app/core/Database.php';

try {
    $db = Database::getConnection();
    echo "Conexión exitosa a la base de datos 'pdflex'.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
