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
                h.ID,
                h.NOMBREHABITACION,
                h.UBICACION,
                h.EQUIPAMIENTO,
                h.AREAS_ID,
                a.NOMBREAREA
            FROM HABITACIONES h
            INNER JOIN AREAS a ON a.ID = h.AREAS_ID
            ORDER BY h.ID ASC";

    $stmt = $conn->query($sql);
    $data = $stmt->fetchAll();

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al listar habitaciones",
        "error" => $e->getMessage()
    ]);
}
?>