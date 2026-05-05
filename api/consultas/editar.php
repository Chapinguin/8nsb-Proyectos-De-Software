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
$fechaConsulta = trim($input["fechaConsulta"] ?? "");
$estatus = $input["estatus"] ?? null;
$consultoriosId = $input["consultoriosId"] ?? null;
$tipoConsulta = trim($input["tipoConsulta"] ?? "");
$medicosExpediente = $input["medicosExpediente"] ?? null;

if (
    $id === null || !is_numeric($id) ||
    $consultoriosId === null || !is_numeric($consultoriosId) ||
    $medicosExpediente === null || !is_numeric($medicosExpediente)
) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID, consultorio y médico son obligatorios"
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
                  FROM CONSULTAS
                  WHERE ID = :id
                  LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":id" => (int)$id
    ]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró la consulta a editar"
        ]);
    }

    $sqlCheckConsultorio = "SELECT ID
                            FROM CONSULTORIOS
                            WHERE ID = :id
                            LIMIT 1";
    $stmtCheckConsultorio = $conn->prepare($sqlCheckConsultorio);
    $stmtCheckConsultorio->execute([
        ":id" => (int)$consultoriosId
    ]);

    if (!$stmtCheckConsultorio->fetch()) {
        jsonResponse(400, [
            "ok" => false,
            "message" => "El consultorio seleccionado no existe"
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

    $sql = "UPDATE CONSULTAS
            SET FECHACONSULTA = :fechaConsulta,
                ESTATUS = :estatus,
                CONSULTORIOS_ID = :consultoriosId,
                TIPOCONSULTA = :tipoConsulta,
                MEDICOS_EXPEDIENTE = :medicosExpediente
            WHERE ID = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":fechaConsulta" => ($fechaConsulta === "" ? null : $fechaConsulta),
        ":estatus" => ($estatus === "" ? null : $estatus),
        ":consultoriosId" => (int)$consultoriosId,
        ":tipoConsulta" => ($tipoConsulta === "" ? null : $tipoConsulta),
        ":medicosExpediente" => (int)$medicosExpediente
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Consulta actualizada correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al editar consulta",
        "error" => $e->getMessage()
    ]);
}
?>