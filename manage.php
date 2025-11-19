<?php
// manage.php - Departments, Subjects & Papers (Admin UI) with Upload integrated
// Assumes: db.php, dept_api.php, subject_api.php, paper_api.php, get_subjects.php, upload_ajax.php exist.
require 'db.php';
// Admin authentication
require_once 'admin_auth.php';
ensure_admin();

// load departments for initial selects
$departments = [];
$res = $mysqli->query("SELECT department_id, department_code, department_name FROM department ORDER BY department_name");
while ($r = $res->fetch_assoc()) $departments[] = $r;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin - Manage Departments, Subjects & Papers</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f6f8fa; }
    .table-actions button { margin-right:6px; }
    .dropzone { border:2px dashed #cbd5e1; padding:20px; text-align:center; background:#fff; border-radius:8px; cursor:pointer; transition: border-color .12s, background .12s; }
    .dropzone.dragover { border-color:#4f46e5; background:#f8fafc; }
    .small-muted { font-size:.85rem; color:#6b7280; }
  </style>
</head>
<body class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 style="margin: 0;">Admin: Manage Departments, Subjects & Papers</h2>
    <div>
      <?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
      <span class="me-2 small-muted">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? ''); ?></strong></span>
        <a class="btn btn-primary me-2" href="create_admin.php">Create Admin</a>
        <a class="btn btn-success me-2" href="create_user.php">Create User</a>
      <a class="btn btn-outline-danger me-2" href="admin_logout.php">Logout</a>
      <a class="btn btn-outline-secondary" href="index.php">Back to Site</a>
    </div>
  </div>

  <!-- Departments -->
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Departments</strong>
      <button class="btn btn-sm btn-primary" id="addDeptBtn">+ Add Department</button>
    </div>
    <div class="card-body">
      <div id="deptAlert"></div>
      <div class="table-responsive">
        <table class="table table-striped" id="deptTable">
          <thead><tr><th>ID</th><th>Code</th><th>Name</th><th>Actions</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Subjects -->
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Subjects</strong>
      <div>
        <select id="manageDeptFilter" class="form-select d-inline-block" style="width:260px">
          <option value="">Filter: All Departments</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?=htmlspecialchars($d['department_id'])?>"><?=htmlspecialchars($d['department_name'])?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-primary ms-2" id="addSubBtn">+ Add Subject</button>
      </div>
    </div>
    <div class="card-body">
      <div id="subAlert"></div>
      <div class="table-responsive">
        <table class="table table-striped" id="subTable">
          <thead><tr><th>ID</th><th>Code</th><th>Name</th><th>Dept</th><th>Sem</th><th>Actions</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Papers (includes Upload New Paper button) -->
  <div class="card mb-4" id="papersCard">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Papers</strong>
      <div>
        <select id="papersDeptFilter" class="form-select d-inline-block" style="width:220px">
          <option value="">Filter: All Departments</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?=htmlspecialchars($d['department_id'])?>"><?=htmlspecialchars($d['department_name'])?></option>
          <?php endforeach; ?>
        </select>

        <select id="papersSubFilter" class="form-select d-inline-block ms-2" style="width:260px">
          <option value="">Filter: All Subjects</option>
        </select>

        <button class="btn btn-sm btn-primary ms-2" id="refreshPapersBtn">Refresh</button>
        <button class="btn btn-sm btn-success ms-2" id="openUploadBtn">+ Upload New Paper</button>
      </div>
    </div>
    <div class="card-body">
      <div id="papersAlert"></div>
      <div class="table-responsive">
        <table class="table table-striped" id="papersTableAdmin">
          <thead><tr><th>ID</th><th>Title</th><th>Dept</th><th>Subject</th><th>Year</th><th>File</th><th>Actions</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modals -->

  <!-- Department Modal -->
  <div class="modal fade" id="deptModal" tabindex="-1"><div class="modal-dialog modal-md modal-dialog-centered"><div class="modal-content">
    <form id="deptForm"><div class="modal-header"><h5 class="modal-title" id="deptModalTitle">Add Department</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div id="deptFormAlert"></div><input type="hidden" id="dept_id" name="department_id">
      <div class="mb-3"><label class="form-label">Department Code</label><input id="dept_code" name="department_code" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Department Name</label><input id="dept_name" name="department_name" class="form-control" required></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-primary" type="submit">Save</button></div></form>
  </div></div></div>

  <!-- Subject Modal -->
  <div class="modal fade" id="subModal" tabindex="-1"><div class="modal-dialog modal-md modal-dialog-centered"><div class="modal-content">
    <form id="subForm"><div class="modal-header"><h5 class="modal-title" id="subModalTitle">Add Subject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div id="subFormAlert"></div><input type="hidden" id="subject_id" name="subject_id">
      <div class="mb-3"><label class="form-label">Department</label>
        <select id="sub_dept" name="department_id" class="form-select" required>
          <option value="">-- Select Dept --</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?=htmlspecialchars($d['department_id'])?>"><?=htmlspecialchars($d['department_name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">Subject Code</label><input id="sub_code" name="subject_code" class="form-control"></div>
      <div class="mb-3"><label class="form-label">Subject Name</label><input id="sub_name" name="subject_name" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Semester</label><input id="sub_sem" name="semester" type="number" class="form-control"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-success" type="submit">Save Subject</button></div></form>
  </div></div></div>

  <!-- Edit Paper Modal (existing) -->
  <div class="modal fade" id="editPaperModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <form id="editPaperForm" enctype="multipart/form-data"><div class="modal-header"><h5 class="modal-title">Edit Paper</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div id="editPaperAlert"></div><input type="hidden" id="edit_paper_id" name="paper_id">
      <div class="mb-3"><label class="form-label">Title</label><input id="edit_title" name="title" class="form-control" required></div>
      <div class="row g-2 mb-3"><div class="col-md-4"><label class="form-label">Year</label><input id="edit_year" name="year" type="number" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Semester</label><input id="edit_sem" name="semester" type="number" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Exam Type</label><input id="edit_exam" name="exam_type" class="form-control"></div>
      </div>
      <div class="mb-3"><label class="form-label">Department</label>
        <select id="edit_dept" name="department_id" class="form-select" required>
          <option value="">-- Select Dept --</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?=htmlspecialchars($d['department_id'])?>"><?=htmlspecialchars($d['department_name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">Subject</label><select id="edit_subject" name="subject_id" class="form-select" required><option value="">-- Select Subject --</option></select></div>
      <div class="mb-3"><label class="form-label">Replace PDF (optional)</label><input type="file" name="paper_file" id="edit_paper_file" accept="application/pdf" class="form-control"><div class="form-text">Leave empty to keep existing file.</div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-danger" id="deletePaperBtn">Delete Paper</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-success">Save Paper</button></div></form>
  </div></div></div>

  <!-- Upload New Paper Modal (moved into Admin) -->
  <div class="modal fade" id="uploadNewModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <form id="uploadNewForm" enctype="multipart/form-data">
      <div class="modal-header"><h5 class="modal-title">Upload New Paper</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div id="uploadNewAlert"></div>

        <div class="row g-3 mb-2">
          <div class="col-md-8"><label class="form-label">Title</label><input name="title" id="new_title" class="form-control" placeholder="e.g., DBMS - End Sem 2020" required></div>
          <div class="col-md-4"><label class="form-label">Year</label><input name="year" id="new_year" type="number" class="form-control" placeholder="2020" required></div>
        </div>

        <div class="row g-3 mb-2 align-items-end">
          <div class="col-md-6"><label class="form-label">Department</label>
            <select id="new_dept" name="department" class="form-select" required>
              <option value="">-- Select department --</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?=htmlspecialchars($d['department_id'])?>"><?=htmlspecialchars($d['department_name'])?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-5"><label class="form-label">Subject</label><select id="new_subject" name="subject" class="form-select" required><option value="">Select department first</option></select></div>

          <div class="col-md-1 text-end"><button type="button" id="addSubFromUpload" class="btn btn-outline-secondary" title="Add Subject">+</button></div>
        </div>

        <div class="row g-3 mb-2">
          <div class="col-md-4"><label class="form-label">Semester</label><input name="semester" id="new_sem" type="number" class="form-control" placeholder="4"></div>
          <div class="col-md-8"><label class="form-label">Exam Type (optional)</label><input name="exam_type" id="new_exam" class="form-control" placeholder="Endsemester / Midterm"></div>
        </div>

        <div class="mb-3">
          <label class="form-label">Select PDF (or drag & drop here)</label>
          <div id="new_dropzone" class="dropzone">
            <div id="new_dropText">Drop PDF here or click to choose file</div>
            <input type="file" id="new_paper_file" name="paper_file" accept="application/pdf" style="display:none">
            <div id="new_filePreview" class="mt-2 small-muted"></div>
          </div>
        </div>

        <div class="progress mb-2" style="height:10px; display:none" id="uploadNewProgress"><div class="progress-bar" role="progressbar" style="width:0%"></div></div>

        <div class="small-muted">Notes: Only PDF files allowed. Max recommended size 10MB.</div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button id="submitNewUpload" type="submit" class="btn btn-success">Upload Paper</button></div>
    </form>
  </div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const $ = s => document.querySelector(s);
const $$ = s => Array.from(document.querySelectorAll(s));
const deptModal = new bootstrap.Modal($('#deptModal'));
const subModal = new bootstrap.Modal($('#subModal'));
const editPaperModal = new bootstrap.Modal($('#editPaperModal'));
const uploadNewModal = new bootstrap.Modal($('#uploadNewModal'));

// Helpers
function escapeHtml(s){ return (s||'').toString().replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

// ----- Departments -----
function loadDepts(){
  fetch('dept_api.php?action=list').then(r=>r.json()).then(data=>{
    const tbody = $('#deptTable tbody'); tbody.innerHTML = '';
    data.forEach(d=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${d.department_id}</td>
                      <td>${escapeHtml(d.department_code)}</td>
                      <td>${escapeHtml(d.department_name)}</td>
                      <td class="table-actions">
                        <button class="btn btn-sm btn-outline-primary" data-id="${d.department_id}" data-act="edit-dept">Edit</button>
                        <button class="btn btn-sm btn-outline-danger" data-id="${d.department_id}" data-act="del-dept">Delete</button>
                      </td>`;
      tbody.appendChild(tr);
    });
  }).catch(err=> { $('#deptAlert').innerHTML = '<div class="alert alert-danger">Failed loading departments</div>'; console.error(err); });
}

// Add Dept
$('#addDeptBtn').addEventListener('click', ()=> {
  $('#deptForm').reset(); $('#dept_id').value=''; $('#deptModalTitle').textContent='Add Department'; $('#deptFormAlert').innerHTML=''; deptModal.show();
});
$('#deptForm').addEventListener('submit', e=>{
  e.preventDefault(); $('#deptFormAlert').innerHTML='';
  const form = new FormData(e.target); const action = $('#dept_id').value ? 'update' : 'create'; form.append('action', action);
  fetch('dept_api.php', { method:'POST', body: form }).then(r=>r.json()).then(resp=>{
    if (resp.success) { $('#deptFormAlert').innerHTML='<div class="alert alert-success">Saved.</div>'; loadDepts(); setTimeout(()=>{ deptModal.hide(); reloadDeptSelects(); },700);} else $('#deptFormAlert').innerHTML='<div class="alert alert-danger">'+(resp.error||'Error')+'</div>';
  }).catch(()=> $('#deptFormAlert').innerHTML='<div class="alert alert-danger">Request failed</div>');
});
$('#deptTable').addEventListener('click', e=>{
  const btn = e.target.closest('button'); if (!btn) return; const id = btn.dataset.id; const act = btn.dataset.act;
  if (act === 'edit-dept') { fetch('dept_api.php?action=get&id='+id).then(r=>r.json()).then(d=>{ if(d){ $('#dept_id').value=d.department_id; $('#dept_code').value=d.department_code; $('#dept_name').value=d.department_name; $('#deptModalTitle').textContent='Edit Department'; $('#deptFormAlert').innerHTML=''; deptModal.show(); } }); }
  else if (act === 'del-dept') { if (!confirm('Delete department and its subjects?')) return; fetch('dept_api.php', { method:'POST', body: new URLSearchParams({action:'delete', department_id:id}) }).then(r=>r.json()).then(resp=>{ if (resp.success) { loadDepts(); loadSubs(); reloadDeptSelects(); } else alert('Delete failed: '+(resp.error||'')); }); }
});

// ----- Subjects -----
function loadSubs(deptId = null){
  let url = 'subject_api.php?action=list';
  if (deptId) url += '&department=' + encodeURIComponent(deptId);
  fetch(url).then(r=>r.json()).then(data=>{
    const tbody = $('#subTable tbody'); tbody.innerHTML = '';
    data.forEach(s=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${s.subject_id}</td>
                      <td>${escapeHtml(s.subject_code||'')}</td>
                      <td>${escapeHtml(s.subject_name)}</td>
                      <td>${escapeHtml(s.department_name||s.department||'')}</td>
                      <td>${s.semester||''}</td>
                      <td class="table-actions">
                        <button class="btn btn-sm btn-outline-primary" data-id="${s.subject_id}" data-act="edit-sub">Edit</button>
                        <button class="btn btn-sm btn-outline-danger" data-id="${s.subject_id}" data-act="del-sub">Delete</button>
                      </td>`;
      tbody.appendChild(tr);
    });
  }).catch(err=> { $('#subAlert').innerHTML = '<div class="alert alert-danger">Failed loading subjects</div>'; console.error(err); });
}
$('#addSubBtn').addEventListener('click', ()=> { $('#subForm').reset(); $('#subject_id').value=''; $('#subModalTitle').textContent='Add Subject'; $('#subFormAlert').innerHTML=''; subModal.show(); });
$('#subForm').addEventListener('submit', e=>{ e.preventDefault(); $('#subFormAlert').innerHTML=''; const form = new FormData(e.target); const action = $('#subject_id').value ? 'update' : 'create'; form.append('action', action); fetch('subject_api.php', { method:'POST', body: form }).then(r=>r.json()).then(resp=>{ if (resp.success) { $('#subFormAlert').innerHTML = '<div class="alert alert-success">Saved.</div>'; loadSubs($('#manageDeptFilter').value || null); setTimeout(()=>subModal.hide(),700); } else { $('#subFormAlert').innerHTML = '<div class="alert alert-danger">'+(resp.error||'Error')+'</div>'; } }).catch(()=> $('#subFormAlert').innerHTML = '<div class="alert alert-danger">Request failed</div>'); });
$('#subTable').addEventListener('click', e=>{ const btn = e.target.closest('button'); if (!btn) return; const id = btn.dataset.id; const act = btn.dataset.act; if (act === 'edit-sub') { fetch('subject_api.php?action=get&id='+id).then(r=>r.json()).then(d=>{ if (d) { $('#subject_id').value = d.subject_id; $('#sub_code').value = d.subject_code || ''; $('#sub_name').value = d.subject_name || ''; $('#sub_sem').value = d.semester || ''; $('#sub_dept').value = d.department_id || ''; $('#subModalTitle').textContent = 'Edit Subject'; $('#subFormAlert').innerHTML = ''; subModal.show(); } }); } else if (act === 'del-sub') { if (!confirm('Delete subject?')) return; fetch('subject_api.php', { method:'POST', body: new URLSearchParams({action:'delete', subject_id:id}) }).then(r=>r.json()).then(resp=>{ if (resp.success) loadSubs($('#manageDeptFilter').value || null); else alert('Delete failed: '+(resp.error||'')); }); } });
$('#manageDeptFilter').addEventListener('change', ()=> loadSubs($('#manageDeptFilter').value || null));

// ----- Papers management -----
$('#papersDeptFilter').addEventListener('change', ()=>{
  const dept = $('#papersDeptFilter').value;
  const sel = $('#papersSubFilter');
  sel.innerHTML = '<option>Loading...</option>';
  if (!dept) { sel.innerHTML = '<option value="">All Subjects</option>'; loadPapers(); return; }
  fetch('subject_api.php?action=list&department=' + encodeURIComponent(dept)).then(r=>r.json()).then(list=>{ sel.innerHTML = '<option value="">All Subjects</option>'; list.forEach(s=>{ const o = document.createElement('option'); o.value=s.subject_id; o.textContent=s.subject_name; sel.appendChild(o); }); loadPapers(); }).catch(()=> sel.innerHTML = '<option value="">Error</option>');
});
$('#papersSubFilter').addEventListener('change', ()=> loadPapers());
$('#refreshPapersBtn').addEventListener('click', ()=> loadPapers());

function loadPapers(){
  const subj = $('#papersSubFilter').value;
  const dept = $('#papersDeptFilter').value;
  let url = 'paper_api.php?action=list';
  if (subj) url += '&subject=' + encodeURIComponent(subj);
  if (dept) url += '&department=' + encodeURIComponent(dept);
  const tbody = $('#papersTableAdmin tbody'); tbody.innerHTML = '<tr><td colspan="7">Loading...</td></tr>';
  fetch(url).then(r=>r.json()).then(data=>{
    tbody.innerHTML = '';
    if (!Array.isArray(data) || data.length===0) { tbody.innerHTML = '<tr><td colspan="7">No papers found.</td></tr>'; return; }
    data.forEach(p=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${p.paper_id}</td>
                      <td>${escapeHtml(p.title)}</td>
                      <td>${escapeHtml(p.department_name || p.department || '')}</td>
                      <td>${escapeHtml(p.subject_name || p.subject || '')}</td>
                      <td>${escapeHtml(p.year)}</td>
                      <td><a class="btn btn-sm btn-outline-primary" href="download.php?id=${p.paper_id}" target="_blank">${escapeHtml(p.filename)}</a></td>
                      <td>
                        <button class="btn btn-sm btn-outline-primary me-1" data-id="${p.paper_id}" data-act="edit-paper">Edit</button>
                        <button class="btn btn-sm btn-outline-danger" data-id="${p.paper_id}" data-act="del-paper">Delete</button>
                      </td>`;
      tbody.appendChild(tr);
    });
  }).catch(err=>{ $('#papersAlert').innerHTML = '<div class="alert alert-danger">Failed loading papers</div>'; console.error(err); });
}

// paper actions
$('#papersTableAdmin').addEventListener('click', e=>{
  const btn = e.target.closest('button'); if (!btn) return; const id = btn.dataset.id; const act = btn.dataset.act;
  if (act === 'edit-paper') openEditPaper(id);
  if (act === 'del-paper') { if (!confirm('Delete this paper?')) return; fetch('paper_api.php', { method:'POST', body: new URLSearchParams({ action:'delete', paper_id: id }) }).then(r=>r.json()).then(resp=>{ if (resp.success) loadPapers(); else alert('Delete failed: ' + (resp.error||'')); }); }
});

function openEditPaper(id){
  $('#editPaperAlert').innerHTML = '';
  fetch('paper_api.php?action=get&id=' + encodeURIComponent(id)).then(r=>r.json()).then(p=>{
    if (!p || !p.paper_id) { $('#editPaperAlert').innerHTML = '<div class="alert alert-danger">Not found</div>'; return; }
    $('#edit_paper_id').value = p.paper_id;
    $('#edit_title').value = p.title || '';
    $('#edit_year').value = p.year || '';
    $('#edit_sem').value = p.semester || '';
    $('#edit_exam').value = p.exam_type || '';
    $('#edit_dept').value = p.department_id || '';
    loadSubjectsForDepartment($('#edit_dept').value, $('#edit_subject'), p.subject_id);
    $('#edit_paper_file').value = '';
    editPaperModal.show();
  }).catch(()=> $('#editPaperAlert').innerHTML = '<div class="alert alert-danger">Failed to load</div>');
}
$('#edit_dept').addEventListener('change', ()=> loadSubjectsForDepartment($('#edit_dept').value, $('#edit_subject')));

// submit edit form
$('#editPaperForm').addEventListener('submit', function(e){
  e.preventDefault();
  const form = new FormData(this);
  form.append('action','update');
  fetch('paper_api.php', { method:'POST', body: form }).then(r=>r.json()).then(resp=>{
    if (resp.success) { $('#editPaperAlert').innerHTML = '<div class="alert alert-success">Saved</div>'; loadPapers(); setTimeout(()=> editPaperModal.hide(),700); } else $('#editPaperAlert').innerHTML = '<div class="alert alert-danger">'+(resp.error||'Save failed')+'</div>';
  }).catch(()=> $('#editPaperAlert').innerHTML = '<div class="alert alert-danger">Request failed</div>');
});
$('#deletePaperBtn').addEventListener('click', function(){ const id = $('#edit_paper_id').value; if (!id || !confirm('Delete this paper?')) return; fetch('paper_api.php', { method:'POST', body: new URLSearchParams({ action:'delete', paper_id:id }) }).then(r=>r.json()).then(resp=>{ if (resp.success) { editPaperModal.hide(); loadPapers(); } else $('#editPaperAlert').innerHTML = '<div class="alert alert-danger">'+(resp.error||'Delete failed')+'</div>'; }); });

// ----- Upload new paper (ADMIN) -----
const newDropzone = $('#new_dropzone');
const newFileInput = $('#new_paper_file');
const newFilePreview = $('#new_filePreview');
const newDept = $('#new_dept');
const newSubject = $('#new_subject');
const uploadNewForm = $('#uploadNewForm');
const uploadNewAlert = $('#uploadNewAlert');
const uploadNewProgress = $('#uploadNewProgress');
const uploadNewProgressBar = uploadNewProgress.querySelector('.progress-bar');

// load subjects for new upload when dept changes
newDept.addEventListener('change', ()=> {
  const dept = newDept.value;
  newSubject.innerHTML = '<option>Loading...</option>';
  if (!dept) { newSubject.innerHTML = '<option value="">Select department first</option>'; return; }
  fetch('get_subjects.php?department=' + encodeURIComponent(dept)).then(r=>r.json()).then(list=>{
    newSubject.innerHTML = '';
    if (!Array.isArray(list) || list.length===0) { newSubject.innerHTML = '<option value="">(no subjects)</option>'; return; }
    newSubject.innerHTML = '<option value="">-- Select subject --</option>';
    list.forEach(s => {
      const o = document.createElement('option'); o.value = s.subject_id; o.textContent = s.subject_name + (s.subject_code?(' ('+s.subject_code+')'):''); newSubject.appendChild(o);
    });
  }).catch(()=> newSubject.innerHTML = '<option value="">Error</option>');
});

// quick add subject from upload (opens subject modal, prefills dept)
$('#addSubFromUpload').addEventListener('click', ()=> {
  $('#subForm').reset(); $('#subject_id').value=''; $('#subModalTitle').textContent='Add Subject'; $('#sub_dept').value = newDept.value || ''; $('#subFormAlert').innerHTML=''; subModal.show();
});

// drag & drop for new upload
newDropzone.addEventListener('click', ()=> newFileInput.click());
newDropzone.addEventListener('dragover', e=>{ e.preventDefault(); newDropzone.classList.add('dragover'); });
newDropzone.addEventListener('dragleave', e=>{ newDropzone.classList.remove('dragover'); });
newDropzone.addEventListener('drop', e=>{ e.preventDefault(); newDropzone.classList.remove('dragover'); const f = e.dataTransfer.files && e.dataTransfer.files[0]; if (f) handleNewChosenFile(f); });
newFileInput.addEventListener('change', e=>{ const f = e.target.files && e.target.files[0]; if (f) handleNewChosenFile(f); });

let newChosenFile = null;
function handleNewChosenFile(f){ newChosenFile = f; newFilePreview.textContent = f.name + ' · ' + Math.round(f.size/1024) + ' KB'; }

// open upload modal
$('#openUploadBtn').addEventListener('click', ()=> {
  $('#uploadNewForm').reset(); newFilePreview.textContent=''; newChosenFile=null; uploadNewAlert.innerHTML=''; uploadNewProgress.style.display='none'; uploadNewProgressBar.style.width='0%';
  uploadNewModal.show();
});

// submit new upload
uploadNewForm.addEventListener('submit', function(e){
  e.preventDefault(); uploadNewAlert.innerHTML='';
  const title = $('#new_title').value.trim();
  const year = $('#new_year').value.trim();
  const dept = newDept.value;
  const subj = newSubject.value;
  const sem = $('#new_sem').value;
  const exam = $('#new_exam').value.trim();

  if (!title || !year || !dept || !subj) { uploadNewAlert.innerHTML = '<div class="alert alert-danger">Please fill Title, Year, Department and Subject.</div>'; return; }
  if (!newChosenFile) { uploadNewAlert.innerHTML = '<div class="alert alert-danger">Please choose a PDF file.</div>'; return; }
  if (newChosenFile.type !== 'application/pdf') { uploadNewAlert.innerHTML = '<div class="alert alert-danger">Only PDF files are allowed.</div>'; return; }

  const fd = new FormData();
  fd.append('title', title);
  fd.append('department', dept);
  fd.append('subject', subj);
  fd.append('year', year);
  fd.append('semester', sem || '');
  fd.append('exam_type', exam || '');
  fd.append('paper_file', newChosenFile);

  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'upload_ajax.php', true);
  xhr.upload.onprogress = function(e){ if (e.lengthComputable) { const pct = Math.round(e.loaded / e.total * 100); uploadNewProgress.style.display='block'; uploadNewProgressBar.style.width = pct + '%'; uploadNewProgressBar.textContent = pct + '%'; } };
  xhr.onload = function(){
    uploadNewProgress.style.display='none'; uploadNewProgressBar.style.width='0%';
    try {
      const res = JSON.parse(xhr.responseText);
      if (res.success) { uploadNewAlert.innerHTML = '<div class="alert alert-success">Uploaded successfully.</div>'; setTimeout(()=>{ uploadNewModal.hide(); loadPapers(); },700); }
      else uploadNewAlert.innerHTML = '<div class="alert alert-danger">' + (res.error||'Upload failed') + '</div>';
    } catch(err){ uploadNewAlert.innerHTML = '<div class="alert alert-danger">Server error. Invalid response.</div>'; console.error(err, xhr.responseText); }
  };
  xhr.onerror = function(){ uploadNewProgress.style.display='none'; uploadNewAlert.innerHTML = '<div class="alert alert-danger">Network error during upload.</div>'; };
  xhr.send(fd);
});

// ----- Utility: load subjects into a select element -----
function loadSubjectsForDepartment(deptId, selectElem, selectedSubjectId = null) {
  if (!selectElem) return;
  selectElem.innerHTML = '<option>Loading...</option>';
  if (!deptId) { selectElem.innerHTML = '<option value="">(select department first)</option>'; return; }
  fetch('get_subjects.php?department=' + encodeURIComponent(deptId)).then(r=>r.json()).then(data=>{
    selectElem.innerHTML = '';
    if (!Array.isArray(data) || data.length === 0) { selectElem.innerHTML = '<option value="">(no subjects)</option>'; return; }
    selectElem.innerHTML = '<option value="">-- Select subject --</option>';
    data.forEach(s => {
      const opt = document.createElement('option'); opt.value = s.subject_id; opt.textContent = s.subject_name + (s.subject_code ? (' ('+s.subject_code+')') : '');
      if (selectedSubjectId && selectedSubjectId == s.subject_id) opt.selected = true;
      selectElem.appendChild(opt);
    });
  }).catch(err=> { selectElem.innerHTML = '<option value="">Error</option>'; console.error(err); });
}

// reload department selects on page after dept changes
function reloadDeptSelects(){
  fetch('dept_api.php?action=list').then(r=>r.json()).then(list=>{
    const selSub = $('#sub_dept'); const selSubFilter = $('#manageDeptFilter'); const selEditDept = $('#edit_dept'); const selPapersDept = $('#papersDeptFilter'); const selNewDept = $('#new_dept');
    [selSub, selSubFilter, selEditDept, selPapersDept, selNewDept].forEach(s=>{ if(!s) return; const cur = s.value; s.innerHTML = '<option value="">-- Select Dept --</option>'; list.forEach(d=>{ const o=document.createElement('option'); o.value = d.department_id; o.textContent = d.department_name; s.appendChild(o); }); s.value = cur; });
    const pd = $('#papersDeptFilter').value; if (pd) $('#papersDeptFilter').dispatchEvent(new Event('change'));
  }).catch(err=> console.error(err));
}

// initial load
loadDepts(); loadSubs(); reloadDeptSelects(); loadPapers();

// when subject modal saved, if created from upload, you may want to refresh new_subject list
// (subject creation code already refreshes via loadSubs or reloadDeptSelects when saved)

</script>
</body>
</html>
