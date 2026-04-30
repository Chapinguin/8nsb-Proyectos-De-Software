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

// Validar parámetro
if (!isset($_GET["area_id"]) || empty($_GET["area_id"])) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El parámetro area_id es obligatorio"
    ]);
}

$area_id = $_GET["area_id"];

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
            WHERE h.AREAS_ID = :area_id
            ORDER BY h.ID ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":area_id", $area_id);
    $stmt->execute();

    $data = $stmt->fetchAll();

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);

} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al listar habitaciones por área",
        "error" => $e->getMessage()
    ]);
}
?>