<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../helpers/auth.php";
require_once __DIR__ . "/../../helpers/response.php";

if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
    jsonResponse(405, [
        "ok" => false,
        "message" => "Método no permitido"
    ]);
}

requireRole("Administrador");

$input = json_decode(file_get_contents("php://input"), true);

$id = $input["id"] ?? null;

if ($id === null || !is_numeric($id)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El ID es obligatorio y debe ser numérico"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $stmtExiste = $conn->prepare("SELECT id FROM usuario_medico WHERE id = :id LIMIT 1");
    $stmtExiste->execute([":id" => (int)$id]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró la asignación usuario-médico a eliminar"
        ]);
    }

    $stmt = $conn->prepare("DELETE FROM usuario_medico WHERE id = :id");
    $stmt->execute([":id" => (int)$id]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Asignación usuario-médico eliminada correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al eliminar usuario-médico",
        "error" => $e->getMessage()
    ]);
}
?>