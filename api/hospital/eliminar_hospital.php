<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../helpers/auth.php";
require_once __DIR__ . "/../../helpers/response.php";

if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
    jsonResponse(405, [
        "ok" => false,
        "message" => "Método no permitido"
    ]);
}

requireRole("Administrador");

$input = json_decode(file_get_contents("php://input"), true);

$uniOrg = trim($input["uniOrg"] ?? "");
$nomuo = trim($input["nomuo"] ?? "");
$direccion = trim($input["direccion"] ?? "");
$director = trim($input["director"] ?? "");
$telefono = $input["telefono"] ?? null;

if ($uniOrg === "" || $nomuo === "") {
    jsonResponse(400, [
        "ok" => false,
        "message" => "UNI_ORG y nombre del hospital son obligatorios"
    ]);
}

if (strlen($uniOrg) > 5) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "UNI_ORG no puede exceder 5 caracteres"
    ]);
}

if ($telefono !== null && $telefono !== "" && !is_numeric($telefono)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El teléfono debe ser numérico"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlExiste = "SELECT UNI_ORG
                  FROM HOSPITAL
                  WHERE UNI_ORG = :uni_org
                  LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":uni_org" => $uniOrg
    ]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el hospital a editar"
        ]);
    }

    $sqlDuplicado = "SELECT UNI_ORG
                     FROM HOSPITAL
                     WHERE UPPER(TRIM(NOMUO)) = UPPER(TRIM(:nomuo))
                       AND UNI_ORG <> :uni_org
                     LIMIT 1";
    $stmtDuplicado = $conn->prepare($sqlDuplicado);
    $stmtDuplicado->execute([
        ":nomuo" => $nomuo,
        ":uni_org" => $uniOrg
    ]);

    if ($stmtDuplicado->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe otro hospital con ese nombre"
        ]);
    }

    $sql = "UPDATE HOSPITAL
            SET NOMUO = :nomuo,
                DIRECCION = :direccion,
                DIRECTOR = :director,
                TELEFONO = :telefono
            WHERE UNI_ORG = :uni_org";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":uni_org" => $uniOrg,
        ":nomuo" => $nomuo,
        ":direccion" => ($direccion === "" ? null : $direccion),
        ":director" => ($director === "" ? null : $director),
        ":telefono" => ($telefono === "" ? null : $telefono)
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Hospital actualizado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al editar hospital",
        "error" => $e->getMessage()
    ]);
}
?>