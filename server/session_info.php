<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

echo json_encode([
    'ok' => true,
    'user_id' => $_SESSION['user_id'],
    'full_name' => $_SESSION['full_name'],
    'role' => $_SESSION['role'],
]);

?>