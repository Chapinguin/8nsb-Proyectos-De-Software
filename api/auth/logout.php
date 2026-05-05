<?php
require_once __DIR__ . "/../../helpers/response.php";
require_once __DIR__ . "/../../helpers/auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonResponse(405, [
        "ok" => false,
        "message" => "Método no permitido"
    ]);
}

logoutSession();

jsonResponse(200, [
    "ok" => true,
    "message" => "Sesión cerrada correctamente"
]);
?>