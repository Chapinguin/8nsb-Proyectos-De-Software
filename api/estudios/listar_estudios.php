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
                e.ID,
                e.TIPOESTUDIOS_ID,
                te.NOMBREESTUDIO,
                e.MEDICOS_EXPEDIENTE,
                m.NOMBRE,
                m.APELLIDOPATERNO,
                m.APELLIDOMATERNO,
                e.FECHAESTUDIO,
                e.ESTATUS
            FROM ESTUDIOS e
            INNER JOIN TIPOESTUDIOS te ON te.ID = e.TIPOESTUDIOS_ID
            INNER JOIN MEDICOS m ON m.EXPEDIENTE = e.MEDICOS_EXPEDIENTE
            ORDER BY e.ID ASC";

    $stmt = $conn->query($sql);
    $data = $stmt->fetchAll();

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al listar estudios",
        "error" => $e->getMessage()
    ]);
}
?>