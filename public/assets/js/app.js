// PDFlex — validación visual de la pantalla de subida (Semana 3, s3-f2).
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

  let archivoValido = false;
  let operacionSeleccionada = '';

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
    btnProcesar.disabled = !(archivoValido && operacionSeleccionada);
  }

  function validarArchivo(archivo) {
    limpiarError();
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
    actualizarBoton();
  }

  function manejarArchivo(archivo) {
    archivoValido = validarArchivo(archivo);
    fileRow.hidden = !archivoValido;
    if (archivoValido) mostrarArchivo(archivo);
    actualizarBoton();
  }

  input.addEventListener('change', () => manejarArchivo(input.files[0]));
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
    const archivo = e.dataTransfer.files[0];
    if (archivo) {
      input.files = e.dataTransfer.files;
      manejarArchivo(archivo);
    }
  });

  opGrid.addEventListener('click', (e) => {
    const tarjeta = e.target.closest('.pdflex-opcard');
    if (!tarjeta) return;
    opGrid.querySelectorAll('.pdflex-opcard').forEach((el) => el.classList.remove('selected'));
    tarjeta.classList.add('selected');
    operacionSeleccionada = tarjeta.dataset.op;
    operacionInput.value = operacionSeleccionada;
    compressionPanel.hidden = operacionSeleccionada !== 'compresion';
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
    if (!archivoValido || !operacionSeleccionada) {
      e.preventDefault();
    }
  });
})();
