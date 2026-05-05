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
            WHERE um.id = :id
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([":id" => (int)$id]);

    $data = $stmt->fetch();

    if (!$data) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró la asignación usuario-médico"
        ]);
    }

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al obtener usuario-médico",
        "error" => $e->getMessage()
    ]);
}
?>