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
$nombreQuirofano = trim($input["nombreQuirofano"] ?? "");
$ubicacion = trim($input["ubicacion"] ?? "");
$areasId = $input["areasId"] ?? null;

if (
    $id === null || !is_numeric($id) ||
    $nombreQuirofano === "" ||
    $areasId === null || !is_numeric($areasId)
) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID, nombre del quirófano y área son obligatorios"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlCheckId = "SELECT ID
                   FROM QUIROFANOS
                   WHERE ID = :id
                   LIMIT 1";
    $stmtCheckId = $conn->prepare($sqlCheckId);
    $stmtCheckId->execute([
        ":id" => (int)$id
    ]);

    if ($stmtCheckId->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe un quirófano con ese ID"
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

    $sqlCheckNombre = "SELECT ID
                       FROM QUIROFANOS
                       WHERE UPPER(TRIM(NOMBREQUIROFANO)) = UPPER(TRIM(:nombreQuirofano))
                         AND AREAS_ID = :areasId
                       LIMIT 1";
    $stmtCheckNombre = $conn->prepare($sqlCheckNombre);
    $stmtCheckNombre->execute([
        ":nombreQuirofano" => $nombreQuirofano,
        ":areasId" => (int)$areasId
    ]);

    if ($stmtCheckNombre->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe un quirófano con ese nombre en esa área"
        ]);
    }

    $sql = "INSERT INTO QUIROFANOS (
                ID,
                NOMBREQUIROFANO,
                UBICACION,
                AREAS_ID
            ) VALUES (
                :id,
                :nombreQuirofano,
                :ubicacion,
                :areasId
            )";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":nombreQuirofano" => $nombreQuirofano,
        ":ubicacion" => ($ubicacion === "" ? null : $ubicacion),
        ":areasId" => (int)$areasId
    ]);

    jsonResponse(201, [
        "ok" => true,
        "message" => "Quirófano insertado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al insertar quirófano",
        "error" => $e->getMessage()
    ]);
}
?>