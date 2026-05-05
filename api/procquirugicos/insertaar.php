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
$fechaProcedimiento = trim($input["fechaProcedimiento"] ?? "");
$estatus = $input["estatus"] ?? null;
$quirofanosId = $input["quirofanosId"] ?? null;
$medicosExpediente = $input["medicosExpediente"] ?? null;
$tipoProcedimientoId = $input["tipoProcedimientoId"] ?? null;
$id1 = $input["id1"] ?? null;

if (
    $id === null || !is_numeric($id) ||
    $quirofanosId === null || !is_numeric($quirofanosId) ||
    $medicosExpediente === null || !is_numeric($medicosExpediente) ||
    $tipoProcedimientoId === null || !is_numeric($tipoProcedimientoId) ||
    $id1 === null || !is_numeric($id1)
) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID, quirófano, médico, tipo de procedimiento e ID1 son obligatorios"
    ]);
}

if ($tipo !== null && $tipo !== "" && !is_numeric($tipo)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El tipo debe ser numérico"
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
                   FROM PROCQUIRURGICOS
                   WHERE ID = :id
                   LIMIT 1";
    $stmtCheckId = $conn->prepare($sqlCheckId);
    $stmtCheckId->execute([
        ":id" => (int)$id
    ]);

    if ($stmtCheckId->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe un procedimiento quirúrgico con ese ID"
        ]);
    }

    $sqlCheckQuirofano = "SELECT ID
                          FROM QUIROFANOS
                          WHERE ID = :id
                          LIMIT 1";
    $stmtCheckQuirofano = $conn->prepare($sqlCheckQuirofano);
    $stmtCheckQuirofano->execute([
        ":id" => (int)$quirofanosId
    ]);

    if (!$stmtCheckQuirofano->fetch()) {
        jsonResponse(400, [
            "ok" => false,
            "message" => "El quirófano seleccionado no existe"
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

    $sqlCheckTipoProc = "SELECT ID
                         FROM TIPOPROCEDIMIENTO
                         WHERE ID = :id
                         LIMIT 1";
    $stmtCheckTipoProc = $conn->prepare($sqlCheckTipoProc);
    $stmtCheckTipoProc->execute([
        ":id" => (int)$tipoProcedimientoId
    ]);

    if (!$stmtCheckTipoProc->fetch()) {
        jsonResponse(400, [
            "ok" => false,
            "message" => "El tipo de procedimiento seleccionado no existe"
        ]);
    }

    $sql = "INSERT INTO PROCQUIRURGICOS (
                ID,
                TIPO,
                FECHAPROCEDIMIENTO,
                ESTATUS,
                QUIROFANOS_ID,
                MEDICOS_EXPEDIENTE,
                TIPOPROCEDIMIENTO_ID,
                ID1
            ) VALUES (
                :id,
                :tipo,
                :fechaProcedimiento,
                :estatus,
                :quirofanosId,
                :medicosExpediente,
                :tipoProcedimientoId,
                :id1
            )";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":tipo" => ($tipo === "" ? null : $tipo),
        ":fechaProcedimiento" => ($fechaProcedimiento === "" ? null : $fechaProcedimiento),
        ":estatus" => ($estatus === "" ? null : $estatus),
        ":quirofanosId" => (int)$quirofanosId,
        ":medicosExpediente" => (int)$medicosExpediente,
        ":tipoProcedimientoId" => (int)$tipoProcedimientoId,
        ":id1" => (int)$id1
    ]);

    jsonResponse(201, [
        "ok" => true,
        "message" => "Procedimiento quirúrgico insertado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al insertar procedimiento quirúrgico",
        "error" => $e->getMessage()
    ]);
}
?>