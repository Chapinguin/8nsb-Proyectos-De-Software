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

$uniOrg = trim($input["uni_org"] ?? "");

if ($uniOrg === "") {
    jsonResponse(400, [
        "ok" => false,
        "message" => "UNI_ORG es obligatorio"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlExiste = "SELECT UNI_ORG
                  FROM HOSPITAL
                  WHERE UNI_ORG = :uni_org
                  LIMIT 1";

    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":uni_org" => $uniOrg
    ]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el hospital a eliminar"
        ]);
    }

    $sql = "DELETE FROM HOSPITAL
            WHERE UNI_ORG = :uni_org";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":uni_org" => $uniOrg
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Hospital eliminado correctamente"
    ]);

} catch (Throwable $e) {
    $mensaje = "Error al eliminar hospital";

    if (
        stripos($e->getMessage(), "foreign key") !== false ||
        stripos($e->getMessage(), "constraint") !== false
    ) {
        $mensaje = "No se puede eliminar el hospital porque tiene áreas u otros registros relacionados";
    }

    jsonResponse(500, [
        "ok" => false,
        "message" => $mensaje,
        "error" => $e->getMessage()
    ]);
}
?>