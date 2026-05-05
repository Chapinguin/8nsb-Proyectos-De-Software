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

    // Filtro opcional por hospital
    $hospital_id = $_GET['hospital_id'] ?? null;

    $sql = "SELECT 
                l.NOMBRELABORATORIO,
                te.NOMBREESTUDIO,
                COUNT(e.ID) AS total,
                h.NOMUO AS HOSPITAL,
                a.NOMBREAREA
            FROM ESTUDIOS e
            INNER JOIN TIPOESTUDIOS te ON te.ID = e.TIPOESTUDIOS_ID
            INNER JOIN LABORATORIOS l ON l.ID = te.LABORATORIOS_ID
            INNER JOIN AREAS a ON a.ID = l.AREAS_ID
            INNER JOIN HOSPITAL h ON h.UNI_ORG = a.HOSPITAL_UNI_ORG
            WHERE 1=1";

    $params = [];

    if (!empty($hospital_id)) {
        $sql .= " AND h.UNI_ORG = :hospital_id";
        $params[':hospital_id'] = $hospital_id;
    }

    $sql .= " 
        GROUP BY l.ID, te.ID
        ORDER BY l.NOMBRELABORATORIO ASC, total DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // También obtenemos un resumen por laboratorio para las tarjetas del front
    $sqlResumen = "SELECT 
                    l.NOMBRELABORATORIO,
                    COUNT(e.ID) AS total_estudios
                   FROM ESTUDIOS e
                   INNER JOIN TIPOESTUDIOS te ON te.ID = e.TIPOESTUDIOS_ID
                   INNER JOIN LABORATORIOS l ON l.ID = te.LABORATORIOS_ID
                   INNER JOIN AREAS a ON a.ID = l.AREAS_ID
                   WHERE 1=1";
    
    if (!empty($hospital_id)) {
        $sqlResumen .= " AND a.HOSPITAL_UNI_ORG = :hospital_id";
    }

    $sqlResumen .= " GROUP BY l.ID ORDER BY total_estudios DESC";
    
    $stmtRes = $conn->prepare($sqlResumen);
    $stmtRes->execute($params);
    $resumen = $stmtRes->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(200, [
        "ok" => true,
        "data" => [
            "detallado" => $data,
            "resumen" => $resumen
        ]
    ]);

} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al generar reporte de estudios",
        "error" => $e->getMessage()
    ]);
}
?>