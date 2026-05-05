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
                a.ID,
                a.UBICACION,
                a.NOMBREAREA,
                a.ID1,
                a.HOSPITAL_UNI_ORG,
                h.NOMUO AS HOSPITAL
            FROM AREAS a
            INNER JOIN HOSPITAL h ON h.UNI_ORG = a.HOSPITAL_UNI_ORG
            WHERE a.ID = :id
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id
    ]);

    $data = $stmt->fetch();

    if (!$data) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el área"
        ]);
    }

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al obtener área",
        "error" => $e->getMessage()
    ]);
}
?>