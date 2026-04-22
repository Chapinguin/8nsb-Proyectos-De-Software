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

requireRole("Administrador");

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sql = "SELECT 
                um.id,
                um.usuario_id,
                u.username,
                u.nombre AS usuario_nombre,
                um.medico_expediente,
                m.NOMBRE,
                m.APELLIDOPATERNO,
                m.APELLIDOMATERNO
            FROM usuario_medico um
            INNER JOIN usuarios u ON u.id = um.usuario_id
            INNER JOIN MEDICOS m ON m.EXPEDIENTE = um.medico_expediente
            ORDER BY um.id ASC";

    $stmt = $conn->query($sql);
    $data = $stmt->fetchAll();

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al listar usuario_medico",
        "error" => $e->getMessage()
    ]);
}
?>