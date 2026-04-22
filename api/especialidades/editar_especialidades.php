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

    $sqlExiste = "SELECT ID
                  FROM ESPECIALIDADES
                  WHERE ID = :id
                  LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":id" => (int)$id
    ]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró la especialidad a editar"
        ]);
    }

    $sqlDuplicado = "SELECT ID
                     FROM ESPECIALIDADES
                     WHERE UPPER(TRIM(ESPECIALIDAD)) = UPPER(TRIM(:especialidad))
                       AND ID <> :id
                     LIMIT 1";
    $stmtDuplicado = $conn->prepare($sqlDuplicado);
    $stmtDuplicado->execute([
        ":especialidad" => $especialidad,
        ":id" => (int)$id
    ]);

    if ($stmtDuplicado->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe otra especialidad con ese nombre"
        ]);
    }

    $sql = "UPDATE ESPECIALIDADES
            SET ESPECIALIDAD = :especialidad
            WHERE ID = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":especialidad" => $especialidad
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Especialidad actualizada correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al editar especialidad",
        "error" => $e->getMessage()
    ]);
}
?>