<?php
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$full_name = trim($data["full_name"]);
$username = trim($data["username"]);
$password = $data["password"];

if (!isset($data["full_name"], $data["username"], $data["password"])) {
    echo json_encode(["ok" => false, "msg" => "Datos incompletos."]);
    exit;
}

// Validaciones básicas
if (strlen($username) < 3 || strlen($password) < 7 || strlen($full_name) < 3) {
    echo json_encode(["ok" => false, "msg" => "Datos inválidos."]);
    exit;
}

// Verifica si ya existe el usuario
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(["ok" => false, "msg" => "El usuario ya existe."]);
    exit;
}
$stmt->close();

// Hashea la contraseña
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Inserta el nuevo usuario
$role = 'student';
$stmt = $conn->prepare("INSERT INTO users (username, password, full_name, rol) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $hashed, $full_name, $role);

if ($stmt->execute()) {
    echo json_encode(["ok" => true]);
} else {
    echo json_encode(["ok" => false, "msg" => "Error al registrar."]);
}
