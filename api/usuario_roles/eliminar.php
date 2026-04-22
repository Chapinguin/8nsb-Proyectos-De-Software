<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../helpers/auth.php";
require_once __DIR__ . "/../../helpers/response.php";

// Aceptar DELETE o POST para mayor compatibilidad
if ($_SERVER["REQUEST_METHOD"] !== "DELETE" && $_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonResponse(405, [
        "ok" => false,
        "message" => "Método no permitido"
    ]);
}

requireRole("Administrador");

$input = json_decode(file_get_contents("php://input"), true);

$id = $input["id"] ?? null;
$usuario_id = $input["usuario_id"] ?? null;

if ($id === null && $usuario_id === null) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID o usuario_id es obligatorio"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    if ($id !== null) {
        // Eliminar por ID de la asignación
        $stmt = $conn->prepare("DELETE FROM usuario_roles WHERE id = :id");
        $stmt->execute([":id" => (int)$id]);
    } else {
        // Eliminar todos los roles de un usuario
        $stmt = $conn->prepare("DELETE FROM usuario_roles WHERE usuario_id = :usuario_id");
        $stmt->execute([":usuario_id" => (int)$usuario_id]);
    }

    jsonResponse(200, [
        "ok" => true,
        "message" => "Asignación(es) eliminada(s) correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al eliminar usuario-rol",
        "error" => $e->getMessage()
    ]);
}
?>