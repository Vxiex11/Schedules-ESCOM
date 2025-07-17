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

function getProfessors($conn) {
    $sql = "SELECT p.id, p.nombre_completo, p.email, p.oficina, p.state
            FROM professors p";
    $result = $conn->query($sql);

    $professors = [];
    while ($row = $result->fetch_assoc()) {
        $id = $row["id"];

        // Preparar consulta para obtener materias asociadas (nombres e IDs)
        $subject_sql = "SELECT s.id, s.nombre FROM professor_subjects ps 
                        JOIN subjects s ON ps.id_subject = s.id 
                        WHERE ps.id_professor = ?";
        $stmt = $conn->prepare($subject_sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $subject_res = $stmt->get_result();

        $subjects = [];
        $subject_ids = [];
        while ($sub = $subject_res->fetch_assoc()) {
            $subjects[] = $sub['nombre'];
            $subject_ids[] = (int)$sub['id'];
        }

        // add the same arrays to professor
        $row["materias"] = $subjects;
        $row["materias_ids"] = $subject_ids;

        $professors[] = $row;
    }

    echo json_encode(["ok" => true, "data" => $professors]);
}

function createProfessor($conn, $data) {
    $name = $conn->real_escape_string($data["name"]);
    $email = $conn->real_escape_string($data["email"]);
    $office = $conn->real_escape_string($data["office"]);
    $subjects = $data["subjects"] ?? [];
    $state = $data['state'] ?? 'active'; 


    $sql = "INSERT INTO professors (nombre_completo, email, oficina, state)
            VALUES ('$name', '$email', '$office', '$state')";

    if ($conn->query($sql)) {
        $professor_id = $conn->insert_id;

        foreach ($subjects as $subject_id) {
            $conn->query("INSERT INTO professor_subjects (id_professor, id_subject) 
                          VALUES ($professor_id, $subject_id)");
        }

        echo json_encode(["ok" => true]);
    } else {
        echo json_encode(["ok" => false, "error" => $conn->error]);
    }
}

function updateProfessor($conn, $data) {
    $id = (int)$data["id"];
    $name = $conn->real_escape_string($data["name"]);
    $email = $conn->real_escape_string($data["email"]);
    $office = $conn->real_escape_string($data["office"]);
    $subjects = $data["subjects"] ?? [];
    $state = $data['state'] ?? 'active'; 

    $sql = "UPDATE professors 
            SET nombre_completo='$name', email='$email', oficina='$office', state='$state'
            WHERE id=$id";

    if ($conn->query($sql)) {
        // Eliminar materias anteriores
        $conn->query("DELETE FROM professor_subjects WHERE id_professor=$id");

        // Insertar materias nuevas
        foreach ($subjects as $subject_id) {
            $conn->query("INSERT INTO professor_subjects (id_professor, id_subject) 
                          VALUES ($id, $subject_id)");
        }

        echo json_encode(["ok" => true]);
    } else {
        echo json_encode(["ok" => false, "error" => $conn->error]);
    }
}

function deleteProfessor($conn, $id, $password) {
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
 
    // Eliminate first the hours
    $stmt = $conn->prepare("DELETE FROM office_hours WHERE id_professor = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // first eliminate schedule classes where we find the professor
    $stmt = $conn->prepare("DELETE FROM class_schedules WHERE id_professor = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // if the professor has subjects, eliminate first
    $stmt = $conn->prepare("DELETE FROM professor_subjects WHERE id_professor = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // ELIMINATE PROFESSOR
    $stmt = $conn->prepare("DELETE FROM professors WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();

    echo json_encode(["ok" => $ok]);
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


switch ($action) {
    case 'read': getProfessors($conn); break;
    case 'create': createProfessor($conn, $data); break;
    case 'update': updateProfessor($conn, $data); break;
    case 'delete': deleteProfessor($conn, $data['id'], $data['password']); break;
    case 'get_subjects': getSubjects($conn); break;
    default: echo json_encode(["ok" => false, "error" => "Acción inválida"]); break;
}
?>
