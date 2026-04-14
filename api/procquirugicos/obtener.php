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

if ($id === null || !is_numeric($id)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El ID es obligatorio y debe ser numérico"
    ]);
}

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
            WHERE p.ID = :id
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id
    ]);

    $data = $stmt->fetch();

    if (!$data) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el procedimiento quirúrgico"
        ]);
    }

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al obtener procedimiento quirúrgico",
        "error" => $e->getMessage()
    ]);
}
?>