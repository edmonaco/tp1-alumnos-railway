<?php
// api/config.php — ya no guarda valores: los LEE del entorno.
// En XAMPP la variable no existe y se usa el valor local de la derecha.
// En la nube, los valores reales se cargan en el panel del proveedor.

function entorno($nombre, $porDefecto = "") {
    $valor = getenv($nombre);
    return $valor !== false ? $valor : $porDefecto;
}

define("DB_HOST", entorno("DB_HOST", "localhost"));
define("DB_PORT", entorno("DB_PORT", "3306"));
define("DB_NAME", entorno("DB_NAME", "semana6"));
define("DB_USER", entorno("DB_USER", "root"));
define("DB_PASS", entorno("DB_PASS", ""));

define("RESEND_API_KEY", entorno("RESEND_API_KEY", "")); // vacía = modo simulación
define("EMAIL_AVISOS", entorno("EMAIL_AVISOS", "tu-email@ejemplo.com"));
define("ENTORNO", entorno("ENTORNO", "local")); // "produccion" en la nube

date_default_timezone_set("America/Argentina/Buenos_Aires");
