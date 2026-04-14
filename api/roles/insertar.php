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

$nombre = trim($input["nombre"] ?? "");
$descripcion = trim($input["descripcion"] ?? "");

if ($nombre === "") {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El nombre del rol es obligatorio"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlDup = "SELECT id FROM roles WHERE UPPER(TRIM(nombre)) = UPPER(TRIM(:nombre)) LIMIT 1";
    $stmtDup = $conn->prepare($sqlDup);
    $stmtDup->execute([":nombre" => $nombre]);

    if ($stmtDup->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe un rol con ese nombre"
        ]);
    }

    $sql = "INSERT INTO roles (nombre, descripcion)
            VALUES (:nombre, :descripcion)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":nombre" => $nombre,
        ":descripcion" => ($descripcion === "" ? null : $descripcion)
    ]);

    jsonResponse(201, [
        "ok" => true,
        "message" => "Rol insertado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al insertar rol",
        "error" => $e->getMessage()
    ]);
}
?>