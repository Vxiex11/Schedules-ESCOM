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
        createTimetable($conn, $data);
        break;
    case 'read':
        getTimetables($conn);
        break;
    case 'update':
        updateTimetable($conn, $data);
        break;
    case 'delete':
        deleteTimetable($conn, $data['id'], $data['password']);
        break;
    case 'get_professors':
        fetchData($conn,'professors',['id','nombre_completo']);
        break;
    case 'get_subjects':
        fetchData($conn, 'subjects', ['id','nombre']);
        break;
    case 'get_groups':
        fetchData($conn,'groups',['id','nombre']);
        break;
    case 'get_days':
        fetchData($conn,'class_schedules',['dia']);
        break;
    case 'get_StartTime':
        fetchData($conn,'class_schedules',['hora_inicio']);
        break;
    case 'get_EndTime':
        fetchData($conn,'class_schedules',['hora_fin']);
        break;
    case 'get_Classroom':
        fetchData($conn,'classrooms',['id','nombre']);
        break;
    default:
        echo json_encode(['ok' => false, 'msg' => 'Acción no válida']);
}

function fetchData($conn, $table, $fields=['id']){
    // NO SQLI
    // Lista blanca de tablas y columnas permitidas
    $allowedTables = [
        'professors' => ['id', 'nombre_completo'],
        'subjects'   => ['id', 'nombre'],
        'groups'     => ['id', 'nombre'],
        'class_schedules'       => ['id','dia','hora_inicio','hora_fin'],
        'classrooms'    => ['id', 'nombre']
    ];

    // validate if the table is on
    if (!array_key_exists($table, $allowedTables)) {
        echo json_encode(["ok" => false, "msg" => "Invalid table"]);
        exit;
    }

    // Si no se especifican campos, usar los por defecto de esa tabla
    if (empty($fields)) {
        $fields = $allowedTables[$table];
    }

    // Validar que todos los campos estén permitidos para esa tabla
    foreach ($fields as $field) {
        if (!in_array($field, $allowedTables[$table])) {
            echo json_encode(["ok" => false, "msg" => "Invalid field: $field"]);
            exit;
        }
    }

    $columns = implode(',', $fields); // add columns in the query

    if ($table === 'class_schedules' && $fields === ['dia']) {
        $result = $conn->query("SELECT DISTINCT dia FROM $table ORDER BY dia ASC");
    } 
    else if($table === 'class_schedules' && $fields === ['hora_inicio']){
        $result = $conn->query("SELECT DISTINCT hora_inicio FROM $table ORDER BY hora_inicio ASC");
    }else if($table === 'class_schedules' && $fields === ['hora_fin']){
        $result = $conn->query("SELECT DISTINCT hora_fin FROM $table ORDER BY hora_fin ASC");
    }else {
        $columns = implode(',', $fields);
        $result = $conn->query("SELECT $columns FROM $table");
    }

    if(!$result){
        echo json_encode(["ok" => false, "msg" => "Query failed", "error" => $conn->error]);
        exit;
    }
    $data = [];

    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }
    echo json_encode(["ok" => true, "data" => $data]);
    exit;
}

function createTimetable($conn, $data) {
    $professor_id = $data["professor_id"];
    $group_id     = $data["group_id"];
    $subject_id   = $data["subject_id"];
    $days         = $data["days"];
    $startTime    = $data["startTime"];
    $endTime      = $data["endTime"];
    $id_classroom    = $data["id_classroom"];

    // validation if doesnt any field full
    if (!$professor_id || !$group_id || !$subject_id || !$days || !$startTime || !$endTime || !$id_classroom) {
        echo json_encode(["ok" => false, "msg" => "Missing required fields."]);
        exit;
    }
    // validation 1h 30m
    $start = new DateTime($startTime);
    $end   = new DateTime($endTime);
    $diff  = $start->diff($end);
    $minutes = $diff->h * 60 + $diff->i;
    if ($minutes !== 90) {
        echo json_encode(["ok" => false, "msg" => "Class must last exactly 1 hour and 30 minutes."]);
        exit;
    }

    // Validaciones de conflicto
    if (hasConflict($conn, 'id_professor', $professor_id, $days, $startTime, $endTime)) {
        echo json_encode(["ok" => false, "msg" => "Professor already has class in this schedule."]);
        exit;
    }

    if (hasConflict($conn, 'id_classroom', $id_classroom, $days, $startTime, $endTime)) {
        echo json_encode(["ok" => false, "msg" => "Classroom already in use."]);
        exit;
    }

    // group-subject
    $group_subject_id = getGroupSubjectId($conn, $group_id, $subject_id);

    if (hasConflict($conn, 'id_group_subject', $group_subject_id, $days, $startTime, $endTime)) {
        echo json_encode(["ok" => false, "msg" => "Group already has class at this time."]);
        exit;
    }

    // quit the same subject in the same day
    $check = $conn->prepare("SELECT c.id FROM class_schedules c
        JOIN group_subjects gs ON c.id_group_subject = gs.id
        WHERE gs.id_group = ? AND gs.id_subject = ? AND dia = ?");
    $check->bind_param("iis", $group_id, $subject_id, $days);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows > 0) {
        echo json_encode(["ok" => false, "msg" => "Group already has this subject on this day."]);
        exit;
    }

    // insert data in class_schedules
    $stmt = $conn->prepare("INSERT INTO class_schedules 
        (id_professor, id_group_subject, dia, hora_inicio, hora_fin, id_classroom)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $professor_id, $group_subject_id, $days, $startTime, $endTime, $id_classroom);

    if ($stmt->execute()) {
        echo json_encode(["ok" => true]);
        exit;
    } else {
        echo json_encode(["ok" => false, "msg" => "Error inserting schedule"]);
        exit;
    }
}

function getTimetables($conn) {
    $result = $conn->query("
        SELECT 
            c.id, 
            p.id AS professor_id,
            s.id AS subject_id,
            g.id AS group_id,
            c.id_classroom,
            p.nombre_completo, 
            s.nombre AS nombre_materia, 
            g.nombre AS nombre_grupo,
            c.dia, 
            c.hora_inicio, 
            c.hora_fin, 
            cl.nombre AS nombre_salon
        FROM class_schedules c
        LEFT JOIN professors p ON c.id_professor = p.id
        LEFT JOIN group_subjects gs ON c.id_group_subject = gs.id
        LEFT JOIN subjects s ON gs.id_subject = s.id
        LEFT JOIN groups g ON gs.id_group = g.id
        LEFT JOIN classrooms cl ON cl.id = c.id_classroom
        ");

    /*LEFT JOIN group_subjects gs ON c.id_group_subject = gs.id_group
    LEFT JOIN groups g ON gs.id_group = g.id*/
    if (!$result) {
        echo json_encode(["ok" => false, "msg" => "[!] ERROR TO QUERY: " . $conn->error]);
        return;
    }

    $timetables = [];

    while ($row = $result->fetch_assoc()) {
        $timetables[] = $row;
    }

    echo json_encode(["ok" => true, "data" => $timetables]);
    exit;
}

function hasConflict($conn, $column, $value, $day, $startTime, $endTime, $excludeId = null) {
    // Asegurarse que no sea array
    if (is_array($value)) {
        $value = $value[0];
    }

    $sql = "
        SELECT id FROM class_schedules
        WHERE $column = ?
        AND dia = ?
        AND hora_inicio < ?
        AND hora_fin > ?
    ";

    if ($excludeId !== null) {
        $sql .= " AND id != ?";
    }

    $stmt = $conn->prepare($sql);

    if ($excludeId !== null) {
        $stmt->bind_param("ssssi", $value, $day, $endTime, $startTime, $excludeId);
    } else {
        $stmt->bind_param("ssss", $value, $day, $endTime, $startTime);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    error_log("[DEBUG] hasConflict $column=$value ($day $startTime-$endTime): {$res->num_rows} conflicts");

    return $res->num_rows > 0;
}


function getGroupSubjectId($conn, $group_id, $subject_id) {
    $stmt = $conn->prepare("SELECT id FROM group_subjects WHERE id_group = ? AND id_subject = ?");
    $stmt->bind_param("ii", $group_id, $subject_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) return $row['id'];

    // Si no existe, lo insertamos
    $insert = $conn->prepare("INSERT INTO group_subjects (id_group, id_subject) VALUES (?, ?)");
    $insert->bind_param("ii", $group_id, $subject_id);
    $insert->execute();
    return $insert->insert_id;
}

function updateTimetable($conn, $data) {
    $id = $data['id'];
    $professor_id = $data["professor_id"];
    $group_id = $data["group_id"];
    $subject_id = $data["subject_id"];
    $days = $data["days"];
    $startTime = is_array($data['startTime']) ? $data['startTime'][0] : $data['startTime'];
    $endTime = is_array($data['endTime']) ? $data['endTime'][0] : $data['endTime'];
    $id_classroom = $data["id_classroom"];

    // errors
    error_log("[UPDATE] Datos recibidos:");
    error_log(print_r($data, true));
    error_log("[UPDATE] startTime: $startTime");
    error_log("[UPDATE] endTime: $endTime");
    // final errors

    $start = new DateTime($startTime);
    $end = new DateTime($endTime);
    $interval = $start->diff($end);

    // Convertir a minutos:
    $minutes = ($interval->h * 60) + $interval->i;

    if ($minutes !== 90) {
        echo json_encode(["ok" => false, "msg" => "Just class 1 hour and 30 minutes."]);
        exit;
    }

    // search and create the relation with the other tables
    $stmt = $conn->prepare("SELECT id FROM group_subjects WHERE id_group = ? AND id_subject = ?");
    $stmt->bind_param("ii", $group_id, $subject_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $gs_row = $res->fetch_assoc();

    // VALIDATIONS
    if (hasConflict($conn, 'id_professor', $professor_id, $days, $startTime, $endTime, $id)) {
        error_log("[DEBUG] Verificando conflicto para profesor $professor_id en $days de $startTime a $endTime");
        echo json_encode(["ok" => false, "msg" => "This professor already has class in this schedule."]);
        exit;
    }

    if (hasConflict($conn, 'id_classroom', $id_classroom, $days, $startTime, $endTime, $id)) {
        echo json_encode(["ok" => false, "msg" => "The classroom already has a professor in this schedule."]);
        exit;
    }

    // relation across yhe id_group_subject, so first i need to get 
    $group_subject_id = getGroupSubjectId($conn, $group_id, $subject_id);
    if (hasConflict($conn, 'id_group_subject', $group_subject_id, $days, $startTime, $endTime, $id)) {
        echo json_encode(["ok" => false, "msg" => "The group already has class in this schedule."]);
        exit;
    }

    // one group must not have the same subject 2 times in the same day
    $checkStmt = $conn->prepare("
        SELECT c.id 
        FROM class_schedules c
        JOIN group_subjects gs ON c.id_group_subject = gs.id
        WHERE gs.id_group = ?
        AND gs.id_subject = ?
        AND c.dia = ?
        AND c.id != ?
    ");

    $checkStmt->bind_param("iisi", $group_id, $subject_id, $days, $id);
    $checkStmt->execute();
    $conflictRes = $checkStmt->get_result();

    if ($conflictRes->num_rows > 0) {
        echo json_encode(["ok" => false, "msg" => "This group already has this subject on that day."]);
        exit;
    }

    // update the timetable
    $stmt = $conn->prepare("UPDATE class_schedules 
        SET id_professor = ?, id_group_subject = ?, dia = ?, hora_inicio = ?, hora_fin = ?, id_classroom = ? 
        WHERE id = ?");
    $stmt->bind_param("iisssii", $professor_id, $group_subject_id, $days, $startTime, $endTime, $id_classroom, $id);

    if ($stmt->execute()) {
        echo json_encode(["ok" => true]);
        exit;
    } else {
        echo json_encode(["ok" => false, "msg" => "Error al actualizar"]);
        exit;
    }
}

function deleteTimetable($conn, $id, $password) {   

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

    $stmt = $conn->prepare("DELETE FROM class_schedules WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();

    echo json_encode(["ok" => $ok]);
    exit;
}
?>