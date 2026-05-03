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

    $sqlExiste = "SELECT ID
                  FROM ESTUDIOS
                  WHERE ID = :id
                  LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":id" => (int)$id
    ]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el estudio a editar"
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

    $sql = "UPDATE ESTUDIOS
            SET TIPOESTUDIOS_ID = :tipoEstudiosId,
                MEDICOS_EXPEDIENTE = :medicosExpediente,
                FECHAESTUDIO = :fechaEstudio,
                ESTATUS = :estatus
            WHERE ID = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":tipoEstudiosId" => (int)$tipoEstudiosId,
        ":medicosExpediente" => (int)$medicosExpediente,
        ":fechaEstudio" => ($fechaEstudio === "" ? null : $fechaEstudio),
        ":estatus" => ($estatus === "" ? null : $estatus)
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Estudio actualizado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al editar estudio",
        "error" => $e->getMessage()
    ]);
}
?>s