<?php
// index.php – main user interface (upload removed; admin panel hosts uploads)
require 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Previous 5-Year Question Bank</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f6f8fa; }
    .file-row:hover { background: #eef3f8; }
  </style>
</head>
<body class="container py-4">

  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">Previous 5-Year Question Bank</h1>

    <div class="btn-group">
      <!-- Admin Panel Button -->
      <a class="btn btn-outline-secondary" href="manage.php">Admin Panel</a>
    </div>
  </div>

  <hr>

  <!-- Filters -->
  <div class="card mb-4">
    <div class="card-body">
      <div class="row g-3">

        <!-- Department Filter -->
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

        <!-- Study Year Filter -->
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
        <!-- Semester Filter -->
        <div class="col-md-2">
          <label class="form-label">Semester</label>
          <select id="filterSemester" class="form-select">
            <option value="">All Semesters</option>
            <?php for ($i=1;$i<=8;$i++): ?>
              <option value="<?= $i ?>">Sem <?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <!-- Subject Code Search -->
        <div class="col-md-4">
          <label class="form-label">Search subject code</label>
          <input type="text" id="filterQ" class="form-control" placeholder="e.g. CS101">
        </div>

        <!-- Go Button -->
        <div class="col-md-1 d-flex align-items-end">
          <button id="filterBtn" class="btn btn-primary w-100">Go</button>
        </div>

      </div>
    </div>
  </div>

  <!-- Results -->
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

<!-- Upload UI removed from home page; uploads managed in Admin Panel (manage.php) -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Escape HTML to avoid XSS
function escapeHtml(s){
  return (s||'').toString().replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
}

// Load papers (same as before)
function loadPapers(){
  const dept = document.getElementById('filterDept').value;
  const studyYear = document.getElementById('filterStudyYear').value;
  const semester = document.getElementById('filterSemester').value;
  const q = document.getElementById('filterQ').value;

  let url = "api_list.php?";
  if (dept) url += "department="+encodeURIComponent(dept)+"&";
  if (studyYear) url += "study_year="+encodeURIComponent(studyYear)+"&";
  if (semester) url += "semester="+encodeURIComponent(semester)+"&";
  if (q) url += "q="+encodeURIComponent(q)+"&";

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
        tr.classList.add("file-row");

        tr.innerHTML = `
          <td>${escapeHtml(p.title)}</td>
          <td>${escapeHtml(p.department)}</td>
          <td>${escapeHtml(p.subject)}</td>
          <td>${escapeHtml(p.subject_code || '')}</td>
          <td>${escapeHtml(p.year)}</td>
          <td><a class="btn btn-sm btn-outline-primary" href="download.php?id=${p.paper_id}" target="_blank">Open</a></td>
        `;

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
