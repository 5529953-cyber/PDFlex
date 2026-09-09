<?php
/**
 * Modelo Usuario — tabla `usuarios` (diseño de Xavier, ver Proyecto:
 * claude/diseno-base-datos.md).
 *
 * Columnas: id, nombre, correo (único), contrasena (hash), rol
 * (ENUM: estudiante/docente/administrativo/admin), fecha_registro.
 *
 * TODO (Backend, s3-b1): usar `crear()` en el registro (con password_hash())
 * y `buscarPorCorreo()` + password_verify() en el login.
 */
class Usuario
{
    public static function buscarPorCorreo(string $correo): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE correo = ?');
        $stmt->execute([$correo]);
        return $stmt->fetch() ?: null;
    }

    public static function crear(string $nombre, string $correo, string $hashContrasena, string $rol = 'estudiante'): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nombre, correo, contrasena, rol, fecha_registro)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$nombre, $correo, $hashContrasena, $rol]);
        return (int) $pdo->lastInsertId();
    }
}
