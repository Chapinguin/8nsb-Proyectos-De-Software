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

// Obtener filtro opcional
$hospital_id = $_GET["hospital_id"] ?? null;

try {
    $database = new Database();
    $conn = $database->getConnection();

    $whereClause = "WHERE a.NOMBREAREA LIKE '%Urgencias%'";
    $params = [];

    if ($hospital_id && $hospital_id !== "") {
        $whereClause .= " AND a.HOSPITAL_UNI_ORG = :hospital_id";
        $params[":hospital_id"] = $hospital_id;
    }

    // Contar ingresos a Urgencias
    $sqlIngresos = "SELECT COUNT(*) as total FROM INGRESOS i 
                    JOIN HABITACIONES h ON i.HABITACIONES_ID = h.ID 
                    JOIN AREAS a ON h.AREAS_ID = a.ID 
                    $whereClause";
    
    $stmtIng = $conn->prepare($sqlIngresos);
    $stmtIng->execute($params);
    $totalIngresos = $stmtIng->fetch()["total"];

    // Contar egresos de Urgencias
    $sqlEgresos = "SELECT COUNT(*) as total FROM EGRESOS e 
                   JOIN HABITACIONES h ON e.HABITACIONES_ID = h.ID 
                   JOIN AREAS a ON h.AREAS_ID = a.ID 
                   $whereClause";
    
    $stmtEgr = $conn->prepare($sqlEgresos);
    $stmtEgr->execute($params);
    $totalEgresos = $stmtEgr->fetch()["total"];

    // Obtener últimos 5 movimientos en Urgencias
    $sqlRecientes = "SELECT 'Ingreso' as tipo_mov, i.FECHAINGRESO as fecha, h.NOMBREHABITACION 
                     FROM INGRESOS i
                     JOIN HABITACIONES h ON i.HABITACIONES_ID = h.ID 
                     JOIN AREAS a ON h.AREAS_ID = a.ID 
                     $whereClause
                     UNION ALL
                     SELECT 'Egreso' as tipo_mov, e.FECHAEGRESO as fecha, h.NOMBREHABITACION 
                     FROM EGRESOS e
                     JOIN HABITACIONES h ON e.HABITACIONES_ID = h.ID 
                     JOIN AREAS a ON h.AREAS_ID = a.ID 
                     $whereClause
                     ORDER BY fecha DESC LIMIT 5";
    
    $stmtRec = $conn->prepare($sqlRecientes);
    $stmtRec->execute($params);
    $recientes = $stmtRec->fetchAll();

    jsonResponse(200, [
        "ok" => true,
        "data" => [
            "ingresos" => (int)$totalIngresos,
            "egresos" => (int)$totalEgresos,
            "recientes" => $recientes
        ]
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al obtener reportes",
        "error" => $e->getMessage()
    ]);
}
?>