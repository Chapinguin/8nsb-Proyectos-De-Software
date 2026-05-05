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
            WHERE e.ID = :id
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id
    ]);

    $data = $stmt->fetch();

    if (!$data) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el estudio"
        ]);
    }

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al obtener estudio",
        "error" => $e->getMessage()
    ]);
}
?>