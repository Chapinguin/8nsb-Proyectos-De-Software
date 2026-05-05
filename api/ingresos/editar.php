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
$tipo = $input["tipo"] ?? null;
$fechaIngreso = trim($input["fechaIngreso"] ?? "");
$observaciones = trim($input["observaciones"] ?? "");
$medicosExpediente = $input["medicosExpediente"] ?? null;
$habitacionesId = $input["habitacionesId"] ?? null;

if (
    $id === null || !is_numeric($id) ||
    $medicosExpediente === null || !is_numeric($medicosExpediente) ||
    $habitacionesId === null || !is_numeric($habitacionesId)
) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID, médico y habitación son obligatorios"
    ]);
}

if ($tipo !== null && $tipo !== "" && !is_numeric($tipo)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El tipo debe ser numérico"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlExiste = "SELECT ID, HABITACIONES_ID
                  FROM INGRESOS
                  WHERE ID = :id
                    AND HABITACIONES_ID = :habitacionesId
                  LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":id" => (int)$id,
        ":habitacionesId" => (int)$habitacionesId
    ]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el ingreso a editar"
        ]);
    }

    $sqlCheckMedico = "SELECT EXPEDIENTE
                       FROM MEDICOS
                       WHERE EXPEDIENTE = :expediente
                       LIMIT 1";
    $stmtCheckMedico = $conn->prepare($sqlCheckMedico);
    $stmtCheckMedico->execute([
        ":expediente" => (int)$medicosExpediente
    ]);

    if (!$stmtCheckMedico->fetch()) {
        jsonResponse(400, [
            "ok" => false,
            "message" => "El médico seleccionado no existe"
        ]);
    }

    $sqlCheckHabitacion = "SELECT ID
                           FROM HABITACIONES
                           WHERE ID = :id
                           LIMIT 1";
    $stmtCheckHabitacion = $conn->prepare($sqlCheckHabitacion);
    $stmtCheckHabitacion->execute([
        ":id" => (int)$habitacionesId
    ]);

    if (!$stmtCheckHabitacion->fetch()) {
        jsonResponse(400, [
            "ok" => false,
            "message" => "La habitación seleccionada no existe"
        ]);
    }

    $sql = "UPDATE INGRESOS
            SET TIPO = :tipo,
                FECHAINGRESO = :fechaIngreso,
                OBSERVACIONES = :observaciones,
                MEDICOS_EXPEDIENTE = :medicosExpediente
            WHERE ID = :id
              AND HABITACIONES_ID = :habitacionesId";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":habitacionesId" => (int)$habitacionesId,
        ":tipo" => ($tipo === "" ? null : $tipo),
        ":fechaIngreso" => ($fechaIngreso === "" ? null : $fechaIngreso),
        ":observaciones" => ($observaciones === "" ? null : $observaciones),
        ":medicosExpediente" => (int)$medicosExpediente
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Ingreso actualizado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al editar ingreso",
        "error" => $e->getMessage()
    ]);
}
?>