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
    // NUEVO — usado por AuthController::procesarRestablecer() al confirmar
    // una recuperación de contraseña.
    public static function actualizarContrasena(int $id, string $hashContrasena): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE usuarios SET contrasena = ? WHERE id = ?');
        $stmt->execute([$hashContrasena, $id]);
    }

    // NUEVO — usado por PerfilController para mostrar y editar los datos
    // de la cuenta del usuario que tiene la sesión iniciada.
    public static function buscarPorId(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // NUEVO — usado por PerfilController::actualizar(). No toca `contrasena`
    // ni `rol`: el cambio de contraseña tiene su propio método
    // (actualizarContrasena) y el rol no es editable por el propio usuario.
    public static function actualizarPerfil(int $id, string $nombre, string $correo): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE usuarios SET nombre = ?, correo = ? WHERE id = ?');
        $stmt->execute([$nombre, $correo, $id]);
    }
}
