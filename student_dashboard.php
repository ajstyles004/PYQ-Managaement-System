<?php
require_once 'db.php';
require_once 'student_auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
ensure_student();

$papers = [];
$res = $mysqli->query("SELECT p.paper_id, p.title, p.year, p.filename, p.filepath, d.department_name, s.subject_name
                       FROM paper p
                       LEFT JOIN department d ON p.department_id = d.department_id
                       LEFT JOIN subject s ON p.subject_id = s.subject_id
                       ORDER BY p.year DESC, p.uploaded_on DESC");
if ($res) {
    while ($r = $res->fetch_assoc()) $papers[] = $r;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Student Dashboard - Papers</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
  <div class="d-flex justify-content-end align-items-center mb-3">
    <span class="me-3 small-muted">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['student_username'] ?? ''); ?></strong></span>
    <a class="btn btn-sm btn-outline-primary me-2" href="student_profile.php">My Profile</a>
    <a class="btn btn-sm btn-outline-danger me-2" href="student_logout.php">Logout</a>
  </div>
  <div class="mb-3">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Department</label>
        <select id="filterDept" class="form-select">
          <option value="">All Departments</option>
          <?php
            $res = $mysqli->query("SELECT * FROM department ORDER BY department_name");
            while ($d = $res->fetch_assoc()):
          ?>
            <option value="<?= $d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Year of study</label>
        <select id="filterStudyYear" class="form-select">
          <option value="">All Years</option>
          <option value="1">1st</option>
          <option value="2">2nd</option>
          <option value="3">3rd</option>
          <option value="4">4th</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Semester</label>
        <select id="filterSemester" class="form-select">
          <option value="">All Semesters</option>
          <?php for ($i=1;$i<=8;$i++): ?>
            <option value="<?= $i ?>">Sem <?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Search subject code</label>
        <input type="text" id="filterQ" class="form-control" placeholder="e.g. CS101">
      </div>
      <div class="col-md-1 d-flex align-items-end">
        <button id="filterBtn" class="btn btn-primary w-100">Go</button>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between">
      <strong>Available Papers</strong>
      <span id="resultCount"></span>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped" id="papersTable">
          <thead>
            <tr>
              <th>Title</th>
              <th>Dept</th>
              <th>Subject</th>
              <th>Code</th>
              <th>Year</th>
              <th>File</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
  // Escape HTML to avoid XSS
  function escapeHtml(s){
    return (s||'').toString().replace(/[&<>"']/g, function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});
  }

  function loadPapers(){
    const dept = document.getElementById('filterDept').value;
    const studyYear = document.getElementById('filterStudyYear').value;
    const semester = document.getElementById('filterSemester').value;
    const q = document.getElementById('filterQ').value;

    let url = "api_list.php?";
    if (dept) url += "department="+encodeURIComponent(dept)+'&';
    if (studyYear) url += "study_year="+encodeURIComponent(studyYear)+'&';
    if (semester) url += "semester="+encodeURIComponent(semester)+'&';
    if (q) url += "q="+encodeURIComponent(q)+'&';

    fetch(url)
      .then(r=>r.json())
      .then(data=>{
        const tbody = document.querySelector('#papersTable tbody');
        tbody.innerHTML = "";

        if (!Array.isArray(data) || data.length === 0) {
          tbody.innerHTML = '<tr><td colspan="5">No papers found.</td></tr>';
          document.getElementById('resultCount').textContent = "0 result(s)";
          return;
        }

        data.forEach(p=>{
          const tr = document.createElement('tr');
          tr.classList.add('file-row');
          tr.innerHTML = `\
            <td>${escapeHtml(p.title)}</td>\
            <td>${escapeHtml(p.department)}</td>\
            <td>${escapeHtml(p.subject)}</td>\
            <td>${escapeHtml(p.subject_code || '')}</td>\
            <td>${escapeHtml(p.year)}</td>\
            <td><a class="btn btn-sm btn-outline-primary" href="download.php?id=${p.paper_id}" target="_blank">Open</a></td>`;
          tbody.appendChild(tr);
        });

        document.getElementById('resultCount').textContent = data.length + " result(s)";
      })
      .catch(err=>{
        const tbody = document.querySelector('#papersTable tbody');
        tbody.innerHTML = '<tr><td colspan="5">Failed to load papers.</td></tr>';
        document.getElementById('resultCount').textContent = "";
        console.error(err);
      });
  }

  document.getElementById('filterBtn').onclick = loadPapers;
  window.addEventListener('load', loadPapers);
  </script>

</body>
</html>
