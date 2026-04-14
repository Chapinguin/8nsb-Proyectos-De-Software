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

requireRole("Administrador");

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sql = "SELECT 
                ur.id,
                ur.usuario_id,
                u.username,
                u.nombre AS usuario_nombre,
                ur.rol_id,
                r.nombre AS rol_nombre
            FROM usuario_roles ur
            INNER JOIN usuarios u ON u.id = ur.usuario_id
            INNER JOIN roles r ON r.id = ur.rol_id
            ORDER BY ur.id ASC";

    $stmt = $conn->query($sql);
    $data = $stmt->fetchAll();

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al listar usuario_roles",
        "error" => $e->getMessage()
    ]);
}
?>