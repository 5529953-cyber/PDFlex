<?php
/**
 * Pantallas y lógica de registro / inicio de sesión.
 * Vista ya diseñada en el wireframe "Login" (Main.dc.html).
 *
 * Semana 1 (ahora):  solo se define esta estructura y las rutas.
 * Semana 3 (después): Frontend maqueta la vista con Bootstrap (s3-f1)
 *                      y Backend escribe la lógica real de sesión (s3-b1).
 */
class AuthController extends Controller
{
    public function login(): void
    {
        $this->vista('auth/login');
    }

    public function procesarLogin(): void
    {
        // TODO (Backend, s3-b1): validar credenciales contra la tabla `usuarios`,
        // iniciar sesión y redirigir a /subir. Por ahora solo redirige.
        $this->redirigir('/subir');
    }

    public function registro(): void
    {
        $this->vista('auth/registro');
    }

    public function procesarRegistro(): void
    {
        // TODO (Backend, s3-b1): crear usuario con contraseña cifrada (password_hash).
        $this->redirigir('/login');
    }

    public function logout(): void
    {
        session_destroy();
        $this->redirigir('/login');
    }
}
