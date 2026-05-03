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
if (!isset($_GET["hospital_id"]) || empty($_GET["hospital_id"])) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El parámetro hospital_id es obligatorio"
    ]);
}

$hospital_id = $_GET["hospital_id"];

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
            INNER JOIN HOSPITAL h 
                ON h.UNI_ORG = a.HOSPITAL_UNI_ORG
            WHERE a.HOSPITAL_UNI_ORG = :hospital_id
            ORDER BY a.ID ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":hospital_id", $hospital_id, PDO::PARAM_STR);
    $stmt->execute();

    $data = $stmt->fetchAll();

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);

} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al listar áreas por hospital",
        "error" => $e->getMessage()
    ]);
}
?>