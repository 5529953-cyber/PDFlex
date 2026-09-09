<?php
/**
 * Modelo ArchivoTemporal — tabla `archivos_temporales` (diseño de Xavier,
 * ver Proyecto: claude/diseno-base-datos.md). Rastrea cada archivo físico
 * (original o resultado) en el servidor, para el borrado automático.
 *
 * Columnas: id, historial_id (FK), ruta_archivo, fecha_creacion,
 * fecha_expiracion, eliminado (booleano).
 *
 * TODO (Backend, s6-b2): job de limpieza que recorra `expirados()`
 * y borre el archivo físico + marque `eliminado = 1`.
 */
class ArchivoTemporal
{
    public static function registrar(int $historialId, string $rutaArchivo, string $fechaExpiracion): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO archivos_temporales (historial_id, ruta_archivo, fecha_creacion, fecha_expiracion, eliminado)
             VALUES (?, ?, NOW(), ?, 0)'
        );
        $stmt->execute([$historialId, $rutaArchivo, $fechaExpiracion]);
        return (int) $pdo->lastInsertId();
    }

    public static function expirados(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            'SELECT * FROM archivos_temporales WHERE eliminado = 0 AND fecha_expiracion <= NOW()'
        );
        return $stmt->fetchAll();
    }

    public static function marcarEliminado(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE archivos_temporales SET eliminado = 1 WHERE id = ?');
        $stmt->execute([$id]);
    }
}
