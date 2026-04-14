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
$usuarioId = $input["usuarioId"] ?? null;
$rolId = $input["rolId"] ?? null;

if ($id === null || !is_numeric($id) || $usuarioId === null || !is_numeric($usuarioId) || $rolId === null || !is_numeric($rolId)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID, usuarioId y rolId son obligatorios"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $stmtExiste = $conn->prepare("SELECT id FROM usuario_roles WHERE id = :id LIMIT 1");
    $stmtExiste->execute([":id" => (int)$id]);
    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró la asignación usuario-rol a editar"
        ]);
    }

    $stmtU = $conn->prepare("SELECT id FROM usuarios WHERE id = :id LIMIT 1");
    $stmtU->execute([":id" => (int)$usuarioId]);
    if (!$stmtU->fetch()) {
        jsonResponse(400, [
            "ok" => false,
            "message" => "El usuario seleccionado no existe"
        ]);
    }

    $stmtR = $conn->prepare("SELECT id FROM roles WHERE id = :id LIMIT 1");
    $stmtR->execute([":id" => (int)$rolId]);
    if (!$stmtR->fetch()) {
        jsonResponse(400, [
            "ok" => false,
            "message" => "El rol seleccionado no existe"
        ]);
    }

    $stmtDup = $conn->prepare("SELECT id FROM usuario_roles WHERE usuario_id = :usuario_id AND rol_id = :rol_id AND id <> :id LIMIT 1");
    $stmtDup->execute([
        ":usuario_id" => (int)$usuarioId,
        ":rol_id" => (int)$rolId,
        ":id" => (int)$id
    ]);

    if ($stmtDup->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ese usuario ya tiene asignado ese rol"
        ]);
    }

    $stmt = $conn->prepare("UPDATE usuario_roles SET usuario_id = :usuario_id, rol_id = :rol_id WHERE id = :id");
    $stmt->execute([
        ":id" => (int)$id,
        ":usuario_id" => (int)$usuarioId,
        ":rol_id" => (int)$rolId
    ]);

    jsonResponse(200, [
        "ok" => true,
        "message" => "Asignación usuario-rol actualizada correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al editar usuario-rol",
        "error" => $e->getMessage()
    ]);
}
?>