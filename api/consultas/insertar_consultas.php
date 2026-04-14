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

    $sqlCheckId = "SELECT ID
                   FROM CONSULTAS
                   WHERE ID = :id
                   LIMIT 1";
    $stmtCheckId = $conn->prepare($sqlCheckId);
    $stmtCheckId->execute([
        ":id" => (int)$id
    ]);

    if ($stmtCheckId->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe una consulta con ese ID"
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

    $sql = "INSERT INTO CONSULTAS (
                ID,
                FECHACONSULTA,
                ESTATUS,
                CONSULTORIOS_ID,
                TIPOCONSULTA,
                MEDICOS_EXPEDIENTE
            ) VALUES (
                :id,
                :fechaConsulta,
                :estatus,
                :consultoriosId,
                :tipoConsulta,
                :medicosExpediente
            )";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":fechaConsulta" => ($fechaConsulta === "" ? null : $fechaConsulta),
        ":estatus" => ($estatus === "" ? null : $estatus),
        ":consultoriosId" => (int)$consultoriosId,
        ":tipoConsulta" => ($tipoConsulta === "" ? null : $tipoConsulta),
        ":medicosExpediente" => (int)$medicosExpediente
    ]);

    jsonResponse(201, [
        "ok" => true,
        "message" => "Consulta insertada correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al insertar consulta",
        "error" => $e->getMessage()
    ]);
}
?>