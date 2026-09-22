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
 * s6-b1: porUsuario() soporta filtro por nombre de archivo (busqueda)
 * y por tipo_operacion (operacion), ambos opcionales.
 * s6-b2: recientes() y porId() para el panel de Estado (EstadoController).
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

    /**
     * Trae el historial de un usuario, más nuevo primero. $busqueda filtra
     * por nombre de archivo original (coincidencia parcial); $operacion
     * filtra por tipo_operacion exacto (los mismos valores del ENUM:
     * conversion_pdf_word, conversion_pdf_imagen, compresion, union,
     * division, ocr). Ambos son opcionales — si vienen null o vacíos, no
     * se aplican.
     */
    public static function porUsuario(int $usuarioId, ?string $busqueda = null, ?string $operacion = null): array
    {
        $pdo = Database::getConnection();

        $sql = 'SELECT * FROM historial WHERE usuario_id = ?';
        $parametros = [$usuarioId];

        if ($busqueda !== null && $busqueda !== '') {
            $sql .= ' AND nombre_archivo_original LIKE ?';
            $parametros[] = '%' . $busqueda . '%';
        }

        if ($operacion !== null && $operacion !== '') {
            $sql .= ' AND tipo_operacion = ?';
            $parametros[] = $operacion;
        }

        $sql .= ' ORDER BY fecha_inicio DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    /**
     * Los últimos $limite procesos de un usuario (para el panel de Estado).
     * $limite es siempre un valor fijo puesto por el propio código (no
     * viene de un input de usuario), así que interpolarlo directo en el
     * LIMIT es seguro.
     */
    public static function recientes(int $usuarioId, int $limite = 10): array
    {
        $pdo = Database::getConnection();
        $limite = max(1, $limite);

        $stmt = $pdo->prepare(
            "SELECT * FROM historial WHERE usuario_id = ? ORDER BY fecha_inicio DESC LIMIT {$limite}"
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }

    /**
     * Un registro puntual, pero SOLO si pertenece a $usuarioId — evita que
     * alguien consulte el proceso de otro usuario adivinando el id
     * (usado por EstadoController::consultar(), el endpoint de polling).
     */
    public static function porId(int $id, int $usuarioId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM historial WHERE id = ? AND usuario_id = ? LIMIT 1');
        $stmt->execute([$id, $usuarioId]);
        $fila = $stmt->fetch();
        return $fila !== false ? $fila : null;
    }
}