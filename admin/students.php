<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Student Management (Admin)
 * BCA 5th Semester Major Project 1
 */

$page_title = 'Student Management';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

// Handle Add Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_student') {
    if (verify_csrf()) {
        $first_name       = sanitize($_POST['first_name'] ?? '');
        $last_name        = sanitize($_POST['last_name'] ?? '');
        $username         = strtolower(sanitize($_POST['username'] ?? ''));
        $enrollment_no    = strtoupper(sanitize($_POST['enrollment_no'] ?? ''));
        $email            = strtolower(sanitize($_POST['email'] ?? ''));
        $phone            = sanitize($_POST['phone'] ?? '');
        $gender           = sanitize($_POST['gender'] ?? 'Male');
        $dob              = sanitize($_POST['dob'] ?? '');
        $course_id        = (int)($_POST['course_id'] ?? 0);
        $current_semester = (int)($_POST['current_semester'] ?? 1);
        $academic_year    = sanitize($_POST['academic_year'] ?? '2026-2027');
        $address          = sanitize($_POST['address'] ?? '');
        $password         = $_POST['password'] ?? 'Student@123';

        if (empty($first_name) || empty($last_name) || empty($username) || empty($enrollment_no) || empty($email) || empty($password)) {
            set_flash('danger', 'Please provide all mandatory student details.');
        } else {
            try {
                // Check uniqueness
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                $check->execute([$email, $username]);
                if ($check->rowCount() > 0) {
                    set_flash('danger', 'A user with this email or username already exists.');
                } else {
                    $pdo->beginTransaction();
                    $pwd_hash = password_hash($password, PASSWORD_BCRYPT);
                    $uStmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'student', 'active')");
                    $uStmt->execute([$username, $email, $pwd_hash]);
                    $userId = $pdo->lastInsertId();

                    $sStmt = $pdo->prepare("
                        INSERT INTO students (user_id, enrollment_no, first_name, last_name, email, phone, gender, dob, course_id, current_semester, academic_year, address)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $sStmt->execute([
                        $userId, $enrollment_no, $first_name, $last_name, $email,
                        $phone, $gender, !empty($dob) ? $dob : null, $course_id,
                        $current_semester, $academic_year, $address
                    ]);
                    $pdo->commit();
                    set_flash('success', 'Student enrolled successfully!');
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                set_flash('danger', 'Error adding student: ' . $e->getMessage());
            }
        }
    }
    header('Location: ' . BASE_URL . 'admin/students.php');
    exit;
}

// Handle Update Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_student') {
    if (verify_csrf()) {
        $student_id       = (int)($_POST['student_id'] ?? 0);
        $first_name       = sanitize($_POST['first_name'] ?? '');
        $last_name        = sanitize($_POST['last_name'] ?? '');
        $phone            = sanitize($_POST['phone'] ?? '');
        $gender           = sanitize($_POST['gender'] ?? 'Male');
        $dob              = sanitize($_POST['dob'] ?? '');
        $course_id        = (int)($_POST['course_id'] ?? 0);
        $current_semester = (int)($_POST['current_semester'] ?? 1);
        $address          = sanitize($_POST['address'] ?? '');
        $status           = sanitize($_POST['status'] ?? 'active');

        if ($student_id > 0 && !empty($first_name) && !empty($last_name)) {
            try {
                $pdo->beginTransaction();
                // Update student table
                $upStudent = $pdo->prepare("
                    UPDATE students 
                    SET first_name = ?, last_name = ?, phone = ?, gender = ?, dob = ?, course_id = ?, current_semester = ?, address = ?
                    WHERE id = ?
                ");
                $upStudent->execute([
                    $first_name, $last_name, $phone, $gender,
                    !empty($dob) ? $dob : null, $course_id, $current_semester, $address, $student_id
                ]);

                // Update user status
                $upUser = $pdo->prepare("
                    UPDATE users u
                    JOIN students s ON u.id = s.user_id
                    SET u.status = ?
                    WHERE s.id = ?
                ");
                $upUser->execute([$status, $student_id]);

                $pdo->commit();
                set_flash('success', 'Student profile updated successfully.');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                set_flash('danger', 'Error updating student: ' . $e->getMessage());
            }
        }
    }
    header('Location: ' . BASE_URL . 'admin/students.php');
    exit;
}

// Handle Delete Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_student') {
    if (verify_csrf()) {
        $student_id = (int)($_POST['student_id'] ?? 0);
        if ($student_id > 0) {
            try {
                // Find user_id to delete from users table (cascades)
                $find = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
                $find->execute([$student_id]);
                $userId = $find->fetchColumn();

                if ($userId) {
                    $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $del->execute([$userId]);
                    set_flash('success', 'Student account removed from system.');
                }
            } catch (Exception $e) {
                set_flash('danger', 'Error removing student: ' . $e->getMessage());
            }
        }
    }
    header('Location: ' . BASE_URL . 'admin/students.php');
    exit;
}

// Fetch Courses for filter and add form
$courses = $pdo->query("SELECT id, course_name, course_code, total_semesters FROM courses ORDER BY course_name ASC")->fetchAll();

// Filter parameters
$course_filter = (int)($_GET['course_id'] ?? 0);
$sem_filter = (int)($_GET['semester'] ?? 0);

$query = "
    SELECT s.*, u.status as user_status, c.course_name, c.course_code
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN courses c ON s.course_id = c.id
    WHERE 1=1
";
$params = [];

if ($course_filter > 0) {
    $query .= " AND s.course_id = ?";
    $params[] = $course_filter;
}
if ($sem_filter > 0) {
    $query .= " AND s.current_semester = ?";
    $params[] = $sem_filter;
}

$query .= " ORDER BY s.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <!-- Header & Action -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Student Management</h3>
                <p class="text-muted small mb-0">Total Active Registrations: <?= count($students) ?> students</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                <i class="bi bi-person-plus-fill me-2"></i> Enroll New Student
            </button>
        </div>

        <?php display_flash(); ?>

        <!-- Search & Filter Bar -->
        <div class="app-card p-3 mb-4">
            <form method="GET" action="students.php" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="studentSearchInput" 
                               data-table-search="studentsTable" placeholder="Instant search by name, enrollment no, or email...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="course_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- All Programs --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($course_filter == $c['id']) ? 'selected' : '' ?>>
                                <?= e($c['course_code']) ?> - <?= e($c['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="semester" class="form-select" onchange="this.form.submit()">
                        <option value="">-- All Semesters --</option>
                        <?php for ($s = 1; $s <= 8; $s++): ?>
                            <option value="<?= $s ?>" <?= ($sem_filter == $s) ? 'selected' : '' ?>>Semester <?= $s ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <?php if ($course_filter > 0 || $sem_filter > 0): ?>
                        <a href="students.php" class="btn btn-outline-secondary w-100"><i class="bi bi-x-circle me-1"></i> Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Students Table -->
        <div class="app-card">
            <div class="table-responsive">
                <table class="table table-custom align-middle" id="studentsTable">
                    <thead>
                        <tr>
                            <th>Enrollment No</th>
                            <th>Student Name</th>
                            <th>Program &amp; Sem</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr class="no-records-row">
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-people display-4 d-block mb-2 text-secondary opacity-50"></i>
                                    No students found matching your criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                            <?= e($s['enrollment_no']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 0.8rem;">
                                                <?= strtoupper(substr($s['first_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></div>
                                                <small class="text-muted"><?= e($s['gender']) ?><?= !empty($s['dob']) ? ' &bull; ' . format_date($s['dob']) : '' ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($s['course_code']) ?></div>
                                        <small class="text-muted">Semester <?= e($s['current_semester']) ?></small>
                                    </td>
                                    <td>
                                        <div class="small"><i class="bi bi-envelope text-muted me-1"></i> <?= e($s['email']) ?></div>
                                        <?php if (!empty($s['phone'])): ?>
                                            <div class="small text-muted"><i class="bi bi-telephone text-muted me-1"></i> <?= e($s['phone']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= get_status_badge($s['user_status']) ?>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary rounded-circle" title="Edit Student" 
                                                onclick='openEditModal(<?= json_encode($s) ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="students.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete student: <?= e($s['first_name']) ?>? This will remove all their records.');">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_student">
                                            <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
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

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="students.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add_student">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i> Enroll New Student</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required placeholder="First Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required placeholder="Last Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Enrollment Number *</label>
                            <input type="text" name="enrollment_no" class="form-control font-monospace" required placeholder="e.g. EN2024BCA105">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Portal Username *</label>
                            <input type="text" name="username" class="form-control" required placeholder="e.g. rahul105">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email Address *</label>
                            <input type="email" name="email" class="form-control" required placeholder="student@campus.edu">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Password *</label>
                            <input type="password" name="password" class="form-control" value="Student@123" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Course Program *</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">-- Choose Program --</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['course_name']) ?> (<?= e($c['course_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Current Semester *</label>
                            <select name="current_semester" class="form-select" required>
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s ?>">Sem <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" placeholder="+91 9876543210">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Date of Birth</label>
                            <input type="date" name="dob" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Residential Address</label>
                            <input type="text" name="address" class="form-control" placeholder="Hostel / Street Address">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Enroll Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="students.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="edit_student">
                <input type="hidden" name="student_id" id="edit_student_id">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Update Student Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">First Name *</label>
                            <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Last Name *</label>
                            <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Enrollment Number</label>
                            <input type="text" id="edit_enrollment_no" class="form-control font-monospace" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Account Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Course Program *</label>
                            <select name="course_id" id="edit_course_id" class="form-select" required>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['course_name']) ?> (<?= e($c['course_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Current Semester *</label>
                            <select name="current_semester" id="edit_current_semester" class="form-select" required>
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s ?>">Sem <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Gender</label>
                            <select name="gender" id="edit_gender" class="form-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="tel" name="phone" id="edit_phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Date of Birth</label>
                            <input type="date" name="dob" id="edit_dob" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Address</label>
                            <input type="text" name="address" id="edit_address" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(student) {
    document.getElementById('edit_student_id').value = student.id;
    document.getElementById('edit_first_name').value = student.first_name;
    document.getElementById('edit_last_name').value = student.last_name;
    document.getElementById('edit_enrollment_no').value = student.enrollment_no;
    document.getElementById('edit_course_id').value = student.course_id;
    document.getElementById('edit_current_semester').value = student.current_semester;
    document.getElementById('edit_gender').value = student.gender;
    document.getElementById('edit_phone').value = student.phone || '';
    document.getElementById('edit_dob').value = student.dob || '';
    document.getElementById('edit_address').value = student.address || '';
    document.getElementById('edit_status').value = student.user_status || 'active';

    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
