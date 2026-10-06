// PDFlex — validación visual de la pantalla de subida (Semana 3, s3-f2) +
// selector visual de páginas para Unir/Dividir (Semana 5, s5-f1).
// El envío real lo procesa Backend (UploadController::procesar, desde s3-b2
// en adelante); aquí solo validamos en el navegador antes de enviar:
// tipo de archivo (PDF), tamaño (máx. 20 MB) y que haya una operación elegida.
(function () {
  const MAX_BYTES = 20 * 1024 * 1024; // 20 MB, igual que el wireframe

  // OCR (29 sep, Marvin): Tesseract lee una imagen directo, así que para esa
  // operación también se acepta una foto/imagen suelta, sin meterla antes en
  // un PDF. Debe coincidir con UploadController::MIME_IMAGEN_PERMITIDOS /
  // EXTENSIONES_IMAGEN_PERMITIDAS — esto es solo una validación visual, la
  // validación real (la que importa) es la del servidor.
  const ACCEPT_PDF = 'application/pdf,.pdf';
  const ACCEPT_PDF_O_IMAGEN = 'application/pdf,.pdf,image/jpeg,image/png,.jpg,.jpeg,.png';
  const EXTENSIONES_IMAGEN_OCR = ['jpg', 'jpeg', 'png'];

  const form = document.getElementById('formSubida');
  if (!form) return; // esta página no es "Subir archivo"

  const dropzone = document.getElementById('dropzone');
  const dropzoneText = document.getElementById('dropzoneText');
  const dropzoneHint = document.getElementById('dropzoneHint');
  const input = document.getElementById('archivo');
  const fileRow = document.getElementById('fileRow');
  const fileNameEl = document.getElementById('fileName');
  const fileSizeEl = document.getElementById('fileSize');
  const fileRemove = document.getElementById('fileRemove');
  const fileError = document.getElementById('fileError');
  const opGrid = document.getElementById('opGrid');
  const operacionInput = document.getElementById('operacionInput');
  const compressionPanel = document.getElementById('compressionPanel');
  const conversionModal = document.getElementById('pdflexConversionModal'); // NUEVO
  const conversionCancelar = document.getElementById('pdflexConversionCancelar');
  const conversionContinuar = document.getElementById('pdflexConversionContinuar');
  const conversionCerrar = document.getElementById('pdflexConversionCerrar');
  const nivelInput = document.getElementById('nivelInput');
  const btnProcesar = document.getElementById('btnProcesar');
  const overlayEnvio = document.getElementById('pdflexSubidaOverlay');

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

    if (operacionSeleccionada === 'ocr') {
      const extension = (archivo.name.split('.').pop() || '').toLowerCase();
      const esImagen = archivo.type.startsWith('image/') || EXTENSIONES_IMAGEN_OCR.includes(extension);
      if (!esPdf && !esImagen) {
        mostrarError('Para OCR se acepta un PDF o una imagen (JPG o PNG).');
        return false;
      }
    } else if (!esPdf) {
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

  // 28 sep (Marvin): UploadController::procesar() es síncrono — la petición
  // completa (subida + LibreOffice/Ghostscript/Tesseract) tarda del lado del
  // servidor antes de responder. Con un <form> normal, el navegador se queda
  // en su propia pantalla en blanco de "cargando" durante todo ese tiempo, y
  // como Subir nunca llega a verse a sí mismo "en curso", parecía que la
  // página no había hecho nada hasta que por fin volvía (ya en /estado).
  // Por eso el envío ahora se hace con fetch(): la validación de abajo es
  // exactamente la misma que antes, solo que en vez de dejar pasar la
  // petición nativa cuando es válida, se llama a enviarConFetch() para poder
  // mostrar el overlay de "procesando" (#pdflexSubidaOverlay) mientras dura.
  form.addEventListener('submit', (e) => {
    e.preventDefault();

    let esValido;
    if (operacionSeleccionada === 'union') {
      esValido = archivosUnion.length >= 2;
    } else if (operacionSeleccionada === 'division') {
      esValido = archivoValido && paginasSeleccionadas.size > 0;
    } else {
      esValido = archivoValido && !!operacionSeleccionada;
    }

    if (!esValido) return;

    // NUEVO — "Convertir a Word" puede perder texto/imágenes en PDF con
    // imágenes flotantes o fuentes en cursiva (limitación del motor de
    // conversión, no del sistema — ver ProcesadorPDF::pdfAWord()). En vez
    // de solo avisar, se interrumpe el envío con un modal de confirmación;
    // el formulario recién se manda de verdad si el usuario elige
    // "Continuar operación" (ver abajo). Para el resto de operaciones el
    // comportamiento no cambió: se envía directo.
    if (operacionSeleccionada === 'conversion_pdf_word' && conversionModal) {
      conversionModal.hidden = false;
      return;
    }

    enviarConFetch();
  });

  if (conversionModal) {
    const cerrarModalConversion = () => {
      conversionModal.hidden = true;
    };

    conversionCancelar.addEventListener('click', cerrarModalConversion);
    conversionCerrar.addEventListener('click', cerrarModalConversion);
    conversionContinuar.addEventListener('click', () => {
      cerrarModalConversion();
      enviarConFetch();
    });
  }

  function enviarConFetch() {
    if (overlayEnvio) overlayEnvio.hidden = false;
    btnProcesar.disabled = true;

    fetch(form.action, { method: 'POST', body: new FormData(form) })
      .then((respuesta) => {
        if (respuesta.redirected) {
          // Salió bien: procesar() terminó y redirigió a /estado. Se navega
          // de verdad (no solo se reemplaza el HTML) para que la barra de
          // direcciones y el botón "Atrás" del navegador queden correctos.
          window.location.href = respuesta.url;
          return null;
        }
        // Sin redirect: procesar() volvió a mostrar este mismo formulario
        // con un error de validación del servidor (archivo inválido, etc.).
        return respuesta.text();
      })
      .then((html) => {
        if (html === null) return; // ya se navegó arriba
        document.open();
        document.write(html);
        document.close();
      })
      .catch(() => {
        if (overlayEnvio) overlayEnvio.hidden = true;
        btnProcesar.disabled = false;
        mostrarError('No se pudo conectar con el servidor. Intentá de nuevo.');
      });
  }

  // ------------------------------------------------------------------
  // s5-f1a — "Unir": varios archivos, reordenables, con miniatura real
  // de la primera página de cada uno.
  // ------------------------------------------------------------------

  function ocultarSelectorPaginas() {
    unionPanel.hidden = true;
    divisionPanel.hidden = true;
  }

  function alCambiarOperacion(anterior, actual) {
    // OCR (29 sep, Marvin): amplía qué acepta el input de archivo (y el
    // texto del dropzone) para que también se pueda subir una imagen
    // directamente; el resto de operaciones siguen siendo solo PDF.
    if (actual === 'ocr') {
      input.accept = ACCEPT_PDF_O_IMAGEN;
      if (dropzoneText) dropzoneText.textContent = 'Arrastra tu PDF o imagen aquí';
      if (dropzoneHint) dropzoneHint.textContent = 'o haz clic para seleccionar · JPG, PNG o PDF · máximo 20 MB';
    } else {
      input.accept = ACCEPT_PDF;
      if (dropzoneText) dropzoneText.textContent = 'Arrastra tu PDF aquí';
      if (dropzoneHint) dropzoneHint.textContent = 'o haz clic para seleccionar · máximo 20 MB';
    }

    // El archivo "principal" que ya estaba cargado (en modo de un solo
    // archivo, o el primero de la lista de Unir) se conserva al cambiar de
    // operación, para no obligar a resubir por elegir mal la primera vez.
    // Unir/Dividir siempre necesitan un PDF real (miniaturas con pdf.js), así
    // que si venía de OCR con una imagen cargada, no se arrastra: es más
    // seguro pedir que se vuelva a elegir el archivo.
    const archivoPrincipal = archivosUnion[0] || (archivoValido ? input.files[0] : null);
    const esPdfReal = archivoPrincipal
      && (archivoPrincipal.type === 'application/pdf' || /\.pdf$/i.test(archivoPrincipal.name));

    if (actual === 'union') {
      const archivoParaUnion = esPdfReal ? archivoPrincipal : null;
      input.multiple = true;
      input.name = 'archivos[]';
      fileRow.hidden = true;
      divisionPanel.hidden = true;
      unionPanel.hidden = false;
      archivosUnion = archivoParaUnion ? [archivoParaUnion] : [];
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

  function inicializarBotonPreview(boton) {
    boton.addEventListener('click', () => {
      abrir(boton.dataset.previewNombre || '', boton.dataset.previewOperacion || '', boton.dataset.previewSrc || '');
    });
  }

  document.querySelectorAll('[data-preview-abrir]').forEach(inicializarBotonPreview);

  // El polling de /estado (más abajo) puede insertar tarjetas nuevas con su
  // propio botón "Vista previa" después de que esta IIFE ya corrió una vez;
  // se expone el helper para que ese botón también quede conectado.
  window.pdflexInicializarBotonPreview = inicializarBotonPreview;

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

// PDFlex — Polling de /estado (pendiente de frontend, conectado 28 sep).
// EstadoController::index() solo pinta la lista de procesos UNA vez, al
// cargar la página; como no había nada que la volviera a consultar, una
// tarjeta "en_curso" (con su ícono girando por CSS) se quedaba girando
// para siempre aunque el proceso ya hubiera terminado en el servidor —
// hacía falta recargar a mano para verlo actualizado. Esto pregunta cada
// pocos segundos a EstadoController::consultar() (ya existía, sin usar) por
// cada tarjeta "en_curso" y, en cuanto deja de estarlo, reconstruye esa
// tarjeta en el DOM con el mismo HTML que ya arma estado.php en PHP.
(function () {
  const INTERVALO_MS = 4000;
  const MAX_INTENTOS = 45; // ~3 minutos; evita seguir preguntando para siempre si algo queda atascado.

  const tarjetas = document.querySelectorAll('.pdflex-estado-card.en_curso[data-consultar-url]');
  if (!tarjetas.length) return; // pantalla sin procesos en curso (o no es Estado)

  function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto == null ? '' : String(texto);
    return div.innerHTML;
  }

  function iconoPara(estado) {
    if (estado === 'completado') {
      return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M8 12.5l2.5 2.5L16 9.5"></path></svg>';
    }
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
  }

  // Reconstruye el contenido de una tarjeta con el mismo markup que
  // app/views/estado/estado.php arma en PHP para "completado"/"error" —
  // si ese HTML cambia allá, hay que reflejarlo también acá.
  function html(proceso, baseUrl) {
    const archivo = escaparHtml(proceso.archivo);
    const operacion = escaparHtml(proceso.operacion);
    const etiqueta = proceso.estado === 'completado' ? 'Completado' : 'Error';

    let subYAcciones;
    if (proceso.estado === 'completado') {
      // "Editar texto" (30 sep, Marvin): solo para OCR — ver el mismo botón
      // en app/views/estado/estado.php y EstadoController::editarOcr().
      const botonEditarOcr = proceso.tipo === 'ocr'
        ? '<a class="pdflex-estado-btn pdflex-estado-btn-outline" href="' + baseUrl + '/estado/editar-ocr?id=' + proceso.id + '">' +
          '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>' +
          'Editar texto</a>'
        : '';
      subYAcciones =
        '<div class="pdflex-estado-sub">Terminó ' + escaparHtml(proceso.terminado_hace || '') + '.</div>' +
        '<div style="display:flex;gap:8px;flex-wrap:wrap;">' +
        '<a class="pdflex-estado-btn" href="' + baseUrl + '/descargar?id=' + proceso.id + '">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12"></path><path d="M6 12l6 6 6-6"></path><path d="M5 20h14"></path></svg>' +
        'Descargar</a>' +
        '<button type="button" class="pdflex-estado-btn pdflex-estado-btn-outline" data-preview-abrir ' +
        'data-preview-nombre="' + archivo + '" data-preview-operacion="' + operacion + '" ' +
        'data-preview-src="' + baseUrl + '/previsualizar?id=' + proceso.id + '">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>' +
        'Vista previa</button>' +
        botonEditarOcr +
        '</div>';
    } else {
      subYAcciones =
        '<div class="pdflex-estado-sub">' + escaparHtml(proceso.mensaje || 'Ocurrió un error al procesar el archivo.') + '</div>' +
        '<a class="pdflex-estado-btn pdflex-estado-btn-outline" href="' + baseUrl + '/reintentar?id=' + proceso.id + '">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.6-6.4"></path><path d="M21 4v5h-5"></path></svg>' +
        'Reintentar</a>';
    }

    return (
      '<div class="pdflex-estado-row">' +
      '<div class="pdflex-estado-info">' +
      '<span class="pdflex-estado-icon">' + iconoPara(proceso.estado) + '</span>' +
      '<span class="pdflex-estado-nombre">' + archivo + '</span>' +
      '<span class="pdflex-estado-separador">·</span>' +
      '<span class="pdflex-estado-operacion">' + operacion + '</span>' +
      '</div>' +
      '<span class="pdflex-estado-label">' + etiqueta + '</span>' +
      '</div>' +
      subYAcciones
    );
  }

  function actualizarTarjeta(tarjeta, proceso) {
    const url = tarjeta.dataset.consultarUrl;
    const baseUrl = url.replace(/\/estado\/consultar.*$/, '');

    tarjeta.classList.remove('en_curso');
    tarjeta.classList.add(proceso.estado);
    tarjeta.removeAttribute('data-historial-id');
    tarjeta.removeAttribute('data-consultar-url');
    tarjeta.innerHTML = html(proceso, baseUrl);

    const botonPreview = tarjeta.querySelector('[data-preview-abrir]');
    if (botonPreview && window.pdflexInicializarBotonPreview) {
      window.pdflexInicializarBotonPreview(botonPreview);
    }
  }

  tarjetas.forEach((tarjeta) => {
    const url = tarjeta.dataset.consultarUrl;
    let intentos = 0;

    const intervalo = setInterval(() => {
      intentos++;
      if (intentos > MAX_INTENTOS) {
        clearInterval(intervalo);
        return;
      }

      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then((respuesta) => respuesta.json())
        .then((proceso) => {
          if (proceso && proceso.estado && proceso.estado !== 'en_curso') {
            clearInterval(intervalo);
            actualizarTarjeta(tarjeta, proceso);
          }
        })
        .catch(() => {
          // Si falla una consulta puntual (red, etc.) simplemente se reintenta
          // en el próximo intervalo; no hace falta romper el polling por eso.
        });
    }, INTERVALO_MS);
  });
})();