<?php
// list.php
require 'db.php';

$departments = [];
$res = $mysqli->query("SELECT department_id, department_name FROM department ORDER BY department_name");
while ($r = $res->fetch_assoc()) $departments[] = $r;

$filterDept = isset($_GET['department']) && $_GET['department'] !== '' ? intval($_GET['department']) : null;
$filterYear = isset($_GET['year']) && $_GET['year'] !== '' ? intval($_GET['year']) : null;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT p.paper_id, p.title, p.year, p.filename, p.filepath, d.department_name, s.subject_name
        FROM paper p
        JOIN department d ON p.department_id = d.department_id
        JOIN subject s ON p.subject_id = s.subject_id
        WHERE 1=1";
$params = [];
$types = '';
if ($filterDept !== null) { $sql .= " AND p.department_id = ?"; $params[] = $filterDept; $types .= 'i'; }
if ($filterYear !== null) { $sql .= " AND p.year = ?"; $params[] = $filterYear; $types .= 'i'; }
if ($q !== '') { $sql .= " AND p.title LIKE ?"; $params[] = '%' . $q . '%'; $types .= 's'; }
$sql .= " ORDER BY p.year DESC, p.uploaded_on DESC";

$stmt = $mysqli->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$papers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>List Papers</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
  <h2>Available Papers</h2>

  <form method="get" class="row g-2 mb-3">
    <div class="col-auto">
      <select name="department" class="form-select">
        <option value="">All Departments</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?=$d['department_id']?>" <?= ($filterDept== $d['department_id']) ? 'selected':''?> >
            <?=htmlspecialchars($d['department_name'])?>
          </option>
        <?php endforeach;?>
      </select>
    </div>
    <div class="col-auto">
      <input name="year" class="form-control" placeholder="Year" value="<?=htmlspecialchars($filterYear ?? '')?>">
    </div>
    <div class="col-auto">
      <input name="q" class="form-control" placeholder="Search title" value="<?=htmlspecialchars($q)?>">
    </div>
    <div class="col-auto">
      <button class="btn btn-primary">Filter</button>
      <a href="list.php" class="btn btn-outline-secondary">Reset</a>
    </div>
  </form>

  <table class="table table-striped">
    <thead><tr><th>Title</th><th>Dept</th><th>Subject</th><th>Year</th><th>File</th></tr></thead>
    <tbody>
    <?php if (empty($papers)): ?>
      <tr><td colspan="5">No papers found.</td></tr>
    <?php else: foreach ($papers as $p): ?>
      <tr>
        <td><?=htmlspecialchars($p['title'])?></td>
        <td><?=htmlspecialchars($p['department_name'])?></td>
        <td><?=htmlspecialchars($p['subject_name'])?></td>
        <td><?=htmlspecialchars($p['year'])?></td>
        <td><a href="download.php?id=<?=$p['paper_id']?>" target="_blank"><?=htmlspecialchars($p['filename'])?></a></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</body>
</html>