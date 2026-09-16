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
         $this->requiereSesion();

        // TODO (Backend, s6-b2): reemplazar este arreglo de ejemplo por los
        // procesos reales del usuario (progreso vía polling a consultar()).
        // Estructura tomada del mockup "Estado de tus procesos" (14 sep 2026):
        // un proceso "en_curso" (con progreso/tiempo restante), uno
        // "completado" (con descarga) y uno "error" (con reintento).
        $procesos = [
            [
                'archivo'   => 'constancia_notas.pdf',
                'operacion' => 'Convertir a Word',
                'estado'    => 'en_curso',
                'progreso'  => 60,
                'restante'  => '8 s',
            ],
            [
                'archivo'        => 'examen_quimica_escaneado.pdf',
                'operacion'      => 'OCR',
                'estado'         => 'completado',
                'terminado_hace' => 'hace 4 minutos',
            ],
            [
                'archivo'   => 'horario_clases.pdf',
                'operacion' => 'Unir / Dividir',
                'estado'    => 'error',
                'mensaje'   => 'No se pudo procesar: el archivo está dañado o protegido con contraseña.',
            ],
        ];

        $this->vista('estado/estado', ['activo' => 'estado', 'procesos' => $procesos]);
    }

    public function consultar(): void
    {
         $this->requiereSesion();
         
        // TODO (Backend, s6-b2): devolver el progreso real en JSON para
        // que la vista lo consulte periódicamente (polling) sin recargar.
        $this->json(['estado' => 'en_curso', 'progreso' => 0]);
    }
}
