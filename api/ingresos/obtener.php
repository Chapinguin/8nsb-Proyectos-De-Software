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
                i.ID,
                i.HABITACIONES_ID,
                h.NOMBREHABITACION,
                i.TIPO,
                i.FECHAINGRESO,
                i.OBSERVACIONES,
                i.MEDICOS_EXPEDIENTE,
                m.NOMBRE,
                m.APELLIDOPATERNO,
                m.APELLIDOMATERNO
            FROM INGRESOS i
            INNER JOIN HABITACIONES h ON h.ID = i.HABITACIONES_ID
            INNER JOIN MEDICOS m ON m.EXPEDIENTE = i.MEDICOS_EXPEDIENTE
            WHERE i.ID = :id
              AND i.HABITACIONES_ID = :habitacionesId
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
            "message" => "No se encontró el ingreso"
        ]);
    }

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al obtener ingreso",
        "error" => $e->getMessage()
    ]);
}
?>