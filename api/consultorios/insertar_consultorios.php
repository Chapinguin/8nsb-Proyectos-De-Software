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

    $sqlCheckId = "SELECT ID
                   FROM CONSULTORIOS
                   WHERE ID = :id
                   LIMIT 1";
    $stmtCheckId = $conn->prepare($sqlCheckId);
    $stmtCheckId->execute([
        ":id" => (int)$id
    ]);

    if ($stmtCheckId->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe un consultorio con ese ID"
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
                       FROM CONSULTORIOS
                       WHERE UPPER(TRIM(CONSULTORIO)) = UPPER(TRIM(:consultorio))
                         AND AREAS_ID = :areasId
                       LIMIT 1";
    $stmtCheckNombre = $conn->prepare($sqlCheckNombre);
    $stmtCheckNombre->execute([
        ":consultorio" => $consultorio,
        ":areasId" => (int)$areasId
    ]);

    if ($stmtCheckNombre->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe un consultorio con ese nombre en esa área"
        ]);
    }

    $sql = "INSERT INTO CONSULTORIOS (
                ID,
                CONSULTORIO,
                UBICACION,
                AREAS_ID
            ) VALUES (
                :id,
                :consultorio,
                :ubicacion,
                :areasId
            )";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":consultorio" => $consultorio,
        ":ubicacion" => ($ubicacion === "" ? null : $ubicacion),
        ":areasId" => (int)$areasId
    ]);

    jsonResponse(201, [
        "ok" => true,
        "message" => "Consultorio insertado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al insertar consultorio",
        "error" => $e->getMessage()
    ]);
}
?>