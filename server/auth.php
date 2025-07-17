<?php
// admin/server/auth.php
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$username = $conn->real_escape_string($data['username'] ?? '');
$password = $data['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(['ok' => false, 'msg' => 'Faltan campos.']);
    exit;
}

$sql = "SELECT id, full_name, password, rol FROM users WHERE username = '$username' LIMIT 1";
$res = $conn->query($sql);

if ($res && $res->num_rows === 1) {
    $user = $res->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        // Credenciales correctas
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['rol'];
        $_SESSION['full_name'] = $user['full_name'];
        echo json_encode(['ok' => true, 'role' => $user['rol']]);
        exit;
    }
}

echo json_encode(['ok' => false, 'msg' => 'User or password are incorrects.']);

?>