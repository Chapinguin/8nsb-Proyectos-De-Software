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

    $sqlExiste = "SELECT ID, HABITACIONES_ID, INGRESOS_ID
                  FROM EGRESOS
                  WHERE ID = :id
                    AND HABITACIONES_ID = :habitacionesId
                  LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":id" => (int)$id,
        ":habitacionesId" => (int)$habitacionesId
    ]);

    $actual = $stmtExiste->fetch();

    if (!$actual) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el egreso a editar"
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
                               AND NOT (ID = :id AND HABITACIONES_ID = :habitacionesId)
                             LIMIT 1";
    $stmtCheckIngresoUnico = $conn->prepare($sqlCheckIngresoUnico);
    $stmtCheckIngresoUnico->execute([
        ":ingresosId" => (int)$ingresosId,
        ":id" => (int)$id,
        ":habitacionesId" => (int)$habitacionesId
    ]);

    if ($stmtCheckIngresoUnico->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ese ingreso ya tiene un egreso registrado"
        ]);
    }

    $sql = "UPDATE EGRESOS
            SET TIPO = :tipo,
                INGRESOS_ID = :ingresosId,
                FECHAEGRESO = :fechaEgreso,
                OBSERVACIONES = :observaciones
            WHERE ID = :id
              AND HABITACIONES_ID = :habitacionesId";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":habitacionesId" => (int)$habitacionesId,
        ":tipo" => ($tipo === "" ? null : $tipo),
        ":ingresosId" => (int)$ingresosId,
        ":fechaEgreso" => ($fechaEgreso === "" ? null : $fechaEgreso),
        ":observaciones" => ($observaciones === "" ? null : $observaciones)
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Egreso actualizado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al editar egreso",
        "error" => $e->getMessage()
    ]);
}
?>