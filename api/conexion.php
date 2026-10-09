<?php
// api/conexion.php — la conexión PDO de la Semana 6, sin datos escritos a mano
require_once "config.php";

if (ENTORNO === "produccion") {
    ini_set("display_errors", "0"); // el detalle del error va al log, nunca a la respuesta
    ini_set("log_errors", "1");
}

// Cualquier error no previsto (por ejemplo, la tabla no existe): 500 en JSON,
// y el detalle sólo en el log.
set_exception_handler(function ($e) {
    error_log("Error no controlado: " . $e->getMessage());
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["error" => "Error interno del servidor"]);
});

try {
    $conexion = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log("Conexión fallida: " . $e->getMessage()); // lo ve el docente en el log
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["error" => "No se pudo conectar a la base de datos"]);
    exit();
}
