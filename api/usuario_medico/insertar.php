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

$usuarioId = $input["usuarioId"] ?? null;
$medicoExpediente = $input["medicoExpediente"] ?? null;

if ($usuarioId === null || !is_numeric($usuarioId) || $medicoExpediente === null || !is_numeric($medicoExpediente)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "usuarioId y medicoExpediente son obligatorios"
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

    $stmtM = $conn->prepare("SELECT EXPEDIENTE FROM MEDICOS WHERE EXPEDIENTE = :expediente LIMIT 1");
    $stmtM->execute([":expediente" => (int)$medicoExpediente]);
    if (!$stmtM->fetch()) {
        jsonResponse(400, [
            "ok" => false,
            "message" => "El médico seleccionado no existe"
        ]);
    }

    $stmtDup = $conn->prepare("SELECT id FROM usuario_medico WHERE usuario_id = :usuario_id AND medico_expediente = :medico_expediente LIMIT 1");
    $stmtDup->execute([
        ":usuario_id" => (int)$usuarioId,
        ":medico_expediente" => (int)$medicoExpediente
    ]);

    if ($stmtDup->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ese usuario ya está ligado a ese médico"
        ]);
    }

    $stmt = $conn->prepare("INSERT INTO usuario_medico (usuario_id, medico_expediente) VALUES (:usuario_id, :medico_expediente)");
    $stmt->execute([
        ":usuario_id" => (int)$usuarioId,
        ":medico_expediente" => (int)$medicoExpediente
    ]);

    jsonResponse(201, [
        "ok" => true,
        "message" => "Asignación usuario-médico insertada correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al insertar usuario-médico",
        "error" => $e->getMessage()
    ]);
}
?>