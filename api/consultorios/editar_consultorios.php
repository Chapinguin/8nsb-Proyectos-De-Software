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

$id = $input["id"] ?? null;
$consultorio = trim($input["consultorio"] ?? "");
$ubicacion = trim($input["ubicacion"] ?? "");
$areasId = $input["areasId"] ?? null;

if (
    $id === null || !is_numeric($id) ||
    $consultorio === "" ||
    $areasId === null || !is_numeric($areasId)
) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID, consultorio y área son obligatorios"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlExiste = "SELECT ID
                  FROM CONSULTORIOS
                  WHERE ID = :id
                  LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":id" => (int)$id
    ]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el consultorio a editar"
        ]);
    }

    $sqlCheckArea = "SELECT ID
                     FROM AREAS
                     WHERE ID = :id
                     LIMIT 1";
    $stmtCheckArea = $conn->prepare($sqlCheckArea);
    $stmtCheckArea->execute([
        ":id" => (int)$areasId
    ]);

    if (!$stmtCheckArea->fetch()) {
        jsonResponse(400, [
            "ok" => false,
            "message" => "El área seleccionada no existe"
        ]);
    }

    $sqlDuplicado = "SELECT ID
                     FROM CONSULTORIOS
                     WHERE UPPER(TRIM(CONSULTORIO)) = UPPER(TRIM(:consultorio))
                       AND AREAS_ID = :areasId
                       AND ID <> :id
                     LIMIT 1";
    $stmtDuplicado = $conn->prepare($sqlDuplicado);
    $stmtDuplicado->execute([
        ":consultorio" => $consultorio,
        ":areasId" => (int)$areasId,
        ":id" => (int)$id
    ]);

    if ($stmtDuplicado->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe otro consultorio con ese nombre en esa área"
        ]);
    }

    $sql = "UPDATE CONSULTORIOS
            SET CONSULTORIO = :consultorio,
                UBICACION = :ubicacion,
                AREAS_ID = :areasId
            WHERE ID = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":consultorio" => $consultorio,
        ":ubicacion" => ($ubicacion === "" ? null : $ubicacion),
        ":areasId" => (int)$areasId
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Consultorio actualizado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al editar consultorio",
        "error" => $e->getMessage()
    ]);
}
?>