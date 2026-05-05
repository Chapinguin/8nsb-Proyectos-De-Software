<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../helpers/auth.php";
require_once __DIR__ . "/../../helpers/response.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
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

    $sqlExiste = "SELECT ID
                  FROM HABITACIONES
                  WHERE ID = :id
                  LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":id" => (int)$id
    ]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró la habitación a eliminar"
        ]);
    }

    $sql = "DELETE FROM HABITACIONES
            WHERE ID = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Habitación eliminada correctamente"
    ]);
} catch (Throwable $e) {
    $mensaje = "Error al eliminar habitación";

    if (
        stripos($e->getMessage(), "foreign key") !== false ||
        stripos($e->getMessage(), "constraint") !== false
    ) {
        $mensaje = "No se puede eliminar la habitación porque está relacionada con otras tablas";
    }

    jsonResponse(500, [
        "ok" => false,
        "message" => $mensaje,
        "error" => $e->getMessage()
    ]);
}
?>