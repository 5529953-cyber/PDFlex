<?php
/**
 * Pantallas y lógica de registro / inicio de sesión.
 */
class AuthController extends Controller
{
    public function login(): void
    {
        $this->vista('auth/login', [], 'auth');
    }

    public function procesarLogin(): void
    {
        $correo = trim($_POST['correo'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';

        if ($correo === '' || $contrasena === '') {
            $this->vista('auth/login', ['error' => 'Completá correo y contraseña.'], 'auth');
            return;
        }

        $usuario = Usuario::buscarPorCorreo($correo);

        if (!$usuario || !password_verify($contrasena, $usuario['contrasena'])) {
            $this->vista('auth/login', ['error' => 'Correo o contraseña incorrectos.'], 'auth');
            return;
        }

        // Credenciales correctas: guardamos los datos clave en la sesión.
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_rol'] = $usuario['rol'];

        $this->redirigir('/subir');
    }

    public function registro(): void
    {
        $this->vista('auth/registro', [], 'auth');
    }

    public function procesarRegistro(): void
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        if ($nombre === '' || $correo === '' || $contrasena === '') {
            $this->vista('auth/registro', ['error' => 'Completá todos los campos.'], 'auth');
            return;
        }

        if ($contrasena !== $confirmar) {
            $this->vista('auth/registro', ['error' => 'Las contraseñas no coinciden.'], 'auth');
            return;
        }

        if (Usuario::buscarPorCorreo($correo)) {
            $this->vista('auth/registro', ['error' => 'Ese correo ya está registrado.'], 'auth');
            return;
        }

        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $usuarioId = Usuario::crear($nombre, $correo, $hash);

        // Registro exitoso: iniciamos sesión automáticamente.
        $_SESSION['usuario_id'] = $usuarioId;
        $_SESSION['usuario_nombre'] = $nombre;
        $_SESSION['usuario_rol'] = 'estudiante';

        $this->redirigir('/subir');
    }

    public function logout(): void
    {
        session_destroy();
        $this->redirigir('/login');
    }

    // ---------------------------------------------------------------
    // NUEVO — "¿Olvidaste tu contraseña?"
    // ---------------------------------------------------------------

    public function olvide(): void
    {
        $this->vista('auth/olvide', [], 'auth');
    }

    public function procesarOlvide(): void
    {
        $correo = trim($_POST['correo'] ?? '');

        if ($correo === '') {
            $this->vista('auth/olvide', ['error' => 'Ingresá tu correo.'], 'auth');
            return;
        }

        $usuario = Usuario::buscarPorCorreo($correo);

        if (!$usuario) {
            // No confirmamos si el correo existe o no (evita filtrar qué
            // correos están registrados) — mismo mensaje genérico.
            $this->vista('auth/olvide', [
                'error' => 'Si el correo está registrado vas a poder generar tu enlace. Revisá que esté bien escrito.',
            ], 'auth');
            return;
        }

        $token = RecuperacionContrasena::crear((int) $usuario['id']);
        $enlace = BASE_URL . '/restablecer?token=' . $token;

        // El enlace se muestra acá mismo porque no hay servidor de correo
        // configurado en WAMP — en un sistema con correo real, este
        // $enlace se enviaría por email en vez de mostrarse en pantalla.
        $this->vista('auth/olvide', ['enlace' => $enlace], 'auth');
    }

    public function restablecer(): void
    {
        $token = $_GET['token'] ?? '';
        $registro = $token !== '' ? RecuperacionContrasena::porToken($token) : null;

        $this->vista('auth/restablecer', [
            'token' => $token,
            'tokenValido' => $registro !== null,
        ], 'auth');
    }

    public function procesarRestablecer(): void
    {
        $token = $_POST['token'] ?? '';
        $contrasena = $_POST['contrasena'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        $registro = $token !== '' ? RecuperacionContrasena::porToken($token) : null;

        if (!$registro) {
            $this->vista('auth/restablecer', [
                'token' => $token,
                'tokenValido' => false,
            ], 'auth');
            return;
        }

        if ($contrasena === '' || $contrasena !== $confirmar) {
            $this->vista('auth/restablecer', [
                'token' => $token,
                'tokenValido' => true,
                'error' => 'Las contraseñas no coinciden.',
            ], 'auth');
            return;
        }

        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        Usuario::actualizarContrasena((int) $registro['usuario_id'], $hash);
        RecuperacionContrasena::marcarUsado((int) $registro['id']);

        $this->redirigir('/login');
    }
}