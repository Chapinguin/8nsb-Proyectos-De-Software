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
                c.ID,
                c.FECHACONSULTA,
                c.ESTATUS,
                c.CONSULTORIOS_ID,
                co.CONSULTORIO,
                c.TIPOCONSULTA,
                c.MEDICOS_EXPEDIENTE,
                m.NOMBRE,
                m.APELLIDOPATERNO,
                m.APELLIDOMATERNO
            FROM CONSULTAS c
            INNER JOIN CONSULTORIOS co ON co.ID = c.CONSULTORIOS_ID
            INNER JOIN MEDICOS m ON m.EXPEDIENTE = c.MEDICOS_EXPEDIENTE
            WHERE c.ID = :id
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id
    ]);

    $data = $stmt->fetch();

    if (!$data) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró la consulta"
        ]);
    }

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al obtener consulta",
        "error" => $e->getMessage()
    ]);
}
?>