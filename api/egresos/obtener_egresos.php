<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../helpers/auth.php";
require_once __DIR__ . "/../../helpers/response.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    jsonResponse(405, [
        "ok" => false,
        "message" => "Método no permitido"
    ]);
}

requireLogin();

$id = $_GET["id"] ?? null;
$habitacionesId = $_GET["habitacionesId"] ?? null;

if (
    $id === null || !is_numeric($id) ||
    $habitacionesId === null || !is_numeric($habitacionesId)
) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID y habitacionesId son obligatorios y deben ser numéricos"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sql = "SELECT 
                e.ID,
                e.HABITACIONES_ID,
                h.NOMBREHABITACION,
                e.TIPO,
                e.INGRESOS_ID,
                e.FECHAEGRESO,
                e.OBSERVACIONES
            FROM EGRESOS e
            INNER JOIN HABITACIONES h ON h.ID = e.HABITACIONES_ID
            WHERE e.ID = :id
              AND e.HABITACIONES_ID = :habitacionesId
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":habitacionesId" => (int)$habitacionesId
    ]);

    $data = $stmt->fetch();

    if (!$data) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el egreso"
        ]);
    }

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al obtener egreso",
        "error" => $e->getMessage()
    ]);
}
?>