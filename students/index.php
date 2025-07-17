<?php
session_start();
require_once '../server/db.php'; // ajusta la ruta si es necesario

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}

// Optional filters
$selected_prof = $_GET['prof'] ?? '';
$selected_day = $_GET['day'] ?? '';

$days_of_week = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

// Base query
$sql = "SELECT 
    p.nombre_completo AS professor,
    p.id AS professor_id,
    p.oficina AS office,
    cs.dia AS day,
    cs.hora_inicio AS start_time,
    cs.hora_fin AS end_time,
    s.nombre AS subject,
    g.nombre AS group_name
FROM class_schedules cs
JOIN professors p ON cs.id_professor = p.id
JOIN group_subjects gs ON cs.id_group_subject = gs.id
JOIN subjects s ON gs.id_subject = s.id
JOIN groups g ON gs.id_group = g.id
WHERE 1=1";

// Apply filters if selected
$params = [];
if (!empty($selected_prof)) {
    $sql .= " AND p.id = ?";
    $params[] = $selected_prof;
}
if (!empty($selected_day)) {
    $sql .= " AND cs.dia = ?";
    $params[] = $selected_day;
}

$sql .= " ORDER BY p.nombre_completo, FIELD(cs.dia, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), cs.hora_inicio";

$stmt = $conn->prepare($sql);
if ($params) {
    $types = str_repeat("s", count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get all professors for dropdown
$professors = $conn->query("SELECT id, nombre_completo FROM professors ORDER BY nombre_completo")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Professor Schedule</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
  <h2 class="mb-4">Professor Schedule</h2>

  <form method="GET" class="row g-3 mb-4">
    <div class="col-md-4">
      <label for="prof" class="form-label">Filter by professor</label>
      <select name="prof" id="prof" class="form-select">
        <option value="">All</option>
        <?php foreach ($professors as $prof): ?>
          <option value="<?= $prof['id'] ?>" <?= $selected_prof == $prof['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($prof['nombre_completo']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label for="day" class="form-label">Filter by day</label>
      <select name="day" id="day" class="form-select">
        <option value="">All</option>
        <?php foreach ($days_of_week as $day): ?>
          <option value="<?= $day ?>" <?= $selected_day == $day ? 'selected' : '' ?>><?= $day ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4 d-flex align-items-end">
      <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-dark">
        <tr>
          <th>Professor</th>
          <th>Office</th>
          <th>Group</th>
          <th>Subject</th>
          <th>Day</th>
          <th>Start Time</th>
          <th>End Time</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['professor']) ?></td>
              <td><?= htmlspecialchars($row['office']) ?></td>
              <td><?= htmlspecialchars($row['group_name']) ?></td>
              <td><?= htmlspecialchars($row['subject']) ?></td>
              <td><?= htmlspecialchars($row['day']) ?></td>
              <td><?= substr($row['start_time'], 0, 5) ?></td>
              <td><?= substr($row['end_time'], 0, 5) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center">No results found for the selected filters.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
