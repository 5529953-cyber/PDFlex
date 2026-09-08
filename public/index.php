<?php

require_once __DIR__ . '/../app/core/Database.php';

try {
    $db = Database::getConnection();
    echo "Conexión exitosa a la base de datos 'pdflex'.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}