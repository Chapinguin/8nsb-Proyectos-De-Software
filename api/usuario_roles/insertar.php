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

// Aceptar tanto usuario_id como usuarioId para mayor flexibilidad
$usuarioId = $input["usuario_id"] ?? $input["usuarioId"] ?? null;
$rolId = $input["rol_id"] ?? $input["rolId"] ?? null;

if ($usuarioId === null || !is_numeric($usuarioId) || $rolId === null || !is_numeric($rolId)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "usuario_id y rol_id son obligatorios"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

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

    $stmtDup = $conn->prepare("SELECT id FROM usuario_roles WHERE usuario_id = :usuario_id AND rol_id = :rol_id LIMIT 1");
    $stmtDup->execute([
        ":usuario_id" => (int)$usuarioId,
        ":rol_id" => (int)$rolId
    ]);

    if ($stmtDup->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ese usuario ya tiene asignado ese rol"
        ]);
    }

    $stmt = $conn->prepare("INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (:usuario_id, :rol_id)");
    $stmt->execute([
        ":usuario_id" => (int)$usuarioId,
        ":rol_id" => (int)$rolId
    ]);

    jsonResponse(201, [
        "ok" => true,
        "message" => "Rol asignado correctamente al usuario"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al insertar usuario-rol",
        "error" => $e->getMessage()
    ]);
}
?>