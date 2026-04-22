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
$habitacionesId = $input["habitacionesId"] ?? null;

if (
    $id === null || !is_numeric($id) ||
    $habitacionesId === null || !is_numeric($habitacionesId)
) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID y habitacionesId son obligatorios y deben ser numéricos"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlExiste = "SELECT ID, HABITACIONES_ID
                  FROM INGRESOS
                  WHERE ID = :id
                    AND HABITACIONES_ID = :habitacionesId
                  LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":id" => (int)$id,
        ":habitacionesId" => (int)$habitacionesId
    ]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el ingreso a eliminar"
        ]);
    }

    $sql = "DELETE FROM INGRESOS
            WHERE ID = :id
              AND HABITACIONES_ID = :habitacionesId";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":habitacionesId" => (int)$habitacionesId
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Ingreso eliminado correctamente"
    ]);
} catch (Throwable $e) {
    $mensaje = "Error al eliminar ingreso";

    if (
        stripos($e->getMessage(), "foreign key") !== false ||
        stripos($e->getMessage(), "constraint") !== false
    ) {
        $mensaje = "No se puede eliminar el ingreso porque está relacionado con otras tablas";
    }

    jsonResponse(500, [
        "ok" => false,
        "message" => $mensaje,
        "error" => $e->getMessage()
    ]);
}
?>