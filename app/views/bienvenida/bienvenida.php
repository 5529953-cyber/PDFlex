<!-- Vista: Bienvenida — puerta de entrada del sitio ("/"). Antes de esto
     caía directo a Login; ahora primero se ve esta animación (mismo
     anillo doble "PDFlex" de Login/Registro, .pdflex-auth-ring-*, pero
     de protagonista: entra como un indicador de carga que se completa y
     voltea para revelarlo, en vez de aparecer ya armado) y recién
     después aparecen los botones. Qué botones mostrar lo decide
     BienvenidaController según si ya había sesión iniciada. -->
<?php
$haySesion = $haySesion ?? false;
$destino = $destino ?? '/subir';
$entradaInstantanea = $entradaInstantanea ?? false;
?>

<div class="pdflex-intro-wrap">

  <?php require __DIR__ . '/../partials/_insignia-animada.php'; ?>

  <div class="pdflex-intro-acciones" id="pdflexAcciones">
    <?php if ($haySesion): ?>
      <a href="<?= BASE_URL . htmlspecialchars($destino) ?>" class="pdflex-intro-btn pdflex-intro-btn-primario">Seguir navegando</a>
    <?php else: ?>
      <!-- data-flip-corner: marca estos dos enlaces (y solo estos) para la
           transición de salida de más abajo — "Seguir navegando" va a una
           pantalla interna (layout "app") que no tiene una insignia fija
           esperándola del otro lado, así que no lleva esta marca. -->
      <a href="<?= BASE_URL ?>/login" class="pdflex-intro-btn pdflex-intro-btn-primario" data-flip-corner="1">Iniciar sesión</a>
      <a href="<?= BASE_URL ?>/registro" class="pdflex-intro-btn pdflex-intro-btn-secundario" data-flip-corner="1">Crear cuenta</a>
    <?php endif; ?>
  </div>

  <p class="pdflex-intro-eslogan" id="pdflexEslogan">PDFs, sin fricción.</p>

</div>

<script>
  (function () {
    // Marca la misma "memoria de pestaña" que usa el overlay genérico de
    // header.php (9 oct, segundo ajuste, a pedido de Marvin): si ya se
    // vio esta animación completa acá, la primera pantalla interna a la
    // que se navegue después (Subir/Historial/Estado) no debe repetirla
    // con su propia versión. sessionStorage, no $_SESSION: dura toda la
    // pestaña y se borra sola al cerrarla.
    try {
      sessionStorage.setItem('pdflexIntroMostrada', '1');
    } catch (error) {
      // sin sessionStorage no hay nada que marcar — en el peor caso el
      // overlay genérico se muestra una vez más de la cuenta, no es grave.
    }

    var logo = document.getElementById('pdflexIntroLogo');
    var acciones = document.getElementById('pdflexAcciones');
    var eslogan = document.getElementById('pdflexEslogan');
    var spinnerArc = logo.querySelector('.pdflex-intro-spinner-arc');
    var temporizadores = [];
    var transicionDeSalidaEnCurso = false;

    // "?volver=1" (ver BienvenidaController): venimos de tocar la
    // insignia fija de Login/Registro/Olvide/Restablecer para volver acá
    // — ahí el anillo YA se agrandó completo, sin spinner, antes de
    // navegar (ver header.php), así que repetirlo desde cero acá se
    // sentiría como dos animaciones sueltas en vez de una continua.
    var entradaInstantanea = <?= $entradaInstantanea ? 'true' : 'false' ?>;

    function reproducirIntro(instantanea) {
      temporizadores.forEach(clearTimeout);
      temporizadores = [];

      transicionDeSalidaEnCurso = false;
      logo.classList.remove('volteado');
      logo.style.transform = '';
      acciones.classList.remove('visible');
      eslogan.classList.remove('visible');

      if (instantanea) {
        // Salta el spinner: el flip (volteo 3D) normalmente también
        // anima, así que se apaga su transición un instante para que el
        // anillo aparezca ya armado de una, sin rotar — coherente con
        // que ya se vio completo un segundo antes, en la pantalla
        // anterior.
        var flip = logo.querySelector('.pdflex-intro-flip');
        flip.style.transition = 'none';
        logo.classList.add('volteado');
        temporizadores.push(setTimeout(function () {
          acciones.classList.add('visible');
          eslogan.classList.add('visible');
        }, 80));
        return;
      }

      // Reinicia la animación CSS del spinner a mano: si ya había corrido
      // antes (ver más abajo, caso "volver con el navegador"), quitar y
      // volver a poner la propiedad "animation" no alcanza sola — hace
      // falta forzar un "reflow" en el medio (leer offsetWidth) para que
      // el navegador la trate como una animación nueva, no como la misma
      // que ya había terminado.
      spinnerArc.style.animation = 'none';
      void spinnerArc.offsetWidth;
      spinnerArc.style.animation = '';

      temporizadores.push(setTimeout(function () {
        logo.classList.add('volteado');
      }, 1050));
      temporizadores.push(setTimeout(function () {
        acciones.classList.add('visible');
        eslogan.classList.add('visible');
      }, 1750));
    }

    reproducirIntro(entradaInstantanea);

    // Volver con el botón "Atrás"/"Adelante" del navegador (9 oct, a
    // pedido de Marvin: la animación tiene que verse SIEMPRE, no solo la
    // primera vez): Chrome/Brave/Firefox suelen restaurar esta pantalla
    // desde una foto en memoria ("bfcache") en vez de pedirla de nuevo al
    // servidor, así que el script de arriba no se vuelve a ejecutar solo
    // y la página aparece ya con la animación terminada. El evento
    // "pageshow" con persisted=true es la señal exacta de que estamos en
    // ese caso — ahí forzamos que la animación arranque de cero otra vez.
    window.addEventListener('pageshow', function (evento) {
      if (evento.persisted) {
        // Siempre la animación completa acá, aunque esta carga haya sido
        // "instantánea": es una visita distinta (se volvió con el
        // navegador), no la misma continuidad que justificaba saltarse
        // el spinner la primera vez.
        reproducirIntro(false);
      }
    });

    // Transición de salida hacia Login/Registro (9 oct, a pedido de
    // Marvin): en vez de cortar seco hacia la otra página, los botones y
    // el eslogan se desvanecen (reutilizando la misma transición CSS que
    // ya tienen para aparecer) y el anillo central se achica y se
    // desliza hasta la posición EXACTA donde Login/Registro dibujan su
    // insignia fija de esquina (.pdflex-auth-badge, top:32px;left:44px;
    // 70px de diámetro — ver header.php). Con eso, cuando la página de
    // verdad llega, su insignia ya "está ahí" y se siente una sola
    // transición continua en vez de dos sueltas.
    //
    // La cuenta es la técnica "FLIP": se mide dónde está el anillo AHORA
    // (getBoundingClientRect) y se calcula, en línea recta, cuánto hay
    // que moverlo y achicarlo para que termine exactamente del tamaño y
    // en el lugar de la insignia de destino — esa cuenta se le pasa a
    // transform (translate + scale), y la transición la hace solo CSS
    // (ver ".pdflex-intro-logo" en style.css).
    var ESQUINA_DESTINO = { top: 32, left: 44, ancho: 70, alto: 70 };

    acciones.querySelectorAll('[data-flip-corner]').forEach(function (enlace) {
      enlace.addEventListener('click', function (evento) {
        evento.preventDefault();
        if (transicionDeSalidaEnCurso) {
          return; // ya en camino — ignora clics repetidos
        }
        transicionDeSalidaEnCurso = true;

        var destino = enlace.getAttribute('href');

        acciones.classList.remove('visible');
        eslogan.classList.remove('visible');

        var rectActual = logo.getBoundingClientRect();
        var centroActualX = rectActual.left + rectActual.width / 2;
        var centroActualY = rectActual.top + rectActual.height / 2;
        var centroDestinoX = ESQUINA_DESTINO.left + ESQUINA_DESTINO.ancho / 2;
        var centroDestinoY = ESQUINA_DESTINO.top + ESQUINA_DESTINO.alto / 2;

        var trasladoX = centroDestinoX - centroActualX;
        var trasladoY = centroDestinoY - centroActualY;
        var escala = ESQUINA_DESTINO.ancho / rectActual.width;

        logo.style.transform =
          'translate(' + trasladoX + 'px, ' + trasladoY + 'px) scale(' + escala + ')';

        temporizadores.push(setTimeout(function () {
          window.location.href = destino;
        }, 600));
      });
    });
  })();
</script>
