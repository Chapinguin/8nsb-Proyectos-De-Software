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
$nombreEstudio = trim($input["nombreEstudio"] ?? "");
$requisitosEstudio = trim($input["requisitosEstudio"] ?? "");
$costo = $input["costo"] ?? null;
$laboratoriosId = $input["laboratoriosId"] ?? null;

if (
    $id === null || !is_numeric($id) ||
    $nombreEstudio === "" ||
    $laboratoriosId === null || !is_numeric($laboratoriosId)
) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID, nombre del estudio y laboratorio son obligatorios"
    ]);
}

if ($costo !== null && $costo !== "" && !is_numeric($costo)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El costo debe ser numérico"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlExiste = "SELECT ID
                  FROM TIPOESTUDIOS
                  WHERE ID = :id
                  LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([
        ":id" => (int)$id
    ]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el tipo de estudio a editar"
        ]);
    }

    $sqlCheckLaboratorio = "SELECT ID
                            FROM LABORATORIOS
                            WHERE ID = :id
                            LIMIT 1";
    $stmtCheckLaboratorio = $conn->prepare($sqlCheckLaboratorio);
    $stmtCheckLaboratorio->execute([
        ":id" => (int)$laboratoriosId
    ]);

    if (!$stmtCheckLaboratorio->fetch()) {
        jsonResponse(400, [
            "ok" => false,
            "message" => "El laboratorio seleccionado no existe"
        ]);
    }

    $sqlDuplicado = "SELECT ID
                     FROM TIPOESTUDIOS
                     WHERE UPPER(TRIM(NOMBREESTUDIO)) = UPPER(TRIM(:nombreEstudio))
                       AND LABORATORIOS_ID = :laboratoriosId
                       AND ID <> :id
                     LIMIT 1";
    $stmtDuplicado = $conn->prepare($sqlDuplicado);
    $stmtDuplicado->execute([
        ":nombreEstudio" => $nombreEstudio,
        ":laboratoriosId" => (int)$laboratoriosId,
        ":id" => (int)$id
    ]);

    if ($stmtDuplicado->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe otro tipo de estudio con ese nombre en ese laboratorio"
        ]);
    }

    $sql = "UPDATE TIPOESTUDIOS
            SET NOMBREESTUDIO = :nombreEstudio,
                REQUISITOSESTUDIO = :requisitosEstudio,
                COSTO = :costo,
                LABORATORIOS_ID = :laboratoriosId
            WHERE ID = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":nombreEstudio" => $nombreEstudio,
        ":requisitosEstudio" => ($requisitosEstudio === "" ? null : $requisitosEstudio),
        ":costo" => ($costo === "" ? null : $costo),
        ":laboratoriosId" => (int)$laboratoriosId
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Tipo de estudio actualizado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al editar tipo de estudio",
        "error" => $e->getMessage()
    ]);
}
?>