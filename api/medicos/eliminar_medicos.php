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

$expediente = $input["expediente"] ?? null;

if ($expediente === null || !is_numeric($expediente)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El expediente es obligatorio y debe ser numérico"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlExiste = "SELECT EXPEDIENTE
                  FROM MEDICOS
                  WHERE EXPEDIENTE = :expediente
                  LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":expediente" => (int)$expediente
    ]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el médico a eliminar"
        ]);
    }

    $sql = "DELETE FROM MEDICOS
            WHERE EXPEDIENTE = :expediente";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":expediente" => (int)$expediente
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Médico eliminado correctamente"
    ]);
} catch (Throwable $e) {
    $mensaje = "Error al eliminar médico";

    if (
        stripos($e->getMessage(), "foreign key") !== false ||
        stripos($e->getMessage(), "constraint") !== false
    ) {
        $mensaje = "No se puede eliminar el médico porque está relacionado con otros registros";
    }

    jsonResponse(500, [
        "ok" => false,
        "message" => $mensaje,
        "error" => $e->getMessage()
    ]);
}
?>