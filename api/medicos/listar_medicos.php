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
                m.EXPEDIENTE,
                m.APELLIDOPATERNO,
                m.APELLIDOMATERNO,
                m.NOMBRE,
                m.TELEFONOMOVIL,
                m.TELEFONOCASA,
                m.ESPECIALIDADES_ID,
                e.ESPECIALIDAD,
                m.HOSPITAL_UNI_ORG,
                h.NOMUO AS HOSPITAL
            FROM MEDICOS m
            INNER JOIN ESPECIALIDADES e ON e.ID = m.ESPECIALIDADES_ID
            INNER JOIN HOSPITAL h ON h.UNI_ORG = m.HOSPITAL_UNI_ORG
            ORDER BY m.EXPEDIENTE ASC";

    $stmt = $conn->query($sql);
    $data = $stmt->fetchAll();

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al listar médicos",
        "error" => $e->getMessage()
    ]);
}
?>