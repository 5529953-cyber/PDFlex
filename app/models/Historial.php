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

    /**
     * Cuántos archivos terminó de procesar (estado "completado") un
     * usuario en total — dato simple que se muestra en /perfil.
     */
    public static function contarCompletados(int $usuarioId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM historial WHERE usuario_id = ? AND estado = 'completado'"
        );
        $stmt->execute([$usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    // ---------------------------------------------------------------
    // NUEVO (extra, oct 2026) — usados por AdminController para el Panel
    // de administrador. A diferencia de todo lo de arriba, estos NO
    // filtran por usuario_id: son estadísticas de TODO el sistema.
    // ---------------------------------------------------------------

    /**
     * Cuántos registros de `historial` hay por cada `estado`
     * (pendiente/procesando/completado/error), de todos los usuarios. Los
     * estados sin ningún registro simplemente no aparecen en el array —
     * quien llama rellena con 0 lo que necesite (ver
     * AdminController::index(), que usa `?? 0` para cada clave).
     */
    public static function contarPorEstado(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT estado, COUNT(*) AS total FROM historial GROUP BY estado');

        $resultado = [];
        foreach ($stmt->fetchAll() as $fila) {
            $resultado[$fila['estado']] = (int) $fila['total'];
        }
        return $resultado;
    }

    /**
     * Igual que contarPorEstado() pero agrupado por tipo_operacion, de más
     * a menos usado.
     */
    public static function contarPorOperacion(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT tipo_operacion, COUNT(*) AS total FROM historial GROUP BY tipo_operacion ORDER BY total DESC');

        $resultado = [];
        foreach ($stmt->fetchAll() as $fila) {
            $resultado[$fila['tipo_operacion']] = (int) $fila['total'];
        }
        return $resultado;
    }

    /**
     * Espacio ahorrado (en KB) por TODAS las compresiones completadas del
     * sistema, sumando (tamaño original − tamaño resultado) de cada una.
     * Filtrado a tipo_operacion = 'compresion' a propósito: el resto de
     * operaciones no busca reducir el tamaño del archivo (una conversión
     * o un OCR pueden perfectamente dar un resultado más pesado que el
     * original, eso no sería "ahorro").
     *
     * CAST(... AS SIGNED) en los dos operandos (encontrado probando esto
     * en vivo, 14 oct 2026): tamano_original_kb/tamano_resultado_kb son
     * INT UNSIGNED (ver schema.sql). Si algún registro de compresión
     * terminó pesando IGUAL O MÁS que el original (pasa, p. ej., con
     * archivos que ya venían casi sin margen para comprimir), la resta
     * directa entre dos UNSIGNED da un resultado negativo que MySQL
     * "envuelve" a un número gigantesco en vez de dejarlo negativo —
     * sumar eso desborda hasta el rango de BIGINT UNSIGNED y tira
     * PDOException en vez de un número. Castear a SIGNED antes de restar
     * evita el envolvimiento: una compresión que no ahorró nada resta en
     * negativo como corresponde, y el total sigue siendo honesto.
     */
    public static function ahorroCompresionKb(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            "SELECT COALESCE(SUM(CAST(tamano_original_kb AS SIGNED) - CAST(tamano_resultado_kb AS SIGNED)), 0) AS ahorro
             FROM historial
             WHERE tipo_operacion = 'compresion' AND estado = 'completado'
               AND tamano_original_kb IS NOT NULL AND tamano_resultado_kb IS NOT NULL"
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Los $limite usuarios con más operaciones registradas (de cualquier
     * estado), para la lista "Usuarios con más actividad" del Panel de
     * administrador. $limite siempre lo pone el propio código (no un input
     * de usuario) — mismo criterio de seguridad que ya documenta
     * recientes() más arriba, así que interpolarlo directo en el LIMIT es
     * seguro.
     */
    public static function usuariosMasActivos(int $limite = 5): array
    {
        $pdo = Database::getConnection();
        $limite = max(1, $limite);

        $stmt = $pdo->query(
            "SELECT u.id, u.nombre, u.rol, COUNT(h.id) AS total
             FROM historial h
             JOIN usuarios u ON u.id = h.usuario_id
             GROUP BY u.id, u.nombre, u.rol
             ORDER BY total DESC
             LIMIT {$limite}"
        );
        return $stmt->fetchAll();
    }

    /**
     * Operaciones por día en los últimos $dias días (incluido hoy), como
     * [fecha "Y-m-d" => cantidad]. Solo trae los días que SÍ tuvieron al
     * menos una operación — AdminController::actividadRellenada() completa
     * los días sin ninguna en 0, para el mini gráfico de actividad.
     */
    public static function actividadPorDia(int $dias = 14): array
    {
        $pdo = Database::getConnection();
        $dias = max(1, $dias);

        $stmt = $pdo->query(
            "SELECT DATE(fecha_inicio) AS dia, COUNT(*) AS total
             FROM historial
             WHERE fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL {$dias} DAY)
             GROUP BY DATE(fecha_inicio)
             ORDER BY dia ASC"
        );

        $resultado = [];
        foreach ($stmt->fetchAll() as $fila) {
            $resultado[$fila['dia']] = (int) $fila['total'];
        }
        return $resultado;
    }
}