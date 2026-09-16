<!-- Partial compartido por auth/login.php y auth/registro.php: la tarjeta
     "carpeta" que se voltea (animación 3D) entre Iniciar sesión y Crear
     cuenta. Viene del artifact aparte "PDFlex — Login carpeta" (11 sep
     2026) — un diseño distinto y más elaborado que el que se había
     aplicado antes al Login, con silueta de carpeta detrás de la tarjeta
     y volteo en vez de pestañas planas.

     Ambos formularios (login y registro) están siempre presentes en el
     DOM, uno en cada cara de la tarjeta; el volteo es solo visual (JS,
     ver app.js) y cada formulario sigue apuntando a su propia ruta real
     (/login, /registro), así que funciona sin importar cuál cara esté
     visible al enviar.

     Variables esperadas en scope: $flipInicial (bool — true = arranca
     mostrando la cara de "Crear cuenta"), $errorLogin, $errorRegistro. -->
<?php
$flipInicial = $flipInicial ?? false;
$errorLogin = $errorLogin ?? null;
$errorRegistro = $errorRegistro ?? null;
?>
<div class="pdflex-auth-stage">
  <div class="pdflex-folder-tab"></div>
  <div class="pdflex-folder-body"></div>

  <div class="pdflex-flip-scene">
    <div class="pdflex-flip-inner<?= $flipInicial ? ' flipped' : '' ?>" id="pdflexFlip">

      <!-- cara frontal: Iniciar sesión -->
      <div class="pdflex-flip-face front">
        <div style="margin-bottom:22px;">
          <div class="pdflex-auth-title">Bienvenido a PDFlex</div>
          <div class="pdflex-auth-subtitle">Convierte, comprime y procesa tus PDF</div>
        </div>

        <div style="display:flex;gap:10px;margin-bottom:24px;justify-content:center;">
          <span class="pdflex-pill">Iniciar sesión</span>
          <button type="button" class="pdflex-pill pdflex-pill-outline" data-flip="back" aria-controls="pdflexFlip">Crear cuenta</button>
        </div>

        <?php if ($errorLogin): ?>
          <div class="alert alert-danger py-2 small"><?= htmlspecialchars($errorLogin) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/login">
          <label class="pdflex-form-label" for="correo-login">Correo institucional</label>
          <input type="email" name="correo" id="correo-login" class="pdflex-field"
                 placeholder="nombre@instituto.edu.sv" required>

          <label class="pdflex-form-label" for="contrasena-login">Contraseña</label>
          <input type="password" name="contrasena" id="contrasena-login" class="pdflex-field"
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

      <!-- cara trasera: Crear cuenta (revelada al "abrir la carpeta") -->
      <div class="pdflex-flip-face back">
        <div style="margin-bottom:22px;">
          <div class="pdflex-auth-title">Crea tu cuenta</div>
          <div class="pdflex-auth-subtitle">Convierte, comprime y procesa tus PDF</div>
        </div>

        <div style="display:flex;gap:10px;margin-bottom:24px;justify-content:center;">
          <button type="button" class="pdflex-pill pdflex-pill-outline" data-flip="front" aria-controls="pdflexFlip">Iniciar sesión</button>
          <span class="pdflex-pill">Crear cuenta</span>
        </div>

        <?php if ($errorRegistro): ?>
          <div class="alert alert-danger py-2 small"><?= htmlspecialchars($errorRegistro) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/registro">
          <label class="pdflex-form-label" for="nombre-registro">Nombre completo</label>
          <input type="text" name="nombre" id="nombre-registro" class="pdflex-field"
                 placeholder="Tu nombre completo" required>

          <label class="pdflex-form-label" for="correo-registro">Correo institucional</label>
          <input type="email" name="correo" id="correo-registro" class="pdflex-field"
                 placeholder="nombre@instituto.edu.sv" required>

          <label class="pdflex-form-label" for="contrasena-registro">Contraseña</label>
          <input type="password" name="contrasena" id="contrasena-registro" class="pdflex-field"
                 placeholder="********" required minlength="8">

          <label class="pdflex-form-label" for="confirmar-registro">Confirmar contraseña</label>
          <input type="password" name="confirmar" id="confirmar-registro" class="pdflex-field"
                 placeholder="********" required minlength="8">

          <button type="submit" class="pdflex-btn-primary">Crear cuenta</button>
        </form>

        <div class="pdflex-auth-footnote">
          Acceso para estudiantes, docentes y personal administrativo del instituto.
        </div>
      </div>

    </div>
  </div>
</div>
