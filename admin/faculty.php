<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Faculty Directory & Subject Assignment (Admin)
 * BCA 5th Semester Major Project 1
 */

$page_title = 'Faculty Management';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

// Handle Add Faculty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_faculty') {
    if (verify_csrf()) {
        $first_name    = sanitize($_POST['first_name'] ?? '');
        $last_name     = sanitize($_POST['last_name'] ?? '');
        $username      = strtolower(sanitize($_POST['username'] ?? ''));
        $employee_id   = strtoupper(sanitize($_POST['employee_id'] ?? ''));
        $email         = strtolower(sanitize($_POST['email'] ?? ''));
        $phone         = sanitize($_POST['phone'] ?? '');
        $designation   = sanitize($_POST['designation'] ?? 'Assistant Professor');
        $department_id = (int)($_POST['department_id'] ?? 0);
        $qualification = sanitize($_POST['qualification'] ?? '');
        $joining_date  = sanitize($_POST['joining_date'] ?? date('Y-m-d'));
        $password      = $_POST['password'] ?? 'Faculty@123';

        if (empty($first_name) || empty($last_name) || empty($username) || empty($employee_id) || empty($email) || $department_id <= 0) {
            set_flash('danger', 'Please provide all mandatory faculty information.');
        } else {
            try {
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                $check->execute([$email, $username]);
                if ($check->rowCount() > 0) {
                    set_flash('danger', 'A user with this email or username already exists.');
                } else {
                    $pdo->beginTransaction();
                    $pwd_hash = password_hash($password, PASSWORD_BCRYPT);
                    $uStmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'faculty', 'active')");
                    $uStmt->execute([$username, $email, $pwd_hash]);
                    $userId = $pdo->lastInsertId();

                    $fStmt = $pdo->prepare("
                        INSERT INTO faculty (user_id, employee_id, first_name, last_name, email, phone, designation, department_id, qualification, joining_date)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $fStmt->execute([
                        $userId, $employee_id, $first_name, $last_name, $email,
                        $phone, $designation, $department_id, $qualification, !empty($joining_date) ? $joining_date : null
                    ]);
                    $pdo->commit();
                    set_flash('success', 'Faculty member registered successfully!');
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                set_flash('danger', 'Error adding faculty: ' . $e->getMessage());
            }
        }
    }
    header('Location: ' . BASE_URL . 'admin/faculty.php');
    exit;
}

// Handle Update Faculty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_faculty') {
    if (verify_csrf()) {
        $faculty_id    = (int)($_POST['faculty_id'] ?? 0);
        $first_name    = sanitize($_POST['first_name'] ?? '');
        $last_name     = sanitize($_POST['last_name'] ?? '');
        $phone         = sanitize($_POST['phone'] ?? '');
        $designation   = sanitize($_POST['designation'] ?? '');
        $department_id = (int)($_POST['department_id'] ?? 0);
        $qualification = sanitize($_POST['qualification'] ?? '');
        $status        = sanitize($_POST['status'] ?? 'active');

        if ($faculty_id > 0 && !empty($first_name) && !empty($last_name) && $department_id > 0) {
            try {
                $pdo->beginTransaction();
                $upFaculty = $pdo->prepare("
                    UPDATE faculty 
                    SET first_name = ?, last_name = ?, phone = ?, designation = ?, department_id = ?, qualification = ?
                    WHERE id = ?
                ");
                $upFaculty->execute([$first_name, $last_name, $phone, $designation, $department_id, $qualification, $faculty_id]);

                $upUser = $pdo->prepare("
                    UPDATE users u
                    JOIN faculty f ON u.id = f.user_id
                    SET u.status = ?
                    WHERE f.id = ?
                ");
                $upUser->execute([$status, $faculty_id]);

                $pdo->commit();
                set_flash('success', 'Faculty details updated successfully.');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                set_flash('danger', 'Error updating faculty: ' . $e->getMessage());
            }
        }
    }
    header('Location: ' . BASE_URL . 'admin/faculty.php');
    exit;
}

// Handle Subject Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_subject') {
    if (verify_csrf()) {
        $faculty_id = (int)($_POST['faculty_id'] ?? 0);
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $acad_year  = sanitize($_POST['academic_year'] ?? '2026-2027');

        if ($faculty_id > 0 && $subject_id > 0) {
            try {
                $assign = $pdo->prepare("
                    INSERT INTO faculty_subjects (faculty_id, subject_id, academic_year)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE academic_year = VALUES(academic_year)
                ");
                $assign->execute([$faculty_id, $subject_id, $acad_year]);
                set_flash('success', 'Subject assigned to faculty member successfully.');
            } catch (Exception $e) {
                set_flash('danger', 'Error assigning subject: ' . $e->getMessage());
            }
        }
    }
    header('Location: ' . BASE_URL . 'admin/faculty.php');
    exit;
}

// Handle Remove Subject Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_assigned_subject') {
    if (verify_csrf()) {
        $map_id = (int)($_POST['map_id'] ?? 0);
        if ($map_id > 0) {
            $del = $pdo->prepare("DELETE FROM faculty_subjects WHERE id = ?");
            $del->execute([$map_id]);
            set_flash('success', 'Subject unassigned successfully.');
        }
    }
    header('Location: ' . BASE_URL . 'admin/faculty.php');
    exit;
}

// Handle Delete Faculty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_faculty') {
    if (verify_csrf()) {
        $faculty_id = (int)($_POST['faculty_id'] ?? 0);
        if ($faculty_id > 0) {
            try {
                $find = $pdo->prepare("SELECT user_id FROM faculty WHERE id = ?");
                $find->execute([$faculty_id]);
                $userId = $find->fetchColumn();

                if ($userId) {
                    $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $del->execute([$userId]);
                    set_flash('success', 'Faculty account deleted.');
                }
            } catch (Exception $e) {
                set_flash('danger', 'Error deleting faculty: ' . $e->getMessage());
            }
        }
    }
    header('Location: ' . BASE_URL . 'admin/faculty.php');
    exit;
}

// Fetch Departments & Subjects
$departments = $pdo->query("SELECT id, dept_name, dept_code FROM departments ORDER BY dept_name ASC")->fetchAll();
$allSubjects = $pdo->query("
    SELECT s.id, s.subject_name, s.subject_code, s.semester, c.course_code
    FROM subjects s
    JOIN courses c ON s.course_id = c.id
    ORDER BY c.course_code, s.semester, s.subject_name
")->fetchAll();

// Fetch Faculty with assigned subjects
$facultyListStmt = $pdo->query("
    SELECT f.*, u.status as user_status, d.dept_name, d.dept_code,
           COUNT(fs.id) as assigned_subjects_count
    FROM faculty f
    JOIN users u ON f.user_id = u.id
    JOIN departments d ON f.department_id = d.id
    LEFT JOIN faculty_subjects fs ON f.id = fs.faculty_id
    GROUP BY f.id
    ORDER BY f.id ASC
");
$facultyList = $facultyListStmt->fetchAll();

// Fetch all subject assignments for modal views
$assignedSubjectsStmt = $pdo->query("
    SELECT fs.id as map_id, fs.faculty_id, fs.academic_year, s.subject_name, s.subject_code, c.course_code
    FROM faculty_subjects fs
    JOIN subjects s ON fs.subject_id = s.id
    JOIN courses c ON s.course_id = c.id
");
$allAssignments = $assignedSubjectsStmt->fetchAll();
$facultySubjectsMap = [];
foreach ($allAssignments as $asg) {
    $facultySubjectsMap[$asg['faculty_id']][] = $asg;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Faculty Directory</h3>
                <p class="text-muted small mb-0">Total Faculty Members: <?= count($facultyList) ?> professors and lecturers</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                <i class="bi bi-person-plus-fill me-2"></i> Register New Faculty
            </button>
        </div>

        <?php display_flash(); ?>

        <!-- Search Bar -->
        <div class="app-card p-3 mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" data-table-search="facultyTable" 
                               placeholder="Search faculty by name, employee ID, designation, or department...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Faculty Table -->
        <div class="app-card">
            <div class="table-responsive">
                <table class="table table-custom align-middle" id="facultyTable">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Faculty Name</th>
                            <th>Designation &amp; Dept</th>
                            <th>Contact</th>
                            <th>Teaching Load</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($facultyList)): ?>
                            <tr class="no-records-row">
                                <td colspan="7" class="text-center py-5 text-muted">No faculty members found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($facultyList as $f): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                            <?= e($f['employee_id']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-info text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 0.8rem;">
                                                <?= strtoupper(substr($f['first_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= e($f['first_name'] . ' ' . $f['last_name']) ?></div>
                                                <small class="text-muted"><?= e($f['qualification']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($f['designation']) ?></div>
                                        <small class="text-muted"><?= e($f['dept_name']) ?> (<?= e($f['dept_code']) ?>)</small>
                                    </td>
                                    <td>
                                        <div class="small"><i class="bi bi-envelope text-muted me-1"></i> <?= e($f['email']) ?></div>
                                        <?php if (!empty($f['phone'])): ?>
                                            <div class="small text-muted"><i class="bi bi-telephone text-muted me-1"></i> <?= e($f['phone']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info rounded-pill px-2.5 py-1" 
                                                onclick='openSubjectAssignModal(<?= json_encode($f) ?>, <?= json_encode($facultySubjectsMap[$f['id']] ?? []) ?>)'>
                                            <i class="bi bi-book me-1"></i> <?= $f['assigned_subjects_count'] ?> Subjects
                                        </button>
                                    </td>
                                    <td><?= get_status_badge($f['user_status']) ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary rounded-circle" title="Edit Faculty" 
                                                onclick='openEditFacultyModal(<?= json_encode($f) ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="faculty.php" class="d-inline" onsubmit="return confirm('Delete faculty member: <?= e($f['first_name'] . ' ' . $f['last_name']) ?>?');">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_faculty">
                                            <input type="hidden" name="faculty_id" value="<?= $f['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

<!-- Add Faculty Modal -->
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="faculty.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add_faculty">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-badge-fill me-2"></i> Register Faculty Member</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required placeholder="Dr. / Prof. First Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required placeholder="Last Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Employee ID *</label>
                            <input type="text" name="employee_id" class="form-control font-monospace" required placeholder="e.g. FAC-DCA-005">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Portal Username *</label>
                            <input type="text" name="username" class="form-control" required placeholder="e.g. prof_sharma">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email Address *</label>
                            <input type="email" name="email" class="form-control" required placeholder="faculty@campusconnect.edu">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Default Password *</label>
                            <input type="password" name="password" class="form-control" value="Faculty@123" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Department *</label>
                            <select name="department_id" class="form-select" required>
                                <option value="">-- Choose Department --</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= e($d['dept_name']) ?> (<?= e($d['dept_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Designation *</label>
                            <select name="designation" class="form-select" required>
                                <option value="Professor & HOD">Professor & HOD</option>
                                <option value="Professor">Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Assistant Professor" selected>Assistant Professor</option>
                                <option value="Guest Lecturer">Guest Lecturer</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Highest Qualification</label>
                            <input type="text" name="qualification" class="form-control" placeholder="e.g. Ph.D. in Computer Science">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" placeholder="+91 9876543210">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Register Faculty</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Faculty Modal -->
<div class="modal fade" id="editFacultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="faculty.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="edit_faculty">
                <input type="hidden" name="faculty_id" id="edit_fac_id">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Update Faculty Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">First Name *</label>
                            <input type="text" name="first_name" id="edit_fac_first" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Last Name *</label>
                            <input type="text" name="last_name" id="edit_fac_last" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Employee ID</label>
                            <input type="text" id="edit_fac_emp_id" class="form-control font-monospace" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Account Status</label>
                            <select name="status" id="edit_fac_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Department *</label>
                            <select name="department_id" id="edit_fac_dept" class="form-select" required>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= e($d['dept_name']) ?> (<?= e($d['dept_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Designation *</label>
                            <select name="designation" id="edit_fac_designation" class="form-select" required>
                                <option value="Professor & HOD">Professor & HOD</option>
                                <option value="Professor">Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Assistant Professor">Assistant Professor</option>
                                <option value="Guest Lecturer">Guest Lecturer</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Qualification</label>
                            <input type="text" name="qualification" id="edit_fac_qual" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="tel" name="phone" id="edit_fac_phone" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Subjects Modal -->
<div class="modal fade" id="subjectAssignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-book-fill me-2"></i> Teaching Subject Assignments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <h6 class="fw-bold mb-1" id="assign_fac_name">Faculty Name</h6>
                    <small class="text-muted" id="assign_fac_meta">Designation &bull; Department</small>
                </div>

                <!-- Currently Assigned Subjects Table -->
                <h6 class="fw-bold small text-uppercase text-muted mb-2">Currently Assigned Subjects:</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered align-middle mb-0" id="assignedSubjectsList">
                        <thead class="table-light">
                            <tr>
                                <th>Course</th>
                                <th>Subject Code</th>
                                <th>Subject Title</th>
                                <th>Academic Session</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="assignedSubjectsBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Assign New Subject Form -->
                <div class="p-3 bg-light rounded-3 border">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-plus-circle me-1"></i> Assign New Subject</h6>
                    <form method="POST" action="faculty.php">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="assign_subject">
                        <input type="hidden" name="faculty_id" id="assign_target_fac_id">

                        <div class="row g-2">
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold">Subject Program *</label>
                                <select name="subject_id" class="form-select form-select-sm" required>
                                    <option value="">-- Choose Subject --</option>
                                    <?php foreach ($allSubjects as $sub): ?>
                                        <option value="<?= $sub['id'] ?>">
                                            [<?= e($sub['course_code']) ?> Sem <?= $sub['semester'] ?>] <?= e($sub['subject_code']) ?> - <?= e($sub['subject_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Academic Year</label>
                                <input type="text" name="academic_year" class="form-control form-control-sm" value="2026-2027" readonly>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-sm btn-primary w-100">
                                    <i class="bi bi-plus-lg"></i> Assign
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function openEditFacultyModal(faculty) {
    document.getElementById('edit_fac_id').value = faculty.id;
    document.getElementById('edit_fac_first').value = faculty.first_name;
    document.getElementById('edit_fac_last').value = faculty.last_name;
    document.getElementById('edit_fac_emp_id').value = faculty.employee_id;
    document.getElementById('edit_fac_dept').value = faculty.department_id;
    document.getElementById('edit_fac_designation').value = faculty.designation;
    document.getElementById('edit_fac_qual').value = faculty.qualification || '';
    document.getElementById('edit_fac_phone').value = faculty.phone || '';
    document.getElementById('edit_fac_status').value = faculty.user_status || 'active';

    new bootstrap.Modal(document.getElementById('editFacultyModal')).show();
}

function openSubjectAssignModal(faculty, assignedSubjects) {
    document.getElementById('assign_target_fac_id').value = faculty.id;
    document.getElementById('assign_fac_name').textContent = faculty.first_name + ' ' + faculty.last_name;
    document.getElementById('assign_fac_meta').textContent = faculty.designation + ' • ' + faculty.dept_name;

    const tbody = document.getElementById('assignedSubjectsBody');
    tbody.innerHTML = '';

    if (!assignedSubjects || assignedSubjects.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">No subjects currently assigned to this faculty member.</td></tr>';
    } else {
        assignedSubjects.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><span class="badge bg-secondary">${item.course_code}</span></td>
                <td class="font-monospace">${item.subject_code}</td>
                <td class="fw-semibold">${item.subject_name}</td>
                <td><small class="text-muted">${item.academic_year}</small></td>
                <td class="text-end">
                    <form method="POST" action="faculty.php" class="d-inline" onsubmit="return confirm('Remove subject assignment?');">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="remove_assigned_subject">
                        <input type="hidden" name="map_id" value="${item.map_id}">
                        <button type="submit" class="btn btn-sm btn-outline-danger p-1" title="Unassign">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </form>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    new bootstrap.Modal(document.getElementById('subjectAssignModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
