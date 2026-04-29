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

    // 🔽 Filtros opcionales
    $hospital = $_GET['hospital'] ?? null;
    $area = $_GET['area'] ?? null;
    $laboratorio = $_GET['laboratorio'] ?? null;
    $tipo = $_GET['tipo'] ?? null;

    // Listar Hospitales

    $sqlHospitales = "SELECT 
                UNI_ORG,
                NOMUO,
                DIRECCION,
                DIRECTOR,
                TELEFONO
            FROM HOSPITAL
            ORDER BY UNI_ORG ASC";

    // Listar Areas
    
    $sqlAreas = "SELECT 
                a.ID,
                a.UBICACION,
                a.NOMBREAREA,
                a.ID1,
                a.HOSPITAL_UNI_ORG,
                h.NOMUO AS HOSPITAL
            FROM AREAS a
            INNER JOIN HOSPITAL h ON h.UNI_ORG = a.HOSPITAL_UNI_ORG
            ORDER BY a.ID ASC";

    // Listar Laboratorios

    $sqlLaboratorios = "SELECT 
                l.ID,
                l.NOMBRELABORATORIO,
                l.UBICACION,
                l.AREAS_ID,
                a.NOMBREAREA
            FROM LABORATORIOS l
            INNER JOIN AREAS a ON a.ID = l.AREAS_ID
            ORDER BY l.ID ASC";

    // Listar Tipo de Estudios

    $sqlTipoEstudios = "SELECT 
                t.ID,
                t.NOMBREESTUDIO,
                t.REQUISITOSESTUDIO,
                t.COSTO,
                t.LABORATORIOS_ID,
                l.NOMBRELABORATORIO
            FROM TIPOESTUDIOS t
            INNER JOIN LABORATORIOS l ON l.ID = t.LABORATORIOS_ID
            ORDER BY t.ID ASC";

    // Listar Estudios

    $sqlEstudios = "SELECT 
                e.ID,
                e.TIPOESTUDIOS_ID,
                te.NOMBREESTUDIO,
                e.MEDICOS_EXPEDIENTE,
                m.NOMBRE,
                m.APELLIDOPATERNO,
                m.APELLIDOMATERNO,
                e.FECHAESTUDIO,
                e.ESTATUS
            FROM ESTUDIOS e
            INNER JOIN TIPOESTUDIOS te ON te.ID = e.TIPOESTUDIOS_ID
            INNER JOIN MEDICOS m ON m.EXPEDIENTE = e.MEDICOS_EXPEDIENTE
            ORDER BY e.ID ASC";

    //

    $sql = "SELECT 
                te.NOMBREESTUDIO,
                COUNT(*) AS total
            FROM ESTUDIOS e
            INNER JOIN TIPOESTUDIOS te 
                ON te.ID = e.TIPOESTUDIOS_ID
            INNER JOIN LABORATORIOS l 
                ON l.ID = te.LABORATORIOS_ID
            INNER JOIN AREAS a 
                ON a.ID = l.AREAS_ID
            INNER JOIN HOSPITAL h 
                ON h.UNI_ORG = a.HOSPITAL_UNI_ORG
            WHERE 1=1";

    $params = [];

    // 🔽 Filtro hospital
    if (!empty($hospital)) {
        $sql .= " AND h.UNI_ORG = :hospital";
        $params[':hospital'] = $hospital;
    }

    // 🔽 Filtro área
    if (!empty($area)) {
        $sql .= " AND a.ID = :area";
        $params[':area'] = $area;
    }

    // 🔽 Filtro laboratorio
    if (!empty($laboratorio)) {
        $sql .= " AND l.ID = :laboratorio";
        $params[':laboratorio'] = $laboratorio;
    }

    // 🔽 Filtro tipo estudio
    if (!empty($tipo)) {
        $sql .= " AND te.ID = :tipo";
        $params[':tipo'] = $tipo;
    }

    $sql .= " 
        GROUP BY te.NOMBREESTUDIO
        ORDER BY total DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);

} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al generar reporte de estudios",
        "error" => $e->getMessage()
    ]);
}
?>