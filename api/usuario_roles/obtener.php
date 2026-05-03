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
                ur.id,
                ur.usuario_id,
                u.username,
                u.nombre AS usuario_nombre,
                ur.rol_id,
                r.nombre AS rol_nombre
            FROM usuario_roles ur
            INNER JOIN usuarios u ON u.id = ur.usuario_id
            INNER JOIN roles r ON r.id = ur.rol_id
            WHERE ur.id = :id
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([":id" => (int)$id]);

    $data = $stmt->fetch();

    if (!$data) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró la asignación usuario-rol"
        ]);
    }

    jsonResponse(200, [
        "ok" => true,
        "data" => $data
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al obtener usuario-rol",
        "error" => $e->getMessage()
    ]);
}
?>