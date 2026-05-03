<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../helpers/response.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonResponse(405, [
        "ok" => false,
        "message" => "Método no permitido"
    ]);
}

$input = json_decode(file_get_contents("php://input"), true);

$username = trim($input["username"] ?? "");
$password = (string)($input["password"] ?? "");

if ($username === "" || $password === "") {
    jsonResponse(400, [
        "ok" => false,
        "message" => "Username y password son obligatorios"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sql = "SELECT id, username, correo, password_hash, nombre, estatus
            FROM usuarios
            WHERE username = :username
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":username" => $username
    ]);

    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(401, [
            "ok" => false,
            "message" => "Usuario o contraseña incorrectos"
        ]);
    }

    if ((int)$user["estatus"] !== 1) {
        jsonResponse(403, [
            "ok" => false,
            "message" => "Usuario inactivo"
        ]);
    }

    if (!password_verify($password, $user["password_hash"])) {
        jsonResponse(401, [
            "ok" => false,
            "message" => "Usuario o contraseña incorrectos"
        ]);
    }

    $sqlRoles = "SELECT r.id, r.nombre
                 FROM usuario_roles ur
                 INNER JOIN roles r ON r.id = ur.rol_id
                 WHERE ur.usuario_id = :usuario_id
                 ORDER BY r.nombre ASC";

    $stmtRoles = $conn->prepare($sqlRoles);
    $stmtRoles->execute([
        ":usuario_id" => $user["id"]
    ]);

    $roles = $stmtRoles->fetchAll();

    $sqlMedico = "SELECT medico_expediente
                  FROM usuario_medico
                  WHERE usuario_id = :usuario_id
                  LIMIT 1";

    $stmtMedico = $conn->prepare($sqlMedico);
    $stmtMedico->execute([
        ":usuario_id" => $user["id"]
    ]);

    $medico = $stmtMedico->fetch();

    session_regenerate_id(true);

    $_SESSION["user"] = [
        "id" => (int)$user["id"],
        "username" => $user["username"],
        "nombre" => $user["nombre"],
        "correo" => $user["correo"],
        "estatus" => (int)$user["estatus"],
        "roles" => $roles,
        "medico_expediente" => isset($medico["medico_expediente"])
            ? (int)$medico["medico_expediente"]
            : null
    ];

    jsonResponse(200, [
        "ok" => true,
        "message" => "Login correcto",
        "user" => $_SESSION["user"]
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error interno en login",
        "error" => $e->getMessage()
    ]);
}
?>