-- Esquema de AulaFix (MySQL 8). Se ejecuta con: php database/instalar.php
-- Las restricciones (UNIQUE, ENUM, claves foráneas) protegen la integridad aunque falle la validación de PHP.
SET NAMES utf8mb4;
DROP TABLE IF EXISTS historial;
DROP TABLE IF EXISTS incidencias;
DROP TABLE IF EXISTS equipos;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(80)  NOT NULL,
    email      VARCHAR(190) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    rol        ENUM('alumno','profesor') NOT NULL DEFAULT 'alumno',
    creado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE equipos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo      VARCHAR(20)  NOT NULL UNIQUE,
    descripcion VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE incidencias (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipo_id      INT UNSIGNED NOT NULL,
    usuario_id     INT UNSIGNED NOT NULL,
    categoria      ENUM('hardware','software','red','otro') NOT NULL,
    prioridad      ENUM('baja','media','alta') NOT NULL,
    descripcion    VARCHAR(500) NOT NULL,
    estado         ENUM('abierta','en_curso','resuelta') NOT NULL DEFAULT 'abierta',
    creada_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_inc_equipo  FOREIGN KEY (equipo_id)  REFERENCES equipos(id),
    CONSTRAINT fk_inc_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE historial (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    incidencia_id   INT UNSIGNED NOT NULL,
    usuario_id      INT UNSIGNED NOT NULL,
    estado_anterior ENUM('abierta','en_curso','resuelta') NOT NULL,
    estado_nuevo    ENUM('abierta','en_curso','resuelta') NOT NULL,
    comentario      VARCHAR(300) NOT NULL,
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- El historial no tiene sentido sin su incidencia: se borra con ella.
    CONSTRAINT fk_his_inc     FOREIGN KEY (incidencia_id) REFERENCES incidencias(id) ON DELETE CASCADE,
    CONSTRAINT fk_his_usuario FOREIGN KEY (usuario_id)    REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
