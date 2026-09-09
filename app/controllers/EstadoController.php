<?php
/**
 * Panel de estado de procesos en curso (en curso / completado / error).
 * Vista ya diseñada en el wireframe "Estado" (Estado.dc.html).
 *
 * Semana 5-6 (s5-f2 progreso de OCR, s6-f2 panel de estado, s6-b2 lógica real).
 */
class EstadoController extends Controller
{
    public function index(): void
    {
        $this->vista('estado/estado');
    }

    public function consultar(): void
    {
        // TODO (Backend, s6-b2): devolver el progreso real en JSON para
        // que la vista lo consulte periódicamente (polling) sin recargar.
        $this->json(['estado' => 'en_curso', 'progreso' => 0]);
    }
}
