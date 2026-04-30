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

$id = $input["id"] ?? null;
$ubicacion = trim($input["ubicacion"] ?? "");
$nombreArea = trim($input["nombreArea"] ?? "");
$id1 = $input["id1"] ?? null;
$hospitalUniOrg = trim($input["hospitalUniOrg"] ?? "");

if (
    $id === null || !is_numeric($id) ||
    $nombreArea === "" ||
    $id1 === null || !is_numeric($id1) ||
    $hospitalUniOrg === ""
) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID, nombre del área, ID1 y hospital son obligatorios"
    ]);
}

if (strlen($hospitalUniOrg) > 5) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "La clave del hospital no puede exceder 5 caracteres"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlExiste = "SELECT ID
                  FROM AREAS
                  WHERE ID = :id
                  LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":id" => (int)$id
    ]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el área a editar"
        ]);
    }

    $sqlCheckHospital = "SELECT UNI_ORG
                         FROM HOSPITAL
                         WHERE UNI_ORG = :uni_org
                         LIMIT 1";
    $stmtCheckHospital = $conn->prepare($sqlCheckHospital);
    $stmtCheckHospital->execute([
        ":uni_org" => $hospitalUniOrg
    ]);

    if (!$stmtCheckHospital->fetch()) {
        jsonResponse(400, [
            "ok" => false,
            "message" => "El hospital seleccionado no existe"
        ]);
    }

    $sqlDuplicado = "SELECT ID
                     FROM AREAS
                     WHERE UPPER(TRIM(NOMBREAREA)) = UPPER(TRIM(:nombreArea))
                       AND HOSPITAL_UNI_ORG = :hospitalUniOrg
                       AND ID <> :id
                     LIMIT 1";
    $stmtDuplicado = $conn->prepare($sqlDuplicado);
    $stmtDuplicado->execute([
        ":nombreArea" => $nombreArea,
        ":hospitalUniOrg" => $hospitalUniOrg,
        ":id" => (int)$id
    ]);

    if ($stmtDuplicado->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe otra área con ese nombre en ese hospital"
        ]);
    }

    $sql = "UPDATE AREAS
            SET UBICACION = :ubicacion,
                NOMBREAREA = :nombreArea,
                ID1 = :id1,
                HOSPITAL_UNI_ORG = :hospitalUniOrg
            WHERE ID = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":ubicacion" => ($ubicacion === "" ? null : $ubicacion),
        ":nombreArea" => $nombreArea,
        ":id1" => (int)$id1,
        ":hospitalUniOrg" => $hospitalUniOrg
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Área actualizada correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al editar área",
        "error" => $e->getMessage()
    ]);
}
?>