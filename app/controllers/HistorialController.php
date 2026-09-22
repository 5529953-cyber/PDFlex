<?php
/**
 * Historial de conversiones del usuario (tabla filtrable).
 * Vista ya diseñada en el wireframe "Historial" (Historial.dc.html).
 *
 * Semana 6 (s6-f1 / s6-b1) — completo.
 */
class HistorialController extends Controller
{
    public function index(): void
    {
        $this->requiereSesion();

        $busqueda = trim($_GET['busqueda'] ?? '');
        $operacion = trim($_GET['operacion'] ?? '');

        $registros = Historial::porUsuario(
            $_SESSION['usuario_id'],
            $busqueda !== '' ? $busqueda : null,
            $operacion !== '' ? $operacion : null
        );

        $this->vista('historial/historial', ['registros' => $registros, 'activo' => 'historial']);
    }
}