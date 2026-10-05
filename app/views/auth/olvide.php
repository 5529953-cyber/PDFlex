<!-- Vista: Olvidé mi contraseña — paso 1: pedir el correo y generar un
     enlace de recuperación. Como el proyecto corre en WAMP local sin
     servidor de correo configurado, en vez de "enviar" el enlace por
     email se muestra directo en esta misma pantalla. -->
<?php $enlace = $enlace ?? null; ?>
<div class="pdflex-auth-stage pdflex-auth-stage-simple">
  <div class="pdflex-folder-tab"></div>
  <div class="pdflex-folder-body"></div>

  <div class="pdflex-simple-card">
    <div style="margin-bottom:22px;">
      <div class="pdflex-auth-title">Recuperar contraseña</div>
      <div class="pdflex-auth-subtitle">Te damos un enlace para elegir una contraseña nueva</div>
    </div>

    <?php if ($error ?? null): ?>
      <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($enlace): ?>
      <div class="alert alert-success py-2 small">
        Este es tu enlace de recuperación, válido por 1 hora. En un sistema con
        correo real este enlace se enviaría por email en vez de mostrarse aquí.
      </div>
      <div style="word-break:break-all;background:var(--surface);border:1.4px solid var(--line-strong);border-radius:10px;padding:12px 14px;font-size:13px;margin-bottom:18px;">
        <a href="<?= htmlspecialchars($enlace) ?>" style="color:var(--acento);"><?= htmlspecialchars($enlace) ?></a>
      </div>
    <?php else: ?>
      <form method="post" action="<?= BASE_URL ?>/olvide">
        <label class="pdflex-form-label" for="correo-olvide">Correo institucional</label>
        <input type="email" name="correo" id="correo-olvide" class="pdflex-field"
               placeholder="nombre@instituto.edu.sv" required>

        <button type="submit" class="pdflex-btn-primary">Enviar enlace</button>
      </form>
    <?php endif; ?>

    <div class="pdflex-auth-footnote">
      <a href="<?= BASE_URL ?>/login" class="pdflex-auth-link">Volver a Iniciar sesión</a>
    </div>
  </div>
</div>