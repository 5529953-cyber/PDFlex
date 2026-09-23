// PDFlex — validación visual de la pantalla de subida (Semana 3, s3-f2) +
// selector visual de páginas para Unir/Dividir (Semana 5, s5-f1).
// El envío real lo procesa Backend (UploadController::procesar, desde s3-b2
// en adelante); aquí solo validamos en el navegador antes de enviar:
// tipo de archivo (PDF), tamaño (máx. 20 MB) y que haya una operación elegida.
(function () {
  const MAX_BYTES = 20 * 1024 * 1024; // 20 MB, igual que el wireframe

  const form = document.getElementById('formSubida');
  if (!form) return; // esta página no es "Subir archivo"

  const dropzone = document.getElementById('dropzone');
  const input = document.getElementById('archivo');
  const fileRow = document.getElementById('fileRow');
  const fileNameEl = document.getElementById('fileName');
  const fileSizeEl = document.getElementById('fileSize');
  const fileRemove = document.getElementById('fileRemove');
  const fileError = document.getElementById('fileError');
  const opGrid = document.getElementById('opGrid');
  const operacionInput = document.getElementById('operacionInput');
  const compressionPanel = document.getElementById('compressionPanel');
  const nivelInput = document.getElementById('nivelInput');
  const btnProcesar = document.getElementById('btnProcesar');

  // s5-f1: selector visual de páginas (Unir / Dividir)
  const unionPanel = document.getElementById('unionPanel');
  const unionItems = document.getElementById('unionItems');
  const unionAdd = document.getElementById('unionAdd');
  const unionHint = document.getElementById('unionHint');
  const divisionPanel = document.getElementById('divisionPanel');
  const divisionGrid = document.getElementById('divisionGrid');
  const divisionHint = document.getElementById('divisionHint');
  const divisionTodas = document.getElementById('divisionTodas');
  const divisionNinguna = document.getElementById('divisionNinguna');
  const paginasInput = document.getElementById('paginasInput');
  const tienePdfJs = typeof window.pdfjsLib !== 'undefined';

  let archivoValido = false;
  let operacionSeleccionada = '';
  let archivosUnion = []; // File[] — orden = orden de unión
  let paginasSeleccionadas = new Set(); // números de página (1-based) para dividir

  function formatoTamano(bytes) {
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }

  function mostrarError(msg) {
    fileError.textContent = msg;
    fileError.hidden = false;
  }

  function limpiarError() {
    fileError.hidden = true;
    fileError.textContent = '';
  }

  function actualizarBoton() {
    if (operacionSeleccionada === 'union') {
      btnProcesar.disabled = archivosUnion.length < 2;
    } else if (operacionSeleccionada === 'division') {
      btnProcesar.disabled = !(archivoValido && paginasSeleccionadas.size > 0);
    } else {
      btnProcesar.disabled = !(archivoValido && operacionSeleccionada);
    }
  }

  function validarArchivo(archivo) {
    if (!archivo) return false;
    const esPdf = archivo.type === 'application/pdf' || /\.pdf$/i.test(archivo.name);
    if (!esPdf) {
      mostrarError('Solo se aceptan archivos PDF.');
      return false;
    }
    if (archivo.size > MAX_BYTES) {
      mostrarError('El archivo supera el máximo de 20 MB.');
      return false;
    }
    return true;
  }

  function mostrarArchivo(archivo) {
    fileNameEl.textContent = archivo.name;
    fileSizeEl.textContent = formatoTamano(archivo.size);
    fileRow.hidden = false;
  }

  function quitarArchivo() {
    input.value = '';
    fileRow.hidden = true;
    archivoValido = false;
    limpiarError();
    ocultarSelectorPaginas();
    actualizarBoton();
  }

  function manejarArchivoUnico(archivo) {
    limpiarError();
    archivoValido = validarArchivo(archivo);
    fileRow.hidden = !archivoValido;
    if (archivoValido) {
      mostrarArchivo(archivo);
      if (operacionSeleccionada === 'division') cargarPaginasDivision(archivo);
    }
    actualizarBoton();
  }

  input.addEventListener('change', () => {
    if (operacionSeleccionada === 'union') {
      agregarArchivosUnion(Array.from(input.files || []));
    } else {
      manejarArchivoUnico(input.files[0]);
    }
  });
  fileRemove.addEventListener('click', quitarArchivo);

  ['dragenter', 'dragover'].forEach((evento) => {
    dropzone.addEventListener(evento, (e) => {
      e.preventDefault();
      dropzone.classList.add('dragover');
    });
  });
  ['dragleave', 'drop'].forEach((evento) => {
    dropzone.addEventListener(evento, (e) => {
      e.preventDefault();
      dropzone.classList.remove('dragover');
    });
  });
  dropzone.addEventListener('drop', (e) => {
    const archivos = Array.from(e.dataTransfer.files || []);
    if (!archivos.length) return;
    if (operacionSeleccionada === 'union') {
      agregarArchivosUnion(archivos);
    } else {
      input.files = e.dataTransfer.files;
      manejarArchivoUnico(archivos[0]);
    }
  });

  opGrid.addEventListener('click', (e) => {
    const tarjeta = e.target.closest('.pdflex-opcard');
    if (!tarjeta) return;
    opGrid.querySelectorAll('.pdflex-opcard').forEach((el) => el.classList.remove('selected'));
    tarjeta.classList.add('selected');
    const operacionAnterior = operacionSeleccionada;
    operacionSeleccionada = tarjeta.dataset.op;
    operacionInput.value = operacionSeleccionada;
    compressionPanel.hidden = operacionSeleccionada !== 'compresion';
    alCambiarOperacion(operacionAnterior, operacionSeleccionada);
    actualizarBoton();
  });

  compressionPanel.addEventListener('click', (e) => {
    const boton = e.target.closest('.pdflex-level-btn');
    if (!boton) return;
    compressionPanel.querySelectorAll('.pdflex-level-btn').forEach((el) => el.classList.remove('selected'));
    boton.classList.add('selected');
    nivelInput.value = boton.dataset.nivel;
  });

  form.addEventListener('submit', (e) => {
    if (operacionSeleccionada === 'union') {
      if (archivosUnion.length < 2) e.preventDefault();
      return;
    }
    if (operacionSeleccionada === 'division' && !(archivoValido && paginasSeleccionadas.size > 0)) {
      e.preventDefault();
      return;
    }
    if (!archivoValido || !operacionSeleccionada) {
      e.preventDefault();
    }
  });

  // ------------------------------------------------------------------
  // s5-f1a — "Unir": varios archivos, reordenables, con miniatura real
  // de la primera página de cada uno.
  // ------------------------------------------------------------------

  function ocultarSelectorPaginas() {
    unionPanel.hidden = true;
    divisionPanel.hidden = true;
  }

  function alCambiarOperacion(anterior, actual) {
    // El archivo "principal" que ya estaba cargado (en modo de un solo
    // archivo, o el primero de la lista de Unir) se conserva al cambiar de
    // operación, para no obligar a resubir por elegir mal la primera vez.
    const archivoPrincipal = archivosUnion[0] || (archivoValido ? input.files[0] : null);

    if (actual === 'union') {
      input.multiple = true;
      input.name = 'archivos[]';
      fileRow.hidden = true;
      divisionPanel.hidden = true;
      unionPanel.hidden = false;
      archivosUnion = archivoPrincipal ? [archivoPrincipal] : [];
      sincronizarInputUnion();
      renderizarUnion();
    } else {
      input.multiple = false;
      input.name = 'archivo';
      unionPanel.hidden = true;
      if (archivoPrincipal) {
        const dt = new DataTransfer();
        dt.items.add(archivoPrincipal);
        input.files = dt.files;
        archivoValido = validarArchivo(archivoPrincipal);
        if (archivoValido) mostrarArchivo(archivoPrincipal);
      }
      archivosUnion = [];
      if (actual === 'division') {
        divisionPanel.hidden = false;
        if (archivoPrincipal && archivoValido) cargarPaginasDivision(archivoPrincipal);
      } else {
        divisionPanel.hidden = true;
      }
    }
  }

  function sincronizarInputUnion() {
    const dt = new DataTransfer();
    archivosUnion.forEach((archivo) => dt.items.add(archivo));
    input.files = dt.files;
  }

  function agregarArchivosUnion(nuevos) {
    limpiarError();
    const validos = [];
    nuevos.forEach((archivo) => {
      const yaEsta = archivosUnion.some((a) => a.name === archivo.name && a.size === archivo.size);
      if (yaEsta) return;
      if (!validarArchivo(archivo)) return;
      validos.push(archivo);
    });
    archivosUnion = archivosUnion.concat(validos);
    sincronizarInputUnion();
    renderizarUnion();
    actualizarBoton();
  }

  function quitarArchivoUnion(indice) {
    archivosUnion.splice(indice, 1);
    sincronizarInputUnion();
    renderizarUnion();
    actualizarBoton();
  }

  function moverArchivoUnion(indice, delta) {
    const destino = indice + delta;
    if (destino < 0 || destino >= archivosUnion.length) return;
    const [archivo] = archivosUnion.splice(indice, 1);
    archivosUnion.splice(destino, 0, archivo);
    sincronizarInputUnion();
    renderizarUnion();
  }

  let indiceArrastrado = null;

  function renderizarUnion() {
    unionItems.innerHTML = '';

    if (archivosUnion.length === 0) {
      unionHint.textContent = 'Agrega al menos dos PDF para unirlos, en el orden en que quieres que queden.';
    } else if (archivosUnion.length === 1) {
      unionHint.textContent = 'Agrega al menos un PDF más para poder unir.';
    } else {
      unionHint.textContent = archivosUnion.length + ' archivos listos, en este orden.';
    }

    archivosUnion.forEach((archivo, indice) => {
      const item = document.createElement('div');
      item.className = 'pdflex-union-item';
      item.draggable = true;
      item.dataset.indice = String(indice);

      item.innerHTML =
        '<span class="pdflex-union-drag" aria-hidden="true">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="8" cy="6" r="1"></circle><circle cx="8" cy="12" r="1"></circle><circle cx="8" cy="18" r="1"></circle><circle cx="16" cy="6" r="1"></circle><circle cx="16" cy="12" r="1"></circle><circle cx="16" cy="18" r="1"></circle></svg>' +
        '</span>' +
        '<canvas class="pdflex-union-thumb" width="44" height="58"></canvas>' +
        '<div class="pdflex-union-info">' +
        '<div class="pdflex-union-name"></div>' +
        '<div class="pdflex-union-size"></div>' +
        '</div>' +
        '<div class="pdflex-union-order-btns">' +
        '<button type="button" data-subir aria-label="Mover arriba">' +
        '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"></path></svg>' +
        '</button>' +
        '<button type="button" data-bajar aria-label="Mover abajo">' +
        '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"></path></svg>' +
        '</button>' +
        '</div>' +
        '<button type="button" class="pdflex-union-remove" aria-label="Quitar archivo">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"></path></svg>' +
        '</button>';

      item.querySelector('.pdflex-union-name').textContent = archivo.name;
      item.querySelector('.pdflex-union-size').textContent = formatoTamano(archivo.size);

      const botonSubir = item.querySelector('[data-subir]');
      const botonBajar = item.querySelector('[data-bajar]');
      botonSubir.disabled = indice === 0;
      botonBajar.disabled = indice === archivosUnion.length - 1;
      botonSubir.addEventListener('click', () => moverArchivoUnion(indice, -1));
      botonBajar.addEventListener('click', () => moverArchivoUnion(indice, 1));
      item.querySelector('.pdflex-union-remove').addEventListener('click', () => quitarArchivoUnion(indice));

      item.addEventListener('dragstart', () => {
        indiceArrastrado = indice;
        item.classList.add('pdflex-dragging');
      });
      item.addEventListener('dragend', () => item.classList.remove('pdflex-dragging'));
      item.addEventListener('dragover', (e) => {
        e.preventDefault();
        item.classList.add('pdflex-drop-target');
      });
      item.addEventListener('dragleave', () => item.classList.remove('pdflex-drop-target'));
      item.addEventListener('drop', (e) => {
        e.preventDefault();
        item.classList.remove('pdflex-drop-target');
        if (indiceArrastrado === null || indiceArrastrado === indice) return;
        const [archivoMovido] = archivosUnion.splice(indiceArrastrado, 1);
        archivosUnion.splice(indice, 0, archivoMovido);
        indiceArrastrado = null;
        sincronizarInputUnion();
        renderizarUnion();
      });

      unionItems.appendChild(item);

      if (tienePdfJs) {
        renderizarMiniatura(archivo, item.querySelector('canvas'), 1);
      }
    });
  }

  unionAdd.addEventListener('click', () => input.click());

  // ------------------------------------------------------------------
  // s5-f1b — "Dividir": una cuadrícula con todas las páginas del archivo,
  // el usuario marca cuáles quiere incluir en el resultado.
  // ------------------------------------------------------------------

  function actualizarInputPaginas() {
    paginasInput.value = Array.from(paginasSeleccionadas).sort((a, b) => a - b).join(',');
  }

  function alternarPagina(numero, tile) {
    if (paginasSeleccionadas.has(numero)) {
      paginasSeleccionadas.delete(numero);
      tile.classList.remove('seleccionada');
    } else {
      paginasSeleccionadas.add(numero);
      tile.classList.add('seleccionada');
    }
    actualizarInputPaginas();
    actualizarBoton();
  }

  async function cargarPaginasDivision(archivo) {
    divisionGrid.innerHTML = '';
    paginasSeleccionadas.clear();
    actualizarInputPaginas();

    if (!tienePdfJs) {
      divisionHint.textContent = 'No se pudieron cargar las miniaturas de página en este navegador.';
      return;
    }

    divisionHint.textContent = 'Cargando páginas…';

    try {
      const bufer = await archivo.arrayBuffer();
      const pdf = await pdfjsLib.getDocument({ data: bufer }).promise;

      for (let numero = 1; numero <= pdf.numPages; numero++) {
        const tile = document.createElement('div');
        tile.className = 'pdflex-division-page';

        const canvas = document.createElement('canvas');
        tile.appendChild(canvas);

        const check = document.createElement('span');
        check.className = 'pdflex-division-check';
        check.innerHTML = '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"></path></svg>';
        tile.appendChild(check);

        const etiqueta = document.createElement('div');
        etiqueta.className = 'pdflex-division-page-num';
        etiqueta.textContent = 'Pág. ' + numero;
        tile.appendChild(etiqueta);

        tile.addEventListener('click', () => alternarPagina(numero, tile));
        divisionGrid.appendChild(tile);

        renderizarPaginaPdf(pdf, numero, canvas);
      }

      divisionHint.textContent = 'Selecciona las páginas que quieres incluir en el resultado.';
    } catch (err) {
      divisionHint.textContent = 'No se pudo leer este PDF para mostrar sus páginas.';
    }
  }

  divisionTodas.addEventListener('click', () => {
    divisionGrid.querySelectorAll('.pdflex-division-page').forEach((tile, indice) => {
      paginasSeleccionadas.add(indice + 1);
      tile.classList.add('seleccionada');
    });
    actualizarInputPaginas();
    actualizarBoton();
  });

  divisionNinguna.addEventListener('click', () => {
    divisionGrid.querySelectorAll('.pdflex-division-page').forEach((tile) => tile.classList.remove('seleccionada'));
    paginasSeleccionadas.clear();
    actualizarInputPaginas();
    actualizarBoton();
  });

  // ------------------------------------------------------------------
  // Helpers de render compartidos (pdf.js)
  // ------------------------------------------------------------------

  async function renderizarPaginaPdf(pdf, numeroPagina, canvas) {
    const pagina = await pdf.getPage(numeroPagina);
    const viewportBase = pagina.getViewport({ scale: 1 });
    const escala = 90 / viewportBase.width;
    const viewport = pagina.getViewport({ scale: escala });
    canvas.width = viewport.width;
    canvas.height = viewport.height;
    await pagina.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
  }

  async function renderizarMiniatura(archivo, canvas, numeroPagina) {
    if (!tienePdfJs) return;
    try {
      const bufer = await archivo.arrayBuffer();
      const pdf = await pdfjsLib.getDocument({ data: bufer }).promise;
      await renderizarPaginaPdf(pdf, numeroPagina, canvas);
    } catch (err) {
      // Si el PDF no se puede leer (dañado, protegido), se deja el
      // recuadro vacío en vez de romper el resto del selector.
    }
  }
})();

// PDFlex — menú del logo (Ver perfil / Cerrar sesión) en el header del
// panel principal. Además de :hover/:focus-within (ya cubiertos en CSS,
// para cursor y teclado), acá se alterna la clase "open" al tocar/hacer
// clic en el ícono, para que funcione igual en teléfonos, que no tienen
// cursor y solo cuentan con touch.
(function () {
  const wrap = document.querySelector('.pdflex-logo-wrap');
  if (!wrap) return; // pantalla sin logo con menú (p. ej. login/registro)

  function cerrar() {
    wrap.classList.remove('open');
    wrap.setAttribute('aria-expanded', 'false');
  }

  function alternar() {
    const abierto = wrap.classList.toggle('open');
    wrap.setAttribute('aria-expanded', abierto ? 'true' : 'false');
  }

  wrap.addEventListener('click', (e) => {
    e.stopPropagation();
    alternar();
  });

  wrap.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      alternar();
    }
  });

  document.addEventListener('click', (e) => {
    if (!wrap.contains(e.target)) cerrar();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') cerrar();
  });
})();

// PDFlex — volteo de la tarjeta "carpeta" en Login/Registro (artifact
// "PDFlex — Login carpeta"). Los botones [data-flip] cambian qué cara se
// ve sin recargar la página; cada formulario sigue apuntando a su propia
// ruta real, así que enviar cualquiera de los dos funciona sin importar
// qué cara esté visible en ese momento.
(function () {
  const flip = document.getElementById('pdflexFlip');
  if (!flip) return; // pantalla sin tarjeta "carpeta"

  const caras = flip.querySelectorAll('.pdflex-flip-face');

  function actualizarAccesibilidad() {
    const mostrandoBack = flip.classList.contains('flipped');
    caras.forEach((cara) => {
      const esVisible = cara.classList.contains('back') === mostrandoBack;
      cara.setAttribute('aria-hidden', esVisible ? 'false' : 'true');
      cara.querySelectorAll('input, button, a, select, textarea').forEach((el) => {
        el.tabIndex = esVisible ? 0 : -1;
      });
    });
  }

  document.querySelectorAll('[data-flip]').forEach((boton) => {
    boton.addEventListener('click', () => {
      flip.classList.toggle('flipped', boton.dataset.flip === 'back');
      actualizarAccesibilidad();
    });
  });

  actualizarAccesibilidad();
})();

// PDFlex — modal de "Vista previa" en Estado (s4-f2). Una sola instancia
// compartida por todas las tarjetas "Completado": cada botón [data-preview-abrir]
// trae el nombre/operación/URL de SU proceso en data-attributes, y este
// script los coloca en el modal (incluida la URL del iframe) antes de
// mostrarlo. La URL real la sirve el backend (HistorialController::previsualizar()).
(function () {
  const overlay = document.getElementById('pdflexPreviewOverlay');
  if (!overlay) return; // pantalla sin modal de vista previa (no es Estado)

  const modal = overlay.querySelector('.pdflex-preview-modal');
  const nombreEl = document.getElementById('pdflexPreviewNombre');
  const operacionEl = document.getElementById('pdflexPreviewOperacion');
  const botonCerrar = document.getElementById('pdflexPreviewCerrar');
  const frameEl = document.getElementById('pdflexPreviewFrame');

  function abrir(nombre, operacion, src) {
    nombreEl.textContent = nombre;
    operacionEl.textContent = operacion;
    if (frameEl && src) frameEl.src = src;
    overlay.hidden = false;
    botonCerrar.focus();
  }

  function cerrar() {
    overlay.hidden = true;
    if (frameEl) frameEl.src = ''; // corta la carga del archivo al cerrar
  }

  document.querySelectorAll('[data-preview-abrir]').forEach((boton) => {
    boton.addEventListener('click', () => {
      abrir(boton.dataset.previewNombre || '', boton.dataset.previewOperacion || '', boton.dataset.previewSrc || '');
    });
  });

  botonCerrar.addEventListener('click', cerrar);

  // Clic fuera de la tarjeta (sobre el fondo oscuro) también cierra.
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) cerrar();
  });

  modal.addEventListener('click', (e) => e.stopPropagation());

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !overlay.hidden) cerrar();
  });
})();