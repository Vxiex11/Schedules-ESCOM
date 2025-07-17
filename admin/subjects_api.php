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
        createSubject($conn, $data);
        break;
    case 'read':
        getSubjects($conn);
        break;
    case 'update':
        updateSubject($conn, $data);
        break;
    case 'delete':
        deleteSubject($conn, $data['id'], $data['password']);
        break;
    default:
        echo json_encode(['ok' => false, 'msg' => 'Acción no válida']);
}

function createSubject($conn, $data) {
    $subjectname = $data['subjectname'];

    $stmt = $conn->prepare("SELECT id FROM subjects WHERE nombre = ?");
    $stmt->bind_param("s", $subjectname);
    $stmt->execute();
    $stmt->store_result();

    // validation if already exist the subject name
    if ($stmt->num_rows > 0) {
        echo json_encode(["ok" => false, "msg" => "Subject name already exists"]);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO subjects (nombre) VALUES (?)");
    $stmt->bind_param("s", $subjectname);

    if ($stmt->execute()) {
        echo json_encode(["ok" => true]);
    } else {
        echo json_encode(["ok" => false, "msg" => "Error al crear"]);
    }
}

function getSubjects($conn) {
    $result = $conn->query("SELECT s.id, s.nombre AS subject_name, p.nombre_completo AS professor_name, p.state
     FROM subjects s 
     LEFT JOIN professor_subjects ps ON s.id = ps.id_subject
     LEFT JOIN professors p ON p.id = ps.id_professor");
    $subjects = [];

    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }

    echo json_encode(["ok" => true, "data" => $subjects]);
}

function updateSubject($conn, $data) {
    $id = $data['id'];
    $subjectname = $data['subjectname'];

    $stmt = $conn->prepare("UPDATE subjects SET nombre=? WHERE id=?");
    $stmt->bind_param("si", $subjectname, $id);

    if ($stmt->execute()) {
        echo json_encode(["ok" => true]);
    } else {
        echo json_encode(["ok" => false, "msg" => "Error al actualizar"]);
    }
}

function deleteSubject($conn, $id, $password) {   

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

    $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();

    echo json_encode(["ok" => $ok]);
}

?>
