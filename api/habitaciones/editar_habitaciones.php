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
$nombreHabitacion = trim($input["nombreHabitacion"] ?? "");
$ubicacion = trim($input["ubicacion"] ?? "");
$equipamiento = trim($input["equipamiento"] ?? "");
$areasId = $input["areasId"] ?? null;

if (
    $id === null || !is_numeric($id) ||
    $nombreHabitacion === "" ||
    $areasId === null || !is_numeric($areasId)
) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID, nombre de la habitación y área son obligatorios"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlExiste = "SELECT ID
                  FROM HABITACIONES
                  WHERE ID = :id
                  LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":id" => (int)$id
    ]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró la habitación a editar"
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
                     FROM HABITACIONES
                     WHERE UPPER(TRIM(NOMBREHABITACION)) = UPPER(TRIM(:nombreHabitacion))
                       AND AREAS_ID = :areasId
                       AND ID <> :id
                     LIMIT 1";
    $stmtDuplicado = $conn->prepare($sqlDuplicado);
    $stmtDuplicado->execute([
        ":nombreHabitacion" => $nombreHabitacion,
        ":areasId" => (int)$areasId,
        ":id" => (int)$id
    ]);

    if ($stmtDuplicado->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe otra habitación con ese nombre en esa área"
        ]);
    }

    $sql = "UPDATE HABITACIONES
            SET NOMBREHABITACION = :nombreHabitacion,
                UBICACION = :ubicacion,
                EQUIPAMIENTO = :equipamiento,
                AREAS_ID = :areasId
            WHERE ID = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":nombreHabitacion" => $nombreHabitacion,
        ":ubicacion" => ($ubicacion === "" ? null : $ubicacion),
        ":equipamiento" => ($equipamiento === "" ? null : $equipamiento),
        ":areasId" => (int)$areasId
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Habitación actualizada correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al editar habitación",
        "error" => $e->getMessage()
    ]);
}
?>