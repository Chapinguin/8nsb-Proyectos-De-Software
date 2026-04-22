<?php

require_once __DIR__ . "/config/database.php";

header("Content-Type: application/json; charset=UTF-8");

try {
    $database = new Database();
    $conn = $database->getConnection();

    $username = "admin";
    $correo = "admin@hospital.com";
    $nombre = "Administrador General";
    $passwordPlano = "123456";
    $passwordHash = password_hash($passwordPlano, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (username, correo, password_hash, nombre, estatus)
            VALUES (:username, :correo, :password_hash, :nombre, 1)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":username" => $username,
        ":correo" => $correo,
        ":password_hash" => $passwordHash,
        ":nombre" => $nombre
    ]);

    $usuarioId = $conn->lastInsertId();

    $sqlRol = "SELECT id FROM roles WHERE nombre = 'Administrador' LIMIT 1";
    $stmtRol = $conn->query($sqlRol);
    $rol = $stmtRol->fetch();

    if (!$rol) {
        throw new Exception("No existe el rol Administrador");
    }

    $sqlUserRol = "INSERT INTO usuario_roles (usuario_id, rol_id)
                   VALUES (:usuario_id, :rol_id)";
    $stmtUserRol = $conn->prepare($sqlUserRol);
    $stmtUserRol->execute([
        ":usuario_id" => $usuarioId,
        ":rol_id" => $rol["id"]
    ]);

    echo json_encode([
        "ok" => true,
        "message" => "Usuario admin creado correctamente",
        "username" => $username,
        "password" => $passwordPlano
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "message" => "No se pudo crear el admin",
        "error" => $e->getMessage()
    ]);
}

?>