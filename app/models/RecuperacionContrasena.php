<?php
/**
 * Modelo RecuperacionContrasena — tabla `recuperaciones_contrasena`.
 * Genera y valida los tokens de "¿Olvidaste tu contraseña?".
 *
 * El proyecto corre en WAMP local sin servidor de correo configurado,
 * así que el enlace no se envía por email real: AuthController::procesarOlvide()
 * lo muestra directo en pantalla. La tabla igual guarda vencimiento y
 * estado "usado" para que el flujo sea el mismo que tendría con correo real.
 */
class RecuperacionContrasena
{
    private const HORAS_VALIDEZ = 1;

    public static function crear(int $usuarioId): string
    {
        $pdo = Database::getConnection();
        $token = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare(
            'INSERT INTO recuperaciones_contrasena (usuario_id, token, fecha_creacion, fecha_expiracion, usado)
             VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ' . self::HORAS_VALIDEZ . ' HOUR), FALSE)'
        );
        $stmt->execute([$usuarioId, $token]);

        return $token;
    }

    public static function porToken(string $token): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM recuperaciones_contrasena
             WHERE token = ? AND usado = FALSE AND fecha_expiracion > NOW()'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public static function marcarUsado(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE recuperaciones_contrasena SET usado = TRUE WHERE id = ?');
        $stmt->execute([$id]);
    }
}