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
$nombreLaboratorio = trim($input["nombreLaboratorio"] ?? "");
$ubicacion = trim($input["ubicacion"] ?? "");
$areasId = $input["areasId"] ?? null;

if (
    $id === null || !is_numeric($id) ||
    $nombreLaboratorio === "" ||
    $areasId === null || !is_numeric($areasId)
) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID, nombre del laboratorio y área son obligatorios"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlExiste = "SELECT ID
                  FROM LABORATORIOS
                  WHERE ID = :id
                  LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":id" => (int)$id
    ]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el laboratorio a editar"
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
                     FROM LABORATORIOS
                     WHERE UPPER(TRIM(NOMBRELABORATORIO)) = UPPER(TRIM(:nombreLaboratorio))
                       AND AREAS_ID = :areasId
                       AND ID <> :id
                     LIMIT 1";
    $stmtDuplicado = $conn->prepare($sqlDuplicado);
    $stmtDuplicado->execute([
        ":nombreLaboratorio" => $nombreLaboratorio,
        ":areasId" => (int)$areasId,
        ":id" => (int)$id
    ]);

    if ($stmtDuplicado->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe otro laboratorio con ese nombre en esa área"
        ]);
    }

    $sql = "UPDATE LABORATORIOS
            SET NOMBRELABORATORIO = :nombreLaboratorio,
                UBICACION = :ubicacion,
                AREAS_ID = :areasId
            WHERE ID = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":nombreLaboratorio" => $nombreLaboratorio,
        ":ubicacion" => ($ubicacion === "" ? null : $ubicacion),
        ":areasId" => (int)$areasId
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Laboratorio actualizado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al editar laboratorio",
        "error" => $e->getMessage()
    ]);
}
?>