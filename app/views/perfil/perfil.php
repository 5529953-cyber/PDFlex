<!-- Vista: Perfil — datos de la cuenta (nombre/correo, editables) y cambio
     de contraseña. Mismo lenguaje visual que el resto del panel a propósito
     (misma tipografía, misma paleta, mismos campos/botones que ya usan
     Login y "Editar texto reconocido"): una sola tarjeta (.pdflex-card),
     dividida en secciones con una línea simple en vez de agregar más
     tarjetas o colores nuevos. Se llega acá desde el menú de cuenta del
     logo (header.php, "Ver perfil"). -->
<?php
$usuario = $usuario ?? [];
$totalCompletados = $totalCompletados ?? 0;

$rolTexto = [
    'estudiante'     => 'Estudiante',
    'docente'        => 'Docente',
    'administrativo' => 'Administrativo',
    'admin'          => 'Administrador',
];

$inicial = mb_strtoupper(mb_substr($usuario['nombre'] ?? '?', 0, 1));
$miembroDesde = !empty($usuario['fecha_registro'])
    ? date('d/m/Y', strtotime($usuario['fecha_registro']))
    : '—';
?>

<div class="pdflex-page-head">
  <h1 class="pdflex-page-title">Tu perfil</h1>
  <p class="pdflex-page-subtitle">Tus datos de cuenta y accesos.</p>
</div>

<div class="pdflex-card">
<div style="max-width:520px;margin:0 auto;">

  <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
    <span class="pdflex-perfil-avatar"><?= htmlspecialchars($inicial) ?></span>
    <div>
      <div style="font-weight:600;"><?= htmlspecialchars($usuario['nombre'] ?? '') ?></div>
      <div style="font-size:12.5px;color:var(--ink-dim);">
        <?= htmlspecialchars($rolTexto[$usuario['rol'] ?? ''] ?? 'Estudiante') ?> · Miembro desde <?= htmlspecialchars($miembroDesde) ?>
      </div>
    </div>
  </div>

  <div style="font-size:12.5px;color:var(--ink-dim);margin-bottom:22px;">
    Procesaste <strong style="color:var(--ink);"><?= (int) $totalCompletados ?></strong>
    archivo<?= $totalCompletados === 1 ? '' : 's' ?> hasta ahora.
    <a href="<?= BASE_URL ?>/historial" class="pdflex-auth-link">Ver historial</a>
  </div>

  <hr style="border:none;border-top:1px solid var(--line);margin:0 0 20px;">

  <div style="font-weight:600;font-size:13.5px;margin-bottom:14px;">Información de la cuenta</div>

  <?php if ($errorPerfil ?? null): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($errorPerfil) ?></div>
  <?php endif; ?>
  <?php if ($exitoPerfil ?? null): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($exitoPerfil) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>/perfil">
    <label class="pdflex-form-label" for="perfil-nombre">Nombre completo</label>
    <input type="text" name="nombre" id="perfil-nombre" class="pdflex-field"
           value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" required>

    <label class="pdflex-form-label" for="perfil-correo">Correo institucional</label>
    <input type="email" name="correo" id="perfil-correo" class="pdflex-field"
           value="<?= htmlspecialchars($usuario['correo'] ?? '') ?>" required>

    <button type="submit" class="pdflex-btn-primary pdflex-perfil-guardar">Guardar cambios</button>
  </form>

  <hr style="border:none;border-top:1px solid var(--line);margin:24px 0 20px;">

  <div style="font-weight:600;font-size:13.5px;margin-bottom:14px;">Cambiar contraseña</div>

  <?php if ($errorContrasena ?? null): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($errorContrasena) ?></div>
  <?php endif; ?>
  <?php if ($exitoContrasena ?? null): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($exitoContrasena) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>/perfil/contrasena">
    <label class="pdflex-form-label" for="perfil-actual">Contraseña actual</label>
    <input type="password" name="actual" id="perfil-actual" class="pdflex-field"
           placeholder="********" required>

    <label class="pdflex-form-label" for="perfil-nueva">Contraseña nueva</label>
    <input type="password" name="nueva" id="perfil-nueva" class="pdflex-field"
           placeholder="********" required minlength="8">

    <label class="pdflex-form-label" for="perfil-confirmar">Confirmar contraseña nueva</label>
    <input type="password" name="confirmar" id="perfil-confirmar" class="pdflex-field"
           placeholder="********" required minlength="8">

    <button type="submit" class="pdflex-btn-primary pdflex-perfil-guardar">Actualizar contraseña</button>
  </form>

</div>
</div>
