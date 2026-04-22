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
$especialidad = trim($input["especialidad"] ?? "");

if ($id === null || !is_numeric($id) || $especialidad === "") {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID numérico y especialidad son obligatorios"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlCheckId = "SELECT ID
                   FROM ESPECIALIDADES
                   WHERE ID = :id
                   LIMIT 1";
    $stmtCheckId = $conn->prepare($sqlCheckId);
    $stmtCheckId->execute([
        ":id" => (int)$id
    ]);

    if ($stmtCheckId->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe una especialidad con ese ID"
        ]);
    }

    $sqlCheckNombre = "SELECT ID
                       FROM ESPECIALIDADES
                       WHERE UPPER(TRIM(ESPECIALIDAD)) = UPPER(TRIM(:especialidad))
                       LIMIT 1";
    $stmtCheckNombre = $conn->prepare($sqlCheckNombre);
    $stmtCheckNombre->execute([
        ":especialidad" => $especialidad
    ]);

    if ($stmtCheckNombre->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe una especialidad con ese nombre"
        ]);
    }

    $sql = "INSERT INTO ESPECIALIDADES (ID, ESPECIALIDAD)
            VALUES (:id, :especialidad)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":especialidad" => $especialidad
    ]);

    jsonResponse(201, [
        "ok" => true,
        "message" => "Especialidad insertada correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al insertar especialidad",
        "error" => $e->getMessage()
    ]);
}
?>