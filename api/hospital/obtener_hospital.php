<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../helpers/auth.php";
require_once __DIR__ . "/../../helpers/response.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    jsonResponse(405, [
        "ok" => false,
        "message" => "Método no permitido"
    ]);
}

requireLogin();

$uniOrg = trim($_GET["uni_org"] ?? "");

if ($uniOrg === "") {
    jsonResponse(400, [
        "ok" => false,
        "message" => "La clave UNI_ORG es obligatoria"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sql = "SELECT 
                UNI_ORG,
                NOMUO,
                DIRECCION,
                DIRECTOR,
                TELEFONO
            FROM HOSPITAL
            WHERE UNI_ORG = :uni_org
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":uni_org" => $uniOrg
    ]);

    $data = $stmt->fetch();

    if (!$data) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el hospital"
        ]);
    }

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al obtener hospital",
        "error" => $e->getMessage()
    ]);
}
?>