<?php
// api/alumnos.php — versión Semana 10: la API de la Semana 9, lista para producción.
// Cambios: validación en el servidor (400 / 422) también en PUT, 404 en PUT y DELETE
// si el alumno no existe, y sin "Access-Control-Allow-Origin: *" (frontend y API
// están en el mismo dominio, no hace falta CORS).

require "conexion.php";
require "servicios.php";

header("Content-Type: application/json; charset=utf-8");

$metodo = $_SERVER["REQUEST_METHOD"];

// Corta la respuesta con un código de error y un mensaje en JSON.
function responderError($codigo, $mensaje) {
    http_response_code($codigo);
    echo json_encode(["error" => $mensaje], JSON_UNESCAPED_UNICODE);
    exit();
}

// Valida nombre y email del cuerpo recibido. Si algo está mal, corta con 400 o 422.
function validarAlumno($cuerpo) {
    if (!is_array($cuerpo) || !isset($cuerpo["nombre"]) || !isset($cuerpo["email"])) {
        responderError(400, "Faltan nombre o email");
    }
    $nombre = trim($cuerpo["nombre"]);
    $email = trim($cuerpo["email"]);

    if ($nombre === "" || mb_strlen($nombre) > 100) {
        responderError(422, "El nombre es obligatorio y tiene hasta 100 caracteres");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        responderError(422, "El email no es válido");
    }
    return [$nombre, $email];
}

// Devuelve el alumno con ese id, o false si no existe.
function buscarAlumno($conexion, $id) {
    $consulta = $conexion->prepare("SELECT * FROM alumnos WHERE id = :id");
    $consulta->execute([":id" => $id]);
    return $consulta->fetch(PDO::FETCH_ASSOC);
}

// ----- POST: crear un alumno (con ciudad, coordenadas y aviso) -----
if ($metodo === "POST") {
    $cuerpo = json_decode(file_get_contents("php://input"), true);
    [$nombre, $email] = validarAlumno($cuerpo);

    $ciudad = trim($cuerpo["ciudad"] ?? "");
    $latitud = null;
    $longitud = null;

    if ($ciudad !== "") {
        $lugar = geocodificar($ciudad);

        if ($lugar["estado"] === "no_encontrada") {
            responderError(422, "No encontramos la ciudad \"$ciudad\"");
        }
        if ($lugar["estado"] === "ok") {
            $latitud = $lugar["latitud"];
            $longitud = $lugar["longitud"];
        }
        // "sin_servicio": el alta sigue igual, sin coordenadas (degradación elegante)
    }

    $consulta = $conexion->prepare(
        "INSERT INTO alumnos (nombre, email, ciudad, latitud, longitud)
         VALUES (:nombre, :email, :ciudad, :latitud, :longitud)"
    );
    $consulta->execute([
        ":nombre"   => $nombre,
        ":email"    => $email,
        ":ciudad"   => $ciudad !== "" ? $ciudad : null,
        ":latitud"  => $latitud,
        ":longitud" => $longitud,
    ]);

    $alumno = [
        "id"       => (int) $conexion->lastInsertId(),
        "nombre"   => $nombre,
        "email"    => $email,
        "ciudad"   => $ciudad !== "" ? $ciudad : null,
        "latitud"  => $latitud,
        "longitud" => $longitud,
    ];

    enviarAviso($alumno);   // si el aviso falla, el alumno ya quedó guardado igual

    http_response_code(201);
    echo json_encode($alumno, JSON_UNESCAPED_UNICODE);
    exit();
}

// ----- PUT: modificar nombre y email de un alumno -----
if ($metodo === "PUT") {
    if (!isset($_GET["id"])) {
        responderError(400, "Falta el id");
    }
    $cuerpo = json_decode(file_get_contents("php://input"), true);
    [$nombre, $email] = validarAlumno($cuerpo);

    $alumno = buscarAlumno($conexion, $_GET["id"]);
    if ($alumno === false) {
        responderError(404, "No existe un alumno con ese id");
    }

    $consulta = $conexion->prepare(
        "UPDATE alumnos SET nombre = :nombre, email = :email WHERE id = :id"
    );
    $consulta->execute([":nombre" => $nombre, ":email" => $email, ":id" => $_GET["id"]]);

    $alumno["nombre"] = $nombre;
    $alumno["email"] = $email;
    http_response_code(200);
    echo json_encode($alumno, JSON_UNESCAPED_UNICODE);
    exit();
}

// ----- DELETE: borrar un alumno -----
if ($metodo === "DELETE") {
    if (!isset($_GET["id"])) {
        responderError(400, "Falta el id");
    }

    $consulta = $conexion->prepare("DELETE FROM alumnos WHERE id = :id");
    $consulta->execute([":id" => $_GET["id"]]);

    if ($consulta->rowCount() === 0) {
        responderError(404, "No existe un alumno con ese id");
    }
    http_response_code(204);
    exit();
}

// ----- Cualquier otro método no está soportado -----
if ($metodo !== "GET") {
    responderError(405, "Método no permitido");
}

// ----- GET con id: uno solo -----
if (isset($_GET["id"])) {
    $alumno = buscarAlumno($conexion, $_GET["id"]);
    if ($alumno === false) {
        responderError(404, "No existe un alumno con ese id");
    }
    echo json_encode($alumno, JSON_UNESCAPED_UNICODE);
    exit();
}

// ----- GET por omisión: la colección completa -----
$consulta = $conexion->query("SELECT * FROM alumnos ORDER BY id");
echo json_encode($consulta->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
