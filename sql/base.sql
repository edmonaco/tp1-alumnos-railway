-- sql/base.sql — crea la tabla alumnos con algunos datos de ejemplo.
-- No tiene CREATE DATABASE ni USE: se ejecuta con la base ya elegida
-- (en XAMPP, "semana6"; en Railway, la base que crea el servicio MySQL, llamada "railway").

SET NAMES utf8mb4;   -- para que los acentos de los INSERT lleguen bien

CREATE TABLE IF NOT EXISTS alumnos (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    nombre   VARCHAR(100) NOT NULL,
    email    VARCHAR(150) NOT NULL,
    ciudad   VARCHAR(100) NULL,
    latitud  DECIMAL(9,6) NULL,
    longitud DECIMAL(9,6) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO alumnos (nombre, email, ciudad, latitud, longitud) VALUES
    ('Ana Pérez',    'ana@ejemplo.com',    'Rosario', -32.946820, -60.639320),
    ('Bruno Gómez',  'bruno@ejemplo.com',  'Mendoza', -32.889460, -68.845820),
    ('Carla Ruiz',   'carla@ejemplo.com',  NULL,      NULL,       NULL);
