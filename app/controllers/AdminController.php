<?php
/**
 * Panel de administrador — estadísticas de uso de TODO el sistema (no solo
 * del usuario logueado). Extra sin semana asignada en el cronograma
 * original (ver Proyecto: PDFlex_Anteproyecto.docx, sección 6: "Panel de
 * administrador con estadísticas de uso, para justificar el proyecto ante
 * el instituto").
 *
 * Solo entran acá cuentas con rol "admin" (ver database/schema.sql,
 * columna `usuarios.rol`) — requiereAdmin() redirige a /subir a cualquier
 * otro rol, en silencio, igual que Controller::requiereSesion() redirige a
 * /login sin sesión. El rol no es editable desde /perfil (ver
 * PerfilController) — solo se asigna directo en la base de datos, así que
 * no hay forma de que un usuario se "auto-ascienda".
 */
class AdminController extends Controller
{
    // Etiquetas propias de esta pantalla: a diferencia de historial.php
    // (que junta "union"/"division" bajo "Unir / Dividir" para no ocupar
    // una fila completa en la tabla), acá interesa verlas separadas —
    // es justo el tipo de detalle que un admin sí quiere poder comparar.
    private const ETIQUETAS_OPERACION = [
        'conversion_pdf_word'   => 'Convertir a Word',
        'conversion_word_pdf'   => 'Convertir a PDF (desde Word)',
        'conversion_pdf_imagen' => 'Convertir a imagen',
        'conversion_imagen_pdf' => 'Convertir a PDF (desde imagen)',
        'compresion'            => 'Comprimir',
        'union'                 => 'Unir',
        'division'              => 'Dividir',
        'ocr'                   => 'Extraer texto (OCR)',
    ];

    // Mismo texto que usa perfil.php para el rol de la propia cuenta —
    // acá en plural, porque son conteos de cuántas cuentas hay de cada uno.
    private const ETIQUETAS_ROL = [
        'estudiante'     => 'Estudiantes',
        'docente'        => 'Docentes',
        'administrativo' => 'Administrativos',
        'admin'          => 'Administradores',
    ];

    private const DIAS_ACTIVIDAD = 14;

    public function index(): void
    {
        $this->requiereSesion();
        $this->requiereAdmin();

        $porRol = Usuario::contarPorRol();
        $totalUsuarios = array_sum($porRol);

        $porEstado = Historial::contarPorEstado();
        $totalOperaciones = array_sum($porEstado);
        $completadas = $porEstado['completado'] ?? 0;
        $conError = $porEstado['error'] ?? 0;
        // "en_curso" junta pendiente + procesando, mismo criterio que ya
        // usa EstadoController::mapearProceso() para la pantalla de Estado.
        $enCurso = ($porEstado['pendiente'] ?? 0) + ($porEstado['procesando'] ?? 0);
        $tasaExito = $totalOperaciones > 0 ? (int) round($completadas / $totalOperaciones * 100) : 0;

        $this->vista('admin/admin', [
            'activo'             => 'admin',
            'totalUsuarios'      => $totalUsuarios,
            'totalOperaciones'   => $totalOperaciones,
            'completadas'        => $completadas,
            'conError'           => $conError,
            'enCurso'            => $enCurso,
            'tasaExito'          => $tasaExito,
            'porOperacion'       => $this->operacionesConEtiqueta($porEstado, Historial::contarPorOperacion()),
            'porRol'             => $this->rolesConEtiqueta($porRol),
            'masActivos'         => Historial::usuariosMasActivos(5),
            'actividad'          => $this->actividadRellenada(Historial::actividadPorDia(self::DIAS_ACTIVIDAD), self::DIAS_ACTIVIDAD),
            'ahorroCompresionMb' => round(Historial::ahorroCompresionKb() / 1024, 1),
        ]);
    }

    private function requiereAdmin(): void
    {
        if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
            $this->redirigir('/subir');
        }
    }

    /**
     * Añade etiqueta en español y porcentaje (sobre el total de
     * operaciones, no solo las mostradas) a cada tipo_operacion, para
     * dibujar las barras de "Operaciones por tipo" sin hacer cuentas en
     * la vista. $porEstado no se usa para el cálculo (el total ya sale de
     * sumar $conteosPorTipo) — se recibe solo para reusar un único total
     * consistente si hiciera falta más adelante; hoy no cambia el número.
     */
    private function operacionesConEtiqueta(array $porEstado, array $conteosPorTipo): array
    {
        $total = array_sum($conteosPorTipo);
        $resultado = [];

        foreach ($conteosPorTipo as $tipo => $cantidad) {
            $resultado[] = [
                'tipo'       => $tipo,
                'etiqueta'   => self::ETIQUETAS_OPERACION[$tipo] ?? $tipo,
                'cantidad'   => $cantidad,
                'porcentaje' => $total > 0 ? (int) round($cantidad / $total * 100) : 0,
            ];
        }

        return $resultado;
    }

    /**
     * Mismo criterio que operacionesConEtiqueta(), pero siempre incluye
     * los 4 roles del ENUM aunque tengan 0 cuentas (a diferencia de
     * Usuario::contarPorRol(), que solo trae los que SÍ tienen alguna) —
     * así la lista de "Cuentas por rol" no cambia de tamaño con los datos.
     */
    private function rolesConEtiqueta(array $conteosPorRol): array
    {
        $total = array_sum($conteosPorRol);
        $resultado = [];

        foreach (self::ETIQUETAS_ROL as $rol => $etiqueta) {
            $cantidad = $conteosPorRol[$rol] ?? 0;
            $resultado[] = [
                'rol'        => $rol,
                'etiqueta'   => $etiqueta,
                'cantidad'   => $cantidad,
                'porcentaje' => $total > 0 ? (int) round($cantidad / $total * 100) : 0,
            ];
        }

        return $resultado;
    }

    /**
     * Historial::actividadPorDia() solo trae los días que SÍ tuvieron al
     * menos una operación — acá se rellenan los $dias días más recientes
     * (incluido hoy) con 0 donde no haya nada, para que el mini gráfico
     * muestre siempre la misma cantidad de barras seguidas en vez de saltar
     * días sin actividad. El porcentaje es relativo al día de MÁS
     * actividad del período (no a 100 fijo), para que la barra más alta
     * siempre llegue arriba del todo.
     */
    private function actividadRellenada(array $porDia, int $dias): array
    {
        $maximo = !empty($porDia) ? max($porDia) : 0;
        $resultado = [];

        for ($i = $dias - 1; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-{$i} days"));
            $cantidad = $porDia[$fecha] ?? 0;
            $resultado[] = [
                'fecha'      => $fecha,
                'etiqueta'   => date('d/m', strtotime($fecha)),
                'cantidad'   => $cantidad,
                'porcentaje' => $maximo > 0 ? (int) round($cantidad / $maximo * 100) : 0,
            ];
        }

        return $resultado;
    }
}
