<?php
require_once __DIR__ . "/response.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): array
{
    if (!isset($_SESSION["user"])) {
        jsonResponse(401, [
            "ok" => false,
            "message" => "No autorizado. Debes iniciar sesión."
        ]);
    }

    return $_SESSION["user"];
}

function getUserRoles(): array
{
    $user = requireLogin();
    $roles = $user["roles"] ?? [];

    return array_map(function ($role) {
        return $role["nombre"] ?? "";
    }, $roles);
}

function requireRole(string $requiredRole): array
{
    $user = requireLogin();
    $roles = getUserRoles();

    if (!in_array($requiredRole, $roles, true)) {
        jsonResponse(403, [
            "ok" => false,
            "message" => "Acceso denegado. Se requiere el rol: {$requiredRole}"
        ]);
    }

    return $user;
}

function requireAnyRole(array $allowedRoles): array
{
    $user = requireLogin();
    $roles = getUserRoles();

    foreach ($allowedRoles as $role) {
        if (in_array($role, $roles, true)) {
            return $user;
        }
    }

    jsonResponse(403, [
        "ok" => false,
        "message" => "Acceso denegado. No cuentas con permisos suficientes."
    ]);
}
?>