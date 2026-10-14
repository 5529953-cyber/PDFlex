<!-- Vista: Panel de administrador — estadísticas de uso de TODO el sistema
     (no solo del usuario logueado), a pedido de la solicitud "Panel de
     administrador con estadísticas de uso, para justificar el proyecto ante
     el instituto" (ver Proyecto: PDFlex_Anteproyecto.docx, sección 6). Solo
     entran acá cuentas con rol "admin" — ver AdminController::requiereAdmin().

     Mismo lenguaje visual que el resto del panel (.pdflex-card, tipografía,
     paleta mostaza/beige) en vez de meter una librería de gráficos nueva:
     las "barras" son simples <div> con ancho/alto en % ya calculado en PHP
     por AdminController — no hay JS ni canvas acá, así que tampoco hace
     falta tocar app.js. -->
<?php
$totalUsuarios      = $totalUsuarios ?? 0;
$totalOperaciones   = $totalOperaciones ?? 0;
$completadas        = $completadas ?? 0;
$conError           = $conError ?? 0;
$enCurso            = $enCurso ?? 0;
$tasaExito          = $tasaExito ?? 0;
$porOperacion       = $porOperacion ?? [];
$porRol             = $porRol ?? [];
$masActivos         = $masActivos ?? [];
$actividad          = $actividad ?? [];
$ahorroCompresionMb = $ahorroCompresionMb ?? 0;

// Mismo texto que ya usa perfil.php para el rol de la propia cuenta — acá
// para la columna "Usuarios con más actividad" del ranking.
$rolTexto = [
    'estudiante'     => 'Estudiante',
    'docente'        => 'Docente',
    'administrativo' => 'Administrativo',
    'admin'          => 'Administrador',
];

$hayActividad = array_sum(array_column($actividad, 'cantidad')) > 0;
?>

<div class="pdflex-page-head">
  <h1 class="pdflex-page-title">Panel de administrador</h1>
  <p class="pdflex-page-subtitle">Estadísticas de uso de todo el sistema, no solo de tu cuenta.</p>
</div>

<div class="pdflex-admin-tiles">
  <div class="pdflex-admin-tile">
    <div class="pdflex-admin-tile-valor"><?= number_format($totalUsuarios) ?></div>
    <div class="pdflex-admin-tile-etiqueta">Usuarios registrados</div>
  </div>
  <div class="pdflex-admin-tile">
    <div class="pdflex-admin-tile-valor"><?= number_format($totalOperaciones) ?></div>
    <div class="pdflex-admin-tile-etiqueta">Operaciones procesadas<?= $enCurso > 0 ? ' (' . number_format($enCurso) . ' en curso)' : '' ?></div>
  </div>
  <div class="pdflex-admin-tile">
    <div class="pdflex-admin-tile-valor"><?= $tasaExito ?>%</div>
    <div class="pdflex-admin-tile-etiqueta">Tasa de éxito — <?= number_format($completadas) ?> completadas<?= $conError > 0 ? ', ' . number_format($conError) . ' con error' : '' ?></div>
  </div>
  <div class="pdflex-admin-tile">
    <div class="pdflex-admin-tile-valor"><?= number_format($ahorroCompresionMb, 1) ?> MB</div>
    <div class="pdflex-admin-tile-etiqueta">Ahorrados al comprimir</div>
  </div>
</div>

<div class="pdflex-admin-cols">
  <div class="pdflex-card">
    <div class="pdflex-admin-card-title">Operaciones por tipo</div>
    <?php if (empty($porOperacion)): ?>
      <p class="pdflex-admin-vacio">Todavía no hay operaciones registradas.</p>
    <?php else: ?>
      <div class="pdflex-admin-barras">
        <?php foreach ($porOperacion as $fila): ?>
          <div class="pdflex-admin-barra-fila">
            <div class="pdflex-admin-barra-etiqueta"><?= htmlspecialchars($fila['etiqueta']) ?></div>
            <div class="pdflex-admin-barra-pista">
              <div class="pdflex-admin-barra-relleno" style="width:<?= (int) $fila['porcentaje'] ?>%;"></div>
            </div>
            <div class="pdflex-admin-barra-valor"><?= number_format($fila['cantidad']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="pdflex-card">
    <div class="pdflex-admin-card-title">Usuarios con más actividad</div>
    <?php if (empty($masActivos)): ?>
      <p class="pdflex-admin-vacio">Todavía no hay operaciones registradas.</p>
    <?php else: ?>
      <div class="pdflex-admin-ranking">
        <?php foreach ($masActivos as $posicion => $fila): ?>
          <div class="pdflex-admin-ranking-fila">
            <span class="pdflex-admin-ranking-num"><?= $posicion + 1 ?></span>
            <div class="pdflex-admin-ranking-info">
              <div class="pdflex-admin-ranking-nombre"><?= htmlspecialchars($fila['nombre']) ?></div>
              <div class="pdflex-admin-ranking-rol"><?= htmlspecialchars($rolTexto[$fila['rol']] ?? $fila['rol']) ?></div>
            </div>
            <span class="pdflex-admin-ranking-total"><?= number_format((int) $fila['total']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="pdflex-admin-cols">
  <div class="pdflex-card">
    <div class="pdflex-admin-card-title">Cuentas por rol</div>
    <div class="pdflex-admin-barras">
      <?php foreach ($porRol as $fila): ?>
        <div class="pdflex-admin-barra-fila">
          <div class="pdflex-admin-barra-etiqueta"><?= htmlspecialchars($fila['etiqueta']) ?></div>
          <div class="pdflex-admin-barra-pista">
            <div class="pdflex-admin-barra-relleno pdflex-admin-barra-relleno-rol" style="width:<?= (int) $fila['porcentaje'] ?>%;"></div>
          </div>
          <div class="pdflex-admin-barra-valor"><?= number_format($fila['cantidad']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="pdflex-card">
    <div class="pdflex-admin-card-title">Actividad de los últimos <?= count($actividad) ?> días</div>
    <?php if (!$hayActividad): ?>
      <p class="pdflex-admin-vacio">Sin operaciones en este período.</p>
    <?php else: ?>
      <div class="pdflex-admin-dias" role="img" aria-label="Operaciones por día en los últimos <?= count($actividad) ?> días">
        <?php foreach ($actividad as $dia): ?>
          <div class="pdflex-admin-dia" title="<?= htmlspecialchars($dia['etiqueta']) ?>: <?= (int) $dia['cantidad'] ?>">
            <div class="pdflex-admin-dia-barra" style="height:<?= (int) $dia['porcentaje'] ?>%;"></div>
            <div class="pdflex-admin-dia-etiqueta"><?= htmlspecialchars($dia['etiqueta']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
