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
$tipoEstudiosId = $input["tipoEstudiosId"] ?? null;
$medicosExpediente = $input["medicosExpediente"] ?? null;
$fechaEstudio = trim($input["fechaEstudio"] ?? "");
$estatus = $input["estatus"] ?? null;

if (
    $id === null || !is_numeric($id) ||
    $tipoEstudiosId === null || !is_numeric($tipoEstudiosId) ||
    $medicosExpediente === null || !is_numeric($medicosExpediente)
) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID, tipo de estudio y médico son obligatorios"
    ]);
}

if ($estatus !== null && $estatus !== "" && !is_numeric($estatus)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El estatus debe ser numérico"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlCheckId = "SELECT ID
                   FROM ESTUDIOS
                   WHERE ID = :id
                   LIMIT 1";
    $stmtCheckId = $conn->prepare($sqlCheckId);
    $stmtCheckId->execute([
        ":id" => (int)$id
    ]);

    if ($stmtCheckId->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe un estudio con ese ID"
        ]);
    }

    $sqlCheckTipoEstudio = "SELECT ID
                            FROM TIPOESTUDIOS
                            WHERE ID = :id
                            LIMIT 1";
    $stmtCheckTipoEstudio = $conn->prepare($sqlCheckTipoEstudio);
    $stmtCheckTipoEstudio->execute([
        ":id" => (int)$tipoEstudiosId
    ]);

    if (!$stmtCheckTipoEstudio->fetch()) {
        jsonResponse(400, [
            "ok" => false,
            "message" => "El tipo de estudio seleccionado no existe"
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

    $sql = "INSERT INTO ESTUDIOS (
                ID,
                TIPOESTUDIOS_ID,
                MEDICOS_EXPEDIENTE,
                FECHAESTUDIO,
                ESTATUS
            ) VALUES (
                :id,
                :tipoEstudiosId,
                :medicosExpediente,
                :fechaEstudio,
                :estatus
            )";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":tipoEstudiosId" => (int)$tipoEstudiosId,
        ":medicosExpediente" => (int)$medicosExpediente,
        ":fechaEstudio" => ($fechaEstudio === "" ? null : $fechaEstudio),
        ":estatus" => ($estatus === "" ? null : $estatus)
    ]);

    jsonResponse(201, [
        "ok" => true,
        "message" => "Estudio insertado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al insertar estudio",
        "error" => $e->getMessage()
    ]);
}
?>