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

$expediente = $input["expediente"] ?? null;
$apellidoPaterno = trim($input["apellidoPaterno"] ?? "");
$apellidoMaterno = trim($input["apellidoMaterno"] ?? "");
$nombre = trim($input["nombre"] ?? "");
$telefonoMovil = $input["telefonoMovil"] ?? null;
$telefonoCasa = $input["telefonoCasa"] ?? null;
$especialidadesId = $input["especialidadesId"] ?? null;
$hospitalUniOrg = trim($input["hospitalUniOrg"] ?? "");

if (
    $expediente === null || !is_numeric($expediente) ||
    $apellidoPaterno === "" ||
    $apellidoMaterno === "" ||
    $nombre === "" ||
    $especialidadesId === null || !is_numeric($especialidadesId) ||
    $hospitalUniOrg === ""
) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "Expediente, apellidos, nombre, especialidad y hospital son obligatorios"
    ]);
}

if ($telefonoMovil !== null && $telefonoMovil !== "" && !is_numeric($telefonoMovil)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El teléfono móvil debe ser numérico"
    ]);
}

if ($telefonoCasa !== null && $telefonoCasa !== "" && !is_numeric($telefonoCasa)) {
    jsonResponse(400, [
        "ok" => false,
        "message" => "El teléfono de casa debe ser numérico"
    ]);
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sqlCheckMedico = "SELECT EXPEDIENTE
                       FROM MEDICOS
                       WHERE EXPEDIENTE = :expediente
                       LIMIT 1";
    $stmtCheckMedico = $conn->prepare($sqlCheckMedico);
    $stmtCheckMedico->execute([
        ":expediente" => (int)$expediente
    ]);

    if ($stmtCheckMedico->fetch()) {
        jsonResponse(409, [
            "ok" => false,
            "message" => "Ya existe un médico con ese expediente"
        ]);
    }

    $sqlCheckEspecialidad = "SELECT ID
                             FROM ESPECIALIDADES
                             WHERE ID = :id
                             LIMIT 1";
    $stmtCheckEspecialidad = $conn->prepare($sqlCheckEspecialidad);
    $stmtCheckEspecialidad->execute([
        ":id" => (int)$especialidadesId
    ]);

    if (!$stmtCheckEspecialidad->fetch()) {
        jsonResponse(400, [
            "ok" => false,
            "message" => "La especialidad seleccionada no existe"
        ]);
    }

    $sqlCheckHospital = "SELECT UNI_ORG
                         FROM HOSPITAL
                         WHERE UNI_ORG = :uni_org
                         LIMIT 1";
    $stmtCheckHospital = $conn->prepare($sqlCheckHospital);
    $stmtCheckHospital->execute([
        ":uni_org" => $hospitalUniOrg
    ]);

    if (!$stmtCheckHospital->fetch()) {
        jsonResponse(400, [
            "ok" => false,
            "message" => "El hospital seleccionado no existe"
        ]);
    }

    $sql = "INSERT INTO MEDICOS (
                EXPEDIENTE,
                APELLIDOPATERNO,
                APELLIDOMATERNO,
                NOMBRE,
                TELEFONOMOVIL,
                TELEFONOCASA,
                ESPECIALIDADES_ID,
                HOSPITAL_UNI_ORG
            ) VALUES (
                :expediente,
                :apellidoPaterno,
                :apellidoMaterno,
                :nombre,
                :telefonoMovil,
                :telefonoCasa,
                :especialidadesId,
                :hospitalUniOrg
            )";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":expediente" => (int)$expediente,
        ":apellidoPaterno" => $apellidoPaterno,
        ":apellidoMaterno" => $apellidoMaterno,
        ":nombre" => $nombre,
        ":telefonoMovil" => ($telefonoMovil === "" ? null : $telefonoMovil),
        ":telefonoCasa" => ($telefonoCasa === "" ? null : $telefonoCasa),
        ":especialidadesId" => (int)$especialidadesId,
        ":hospitalUniOrg" => $hospitalUniOrg
    ]);

    jsonResponse(201, [
        "ok" => true,
        "message" => "Médico insertado correctamente"
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "ok" => false,
        "message" => "Error al insertar médico",
        "error" => $e->getMessage()
    ]);
}
?>