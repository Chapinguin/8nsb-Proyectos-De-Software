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

    $sqlCheckId = "SELECT ID
                   FROM HABITACIONES
                   WHERE ID = :id
                   LIMIT 1";
    $stmtCheckId = $conn->prepare($sqlCheckId);
    $stmtCheckId->execute([
        ":id" => (int)$id
    ]);

    if ($stmtCheckId->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe una habitación con ese ID"
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
                       FROM HABITACIONES
                       WHERE UPPER(TRIM(NOMBREHABITACION)) = UPPER(TRIM(:nombreHabitacion))
                         AND AREAS_ID = :areasId
                       LIMIT 1";
    $stmtCheckNombre = $conn->prepare($sqlCheckNombre);
    $stmtCheckNombre->execute([
        ":nombreHabitacion" => $nombreHabitacion,
        ":areasId" => (int)$areasId
    ]);

    if ($stmtCheckNombre->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe una habitación con ese nombre en esa área"
        ]);
    }

    $sql = "INSERT INTO HABITACIONES (
                ID,
                NOMBREHABITACION,
                UBICACION,
                EQUIPAMIENTO,
                AREAS_ID
            ) VALUES (
                :id,
                :nombreHabitacion,
                :ubicacion,
                :equipamiento,
                :areasId
            )";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":nombreHabitacion" => $nombreHabitacion,
        ":ubicacion" => ($ubicacion === "" ? null : $ubicacion),
        ":equipamiento" => ($equipamiento === "" ? null : $equipamiento),
        ":areasId" => (int)$areasId
    ]);

    jsonResponse(201, [
        "ok" => true,
        "message" => "Habitación insertada correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al insertar habitación",
        "error" => $e->getMessage()
    ]);
}
?>