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

    // Obtener todos los usuarios
    $sql = "SELECT 
                id,
                username,
                correo,
                nombre,
                estatus,
                fecha_creacion
            FROM usuarios
            ORDER BY id ASC";

    $stmt = $conn->query($sql);
    $usuarios = $stmt->fetchAll();

    // Para cada usuario, obtener sus roles
    foreach ($usuarios as &$usuario) {
        $sqlRoles = "SELECT r.id, r.nombre
                     FROM usuario_roles ur
                     INNER JOIN roles r ON r.id = ur.rol_id
                     WHERE ur.usuario_id = :usuario_id";
        
        $stmtRoles = $conn->prepare($sqlRoles);
        $stmtRoles->execute([":usuario_id" => $usuario["id"]]);
        $usuario["roles"] = $stmtRoles->fetchAll();
    }

    jsonResponse(200, [
        "ok" => true,
        "data" => $usuarios
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al listar usuarios",
        "error" => $e->getMessage()
    ]);
}
?>