<!-- Vista: Restablecer contraseña — paso 2: validar el token (?token=...)
     recibido desde /olvide y permitir elegir una contraseña nueva. -->
<?php $token = $token ?? ''; ?>
<div class="pdflex-auth-stage pdflex-auth-stage-simple">
  <div class="pdflex-folder-tab"></div>
  <div class="pdflex-folder-body"></div>

  <div class="pdflex-simple-card">
    <div style="margin-bottom:22px;">
      <div class="pdflex-auth-title">Elegí tu nueva contraseña</div>
      <div class="pdflex-auth-subtitle">Convierte, comprime y procesa tus PDF</div>
    </div>

    <?php if (!($tokenValido ?? true)): ?>
      <div class="alert alert-danger py-2 small">Este enlace ya no es válido o ya venció.</div>
      <div class="pdflex-auth-footnote">
        <a href="<?= BASE_URL ?>/olvide" class="pdflex-auth-link">Solicitar un enlace nuevo</a>
      </div>
    <?php else: ?>
      <?php if ($error ?? null): ?>
        <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= BASE_URL ?>/restablecer">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <label class="pdflex-form-label" for="contrasena-nueva">Contraseña nueva</label>
        <input type="password" name="contrasena" id="contrasena-nueva" class="pdflex-field"
               placeholder="********" required minlength="8">

        <label class="pdflex-form-label" for="confirmar-nueva">Confirmar contraseña</label>
        <input type="password" name="confirmar" id="confirmar-nueva" class="pdflex-field"
               placeholder="********" required minlength="8">

        <button type="submit" class="pdflex-btn-primary">Guardar contraseña</button>
      </form>

      <div class="pdflex-auth-footnote">
        <a href="<?= BASE_URL ?>/login" class="pdflex-auth-link">Volver a Iniciar sesión</a>
      </div>
    <?php endif; ?>
  </div>
</div>