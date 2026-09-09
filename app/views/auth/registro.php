<!-- Vista: Crear cuenta — mismo wireframe que login (pestaña "Crear cuenta"), Main.dc.html.
     Maquetado con Bootstrap 5 — Semana 3 (s3-f1). La lógica real de
     registro (procesarRegistro) es Backend, s3-b1. -->
<?php $error = $error ?? null; ?>

<div class="pdflex-auth-card">

  <div class="d-flex align-items-center gap-3 mb-1">
    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="var(--ink)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
      <path d="M7 3h6l4 4v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"></path>
      <path d="M13 3v4h4"></path>
    </svg>
    <div>
      <div class="pdflex-auth-title">Crea tu cuenta</div>
      <div class="pdflex-auth-subtitle">Convierte, comprime y procesa tus PDF</div>
    </div>
  </div>

  <div class="pdflex-auth-tabs">
    <a href="<?= BASE_URL ?>/login" class="pdflex-auth-tab">Iniciar sesión</a>
    <a href="<?= BASE_URL ?>/registro" class="pdflex-auth-tab active">Crear cuenta</a>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>/registro">
    <label class="pdflex-form-label" for="nombre">Nombre completo</label>
    <input type="text" name="nombre" id="nombre" class="pdflex-form-control"
           placeholder="Tu nombre completo" required>

    <label class="pdflex-form-label" for="correo">Correo institucional</label>
    <input type="email" name="correo" id="correo" class="pdflex-form-control"
           placeholder="nombre@instituto.edu.sv" required>

    <label class="pdflex-form-label" for="contrasena">Contraseña</label>
    <input type="password" name="contrasena" id="contrasena" class="pdflex-form-control"
           placeholder="********" required minlength="8">

    <label class="pdflex-form-label" for="confirmar">Confirmar contraseña</label>
    <input type="password" name="confirmar" id="confirmar" class="pdflex-form-control"
           placeholder="********" required minlength="8">

    <button type="submit" class="pdflex-btn-primary mt-1">Crear cuenta</button>
  </form>

  <div class="pdflex-auth-footnote">
    Acceso para estudiantes, docentes y personal administrativo del instituto.
  </div>
</div>
