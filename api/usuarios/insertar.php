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

$username = trim($input["username"] ?? "");
$correo = trim($input["correo"] ?? "");
$password = $input["password"] ?? "";
$nombre = trim($input["nombre"] ?? "");
$estatus = $input["estatus"] ?? 1;

if ($username === "" || $password === "" || $nombre === "") {
    jsonResponse(400, [
        "ok" => false,
        "message" => "Username, password y nombre son obligatorios"
    ]);
}

if ($correo !== "" && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El correo no es válido"
    ]);
}

if (!is_numeric($estatus)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El estatus debe ser numérico"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlCheckUsername = "SELECT id FROM usuarios WHERE username = :username LIMIT 1";
    $stmtCheckUsername = $conn->prepare($sqlCheckUsername);
    $stmtCheckUsername->execute([":username" => $username]);

    if ($stmtCheckUsername->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe un usuario con ese username"
        ]);
    }

    if ($correo !== "") {
        $sqlCheckCorreo = "SELECT id FROM usuarios WHERE correo = :correo LIMIT 1";
        $stmtCheckCorreo = $conn->prepare($sqlCheckCorreo);
        $stmtCheckCorreo->execute([":correo" => $correo]);

        if ($stmtCheckCorreo->fetch()) {
            jsonResponse(409, [
                "ok" => false,
                "message" => "Ya existe un usuario con ese correo"
            ]);
        }
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (
                username,
                correo,
                password_hash,
                nombre,
                estatus
            ) VALUES (
                :username,
                :correo,
                :password_hash,
                :nombre,
                :estatus
            )";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":username" => $username,
        ":correo" => ($correo === "" ? null : $correo),
        ":password_hash" => $passwordHash,
        ":nombre" => $nombre,
        ":estatus" => (int)$estatus
    ]);

    $newId = $conn->lastInsertId();

    jsonResponse(201, [
        "ok" => true,
        "message" => "Usuario insertado correctamente",
        "id" => (int)$newId
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al insertar usuario",
        "error" => $e->getMessage()
    ]);
}
?>