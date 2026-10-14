<?php
/**
 * Header compartido. Dos modos, elegidos por Controller::vista($ruta, $datos, $layout):
 *  - "app"  (por defecto): pantallas internas — barra flotante con logo
 *            y pestañas (Subir/Historial/Estado).
 *            Rediseño cálido (mostaza/beige) aplicado según la solicitud
 *            "Interfaz completa del Panel Principal" (13 sep 2026).
 *  - "auth": login/registro — sin barra de pestañas, solo la insignia de
 *            marca arriba a la izquierda.
 *
 * $activo (opcional, solo layout "app"): 'subir' | 'historial' | 'estado' — resalta la pestaña activa.
 * $ocultarInsignia (opcional, solo layout "auth"): true para no dibujar la
 *            insignia fija de la esquina — la usa Bienvenida (ver
 *            BienvenidaController), que ya trae su propia insignia animada
 *            como protagonista de la pantalla. Cuando SÍ se dibuja
 *            (Login/Registro/Olvide/Restablecer), también sirve de botón
 *            de "volver": tocarla agranda el anillo (sin spinner, ya está
 *            completo) y navega de vuelta a "/" — ver el script junto a
 *            ella, más abajo.
 *
 * La pantalla de carga de acá abajo (spinner que se completa y voltea, con
 * botón "Seguir navegando" — estas rutas siempre tienen sesión, ver
 * Controller::requiereSesion()) solo existe en layout "app", nunca en
 * "auth" (Login/Registro/Bienvenida/Olvide/Restablecer: esas pantallas ya
 * tienen su propia insignia fija en la esquina, o en el caso de Bienvenida
 * su propia versión completa — repetirla encima se sentía como dos
 * transiciones sueltas, no una sola).
 *
 * Dentro de layout "app" tampoco se muestra siempre: solo la PRIMERA vez
 * que esta pestaña del navegador ve una pantalla interna (9 oct, segundo
 * ajuste, a pedido de Marvin) — sea porque entró directo por acá (enlace
 * guardado, pestaña nueva, volvió a abrir el navegador) o porque vino de
 * Bienvenida/Login. Tocar "Seguir navegando" no navega a ningún lado: la
 * pantalla real ya está renderizada debajo, el botón solo la destapa.
 * Cambiar de pestaña (Subir ↔ Historial ↔ Estado) DESPUÉS de esa primera
 * vez no debe repetirla — por eso la decisión de mostrarla la toma el
 * script de abajo con sessionStorage, no PHP: esa memoria dura toda la
 * pestaña del navegador y se borra sola al cerrarla, que es exactamente
 * lo que hace falta acá (ver $_SESSION, en cambio, que dura toda la
 * sesión de LOGIN, no de pestaña — por eso no sirve para esto). La propia
 * Bienvenida (ver bienvenida.php) marca esa misma memoria al jugar su
 * animación, para no repetirla de nuevo apenas se entra a la primera
 * pantalla interna.
 */
$layout = $layout ?? 'app';

// "Cache-buster" del CSS propio (9 oct, a pedido de Marvin): sin esto el
// navegador puede seguir usando una copia vieja de style.css en caché
// después de que lo editamos, y los cambios no se ven hasta un refresco
// forzado (Ctrl+Shift+R). Usamos la fecha de modificación real del
// archivo como "?v=" — cambia solo cuando el CSS realmente cambia, así
// que no rompe la caché en el resto de las visitas.
$cssVersion = @filemtime(__DIR__ . '/../../../public/assets/css/style.css') ?: time();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PDFlex</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Work+Sans:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= $cssVersion ?>">
</head>
<body>
<?php if ($layout === 'app'): ?>
  <!-- Pantalla de carga de "entrada fresca" — ver la nota de arriba.
       Empieza tapada (display:none) para no destellar mientras el script
       de abajo decide, de forma sincrónica y antes de pintar nada más, si
       esta pestaña ya la vio o no. Siempre lleva botón: estas rutas
       exigen sesión (Controller::requiereSesion()), así que nunca hace
       falta la variante "sin sesión". -->
  <div class="pdflex-intro-overlay" id="pdflexIntroOverlay" style="display:none;">
    <div class="pdflex-intro-wrap">
      <?php $idLogo = 'pdflexOverlayLogo'; require __DIR__ . '/../partials/_insignia-animada.php'; ?>
      <div class="pdflex-intro-acciones" id="pdflexOverlayAcciones">
        <button type="button" class="pdflex-intro-btn pdflex-intro-btn-primario" id="pdflexOverlaySeguir">Seguir navegando</button>
      </div>
      <p class="pdflex-intro-eslogan" id="pdflexOverlayEslogan">PDFs, sin fricción.</p>
    </div>
  </div>
  <script>
    (function () {
      // "Memoria de pestaña": sessionStorage, a diferencia de $_SESSION,
      // se borra solo al cerrar la pestaña — exactamente la vida que
      // necesita esta bandera. Bienvenida usa la misma clave.
      var CLAVE = 'pdflexIntroMostrada';
      var yaMostrada = true;
      try {
        yaMostrada = sessionStorage.getItem(CLAVE) === '1';
      } catch (error) {
        // Sin sessionStorage (navegación privada estricta, almacenamiento
        // bloqueado): mejor no mostrar nada a arriesgarse a tapar la
        // pantalla con algo que después no se pueda descartar.
        yaMostrada = true;
      }

      var overlay = document.getElementById('pdflexIntroOverlay');

      if (yaMostrada) {
        overlay.parentNode.removeChild(overlay);
        return;
      }

      try {
        sessionStorage.setItem(CLAVE, '1');
      } catch (error) {
        // nada que hacer — en el peor caso se vuelve a mostrar en la próxima pantalla
      }

      overlay.style.removeProperty('display');

      var logo = document.getElementById('pdflexOverlayLogo');
      var acciones = document.getElementById('pdflexOverlayAcciones');
      var eslogan = document.getElementById('pdflexOverlayEslogan');
      var boton = document.getElementById('pdflexOverlaySeguir');

      setTimeout(function () {
        logo.classList.add('volteado');
      }, 1050);
      setTimeout(function () {
        acciones.classList.add('visible');
        eslogan.classList.add('visible');
      }, 1750);

      boton.addEventListener('click', function () {
        overlay.classList.add('oculto');
        setTimeout(function () {
          overlay.style.display = 'none'; // tras el fundido, para no tapar clics
        }, 400);
      });
    })();
  </script>

  <?php
  // Recuerda esta pantalla como "donde estabas" — la pantalla de
  // Bienvenida ("/") la usa para mandarte de vuelta acá si volvés a
  // entrar al sitio con la sesión todavía activa (ver BienvenidaController
  // y Router::resolver(), que deja la ruta ya normalizada en
  // $_SERVER['PDFLEX_URI']). Se guarda justo acá — nunca en Router — porque
  // header.php solo se incluye al renderizar una pantalla real: los
  // endpoints de AJAX/descarga (estado/consultar, descargar, previsualizar)
  // no pasan por vista() y por lo tanto nunca pisan este valor con una
  // ruta que no es una pantalla de verdad.
  if (!empty($_SERVER['PDFLEX_URI'])) {
      $_SESSION['ultima_pagina'] = $_SERVER['PDFLEX_URI'];
  }
  ?>

  <header class="pdflex-topbar">
    <!-- El menú de cuenta (Ver perfil / Cerrar sesión) vive dentro del logo:
         al pasar el cursor, junto con el nombre "PDFlex" que se desliza al
         lado, este menú aparece desplegado debajo, con sombra flotante.
         En pantallas táctiles (sin cursor) el mismo estado se abre al tocar
         el ícono, con la clase "open" que alterna app.js. -->
    <div class="pdflex-logo-wrap" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false">
      <span class="pdflex-logo-clip">
        <span class="pdflex-logo-ring-outer">
          <span class="pdflex-logo-ring-inner"></span>
        </span>
      </span>
      <span class="pdflex-logo-name"><span>PDFlex</span></span>
      <div class="pdflex-logo-menu" role="menu">
        <a href="<?= BASE_URL ?>/perfil" role="menuitem">Ver perfil</a>
        <a href="<?= BASE_URL ?>/logout" role="menuitem">Cerrar sesión</a>
      </div>
    </div>

    <div class="pdflex-topbar-inner">
      <span class="pdflex-logo-spacer"></span>
      <?php
      $navItems = [
          'subir'     => ['url' => '/subir',     'texto' => 'Subir',     'icono' => '<path d="M12 16V4"></path><path d="M6 10l6-6 6 6"></path><path d="M4 20h16"></path>'],
          'historial' => ['url' => '/historial', 'texto' => 'Historial', 'icono' => '<circle cx="12" cy="12" r="8.5"></circle><path d="M12 7.5V12l3 2"></path>'],
          'estado'    => ['url' => '/estado',     'texto' => 'Estado',    'icono' => '<path d="M3 12h4l2.5-7L13 19l2.5-7H21"></path>'],
      ];
      // NUEVO (extra, oct 2026) — pestaña "Admin" solo para cuentas con
      // rol "admin" (ver AdminController::requiereAdmin(); mismo dato de
      // sesión que ya guarda AuthController::procesarLogin()). El resto de
      // usuarios ni siquiera ve el enlace, aunque la ruta igual está
      // protegida del lado del servidor por si alguien escribe /admin a mano.
      if (($_SESSION['usuario_rol'] ?? '') === 'admin') {
          $navItems['admin'] = ['url' => '/admin', 'texto' => 'Admin', 'icono' => '<path d="M4 20V13"></path><path d="M10 20V6"></path><path d="M16 20v-9"></path><path d="M3 20h18"></path>'];
      }
      ?>
      <nav class="pdflex-nav">
        <?php foreach ($navItems as $clave => $item):
            $claseActiva = ($activo ?? '') === $clave ? ' active' : '';
        ?>
          <a href="<?= BASE_URL . $item['url'] ?>" class="pdflex-navtab<?= $claseActiva ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $item['icono'] ?></svg>
            <?= $item['texto'] ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>
  </header>

  <main class="pdflex-main">

<?php else: /* layout "auth" */ ?>

  <?php if (empty($ocultarInsignia)): ?>
  <!-- Insignia fija (Login/Registro/Olvide/Restablecer) — ahora también
       es el camino de vuelta a Bienvenida (9 oct, tercer ajuste, a
       pedido de Marvin): tocarla hace lo inverso de la transición con la
       que llegamos (ver bienvenida.php): el anillo, ya completo acá, se
       agranda hasta el tamaño/posición de protagonista — SIN el spinner,
       porque ya está armado, no hace falta rearmarlo — mientras la
       tarjeta se desvanece, y recién ahí navega a "/". Bienvenida
       detecta ese "?volver=1" (ver BienvenidaController) y también se
       salta su propio spinner por la misma razón. -->
  <div class="pdflex-auth-badge position-absolute" id="pdflexAuthBadge" role="button" tabindex="0" aria-label="Volver a la pantalla de entrada" style="top:32px;left:44px;cursor:pointer;">
    <span class="pdflex-auth-ring-outer">
      <span class="pdflex-auth-ring-inner"><span>PDFlex</span></span>
    </span>
  </div>
  <script>
    (function () {
      var badge = document.getElementById('pdflexAuthBadge');
      var enCurso = false;

      function volver() {
        if (enCurso) {
          return;
        }
        enCurso = true;

        // El contenido (la tarjeta de Login/Registro, etc.) lo pinta la
        // vista después de este bloque — por eso se busca recién acá
        // adentro, no arriba: todavía no existe en el DOM cuando este
        // script corre.
        var contenido = document.getElementById('pdflexAuthContenido');
        if (contenido) {
          contenido.classList.add('pdflex-auth-contenido-saliendo');
        }

        var rectActual = badge.getBoundingClientRect();
        var centroActualX = rectActual.left + rectActual.width / 2;
        var centroActualY = rectActual.top + rectActual.height / 2;
        var centroDestinoX = window.innerWidth / 2;
        var centroDestinoY = window.innerHeight / 2;
        var anchoDestino = 150; // mismo tamaño que el anillo protagonista de Bienvenida

        var trasladoX = centroDestinoX - centroActualX;
        var trasladoY = centroDestinoY - centroActualY;
        var escala = anchoDestino / rectActual.width;

        badge.style.transform =
          'translate(' + trasladoX + 'px, ' + trasladoY + 'px) scale(' + escala + ')';

        setTimeout(function () {
          window.location.href = '<?= BASE_URL ?>/?volver=1';
        }, 600);
      }

      badge.addEventListener('click', volver);
      badge.addEventListener('keydown', function (evento) {
        if (evento.key === 'Enter' || evento.key === ' ') {
          evento.preventDefault();
          volver();
        }
      });
    })();
  </script>
  <?php endif; ?>
  <div class="d-flex align-items-center justify-content-center pdflex-auth-contenido" id="pdflexAuthContenido" style="min-height:100vh;background:var(--paper);">

<?php endif; ?>
