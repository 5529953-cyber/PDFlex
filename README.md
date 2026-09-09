# PDFlex

Aplicación web para procesamiento de PDF (conversión, compresión, unión/división, OCR) — proyecto de instituto.

## Estructura MVC

```
PDFlex/
├── public/                 ← raíz pública (document root del servidor)
│   ├── index.php           ← front controller: único punto de entrada
│   ├── test_db.php         ← prueba rápida de conexión a la BD
│   ├── .htaccess           ← reescribe todo hacia index.php
│   └── assets/             ← css, js, imágenes
├── app/
│   ├── controllers/        ← un controlador por pantalla (Auth, Upload, Historial, Estado)
│   ├── models/              ← Usuario, Historial, ArchivoTemporal (acceso a datos vía PDO)
│   ├── views/                ← una vista por pantalla, agrupadas por controlador
│   │   └── layouts/           ← header/footer compartidos
│   └── core/                  ← Router, Controller base, Database (PDO)
├── config/
│   ├── config.example.php     ← plantilla de config general (BASE_URL) — sí va a Git
│   ├── database.example.php   ← plantilla de credenciales de BD — sí va a Git
│   ├── config.php             ← copia local (NO va a Git)
│   └── database.php           ← copia local con credenciales reales (NO va a Git)
├── database/
│   └── schema.sql              ← estructura de la BD (de Xavier), se sincroniza por Git
└── storage/                     ← uploads/processed/temp (tampoco van a Git)
```

## Primeros pasos (Semana 2)

1. Copiar `config/config.example.php` → `config/config.php`.
2. Copiar `config/database.example.php` → `config/database.php` y poner ahí tus credenciales locales de MySQL.
3. Importar `database/schema.sql` en tu phpMyAdmin local.
4. Servir `/public` como document root en WampServer (o apuntar el navegador a `http://localhost/PDFlex/public`).
5. Confirmar que `/public/test_db.php` responde "Conexión exitosa", y luego que `/login` carga.

## Flujo de navegación

| Ruta                | Controlador → método                  | Pantalla (wireframe)     |
|----------------------|------------------------------------------|---------------------------|
| `/login`             | `AuthController::login`                  | Login                     |
| `/registro`          | `AuthController::registro`               | Login (pestaña "Crear cuenta") |
| `/subir`             | `UploadController::index`                | Subir archivo             |
| `/historial`         | `HistorialController::index`             | Historial                 |
| `/estado`            | `EstadoController::index`                | Estado de procesos        |
| `/estado/consultar`  | `EstadoController::consultar`            | (AJAX — progreso en vivo) |

Todo pasa por `public/index.php`, que arma el `Router` y despacha al controlador correspondiente. Cada controlador carga su vista a través de `Controller::vista()`, que envuelve el contenido con `layouts/header.php` y `layouts/footer.php`.

## Modelo de datos (diseño de Xavier — ver Proyecto: `claude/diseno-base-datos.md`)

- **usuarios** — cuentas del sistema (id, nombre, correo, contrasena, rol, fecha_registro).
- **historial** — cada operación ejecutada (conversión/compresión/unión/división/OCR), con su estado. 1 usuario → N historial.
- **archivos_temporales** — rastro de archivos físicos (subidos y generados) para el borrado automático. 1 historial → N archivos_temporales.

Los PDF en sí **no** se guardan en la base de datos, solo metadatos; el archivo va a `storage/`.

`Database::getConnection()` (en `app/core/Database.php`) lee `config/database.php` y devuelve el PDO ya listo para usar en los modelos.

## Convención de trabajo

- **Frontend (Marvin):** `app/views/`, `public/assets/`, estructura general y flujo de navegación.
- **Backend (Xavier):** lógica real dentro de los controladores marcada con `// TODO (Backend, ...)`, `database/schema.sql`, `app/core/Database.php`.
- Los `TODO` en el código apuntan a la semana del cronograma donde corresponde resolverlos.
