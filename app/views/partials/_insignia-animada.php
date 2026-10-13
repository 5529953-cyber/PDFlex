<?php
/**
 * Insignia animada de PDFlex — spinner que se completa y voltea como una
 * corcholata para revelar el anillo doble "PDFlex" (mismo que usan
 * Login/Registro). Compartida entre dos lugares:
 *  - app/views/bienvenida/bienvenida.php: la pantalla de entrada del
 *    sitio ("/"), donde termina mostrando botones.
 *  - app/views/layouts/header.php: la pantalla de carga que se ve un
 *    instante arriba de CUALQUIER otra pantalla del sistema y se oculta
 *    sola, sin botones.
 * $idLogo identifica el elemento para que el script de cada lugar se
 * enganche a su propia copia (puede haber, como mucho, una por página).
 */
$idLogo = $idLogo ?? 'pdflexIntroLogo';
?>
<div class="pdflex-intro-logo" id="<?= htmlspecialchars($idLogo) ?>">
  <div class="pdflex-intro-flip">
    <div class="pdflex-intro-cara pdflex-intro-cara-frontal">
      <svg class="pdflex-intro-spinner" viewBox="0 0 140 140" width="150" height="150" aria-hidden="true">
        <circle class="pdflex-intro-spinner-track" cx="70" cy="70" r="55"></circle>
        <circle class="pdflex-intro-spinner-arc" cx="70" cy="70" r="55"></circle>
      </svg>
    </div>
    <div class="pdflex-intro-cara pdflex-intro-cara-trasera">
      <span class="pdflex-auth-ring-outer pdflex-intro-ring-outer">
        <span class="pdflex-auth-ring-inner pdflex-intro-ring-inner"><span>PDFlex</span></span>
      </span>
    </div>
  </div>
</div>
