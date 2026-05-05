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

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sql = "SELECT 
                i.ID,
                i.HABITACIONES_ID,
                h.NOMBREHABITACION,
                h.AREAS_ID,
                a.HOSPITAL_UNI_ORG,
                i.TIPO,
                i.FECHAINGRESO,
                i.OBSERVACIONES,
                i.MEDICOS_EXPEDIENTE,
                m.NOMBRE,
                m.APELLIDOPATERNO,
                m.APELLIDOMATERNO
            FROM INGRESOS i
            INNER JOIN HABITACIONES h ON h.ID = i.HABITACIONES_ID
            INNER JOIN AREAS a ON a.ID = h.AREAS_ID
            INNER JOIN MEDICOS m ON m.EXPEDIENTE = i.MEDICOS_EXPEDIENTE
            ORDER BY i.ID ASC, i.HABITACIONES_ID ASC";

    $stmt = $conn->query($sql);
    $data = $stmt->fetchAll();

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al listar ingresos",
        "error" => $e->getMessage()
    ]);
}
?>