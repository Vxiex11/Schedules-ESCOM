<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../server/db.php'; 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$data = json_decode(file_get_contents("php://input"), true);

// verify if action arrived
if (!isset($data["action"])) {
    echo json_encode(["ok" => false, "msg" => "Missing action"]);
    exit;
}

$action = $data["action"] ?? '';

function getGroup($conn) {
    $sql = "SELECT id, nombre AS nombre_grupo FROM groups";
    $result = $conn->query($sql);

    if($result){
        $data = [];
        while($row = $result->fetch_assoc()){
            $data[] = $row;
        }

        echo json_encode(["ok" => true, "items" => $data]); // content data
    }else{
        echo json_encode(["ok" => false, "error" => $conn->error]);
    }
}

function getClassroom($conn) {
    $sql = "SELECT DISTINCT nombre, MIN(id) as id FROM classrooms GROUP BY nombre";
    $result = $conn->query($sql);

    if($result){
        $data = [];
        while($row = $result->fetch_assoc()){
            $data[] = $row;
        }

        echo json_encode(["ok" => true, "items" => $data]); // content data
    }else{
        echo json_encode(["ok" => false, "error" => $conn->error]);
    }
}

function updateGroup($conn, $data){
    $id = (int)$data["id"];
    $name_group = $conn->real_escape_string($data["group_name"]);

    $sql = "UPDATE groups 
            SET nombre = '$name_group'
            WHERE id=$id";

    if ($conn->query($sql)) {
        echo json_encode(["ok" => true, "items" => $data]);
    } else {
        echo json_encode(["ok" => false, "error" => $conn->error]);
    }
}

function updateClassroom($conn, $data){
    $id = (int)$data["id"];
    $name_classroom = $conn->real_escape_string($data["classroom_name"]);

    $sql = "UPDATE classrooms 
            SET nombre = '$name_classroom'
            WHERE id=$id";

    if ($conn->query($sql)) {
        echo json_encode(["ok" => true, "items" => $data]);
    } else {
        echo json_encode(["ok" => false, "error" => $conn->error]);
    }
}

function createClassroom($conn, $data) {
    $id = (int)$data["id"];
    $name_classroom = $conn->real_escape_string($data["classroom_name"]);

    $sql = "INSERT INTO classrooms (nombre)
            VALUES ('$name_classroom')";

    if ($conn->query($sql)) {
        echo json_encode(["ok" => true]);
    } else {
        echo json_encode(["ok" => false, "error" => $conn->error]);
    }
}

function createGroup($conn, $data) {
    $id = (int)$data["id"];
    $name_group = $conn->real_escape_string($data["group_name"]);

    $sql = "INSERT INTO groups (nombre)
            VALUES ('$name_group')";

    if ($conn->query($sql)) {
        echo json_encode(["ok" => true]);
    } else {
        echo json_encode(["ok" => false, "error" => $conn->error]);
    }
}

// view subjects to front
function getSubjects($conn) {
    $sql = "SELECT id, nombre FROM subjects";
    $result = $conn->query($sql);

    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }

    echo json_encode(["ok" => true, "data" => $subjects]);
}

function deleteClassroom($conn, $id, $password, $table) {
    $adminId = $_SESSION['user_id'] ?? null;

    if (!$adminId) {
        echo json_encode(["ok" => false, "msg" => "Not authenticated"]);
        return;
    }

    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(["ok" => false, "msg" => "Unauthorized"]);
        return;
    }

    if (!$password) {
        echo json_encode(["ok" => false, "msg" => "Password is empty"]);
        return;
    }

    // Verificar la contraseña del admin
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    if (!$row || !password_verify($password, $row['password'])) {
        echo json_encode(["ok" => false, "msg" => "Incorrect password"]);
        return;
    }
 
    // ELIMINATE classroom
    $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();

    echo json_encode(["ok" => $ok]);
}

switch ($action) {
    case 'read_classroom': getClassroom($conn); break;
    case 'read_groups' : getGroup($conn); break;
    case 'createClassroom': createClassroom($conn, $data); break;
    case 'createGroup': createGroup($conn, $data); break;
    case 'updateClassroom': updateClassroom($conn, $data); break;
    case 'updateGroup': updateGroup($conn, $data); break;
    case 'delete': deleteClassroom($conn, $data['id'], $data['password']); break;
    case 'getClassroom': getClassroom($conn); break;
    case 'getGroup': getGroup($conn); break;
    default: echo json_encode(["ok" => false, "error" => "Acción inválida"]); break;
}
?>
