<?php
// api/servicios.php — todo lo que la API le pide a servicios cloud externos.
// alumnos.php no sabe cómo se habla con cada servicio: sólo llama a estas funciones.

require_once "config.php";

// ----- Cliente HTTP genérico: el "fetch" del lado de PHP -----
// Devuelve [código de estado, cuerpo ya decodificado]. Si no hubo respuesta
// (sin red, timeout, dominio inexistente), el código es 0.
function pedirJSON($url, $metodo = "GET", $cuerpo = null, $cabeceras = []) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  // devolver la respuesta, no imprimirla
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);            // no esperar más de 5 segundos
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $metodo);
    // En Windows, validar HTTPS con los certificados del sistema (evita el error
    // "SSL certificate problem" de XAMPP). En un servidor Linux no tiene efecto.
    curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);

    if ($cuerpo !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($cuerpo));
        $cabeceras[] = "Content-Type: application/json";
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $cabeceras);

    $respuesta = curl_exec($curl);
    $estado = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($respuesta === false) {
        return [0, null];
    }
    return [$estado, json_decode($respuesta, true)];
}

// ----- Etapa 1: geocodificación (Open-Meteo, sin clave) -----
// Convierte el nombre de una ciudad en coordenadas.
// Devuelve un array con "estado": "ok", "no_encontrada" o "sin_servicio".
function geocodificar($ciudad) {
    $url = "https://geocoding-api.open-meteo.com/v1/search?" . http_build_query([
        "name"     => $ciudad,
        "count"    => 1,
        "language" => "es",
        "format"   => "json",
    ]);

    [$estado, $datos] = pedirJSON($url);

    if ($estado !== 200) {
        return ["estado" => "sin_servicio"];   // el servicio no contestó, o contestó con error
    }
    if (empty($datos["results"])) {
        return ["estado" => "no_encontrada"];  // contestó bien, pero no conoce esa ciudad
    }

    $lugar = $datos["results"][0];
    return [
        "estado"   => "ok",
        "latitud"  => $lugar["latitude"],
        "longitud" => $lugar["longitude"],
    ];
}

// ----- Etapa 3: aviso por email (Resend, con clave) -----
// Devuelve true si el aviso salió (o quedó registrado en modo simulación).
function enviarAviso($alumno) {
    $texto = "Se registró " . $alumno["nombre"] . " (" . $alumno["email"] . ")"
           . ($alumno["ciudad"] ? ", de " . $alumno["ciudad"] : "") . ".";

    // Modo simulación: sin clave configurada, el aviso se anota en un archivo
    // y en el log del servidor (en la nube el archivo se pierde en cada despliegue).
    if (RESEND_API_KEY === "") {
        file_put_contents(__DIR__ . "/avisos.log",
            date("Y-m-d H:i:s") . "  " . $texto . PHP_EOL, FILE_APPEND);
        error_log("[aviso simulado] " . $texto);
        return true;
    }

    [$estado, $datos] = pedirJSON(
        "https://api.resend.com/emails",
        "POST",
        [
            "from"    => "App Alumnos <onboarding@resend.dev>",
            "to"      => [EMAIL_AVISOS],
            "subject" => "Nuevo alumno: " . $alumno["nombre"],
            "html"    => "<p>" . htmlspecialchars($texto) . "</p>",
        ],
        ["Authorization: Bearer " . RESEND_API_KEY]
    );

    if ($estado !== 200) {
        error_log("No se pudo enviar el aviso (" . $estado . "): " . json_encode($datos));
        return false;
    }
    return true;
}
