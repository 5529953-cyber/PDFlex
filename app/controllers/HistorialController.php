<?php
/**
 * Historial de conversiones del usuario (tabla filtrable).
 * Vista ya diseñada en el wireframe "Historial" (Historial.dc.html).
 *
 * Semana 6 (s6-f1 / s6-b1).
 */
class HistorialController extends Controller
{
    public function index(): void
    {
        $this->requiereSesion();

        // TODO (Backend, s6-b1): traer registros reales del modelo Historial.
        $registros = [];
        $this->vista('historial/historial', ['registros' => $registros, 'activo' => 'historial']);
    }
}
