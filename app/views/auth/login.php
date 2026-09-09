<!-- Vista: Login — ver wireframe "Login" (Main.dc.html / MainMobile.dc.html)
     Maquetado con Bootstrap 5 — Semana 3 (s3-f1). La lógica real de
     autenticación (procesarLogin) es Backend, s3-b1. -->
<?php $error = $error ?? null; ?>

<div class="pdflex-auth-card">

  <div class="d-flex align-items-center gap-3 mb-1">
    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="var(--ink)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
      <path d="M7 3h6l4 4v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"></path>
      <path d="M13 3v4h4"></path>
    </svg>
    <div>
      <div class="pdflex-auth-title">Bienvenido a PDFlex</div>
      <div class="pdflex-auth-subtitle">Convierte, comprime y procesa tus PDF</div>
    </div>
  </div>

  <div class="pdflex-auth-tabs">
    <a href="<?= BASE_URL ?>/login" class="pdflex-auth-tab active">Iniciar sesión</a>
    <a href="<?= BASE_URL ?>/registro" class="pdflex-auth-tab">Crear cuenta</a>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>/login">
    <label class="pdflex-form-label" for="correo">Correo institucional</label>
    <input type="email" name="correo" id="correo" class="pdflex-form-control"
           placeholder="nombre@instituto.edu.sv" required>

    <label class="pdflex-form-label" for="contrasena">Contraseña</label>
    <input type="password" name="contrasena" id="contrasena" class="pdflex-form-control"
           placeholder="********" required>

    <div class="d-flex justify-content-between align-items-center pdflex-auth-row">
      <label class="pdflex-checkbox-label">
        <input type="checkbox" name="recordarme" value="1"> Recordarme
      </label>
      <a href="#" class="pdflex-auth-link">¿Olvidaste tu contraseña?</a>
    </div>

    <button type="submit" class="pdflex-btn-primary">Entrar</button>
  </form>

  <div class="pdflex-auth-footnote">
    Acceso para estudiantes, docentes y personal administrativo del instituto.
  </div>
</div>
