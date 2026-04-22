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
                p.ID,
                p.TIPO,
                p.FECHAPROCEDIMIENTO,
                p.ESTATUS,
                p.QUIROFANOS_ID,
                q.NOMBREQUIROFANO,
                p.MEDICOS_EXPEDIENTE,
                m.NOMBRE,
                m.APELLIDOPATERNO,
                m.APELLIDOMATERNO,
                p.TIPOPROCEDIMIENTO_ID,
                tp.NOMBREPROCEDIMIENTO,
                p.ID1
            FROM PROCQUIRURGICOS p
            INNER JOIN QUIROFANOS q ON q.ID = p.QUIROFANOS_ID
            INNER JOIN MEDICOS m ON m.EXPEDIENTE = p.MEDICOS_EXPEDIENTE
            INNER JOIN TIPOPROCEDIMIENTO tp ON tp.ID = p.TIPOPROCEDIMIENTO_ID
            ORDER BY p.ID ASC";

    $stmt = $conn->query($sql);
    $data = $stmt->fetchAll();

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al listar procedimientos quirúrgicos",
        "error" => $e->getMessage()
    ]);
}
?>