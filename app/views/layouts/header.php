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
 */
$layout = $layout ?? 'app';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PDFlex</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Work+Sans:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<?php if ($layout === 'app'): ?>

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
        <a href="#" role="menuitem">Ver perfil</a>
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

  <div class="pdflex-auth-badge position-absolute" style="top:32px;left:44px;">
    <span class="pdflex-auth-ring-outer">
      <span class="pdflex-auth-ring-inner"><span>PDFlex</span></span>
    </span>
  </div>
  <div class="d-flex align-items-center justify-content-center" style="min-height:100vh;background:var(--paper);">

<?php endif; ?>
