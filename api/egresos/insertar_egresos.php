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
$ingresosId = $input["ingresosId"] ?? null;
$fechaEgreso = trim($input["fechaEgreso"] ?? "");
$observaciones = trim($input["observaciones"] ?? "");
$habitacionesId = $input["habitacionesId"] ?? null;

if (
    $id === null || !is_numeric($id) ||
    $ingresosId === null || !is_numeric($ingresosId) ||
    $habitacionesId === null || !is_numeric($habitacionesId)
) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID, ingreso y habitación son obligatorios"
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

    $sqlCheckPk = "SELECT ID, HABITACIONES_ID
                   FROM EGRESOS
                   WHERE ID = :id
                     AND HABITACIONES_ID = :habitacionesId
                   LIMIT 1";
    $stmtCheckPk = $conn->prepare($sqlCheckPk);
    $stmtCheckPk->execute([
        ":id" => (int)$id,
        ":habitacionesId" => (int)$habitacionesId
    ]);

    if ($stmtCheckPk->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe un egreso con ese ID y habitación"
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

    $sqlCheckIngreso = "SELECT ID, HABITACIONES_ID
                        FROM INGRESOS
                        WHERE ID = :ingresosId
                          AND HABITACIONES_ID = :habitacionesId
                        LIMIT 1";
    $stmtCheckIngreso = $conn->prepare($sqlCheckIngreso);
    $stmtCheckIngreso->execute([
        ":ingresosId" => (int)$ingresosId,
        ":habitacionesId" => (int)$habitacionesId
    ]);

    if (!$stmtCheckIngreso->fetch()) {
        jsonResponse(400, [
            "ok" => false,
            "message" => "El ingreso seleccionado no existe para esa habitación"
        ]);
    }

    $sqlCheckIngresoUnico = "SELECT INGRESOS_ID
                             FROM EGRESOS
                             WHERE INGRESOS_ID = :ingresosId
                             LIMIT 1";
    $stmtCheckIngresoUnico = $conn->prepare($sqlCheckIngresoUnico);
    $stmtCheckIngresoUnico->execute([
        ":ingresosId" => (int)$ingresosId
    ]);

    if ($stmtCheckIngresoUnico->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ese ingreso ya tiene un egreso registrado"
        ]);
    }

    $sql = "INSERT INTO EGRESOS (
                ID,
                TIPO,
                INGRESOS_ID,
                FECHAEGRESO,
                OBSERVACIONES,
                HABITACIONES_ID
            ) VALUES (
                :id,
                :tipo,
                :ingresosId,
                :fechaEgreso,
                :observaciones,
                :habitacionesId
            )";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":tipo" => ($tipo === "" ? null : $tipo),
        ":ingresosId" => (int)$ingresosId,
        ":fechaEgreso" => ($fechaEgreso === "" ? null : $fechaEgreso),
        ":observaciones" => ($observaciones === "" ? null : $observaciones),
        ":habitacionesId" => (int)$habitacionesId
    ]);

    jsonResponse(201, [
        "ok" => true,
        "message" => "Egreso insertado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al insertar egreso",
        "error" => $e->getMessage()
    ]);
}
?>