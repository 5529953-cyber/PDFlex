<!-- Vista: Subir archivo — ver wireframe "Subir archivo" (Subida.dc.html / SubidaMobile.dc.html)
     Maquetado con Bootstrap 5 + validación visual en JS — Semana 3 (s3-f2).
     El procesamiento real (mover archivo, registrar en `historial`, disparar
     el módulo elegido) es Backend: s3-b2 en adelante (s4-b1/b2 conversión y
     compresión, s5-b1 unión/división, s5-b2 OCR). -->
<?php $error = $error ?? null; ?>

<div class="pdflex-page-head">
  <h1 class="pdflex-page-title">Subir un archivo</h1>
  <p class="pdflex-page-subtitle">Sube un PDF para convertirlo, comprimirlo, unirlo/dividirlo o extraer su texto.</p>
</div>

<?php if ($error): ?>
  <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= BASE_URL ?>/subir" enctype="multipart/form-data" id="formSubida" class="pdflex-upload-grid">

  <div class="pdflex-upload-col">
    <label class="pdflex-dropzone" id="dropzone" for="archivo">
      <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <path d="M7 18a4.5 4.5 0 0 1-1-8.9A5.5 5.5 0 0 1 17 8.5a4 4 0 0 1-1 7.5"></path>
        <path d="M12 12v7"></path>
        <path d="M9.5 15.5L12 13l2.5 2.5"></path>
      </svg>
      <div class="pdflex-dropzone-text">Arrastra tu PDF aquí</div>
      <div class="pdflex-dropzone-hint">o haz clic para seleccionar · máximo 20 MB</div>
    </label>
    <input type="file" name="archivo" id="archivo" accept="application/pdf,.pdf" class="visually-hidden" required>

    <div class="pdflex-file-row" id="fileRow" hidden>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l4 4v14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"></path><path d="M14 3v4h4"></path></svg>
      <div class="pdflex-file-info">
        <div class="pdflex-file-name" id="fileName">—</div>
        <div class="pdflex-file-size" id="fileSize">—</div>
      </div>
      <button type="button" class="pdflex-file-remove" id="fileRemove" aria-label="Quitar archivo">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"></path></svg>
      </button>
    </div>

    <div class="pdflex-file-error" id="fileError" hidden></div>
  </div>

  <div class="pdflex-upload-col">
    <div class="pdflex-op-label">¿Qué quieres hacer con este archivo?</div>
    <div class="pdflex-op-grid" id="opGrid">
      <button type="button" class="pdflex-opcard" data-op="conversion_pdf_word">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="1"></rect><path d="M8 9h5M8 13h8M8 17h6"></path></svg>
        Convertir a Word
      </button>
      <button type="button" class="pdflex-opcard" data-op="conversion_pdf_imagen">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="5" width="16" height="14" rx="1"></rect><circle cx="9" cy="10" r="1.4"></circle><path d="M5 16l4-4 4 4 3-3 3 3"></path></svg>
        Convertir a imagen
      </button>
      <button type="button" class="pdflex-opcard" data-op="compresion">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4l4 4 4-4"></path><path d="M8 20l4-4 4 4"></path><path d="M12 8v8"></path></svg>
        Comprimir
      </button>
      <button type="button" class="pdflex-opcard" data-op="union_division">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="7" height="16" rx="1"></rect><rect x="13" y="4" width="7" height="16" rx="1"></rect></svg>
        Unir / Dividir
      </button>
      <button type="button" class="pdflex-opcard" data-op="ocr">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="1"></rect><path d="M8 9h8M8 12.5h5"></path></svg>
        Extraer texto (OCR)
      </button>
    </div>
    <input type="hidden" name="operacion" id="operacionInput">

    <div class="pdflex-compression-panel" id="compressionPanel" hidden>
      <div class="pdflex-compression-label">Nivel de compresión</div>
      <div class="pdflex-level-group">
        <button type="button" class="pdflex-level-btn" data-nivel="baja">Baja</button>
        <button type="button" class="pdflex-level-btn selected" data-nivel="media">Media</button>
        <button type="button" class="pdflex-level-btn" data-nivel="alta">Alta</button>
      </div>
      <input type="hidden" name="nivel_compresion" id="nivelInput" value="media">
    </div>

    <button type="submit" class="pdflex-btn-primary" id="btnProcesar" disabled>Procesar archivo</button>
  </div>

</form>
