<?php
/**
 * Pantalla de Perfil: datos de la cuenta (nombre/correo, editables) y
 * cambio de contraseña. Se llega acá desde el menú de cuenta que vive en
 * el logo del header ("Ver perfil" — ver app/views/layouts/header.php).
 *
 * El rol (rol) y la fecha de registro NO son editables desde acá: el rol
 * lo asigna quien administre el sistema, no el propio usuario, y la fecha
 * de registro es un dato histórico.
 */
class PerfilController extends Controller
{
    public function index(): void
    {
        $this->requiereSesion();

        $usuarioId = (int) $_SESSION['usuario_id'];

        $this->vista('perfil/perfil', [
            'usuario' => Usuario::buscarPorId($usuarioId),
            'totalCompletados' => Historial::contarCompletados($usuarioId),
        ], 'app');
    }

    public function actualizar(): void
    {
        $this->requiereSesion();

        $usuarioId = (int) $_SESSION['usuario_id'];
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $totalCompletados = Historial::contarCompletados($usuarioId);

        if ($nombre === '' || $correo === '') {
            $this->vista('perfil/perfil', [
                'usuario' => array_merge(Usuario::buscarPorId($usuarioId) ?? [], ['nombre' => $nombre, 'correo' => $correo]),
                'totalCompletados' => $totalCompletados,
                'errorPerfil' => 'Completá nombre y correo.',
            ], 'app');
            return;
        }

        $existente = Usuario::buscarPorCorreo($correo);
        if ($existente && (int) $existente['id'] !== $usuarioId) {
            $this->vista('perfil/perfil', [
                'usuario' => array_merge(Usuario::buscarPorId($usuarioId) ?? [], ['nombre' => $nombre, 'correo' => $correo]),
                'totalCompletados' => $totalCompletados,
                'errorPerfil' => 'Ese correo ya lo usa otra cuenta.',
            ], 'app');
            return;
        }

        Usuario::actualizarPerfil($usuarioId, $nombre, $correo);

        // El nombre en sesión se guarda desde el login (ver AuthController)
        // para no tener que ir a la base cada vez; lo mantenemos al día acá.
        $_SESSION['usuario_nombre'] = $nombre;

        $this->vista('perfil/perfil', [
            'usuario' => Usuario::buscarPorId($usuarioId),
            'totalCompletados' => $totalCompletados,
            'exitoPerfil' => 'Tus datos se actualizaron correctamente.',
        ], 'app');
    }

    public function actualizarContrasena(): void
    {
        $this->requiereSesion();

        $usuarioId = (int) $_SESSION['usuario_id'];
        $usuario = Usuario::buscarPorId($usuarioId);
        $totalCompletados = Historial::contarCompletados($usuarioId);

        $actual = $_POST['actual'] ?? '';
        $nueva = $_POST['nueva'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        if (!$usuario || !password_verify($actual, $usuario['contrasena'])) {
            $this->vista('perfil/perfil', [
                'usuario' => $usuario,
                'totalCompletados' => $totalCompletados,
                'errorContrasena' => 'La contraseña actual no es correcta.',
            ], 'app');
            return;
        }

        if ($nueva === '' || $nueva !== $confirmar) {
            $this->vista('perfil/perfil', [
                'usuario' => $usuario,
                'totalCompletados' => $totalCompletados,
                'errorContrasena' => 'Las contraseñas nuevas no coinciden.',
            ], 'app');
            return;
        }

        Usuario::actualizarContrasena($usuarioId, password_hash($nueva, PASSWORD_DEFAULT));

        $this->vista('perfil/perfil', [
            'usuario' => $usuario,
            'totalCompletados' => $totalCompletados,
            'exitoContrasena' => 'Tu contraseña se actualizó correctamente.',
        ], 'app');
    }
}
