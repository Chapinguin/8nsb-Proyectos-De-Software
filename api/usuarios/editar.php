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
$username = trim($input["username"] ?? "");
$correo = trim($input["correo"] ?? "");
$nombre = trim($input["nombre"] ?? "");
$estatus = $input["estatus"] ?? null;
$password = $input["password"] ?? null;

if ($id === null || !is_numeric($id) || $username === "" || $nombre === "" || $estatus === null || !is_numeric($estatus)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "ID, username, nombre y estatus son obligatorios"
    ]);
}

if ($correo !== "" && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El correo no es válido"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlExiste = "SELECT id FROM usuarios WHERE id = :id LIMIT 1";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->execute([":id" => (int)$id]);

    if (!$stmtExiste->fetch()) {
        jsonResponse(404, [
            "ok" => false,
            "message" => "No se encontró el usuario a editar"
        ]);
    }

    $sqlDupUsername = "SELECT id FROM usuarios WHERE username = :username AND id <> :id LIMIT 1";
    $stmtDupUsername = $conn->prepare($sqlDupUsername);
    $stmtDupUsername->execute([
        ":username" => $username,
        ":id" => (int)$id
    ]);

    if ($stmtDupUsername->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe otro usuario con ese username"
        ]);
    }

    if ($correo !== "") {
        $sqlDupCorreo = "SELECT id FROM usuarios WHERE correo = :correo AND id <> :id LIMIT 1";
        $stmtDupCorreo = $conn->prepare($sqlDupCorreo);
        $stmtDupCorreo->execute([
            ":correo" => $correo,
            ":id" => (int)$id
        ]);

        if ($stmtDupCorreo->fetch()) {
            jsonResponse(409, [
                "ok" => false,
                "message" => "Ya existe otro usuario con ese correo"
            ]);
        }
    }

    if ($password !== null && trim($password) !== "") {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "UPDATE usuarios
                SET username = :username,
                    correo = :correo,
                    password_hash = :password_hash,
                    nombre = :nombre,
                    estatus = :estatus
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ":id" => (int)$id,
            ":username" => $username,
            ":correo" => ($correo === "" ? null : $correo),
            ":password_hash" => $passwordHash,
            ":nombre" => $nombre,
            ":estatus" => (int)$estatus
        ]);
    } else {
        $sql = "UPDATE usuarios
                SET username = :username,
                    correo = :correo,
                    nombre = :nombre,
                    estatus = :estatus
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ":id" => (int)$id,
            ":username" => $username,
            ":correo" => ($correo === "" ? null : $correo),
            ":nombre" => $nombre,
            ":estatus" => (int)$estatus
        ]);
    }

    jsonResponse(200, [
        "ok" => true,
        "message" => "Usuario actualizado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al editar usuario",
        "error" => $e->getMessage()
    ]);
}
?>