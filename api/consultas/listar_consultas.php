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
            ORDER BY c.ID ASC";

    $stmt = $conn->query($sql);
    $data = $stmt->fetchAll();

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al listar consultas",
        "error" => $e->getMessage()
    ]);
}
?>