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
$nombre = trim($input["nombre"] ?? "");
$descripcion = trim($input["descripcion"] ?? "");

if ($id === null || !is_numeric($id) || $nombre === "") {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID y nombre del rol son obligatorios"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlExiste = "SELECT id FROM roles WHERE id = :id LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([":id" => (int)$id]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el rol a editar"
        ]);
    }

    $sqlDup = "SELECT id
               FROM roles
               WHERE UPPER(TRIM(nombre)) = UPPER(TRIM(:nombre))
                 AND id <> :id
               LIMIT 1";
    $stmtDup = $conn->prepare($sqlDup);
    $stmtDup->execute([
        ":nombre" => $nombre,
        ":id" => (int)$id
    ]);

    if ($stmtDup->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe otro rol con ese nombre"
        ]);
    }

    $sql = "UPDATE roles
            SET nombre = :nombre,
                descripcion = :descripcion
            WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id" => (int)$id,
        ":nombre" => $nombre,
        ":descripcion" => ($descripcion === "" ? null : $descripcion)
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Rol actualizado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al editar rol",
        "error" => $e->getMessage()
    ]);
}
?>