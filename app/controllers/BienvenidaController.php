<?php
/**
 * Pantalla de carga / puerta de entrada del sitio ("/"). Antes la raíz
 * mandaba directo a AuthController::login; ahora primero se ve esta
 * animación con la insignia de PDFlex, y según si ya había sesión
 * iniciada o no, termina en botones distintos:
 *  - sin sesión (primera vez, cerraste sesión, o venció): "Iniciar
 *    sesión" / "Crear cuenta".
 *  - con sesión todavía activa (cerraste la pestaña/navegador y volviste
 *    a entrar a la raíz sin haber tocado "Cerrar sesión"): un solo
 *    botón, "Seguir navegando", que manda de vuelta exactamente a la
 *    última pantalla interna visitada ($_SESSION['ultima_pagina'],
 *    guardada por app/views/layouts/header.php en cada pantalla con
 *    layout "app").
 *
 * A propósito NO cierra la sesión acá: se respeta tal cual la sesión de
 * PHP que ya usa el resto del sistema, sin inventar un mecanismo de
 * bloqueo aparte.
 *
 * Nota (9 oct): header.php ya no decide esto por una bandera que le
 * mandemos — el overlay genérico ahí solo aparece en layout "app", y esta
 * pantalla siempre usa layout "auth", así que nunca se superpone con la
 * animación propia de acá abajo sin que haga falta pasarle nada especial.
 *
 * Nota (9 oct, tercer ajuste): "?volver=1" llega cuando se tocó la
 * insignia fija de Login/Registro/Olvide/Restablecer para volver acá (ver
 * header.php) — ahí el anillo ya se agrandó completo en esa misma
 * pantalla antes de navegar, así que acá no tiene sentido repetir el
 * spinner desde cero: 'entradaInstantanea' le avisa a la vista que lo
 * salte y muestre el anillo ya armado de una.
 */
class BienvenidaController extends Controller
{
    public function index(): void
    {
        $haySesion = !empty($_SESSION['usuario_id']);

        $this->vista('bienvenida/bienvenida', [
            'haySesion' => $haySesion,
            'destino' => $haySesion ? ($_SESSION['ultima_pagina'] ?? '/subir') : null,
            'ocultarInsignia' => true,
            'entradaInstantanea' => isset($_GET['volver']),
        ], 'auth');
    }
}
