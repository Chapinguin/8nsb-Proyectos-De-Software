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
                a.ID,
                a.UBICACION,
                a.NOMBREAREA,
                a.ID1,
                a.HOSPITAL_UNI_ORG,
                h.NOMUO AS HOSPITAL
            FROM AREAS a
            INNER JOIN HOSPITAL h ON h.UNI_ORG = a.HOSPITAL_UNI_ORG
            ORDER BY a.ID ASC";

    $stmt = $conn->query($sql);
    $data = $stmt->fetchAll();

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al listar áreas",
        "error" => $e->getMessage()
    ]);
}
?>