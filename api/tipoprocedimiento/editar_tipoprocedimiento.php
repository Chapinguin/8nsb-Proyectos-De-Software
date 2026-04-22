<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../helpers/auth.php";
require_once __DIR__ . "/../../helpers/response.php";

if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
    jsonResponse(405, [
        "ok" => false,
        "message" => "Método no permitido"
    ]);
}

requireRole("Administrador");

$input = json_decode(file_get_contents("php://input"), true);

$id = $input["id"] ?? null;
$nombreProcedimiento = trim($input["nombreProcedimiento"] ?? "");
$requisitos = trim($input["requisitos"] ?? "");
$estatus = $input["estatus"] ?? null;

if (
    $id === null || !is_numeric($id) ||
    $nombreProcedimiento === ""
) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID y nombre del procedimiento son obligatorios"
    ]);
}

if ($estatus !== null && $estatus !== "" && !is_numeric($estatus)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El estatus debe ser numérico"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlExiste = "SELECT ID
                  FROM TIPOPROCEDIMIENTO
                  WHERE ID = :id
                  LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":id" => (int)$id
    ]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el tipo de procedimiento a editar"
        ]);
    }

    $sqlDuplicado = "SELECT ID
                     FROM TIPOPROCEDIMIENTO
                     WHERE UPPER(TRIM(NOMBREPROCEDIMIENTO)) = UPPER(TRIM(:nombreProcedimiento))
                       AND ID <> :id
                     LIMIT 1";
    $stmtDuplicado = $conn->prepare($sqlDuplicado);
    $stmtDuplicado->execute([
        ":nombreProcedimiento" => $nombreProcedimiento,
        ":id" => (int)$id
    ]);

    if ($stmtDuplicado->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe otro tipo de procedimiento con ese nombre"
        ]);
    }

    $sql = "UPDATE TIPOPROCEDIMIENTO
            SET NOMBREPROCEDIMIENTO = :nombreProcedimiento,
                REQUISITOS = :requisitos,
                ESTATUS = :estatus
            WHERE ID = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":nombreProcedimiento" => $nombreProcedimiento,
        ":requisitos" => ($requisitos === "" ? null : $requisitos),
        ":estatus" => ($estatus === "" ? null : $estatus)
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Tipo de procedimiento actualizado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al editar tipo de procedimiento",
        "error" => $e->getMessage()
    ]);
}
?>