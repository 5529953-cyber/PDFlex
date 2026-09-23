<!-- Vista: Estado de procesos — según mockup "Estado de tus procesos"
     (14 sep 2026): una tarjeta por archivo, coloreada según su estado
     (en curso / completado / error), con la acción correspondiente.
     $procesos llega del controlador (EstadoController::index).
     Incluye el modal de "Vista previa" (s4-f2), con estilos propios
     (inline) para no depender de clases CSS externas. -->
<?php $procesos = $procesos ?? []; ?>

<div class="pdflex-estado-title">Estado de tus procesos</div>
<div class="pdflex-estado-subtitle">Sigue el avance de lo que estás procesando en este momento.</div>

<div class="pdflex-estado-list">
  <?php if (empty($procesos)): ?>
    <div class="pdflex-estado-empty">No tienes procesos en este momento.</div>
  <?php else: ?>
    <?php foreach ($procesos as $proceso):
        $estado = $proceso['estado'] ?? 'en_curso';
    ?>
      <div class="pdflex-estado-card <?= htmlspecialchars($estado) ?>">
        <div class="pdflex-estado-row">
          <div class="pdflex-estado-info">
            <span class="pdflex-estado-icon">
              <?php if ($estado === 'en_curso'): ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M21 12a9 9 0 1 1-9-9"></path></svg>
              <?php elseif ($estado === 'completado'): ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M8 12.5l2.5 2.5L16 9.5"></path></svg>
              <?php else: ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
              <?php endif; ?>
            </span>
            <span class="pdflex-estado-nombre"><?= htmlspecialchars($proceso['archivo'] ?? '') ?></span>
            <span class="pdflex-estado-separador">·</span>
            <span class="pdflex-estado-operacion"><?= htmlspecialchars($proceso['operacion'] ?? '') ?></span>
          </div>

          <?php if ($estado === 'en_curso'): ?>
            <span class="pdflex-estado-badge">En curso</span>
          <?php elseif ($estado === 'completado'): ?>
            <span class="pdflex-estado-label">Completado</span>
          <?php else: ?>
            <span class="pdflex-estado-label">Error</span>
          <?php endif; ?>
        </div>

        <?php if ($estado === 'en_curso'): ?>
          <?php if (isset($proceso['progreso'])): ?>
            <div class="pdflex-estado-progress">
              <div class="pdflex-estado-progress-fill" style="width: <?= (int) $proceso['progreso'] ?>%;"></div>
            </div>
            <div class="pdflex-estado-sub"><?= (int) $proceso['progreso'] ?>% · tiempo estimado restante: <?= htmlspecialchars($proceso['restante'] ?? '—') ?></div>
          <?php else: ?>
            <div class="pdflex-estado-sub">Procesando…</div>
          <?php endif; ?>

        <?php elseif ($estado === 'completado'): ?>
          <div class="pdflex-estado-sub">Terminó <?= htmlspecialchars($proceso['terminado_hace'] ?? '') ?>.</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a class="pdflex-estado-btn" href="<?= BASE_URL ?>/descargar?id=<?= (int) ($proceso['id'] ?? 0) ?>">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12"></path><path d="M6 12l6 6 6-6"></path><path d="M5 20h14"></path></svg>
              Descargar
            </a>
            <button
              type="button"
              class="pdflex-estado-btn pdflex-estado-btn-outline"
              data-preview-abrir
              data-preview-nombre="<?= htmlspecialchars($proceso['archivo'] ?? '') ?>"
              data-preview-operacion="<?= htmlspecialchars($proceso['operacion'] ?? '') ?>"
              data-preview-src="<?= BASE_URL ?>/previsualizar?id=<?= (int) ($proceso['id'] ?? 0) ?>"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              Vista previa
            </button>
          </div>

        <?php else: ?>
          <div class="pdflex-estado-sub"><?= htmlspecialchars($proceso['mensaje'] ?? '') ?></div>
          <a class="pdflex-estado-btn pdflex-estado-btn-outline" href="<?= BASE_URL ?>/reintentar?id=<?= (int) ($proceso['id'] ?? 0) ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.6-6.4"></path><path d="M21 4v5h-5"></path></svg>
            Reintentar
          </a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Modal de Vista previa (s4-f2). Estilos inline a propósito: así no
     depende de ninguna clase CSS externa y funciona apenas se pega. -->
<div id="pdflexPreviewOverlay" hidden style="position:fixed;inset:0;background:rgba(20,16,12,0.55);display:flex;align-items:center;justify-content:center;z-index:1000;padding:20px;">
  <div class="pdflex-preview-modal" style="background:#fff;border-radius:12px;max-width:760px;width:100%;max-height:85vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-bottom:1px solid #eee;">
      <div style="min-width:0;">
        <div id="pdflexPreviewNombre" style="font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>
        <div id="pdflexPreviewOperacion" style="font-size:13px;color:#888;"></div>
      </div>
      <button type="button" id="pdflexPreviewCerrar" aria-label="Cerrar vista previa" style="background:none;border:none;cursor:pointer;padding:6px;color:#555;flex-shrink:0;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"></path></svg>
      </button>
    </div>
    <iframe id="pdflexPreviewFrame" title="Vista previa del archivo" style="border:0;flex:1;width:100%;min-height:420px;background:#f5f5f5;"></iframe>
  </div>
</div>