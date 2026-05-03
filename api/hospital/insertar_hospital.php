<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../helpers/auth.php";
require_once __DIR__ . "/../../helpers/response.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonResponse(405, [
        "ok" => false,
        "message" => "Método no permitido"
    ]);
}

requireRole("Administrador");

$input = json_decode(file_get_contents("php://input"), true);

$uniOrg = trim($input["uniOrg"] ?? $input["uni_org"] ?? "");
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

    $sqlCheckPk = "SELECT UNI_ORG
                   FROM HOSPITAL
                   WHERE UNI_ORG = :uni_org
                   LIMIT 1";
    $stmtCheckPk = $conn->prepare($sqlCheckPk);
    $stmtCheckPk->execute([
        ":uni_org" => $uniOrg
    ]);

    if ($stmtCheckPk->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe un hospital con esa clave UNI_ORG"
        ]);
    }

    $sqlCheckNombre = "SELECT UNI_ORG
                       FROM HOSPITAL
                       WHERE UPPER(TRIM(NOMUO)) = UPPER(TRIM(:nomuo))
                       LIMIT 1";
    $stmtCheckNombre = $conn->prepare($sqlCheckNombre);
    $stmtCheckNombre->execute([
        ":nomuo" => $nomuo
    ]);

    if ($stmtCheckNombre->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe un hospital con ese nombre"
        ]);
    }

    $sql = "INSERT INTO HOSPITAL (
                UNI_ORG,
                NOMUO,
                DIRECCION,
                DIRECTOR,
                TELEFONO
            ) VALUES (
                :uni_org,
                :nomuo,
                :direccion,
                :director,
                :telefono
            )";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":uni_org" => $uniOrg,
        ":nomuo" => $nomuo,
        ":direccion" => ($direccion === "" ? null : $direccion),
        ":director" => ($director === "" ? null : $director),
        ":telefono" => ($telefono === "" ? null : $telefono)
    ]);

    jsonResponse(201, [
        "ok" => true,
        "message" => "Hospital insertado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al insertar hospital",
        "error" => $e->getMessage()
    ]);
}
?>