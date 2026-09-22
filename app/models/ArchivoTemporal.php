<?php
/**
 * Modelo ArchivoTemporal — tabla `archivos_temporales` (diseño de Xavier,
 * ver Proyecto: claude/diseno-base-datos.md). Rastrea cada archivo físico
 * (original o resultado) en el servidor, para el borrado automático.
 *
 * Columnas: id, historial_id (FK), ruta_archivo, fecha_creacion,
 * fecha_expiracion, eliminado (booleano).
 *
 * TODO (Backend): job de limpieza que recorra `expirados()` y borre el
 * archivo físico + marque `eliminado = 1`.
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

    /**
     * Archivos (no eliminados) ligados a un historial_id, en el orden en
     * que se guardaron. Usado por UploadController::reintentar() para
     * recuperar el/los archivo(s) original(es) de una operación fallida.
     */
    public static function porHistorial(int $historialId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM archivos_temporales WHERE historial_id = ? AND eliminado = 0 ORDER BY id ASC'
        );
        $stmt->execute([$historialId]);
        return $stmt->fetchAll();
    }
}