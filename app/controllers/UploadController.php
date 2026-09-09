<?php
/**
 * Pantalla de subida de archivos y elección de operación
 * (convertir, comprimir, unir/dividir, OCR).
 * Vista ya diseñada en el wireframe "Subir archivo" (Subida.dc.html).
 *
 * Semana 3 (s3-f2): Frontend maqueta subida + validación visual.
 * Semana 4 (s4-b1/s4-b2): Backend conecta conversión y compresión reales.
 */
class UploadController extends Controller
{
    public function index(): void
    {
        $this->vista('upload/subida');
    }

    public function procesar(): void
    {
        // TODO (Backend): mover el archivo a storage/uploads, registrar la
        // operación con Historial::registrar() (tabla `historial`), guardar
        // su ruta física con ArchivoTemporal::registrar() y disparar el
        // módulo elegido (conversión / compresión / unión-división / OCR).
        $this->redirigir('/estado');
    }
}
