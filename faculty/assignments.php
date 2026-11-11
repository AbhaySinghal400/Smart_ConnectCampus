<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Assignment Management (Faculty)
 * BCA 5th Semester Major Project 1
 */

$page_title = 'Assignments';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('faculty');

$facultyId = $currentUser['faculty_id'] ?? 0;

// Fetch faculty's assigned subjects
$subjectsStmt = $pdo->prepare("
    SELECT s.id, s.subject_name, s.subject_code, s.semester, c.course_code
    FROM faculty_subjects fs
    JOIN subjects s ON fs.subject_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE fs.faculty_id = ?
    ORDER BY c.course_code, s.semester, s.subject_name
");
$subjectsStmt->execute([$facultyId]);
$mySubjects = $subjectsStmt->fetchAll();

// 1. Handle Create Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_assignment') {
    if (verify_csrf()) {
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $title      = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $due_date   = sanitize($_POST['due_date'] ?? '');
        $max_marks  = (int)($_POST['max_marks'] ?? 50);
        $file_path  = null;

        if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
            $upload = handle_file_upload($_FILES['assignment_file'], 'assignments', ['pdf', 'doc', 'docx', 'zip', 'png', 'jpg'], 15);
            if ($upload['success']) {
                $file_path = $upload['filename'];
            } else {
                set_flash('danger', 'File upload error: ' . $upload['error']);
                header('Location: ' . BASE_URL . 'faculty/assignments.php');
                exit;
            }
        }

        if ($subject_id > 0 && !empty($title) && !empty($description) && !empty($due_date)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO assignments (subject_id, faculty_id, title, description, file_path, due_date, max_marks)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$subject_id, $facultyId, $title, $description, $file_path, $due_date, $max_marks]);
                set_flash('success', 'Assignment created and posted to student dashboards!');
            } catch (Exception $e) {
                set_flash('danger', 'Error creating assignment: ' . $e->getMessage());
            }
        } else {
            set_flash('danger', 'Please provide subject, title, instructions, and deadline.');
        }
    }
    header('Location: ' . BASE_URL . 'faculty/assignments.php');
    exit;
}

// 2. Handle Edit Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_assignment') {
    if (verify_csrf()) {
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);
        $title         = sanitize($_POST['title'] ?? '');
        $description   = sanitize($_POST['description'] ?? '');
        $due_date      = sanitize($_POST['due_date'] ?? '');
        $max_marks     = (int)($_POST['max_marks'] ?? 50);

        if ($assignment_id > 0 && !empty($title) && !empty($description) && !empty($due_date)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE assignments 
                    SET title = ?, description = ?, due_date = ?, max_marks = ?
                    WHERE id = ? AND faculty_id = ?
                ");
                $stmt->execute([$title, $description, $due_date, $max_marks, $assignment_id, $facultyId]);
                set_flash('success', 'Assignment updated successfully.');
            } catch (Exception $e) {
                set_flash('danger', 'Error updating assignment: ' . $e->getMessage());
            }
        }
    }
    header('Location: ' . BASE_URL . 'faculty/assignments.php');
    exit;
}

// 3. Handle Delete Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_assignment') {
    if (verify_csrf()) {
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);
        if ($assignment_id > 0) {
            $del = $pdo->prepare("DELETE FROM assignments WHERE id = ? AND faculty_id = ?");
            $del->execute([$assignment_id, $facultyId]);
            set_flash('success', 'Assignment and related submissions deleted.');
        }
    }
    header('Location: ' . BASE_URL . 'faculty/assignments.php');
    exit;
}

// Fetch all assignments created by this faculty
$assignmentsStmt = $pdo->prepare("
    SELECT a.*, s.subject_name, s.subject_code, c.course_code, s.semester,
           COUNT(sub.id) as total_submissions,
           SUM(CASE WHEN sub.status = 'Submitted' THEN 1 ELSE 0 END) as pending_grading,
           SUM(CASE WHEN sub.status = 'Graded' THEN 1 ELSE 0 END) as graded_count
    FROM assignments a
    JOIN subjects s ON a.subject_id = s.id
    JOIN courses c ON s.course_id = c.id
    LEFT JOIN submissions sub ON a.id = sub.assignment_id
    WHERE a.faculty_id = ?
    GROUP BY a.id
    ORDER BY a.created_at DESC
");
$assignmentsStmt->execute([$facultyId]);
$assignments = $assignmentsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Academic Assignments &amp; Tasks</h3>
                <p class="text-muted small mb-0">Publish homework, project guidelines, problem sets, and review submissions</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addAssignmentModal">
                <i class="bi bi-file-earmark-plus-fill me-2"></i> Create New Assignment
            </button>
        </div>

        <?php display_flash(); ?>

        <!-- Search Bar -->
        <div class="app-card p-3 mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" data-table-search="assignmentsTable" 
                               placeholder="Search assignments by title, subject, or course...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignments Table -->
        <div class="app-card">
            <div class="table-responsive">
                <table class="table table-custom align-middle" id="assignmentsTable">
                    <thead>
                        <tr>
                            <th>Program &amp; Subject</th>
                            <th>Title &amp; Brief</th>
                            <th>Deadline</th>
                            <th>Max Marks</th>
                            <th>Submissions</th>
                            <th>File</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assignments)): ?>
                            <tr class="no-records-row">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x display-5 d-block mb-2 opacity-50"></i>
                                    You have not created any assignments yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assignments as $a): ?>
                                <?php 
                                    $isExpired = (strtotime($a['due_date']) < time());
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary font-monospace"><?= e($a['course_code']) ?> Sem <?= e($a['semester']) ?></span>
                                        <div class="fw-bold text-dark mt-1"><?= e($a['subject_name']) ?></div>
                                        <small class="text-muted font-monospace"><?= e($a['subject_code']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($a['title']) ?></div>
                                        <small class="text-muted d-block text-truncate" style="max-width: 280px;">
                                            <?= e($a['description']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold <?= $isExpired ? 'text-danger' : 'text-dark' ?>">
                                            <?= format_datetime($a['due_date']) ?>
                                        </div>
                                        <?php if ($isExpired): ?>
                                            <span class="badge bg-danger-subtle text-danger small">Deadline Passed</span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success small">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= e($a['max_marks']) ?> Pts</span>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>faculty/submissions.php?assignment_id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1">
                                            <i class="bi bi-people me-1"></i> <?= $a['total_submissions'] ?> Turn-ins
                                            <?php if ($a['pending_grading'] > 0): ?>
                                                <span class="badge bg-danger ms-1"><?= $a['pending_grading'] ?> New</span>
                                            <?php endif; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($a['file_path'])): ?>
                                            <a href="<?= BASE_URL ?>uploads/assignments/<?= e($a['file_path']) ?>" target="_blank" class="btn btn-sm btn-light border p-1" title="Download Document">
                                                <i class="bi bi-paperclip"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary rounded-circle" title="Edit Assignment" 
                                                onclick='openEditAssignmentModal(<?= json_encode($a) ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="assignments.php" class="d-inline" onsubmit="return confirm('Delete this assignment and all student submissions?');">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_assignment">
                                            <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
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

<!-- Add Assignment Modal -->
<div class="modal fade" id="addAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="assignments.php" enctype="multipart/form-data">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add_assignment">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus-fill me-2"></i> Post New Assignment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Teaching Subject *</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">-- Choose Subject --</option>
                                <?php foreach ($mySubjects as $sub): ?>
                                    <option value="<?= $sub['id'] ?>">
                                        [<?= e($sub['course_code']) ?> Sem <?= $sub['semester'] ?>] <?= e($sub['subject_code']) ?> - <?= e($sub['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Maximum Marks *</label>
                            <input type="number" name="max_marks" class="form-control" value="50" min="5" max="200" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Assignment Title *</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. PHP Session Authentication &amp; PDO CRUD">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Submission Due Date &amp; Time *</label>
                            <input type="datetime-local" name="due_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Attachment Question Sheet (PDF/ZIP/DOCX)</label>
                            <input type="file" name="assignment_file" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Instructions &amp; Problem Statement *</label>
                            <textarea name="description" class="form-control" rows="5" required placeholder="Detail the task requirements, coding standards, expected deliverables, and evaluation criteria..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Publish Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Assignment Modal -->
<div class="modal fade" id="editAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="assignments.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="edit_assignment">
                <input type="hidden" name="assignment_id" id="edit_asg_id">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit Assignment Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Maximum Marks *</label>
                            <input type="number" name="max_marks" id="edit_asg_marks" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Submission Due Date &amp; Time *</label>
                            <input type="datetime-local" name="due_date" id="edit_asg_due" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Assignment Title *</label>
                            <input type="text" name="title" id="edit_asg_title" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Instructions *</label>
                            <textarea name="description" id="edit_asg_desc" class="form-control" rows="5" required></textarea>
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
function openEditAssignmentModal(asg) {
    document.getElementById('edit_asg_id').value = asg.id;
    document.getElementById('edit_asg_title').value = asg.title;
    document.getElementById('edit_asg_marks').value = asg.max_marks;
    document.getElementById('edit_asg_desc').value = asg.description;

    // Convert SQL datetime to datetime-local format
    if (asg.due_date) {
        document.getElementById('edit_asg_due').value = asg.due_date.replace(' ', 'T').substring(0, 16);
    }

    new bootstrap.Modal(document.getElementById('editAssignmentModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
