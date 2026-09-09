<?php
/**
 * Header compartido. Dos modos, elegidos por Controller::vista($ruta, $datos, $layout):
 *  - "app"  (por defecto): pantallas internas — barra superior + sidebar (Subir/Historial/Estado).
 *  - "auth": login/registro — sin sidebar, solo la marca arriba a la izquierda
 *            (el maquetado de la tarjeta de login se hace en la Semana 3, s3-f1).
 *
 * $activo (opcional, solo layout "app"): 'subir' | 'historial' | 'estado' — resalta el ítem del sidebar.
 */
$layout = $layout ?? 'app';

$logoSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
    . '<path d="M6 3h9l5 5v12a1.3 1.3 0 0 1-1.3 1.3H6A1.3 1.3 0 0 1 4.7 20V4.3A1.3 1.3 0 0 1 6 3z"></path>'
    . '<path d="M15 3v5h5z" fill="currentColor" stroke="none"></path>'
    . '</svg>';
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
    <div class="d-flex align-items-center gap-2">
      <span class="pdflex-badge"><?= $logoSvg ?></span>
      <span class="pdflex-logo">PDFlex</span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="pdflex-avatar"><?= strtoupper(substr($_SESSION['usuario']['nombre'] ?? 'U', 0, 1)) ?></span>
      <span class="text-body-secondary small"><?= htmlspecialchars($_SESSION['usuario']['nombre'] ?? 'Usuario') ?></span>
      <a href="<?= BASE_URL ?>/logout" title="Cerrar sesión" class="text-body-secondary d-flex">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>
      </a>
    </div>
  </header>

  <div class="pdflex-shell">
    <nav class="pdflex-sidebar">
      <?php
      $navItems = [
          'subir'     => ['url' => '/subir',     'texto' => 'Subir',     'icono' => '<path d="M12 16V4"></path><path d="M6 10l6-6 6 6"></path><path d="M4 20h16"></path>'],
          'historial' => ['url' => '/historial', 'texto' => 'Historial', 'icono' => '<circle cx="12" cy="12" r="8.5"></circle><path d="M12 7.5V12l3 2"></path>'],
          'estado'    => ['url' => '/estado',     'texto' => 'Estado',    'icono' => '<path d="M3 12h4l2.5-7L13 19l2.5-7H21"></path>'],
      ];
      foreach ($navItems as $clave => $item):
          $claseActiva = ($activo ?? '') === $clave ? ' active' : '';
      ?>
        <a href="<?= BASE_URL . $item['url'] ?>" class="pdflex-navitem<?= $claseActiva ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $item['icono'] ?></svg>
          <?= $item['texto'] ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <main class="pdflex-main">

<?php else: /* layout "auth" */ ?>

  <div class="pdflex-auth-badge position-absolute top-0 start-0 m-4 d-flex align-items-center gap-2">
    <span class="pdflex-badge"><?= $logoSvg ?></span>
    <span class="pdflex-logo">PDFlex</span>
  </div>
  <div class="d-flex align-items-center justify-content-center" style="min-height:100vh;">

<?php endif; ?>
