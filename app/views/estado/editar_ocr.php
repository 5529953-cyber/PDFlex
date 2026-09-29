<!-- Vista: Editar texto de OCR (30 sep, Marvin) — para un OCR ya
     completado, deja corregir a mano el texto que reconoció Tesseract
     (guardado en el .txt "hermano" del PDF, ver
     ProcesadorPDF::rutaSidecarTexto()) y volver a generar el mismo PDF de
     resultado con ese texto corregido, sin re-procesar la imagen/PDF
     original. Ver EstadoController::editarOcr() (GET, llena esta vista) y
     EstadoController::guardarOcr() (POST, adonde apunta el formulario). -->
<?php
$id = $id ?? 0;
$archivo = $archivo ?? '';
$texto = $texto ?? '';
?>

<div class="pdflex-page-head">
  <h1 class="pdflex-page-title">Editar texto reconocido</h1>
  <p class="pdflex-page-subtitle">
    Corregí lo que haga falta y guardá para regenerar el PDF de
    "<?= htmlspecialchars($archivo) ?>" con el texto ya corregido.
  </p>
</div>

<div class="pdflex-card">
  <form method="post" action="<?= BASE_URL ?>/estado/editar-ocr">
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <textarea name="texto" class="pdflex-ocr-textarea" rows="16"><?= htmlspecialchars($texto) ?></textarea>

    <div class="pdflex-ocr-acciones">
      <button type="submit" class="pdflex-btn-primary pdflex-btn-procesar pdflex-ocr-guardar">
        Guardar y regenerar PDF
      </button>
      <a href="<?= BASE_URL ?>/estado" class="pdflex-estado-btn pdflex-estado-btn-secundario" style="margin-top:0;">
        Cancelar
      </a>
    </div>
  </form>
</div>
