-- ============================================================
-- PDFlex - Estructura de base de datos
-- Motor: MySQL (InnoDB, utf8mb4)
-- Autor: Xavier Stanly Carranza Méndez (Backend)
--
-- Este archivo SOLO define la estructura (sin datos).
-- Se sincroniza vía GitHub: cada integrante lo reimporta en su
-- propio phpMyAdmin local. Las credenciales de conexión NO van
-- aquí; van en un archivo de configuración fuera de Git.
-- ============================================================

CREATE DATABASE IF NOT EXISTS pdflex
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pdflex;

-- ------------------------------------------------------------
-- Tabla: usuarios
-- Quién usa el sistema y con qué rol.
-- ------------------------------------------------------------
CREATE TABLE usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100)    NOT NULL,
    correo          VARCHAR(150)    NOT NULL UNIQUE,
    contrasena      VARCHAR(255)    NOT NULL,          -- hash (password_hash de PHP), nunca texto plano
    rol             ENUM('estudiante', 'docente', 'administrativo', 'admin')
                    NOT NULL DEFAULT 'estudiante',
    fecha_registro  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabla: historial
-- Cada operación (conversión/compresión/unión/división/OCR)
-- que un usuario ejecuta, y su estado.
-- ------------------------------------------------------------
CREATE TABLE historial (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id                  INT UNSIGNED NOT NULL,
    tipo_operacion              ENUM(
                                    'conversion_pdf_word',
                                    'conversion_word_pdf',
                                    'conversion_pdf_imagen',
                                    'conversion_imagen_pdf',
                                    'compresion',
                                    'union',
                                    'division',
                                    'ocr'
                                ) NOT NULL,
    nombre_archivo_original     VARCHAR(255) NOT NULL,
    nombre_archivo_resultado    VARCHAR(255) NULL,
    tamano_original_kb          INT UNSIGNED NULL,
    tamano_resultado_kb         INT UNSIGNED NULL,
    estado                      ENUM('pendiente', 'procesando', 'completado', 'error')
                                 NOT NULL DEFAULT 'pendiente',
    mensaje_error                TEXT NULL,
    fecha_inicio                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_fin                   DATETIME NULL,

    CONSTRAINT fk_historial_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_historial_usuario ON historial(usuario_id);
CREATE INDEX idx_historial_estado  ON historial(estado);

-- ------------------------------------------------------------
-- Tabla: archivos_temporales
-- Rastro de los archivos físicos (subidos y generados) para
-- poder borrarlos automáticamente tras su expiración.
-- ------------------------------------------------------------
CREATE TABLE archivos_temporales (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    historial_id        INT UNSIGNED NOT NULL,
    ruta_archivo         VARCHAR(500) NOT NULL,
    fecha_creacion       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion     DATETIME NOT NULL,
    eliminado            BOOLEAN NOT NULL DEFAULT FALSE,

    CONSTRAINT fk_archivo_historial
        FOREIGN KEY (historial_id) REFERENCES historial(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_archivos_expiracion ON archivos_temporales(fecha_expiracion, eliminado);
