<?php
/**
 * Job de limpieza de archivos temporales — borra del disco los archivos
 * que ya vencieron (fecha_expiracion <= NOW()) y los marca en la base
 * con ArchivoTemporal::marcarEliminado().
 *
 * No corre solo: hay que programarlo en el Programador de tareas de
 * Windows para que se ejecute periódicamente (ver instrucciones aparte).
 * Pensado para correr por línea de comandos (CLI), no por navegador.
 *
 * Uso manual, para probarlo a mano:
 *   php scripts/limpiar_temporales.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Este script solo se puede ejecutar por línea de comandos.');
}

date_default_timezone_set('America/Tegucigalpa'); // mismo huso que public/index.php, solo para que el log de abajo muestre la hora correcta

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/ArchivoTemporal.php';

$expirados = ArchivoTemporal::expirados();

if (empty($expirados)) {
    echo '[' . date('Y-m-d H:i:s') . "] No hay archivos vencidos. Nada que borrar.\n";
    exit(0);
}

$borrados = 0;
$yaNoExistian = 0;

foreach ($expirados as $archivo) {
    $ruta = $archivo['ruta_archivo'];

    if (file_exists($ruta)) {
        if (unlink($ruta)) {
            $borrados++;
        } else {
            // No se pudo borrar (permisos, archivo en uso, etc.) — no lo
            // marcamos eliminado todavía, para que el próximo run lo
            // vuelva a intentar en vez de darlo por perdido.
            echo '[' . date('Y-m-d H:i:s') . "] AVISO: no se pudo borrar {$ruta} (revisar permisos).\n";
            continue;
        }
    } else {
        // El archivo físico ya no está (se borró a mano, o quedó suelto
        // de antes de tener este script). Se marca igual como eliminado,
        // no tiene sentido seguir intentando borrar algo que no existe.
        $yaNoExistian++;
    }

    ArchivoTemporal::marcarEliminado((int) $archivo['id']);
}

echo '[' . date('Y-m-d H:i:s') . "] Listo. Borrados del disco: {$borrados}. Ya no existían: {$yaNoExistian}. Total marcados en la base: " . count($expirados) . ".\n";