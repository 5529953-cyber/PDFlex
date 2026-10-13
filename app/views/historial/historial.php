<!-- Vista: Historial de conversiones — según solicitud "Historial de
     conversiones" (14 sep 2026): título/subtítulo fuera de tarjeta, buscador +
     filtro, tabla de 5 columnas coloreada por estado. $registros llega del
     controlador (HistorialController::index), como array de filas de la
     tabla `historial` (ver app/models/Historial.php).

     El buscador sigue siendo un formulario real (GET, filtra en el backend
     por nombre de archivo) para que funcione también sin JavaScript y cubra
     resultados fuera de la página actual. Encima de eso, el script al final
     de este archivo filtra en vivo, letra por letra, las filas que ya están
     en pantalla (sin pedir nada al servidor), para que no haya que presionar
     Enter cada vez; al perder el foco o presionar Enter, el formulario se
     sigue enviando normalmente como respaldo. -->
<!-- cambio: filtrado en vivo del buscador del historial, pedido 12/10/2026 -->
<?php
$registros = $registros ?? [];

// Traducción de tipo_operacion (valores reales usados en app/views/upload/subida.php,
// incluido "union"/"division" ya separados) al texto que pide la solicitud de Historial.
// No cubierto explícitamente por ninguna solicitud: se infiere del mismo texto usado
// en la pantalla "Subir".
$etiquetaOperacion = [
    'conversion_pdf_word'   => 'Convertir a Word',
    'conversion_pdf_imagen' => 'Convertir a imagen',
    'compresion'            => 'Comprimir',
    'union'                 => 'Unir / Dividir',
    'division'              => 'Unir / Dividir',
    'union_division'        => 'Unir / Dividir', // compatibilidad con el valor anterior
    'ocr'                   => 'Extraer texto (OCR)',
];

// Estado -> clase visual. La solicitud solo define "Completado" (Tono 1) y
// "Error" (Tono 6); "pendiente"/"procesando" no tienen tratamiento propio en
// ese documento, así que usan un tono neutro hasta que el equipo confirme si
// quiere uno específico.
$estadoInfo = [
    'completado' => ['clase' => 'exito',   'texto' => 'Completado'],
    'error'      => ['clase' => 'error',   'texto' => 'Error'],
    'procesando' => ['clase' => 'neutral', 'texto' => 'Procesando'],
    'pendiente'  => ['clase' => 'neutral', 'texto' => 'Pendiente'],
];
?>

<div class="pdflex-hist-title">Historial de conversiones</div>
<div class="pdflex-hist-subtitle">Todo lo que has procesado en tu cuenta.</div>

<form method="get" action="<?= BASE_URL ?>/historial" class="pdflex-hist-toolbar">
  <label class="pdflex-hist-search">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9A9086" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
    <input type="text" id="pdflexHistBusqueda" name="busqueda" placeholder="Buscar por nombre de archivo" value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>" autocomplete="off">
  </label>
  <select name="operacion" class="pdflex-hist-filter" onchange="this.form.submit()">
    <option value="">Todas las operaciones</option>
    <?php foreach (['conversion_pdf_word' => 'Convertir a Word', 'conversion_pdf_imagen' => 'Convertir a imagen', 'compresion' => 'Comprimir', 'union' => 'Unir', 'division' => 'Dividir', 'ocr' => 'Extraer texto (OCR)'] as $valor => $texto): ?>
      <option value="<?= $valor ?>" <?= ($_GET['operacion'] ?? '') === $valor ? 'selected' : '' ?>><?= $texto ?></option>
    <?php endforeach; ?>
  </select>
</form>

<div class="pdflex-hist-tablecard">
  <div class="pdflex-hist-row pdflex-hist-head">
    <div>Archivo</div>
    <div>Operación</div>
    <div>Fecha</div>
    <div>Estado</div>
    <div>Acción</div>
  </div>

  <div class="pdflex-hist-body" id="pdflexHistBody">
    <?php if (empty($registros)): ?>
      <div class="pdflex-hist-empty">Aún no has procesado ningún archivo.</div>
    <?php else: ?>
      <div class="pdflex-hist-empty" id="pdflexHistSinCoincidencias" style="display:none;">No hay archivos que coincidan con tu búsqueda.</div>
    <?php endif; ?>
    <?php if (!empty($registros)): ?>
      <?php foreach ($registros as $registro):
          $tipo = $registro['tipo_operacion'] ?? '';
          $estado = $registro['estado'] ?? 'pendiente';
          $info = $estadoInfo[$estado] ?? $estadoInfo['pendiente'];
          // Fecha a mostrar: fecha_fin si ya terminó, si no fecha_inicio.
          $fecha = $registro['fecha_fin'] ?? $registro['fecha_inicio'] ?? null;
          $fechaTexto = $fecha ? date('d/m/Y · H:i', strtotime($fecha)) : '—';
      ?>
        <div class="pdflex-hist-row pdflex-hist-data <?= $info['clase'] ?>">
          <div class="pdflex-hist-file"><?= htmlspecialchars($registro['nombre_archivo_original'] ?? '—') ?></div>
          <div><?= htmlspecialchars($etiquetaOperacion[$tipo] ?? $tipo) ?></div>
          <div><?= htmlspecialchars($fechaTexto) ?></div>
          <div><?= htmlspecialchars($info['texto']) ?></div>
          <div class="pdflex-hist-action">
                        <?php if ($estado === 'completado'): ?>
              <a href="<?= BASE_URL ?>/descargar?id=<?= (int) ($registro['id'] ?? 0) ?>" style="display:contents;color:inherit;text-decoration:none;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12"></path><path d="M6 12l6 6 6-6"></path><path d="M5 20h14"></path></svg>
                Descargar
              </a>
            <?php elseif ($estado === 'error'): ?>
              <a href="<?= BASE_URL ?>/reintentar?id=<?= (int) ($registro['id'] ?? 0) ?>" style="display:contents;color:inherit;text-decoration:none;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.6-6.4"></path><path d="M21 4v5h-5"></path></svg>
                Reintentar
              </a>
            <?php else: ?>
              <span style="font-weight:400;">En proceso…</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($registros)): ?>
<script>
// Filtrado en vivo del historial: a cada letra, oculta/muestra las filas
// ya presentes en la página comparando con el nombre de archivo. No hace
// ninguna petición al servidor — el formulario de arriba sigue funcionando
// tal cual (Enter o quitar el foco) como respaldo con o sin JavaScript.
(function () {
  try {
    var campo = document.getElementById('pdflexHistBusqueda');
    var cuerpo = document.getElementById('pdflexHistBody');
    var sinCoincidencias = document.getElementById('pdflexHistSinCoincidencias');
    if (!campo || !cuerpo) return;

    var filas = Array.prototype.slice.call(cuerpo.querySelectorAll('.pdflex-hist-data'));

    function normalizar(texto) {
      return (texto || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '');
    }

    function filtrar() {
      var consulta = normalizar(campo.value.trim());
      var visibles = 0;

      filas.forEach(function (fila) {
        var nombreEl = fila.querySelector('.pdflex-hist-file');
        var nombre = normalizar(nombreEl ? nombreEl.textContent : '');
        var coincide = consulta === '' || nombre.indexOf(consulta) !== -1;
        fila.style.display = coincide ? '' : 'none';
        if (coincide) visibles++;
      });

      if (sinCoincidencias) {
        sinCoincidencias.style.display = (visibles === 0 && consulta !== '') ? '' : 'none';
      }
    }

    campo.addEventListener('input', filtrar);
    filtrar();
  } catch (e) {
    // Si algo falla, el buscador sigue funcionando por el envío normal del formulario.
  }
})();
</script>
<?php endif; ?>
