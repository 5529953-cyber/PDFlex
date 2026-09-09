<?php
/**
 * Modelo Historial — tabla `historial` (diseño de Xavier, ver Proyecto:
 * claude/diseno-base-datos.md). Cada fila es UNA operación ejecutada
 * sobre un archivo (conversión, compresión, unión, división u OCR).
 *
 * Columnas: id, usuario_id (FK), tipo_operacion (ENUM), nombre_archivo_original,
 * nombre_archivo_resultado, tamano_original_kb, tamano_resultado_kb,
 * estado (ENUM: pendiente/procesando/completado/error), mensaje_error,
 * fecha_inicio, fecha_fin.
 *
 * TODO (Backend, s4-b1/s4-b2/s5-b1/s5-b2): usar `registrar()` y
 * `actualizarEstado()` desde cada módulo de procesamiento.
 * TODO (Backend, s6-b1): completar filtros (por operación, por fecha) en `porUsuario()`.
 */
class Historial
{
    public static function registrar(int $usuarioId, string $tipoOperacion, string $nombreArchivoOriginal): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO historial (usuario_id, tipo_operacion, nombre_archivo_original, estado, fecha_inicio)
             VALUES (?, ?, ?, "pendiente", NOW())'
        );
        $stmt->execute([$usuarioId, $tipoOperacion, $nombreArchivoOriginal]);
        return (int) $pdo->lastInsertId();
    }

    public static function actualizarEstado(int $id, string $estado, ?string $mensajeError = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE historial SET estado = ?, mensaje_error = ?, fecha_fin = IF(? IN ("completado","error"), NOW(), fecha_fin)
             WHERE id = ?'
        );
        $stmt->execute([$estado, $mensajeError, $estado, $id]);
    }

    public static function guardarResultado(int $id, string $nombreArchivoResultado, int $tamanoOriginalKb, int $tamanoResultadoKb): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE historial SET nombre_archivo_resultado = ?, tamano_original_kb = ?, tamano_resultado_kb = ?
             WHERE id = ?'
        );
        $stmt->execute([$nombreArchivoResultado, $tamanoOriginalKb, $tamanoResultadoKb, $id]);
    }

    public static function porUsuario(int $usuarioId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM historial WHERE usuario_id = ? ORDER BY fecha_inicio DESC'
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }
}
