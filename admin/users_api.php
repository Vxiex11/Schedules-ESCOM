<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include '../server/db.php';

$data = json_decode(file_get_contents("php://input"), true);
error_log("Datos recibidos: " . print_r($data, true));

$action = $data['action'] ?? '';

error_log("[SESSION] ID de sesión: " . session_id());
error_log("[SESSION] user_id: " . ($_SESSION['user_id'] ?? 'NO DEFINIDO'));

switch ($action) {
    case 'create':
        createUser($conn, $data);
        break;
    case 'read':
        getUsers($conn);
        break;
    case 'update':
        updateUser($conn, $data);
        break;
    case 'delete':
        deleteUser($conn, $data['id'], $data['password']);
        break;
    default:
        echo json_encode(['ok' => false, 'msg' => 'Acción no válida']);
}

function createUser($conn, $data) {
    $username = $data['username'];
    $password = isset($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
    $rol = $data['rol'];
    $full_name = $data['full_name'];

    if (!$password) {
        echo json_encode(["ok" => false, "msg" => "Password is required"]);
        return;
    }
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(["ok" => false, "msg" => "Username already exists"]);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO users (username, password, rol, full_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $rol, $full_name);

    if ($stmt->execute()) {
        echo json_encode(["ok" => true]);
    } else {
        echo json_encode(["ok" => false, "msg" => "Error al crear"]);
    }
}

function getUsers($conn) {
    $result = $conn->query("SELECT id, username, rol, full_name, created_at FROM users");
    $users = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode(["ok" => true, "data" => $users]);
}

function updateUser($conn, $data) {
    $id = $data['id'];
    $username = $data['username'];
    $rol = $data['rol'];
    $full_name = $data['full_name'];

    if (!empty($data['password'])) {
        // validate password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!password_verify($data['currentPassword'], $row['password'])) {
            echo json_encode(["ok" => false, "msg" => "Current password is incorrect"]);
            return;
        }

        // update the password
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, password=?, rol=?, full_name=? WHERE id=?");
        $stmt->bind_param("ssssi", $username, $password, $rol, $full_name, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, rol=?, full_name=? WHERE id=?");
        $stmt->bind_param("sssi", $username, $rol, $full_name, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(["ok" => true]);
    } else {
        echo json_encode(["ok" => false, "msg" => "Error al actualizar"]);
    }
}

function deleteUser($conn, $id, $password) {   

    // error_log(">>> [DELETE] TRYING ELIMINATE WITH ID: $id WITH PASSWORD: $password"); //DEBUG
    // ONLY ADMIN
    $adminId = $_SESSION['user_id'] ?? null;

    if (!$adminId) {
        echo json_encode(["ok" => false, "msg" => "Not authenticated"]);
        return;
    }

    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(["ok" => false, "msg" => "Unauthorized"]);
        return;
    }

    // security validations
    if (!$password) {
        echo json_encode(["ok" => false, "msg" => "Password is empty"]);
        return;
    }

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    if (!$row) {
        echo json_encode(["ok" => false, "msg" => "Invalid Credentials"]);
        return;
    }

    // error_log(">>> [DELETE] HASH IN DB: " . $row['password']); DEBUG


    if (!password_verify($password, $row['password'])) {
        echo json_encode(["ok" => false, "msg" => "Incorrect password"]);
        return;
    }

    // error_log(">>> [DELETE] VERIFICATE PASSWORD: CORRECT. ELIMINATING..."); DEBUG 5

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();

    echo json_encode(["ok" => $ok]);
}

?>
