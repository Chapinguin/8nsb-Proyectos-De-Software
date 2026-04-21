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

    // Contar ingresos a Urgencias
    $sqlIngresos = "SELECT COUNT(*) as total FROM INGRESOS i 
                    JOIN HABITACIONES h ON i.HABITACIONES_ID = h.ID 
                    JOIN AREAS a ON h.AREAS_ID = a.ID 
                    WHERE a.NOMBREAREA LIKE '%Urgencias%'";
    
    $stmtIng = $conn->query($sqlIngresos);
    $totalIngresos = $stmtIng->fetch()["total"];

    // Contar egresos de Urgencias
    $sqlEgresos = "SELECT COUNT(*) as total FROM EGRESOS e 
                   JOIN HABITACIONES h ON e.HABITACIONES_ID = h.ID 
                   JOIN AREAS a ON h.AREAS_ID = a.ID 
                   WHERE a.NOMBREAREA LIKE '%Urgencias%'";
    
    $stmtEgr = $conn->query($sqlEgresos);
    $totalEgresos = $stmtEgr->fetch()["total"];

    // Obtener últimos 5 movimientos en Urgencias para darle dinamismo
    $sqlRecientes = "SELECT 'Ingreso' as tipo_mov, i.FECHAINGRESO as fecha, h.NOMBREHABITACION 
                     FROM INGRESOS i
                     JOIN HABITACIONES h ON i.HABITACIONES_ID = h.ID 
                     JOIN AREAS a ON h.AREAS_ID = a.ID 
                     WHERE a.NOMBREAREA LIKE '%Urgencias%'
                     UNION ALL
                     SELECT 'Egreso' as tipo_mov, e.FECHAEGRESO as fecha, h.NOMBREHABITACION 
                     FROM EGRESOS e
                     JOIN HABITACIONES h ON e.HABITACIONES_ID = h.ID 
                     JOIN AREAS a ON h.AREAS_ID = a.ID 
                     WHERE a.NOMBREAREA LIKE '%Urgencias%'
                     ORDER BY fecha DESC LIMIT 5";
    
    $stmtRec = $conn->query($sqlRecientes);
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