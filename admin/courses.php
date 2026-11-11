<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Academic Structure: Departments, Courses & Subjects (Admin)
 * BCA 5th Semester Major Project 1
 */

$page_title = 'Departments & Courses';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

// 1. Handle Department Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verify_csrf()) {
        $act = $_POST['action'];

        // Add Department
        if ($act === 'add_department') {
            $dept_name = sanitize($_POST['dept_name'] ?? '');
            $dept_code = strtoupper(sanitize($_POST['dept_code'] ?? ''));
            $desc      = sanitize($_POST['description'] ?? '');

            if (!empty($dept_name) && !empty($dept_code)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO departments (dept_name, dept_code, description) VALUES (?, ?, ?)");
                    $stmt->execute([$dept_name, $dept_code, $desc]);
                    set_flash('success', "Department '{$dept_code}' created successfully.");
                } catch (Exception $e) {
                    set_flash('danger', 'Error adding department: ' . $e->getMessage());
                }
            }
        }
        // Delete Department
        elseif ($act === 'delete_department') {
            $dept_id = (int)($_POST['dept_id'] ?? 0);
            try {
                $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
                $stmt->execute([$dept_id]);
                set_flash('success', 'Department deleted successfully.');
            } catch (Exception $e) {
                set_flash('danger', 'Cannot delete department: ' . $e->getMessage());
            }
        }
        // Add Course
        elseif ($act === 'add_course') {
            $dept_id     = (int)($_POST['dept_id'] ?? 0);
            $course_name = sanitize($_POST['course_name'] ?? '');
            $course_code = strtoupper(sanitize($_POST['course_code'] ?? ''));
            $duration    = (int)($_POST['duration_years'] ?? 3);
            $semesters   = (int)($_POST['total_semesters'] ?? 6);

            if ($dept_id > 0 && !empty($course_name) && !empty($course_code)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO courses (dept_id, course_name, course_code, duration_years, total_semesters) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$dept_id, $course_name, $course_code, $duration, $semesters]);
                    set_flash('success', "Course '{$course_code}' registered successfully.");
                } catch (Exception $e) {
                    set_flash('danger', 'Error adding course: ' . $e->getMessage());
                }
            }
        }
        // Delete Course
        elseif ($act === 'delete_course') {
            $course_id = (int)($_POST['course_id'] ?? 0);
            try {
                $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
                $stmt->execute([$course_id]);
                set_flash('success', 'Course program deleted.');
            } catch (Exception $e) {
                set_flash('danger', 'Error deleting course: ' . $e->getMessage());
            }
        }
        // Add Subject
        elseif ($act === 'add_subject') {
            $course_id    = (int)($_POST['course_id'] ?? 0);
            $semester     = (int)($_POST['semester'] ?? 1);
            $subject_name = sanitize($_POST['subject_name'] ?? '');
            $subject_code = strtoupper(sanitize($_POST['subject_code'] ?? ''));
            $credits      = (int)($_POST['credits'] ?? 4);
            $type         = sanitize($_POST['type'] ?? 'Theory');

            if ($course_id > 0 && !empty($subject_name) && !empty($subject_code)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO subjects (course_id, semester, subject_name, subject_code, credits, type) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$course_id, $semester, $subject_name, $subject_code, $credits, $type]);
                    set_flash('success', "Subject '{$subject_code}' added to curriculum.");
                } catch (Exception $e) {
                    set_flash('danger', 'Error adding subject: ' . $e->getMessage());
                }
            }
        }
        // Delete Subject
        elseif ($act === 'delete_subject') {
            $subject_id = (int)($_POST['subject_id'] ?? 0);
            try {
                $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
                $stmt->execute([$subject_id]);
                set_flash('success', 'Subject removed from curriculum.');
            } catch (Exception $e) {
                set_flash('danger', 'Error deleting subject: ' . $e->getMessage());
            }
        }
    }
    header('Location: ' . BASE_URL . 'admin/courses.php');
    exit;
}

// Fetch Departments with Courses count
$departments = $pdo->query("
    SELECT d.*, COUNT(DISTINCT c.id) as course_count, COUNT(DISTINCT f.id) as faculty_count
    FROM departments d
    LEFT JOIN courses c ON d.id = c.dept_id
    LEFT JOIN faculty f ON d.id = f.department_id
    GROUP BY d.id
    ORDER BY d.dept_name ASC
")->fetchAll();

// Fetch Courses with Subjects & Students count
$courses = $pdo->query("
    SELECT c.*, d.dept_name, d.dept_code,
           COUNT(DISTINCT s.id) as subject_count,
           COUNT(DISTINCT st.id) as student_count
    FROM courses c
    JOIN departments d ON c.dept_id = d.id
    LEFT JOIN subjects s ON c.id = s.course_id
    LEFT JOIN students st ON c.id = st.course_id
    GROUP BY c.id
    ORDER BY c.course_name ASC
")->fetchAll();

// Selected Course for Curricula viewing
$selected_course_id = (int)($_GET['view_course'] ?? ($courses[0]['id'] ?? 0));
$subjectsStmt = $pdo->prepare("
    SELECT s.*, c.course_name, c.course_code
    FROM subjects s
    JOIN courses c ON s.course_id = c.id
    WHERE s.course_id = ?
    ORDER BY s.semester ASC, s.subject_name ASC
");
$subjectsStmt->execute([$selected_course_id]);
$subjects = $subjectsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Academic Programs &amp; Curricula</h3>
                <p class="text-muted small mb-0">Manage university departments, degree courses, and semester syllabi</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addDeptModal">
                    <i class="bi bi-building me-1"></i> Add Department
                </button>
                <button class="btn btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                    <i class="bi bi-book-half me-1"></i> Add Course
                </button>
                <button class="btn btn-teal rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                    <i class="bi bi-journal-plus me-1"></i> Add Subject
                </button>
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- Departments Grid -->
        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-diagram-3-fill text-primary me-2"></i> Academic Departments</h5>
        <div class="row g-3 mb-4">
            <?php foreach ($departments as $d): ?>
                <div class="col-md-4">
                    <div class="app-card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-primary text-white font-monospace px-2.5 py-1.5"><?= e($d['dept_code']) ?></span>
                            <form method="POST" action="courses.php" class="d-inline" onsubmit="return confirm('Delete department <?= e($d['dept_name']) ?>?');">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="delete_department">
                                <input type="hidden" name="dept_id" value="<?= $d['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Delete Department">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                        <h6 class="fw-bold text-dark mb-1"><?= e($d['dept_name']) ?></h6>
                        <p class="text-muted small mb-3"><?= e($d['description']) ?></p>
                        <div class="d-flex gap-3 pt-2 border-top text-muted small">
                            <span><i class="bi bi-book me-1 text-primary"></i> <?= $d['course_count'] ?> Programs</span>
                            <span><i class="bi bi-person-video3 me-1 text-info"></i> <?= $d['faculty_count'] ?> Faculty</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Courses & Curriculum Section -->
        <div class="row g-4">
            <!-- Courses List -->
            <div class="col-lg-5">
                <div class="app-card h-100">
                    <div class="app-card-header">
                        <h6 class="app-card-title"><i class="bi bi-mortarboard text-primary"></i> Degree Programs</h6>
                        <span class="badge bg-light text-dark border"><?= count($courses) ?> Total</span>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($courses as $c): ?>
                            <div class="list-group-item p-3 d-flex justify-content-between align-items-center <?= ($selected_course_id == $c['id']) ? 'bg-light border-start border-primary border-4' : '' ?>">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-secondary font-monospace"><?= e($c['course_code']) ?></span>
                                        <h6 class="mb-0 fw-bold text-dark"><?= e($c['course_name']) ?></h6>
                                    </div>
                                    <small class="text-muted">
                                        <?= e($c['duration_years']) ?> Years &bull; <?= e($c['total_semesters']) ?> Semesters &bull; <?= $c['student_count'] ?> Students
                                    </small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="courses.php?view_course=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Subjects">
                                        <i class="bi bi-list-nested"></i> Subjects (<?= $c['subject_count'] ?>)
                                    </a>
                                    <form method="POST" action="courses.php" onsubmit="return confirm('Delete program <?= e($c['course_name']) ?>?');">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete_course">
                                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger p-1" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Subjects Curricula for Selected Course -->
            <div class="col-lg-7">
                <div class="app-card h-100">
                    <div class="app-card-header">
                        <h6 class="app-card-title">
                            <i class="bi bi-journal-code text-teal"></i> Subjects &amp; Syllabus
                        </h6>
                        <button class="btn btn-sm btn-teal rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                            <i class="bi bi-plus-lg me-1"></i> Add Subject
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>Sem</th>
                                    <th>Subject Code</th>
                                    <th>Title</th>
                                    <th>Credits</th>
                                    <th>Type</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($subjects)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No subjects configured for this course yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($subjects as $sub): ?>
                                        <tr>
                                            <td><span class="badge bg-light text-dark border">Sem <?= e($sub['semester']) ?></span></td>
                                            <td class="font-monospace fw-bold text-dark"><?= e($sub['subject_code']) ?></td>
                                            <td class="fw-semibold"><?= e($sub['subject_name']) ?></td>
                                            <td><?= e($sub['credits']) ?> Credits</td>
                                            <td>
                                                <span class="badge <?= ($sub['type'] === 'Practical') ? 'bg-info-subtle text-info' : (($sub['type'] === 'Project') ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary') ?>">
                                                    <?= e($sub['type']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <form method="POST" action="courses.php" onsubmit="return confirm('Delete subject <?= e($sub['subject_name']) ?>?');">
                                                    <?php csrf_field(); ?>
                                                    <input type="hidden" name="action" value="delete_subject">
                                                    <input type="hidden" name="subject_id" value="<?= $sub['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger p-1" title="Delete">
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
        </div>

    </div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="courses.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add_department">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Create Academic Department</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Department Name *</label>
                        <input type="text" name="dept_name" class="form-control" required placeholder="e.g. Department of Computer Applications">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Department Code *</label>
                        <input type="text" name="dept_code" class="form-control font-monospace" required placeholder="e.g. DCA">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Department overview and focus areas"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Create Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="courses.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add_course">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Register Degree Course</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Parent Department *</label>
                        <select name="dept_id" class="form-select" required>
                            <option value="">-- Choose Department --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= e($d['dept_name']) ?> (<?= e($d['dept_code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Course Name *</label>
                        <input type="text" name="course_name" class="form-control" required placeholder="e.g. Bachelor of Computer Applications">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Course Code *</label>
                        <input type="text" name="course_code" class="form-control font-monospace" required placeholder="e.g. BCA">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Duration (Years)</label>
                            <input type="number" name="duration_years" class="form-control" value="3" min="1" max="6" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Total Semesters</label>
                            <input type="number" name="total_semesters" class="form-control" value="6" min="1" max="12" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Register Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="courses.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add_subject">
                <div class="modal-header bg-teal text-white" style="background-color: var(--accent-color);">
                    <h5 class="modal-title fw-bold">Add Subject to Syllabus</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Course Program *</label>
                        <select name="course_id" class="form-select" required>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($selected_course_id == $c['id']) ? 'selected' : '' ?>>
                                    <?= e($c['course_name']) ?> (<?= e($c['course_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Semester *</label>
                            <select name="semester" class="form-select" required>
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s ?>">Sem <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Credits *</label>
                            <input type="number" name="credits" class="form-control" value="4" min="1" max="10" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Subject Title *</label>
                        <input type="text" name="subject_name" class="form-control" required placeholder="e.g. Web Development with PHP & MySQL">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Subject Code *</label>
                            <input type="text" name="subject_code" class="form-control font-monospace" required placeholder="e.g. BCA501">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Course Type</label>
                            <select name="type" class="form-select">
                                <option value="Theory">Theory</option>
                                <option value="Practical" selected>Practical / Lab</option>
                                <option value="Project">Project</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-teal px-4 text-white">Save Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
