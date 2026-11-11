<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Student Assignments & File Submission Desk
 * BCA 5th Semester Major Project 1
 */

$page_title = 'My Assignments';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('student');

$studentId = $currentUser['student_id'] ?? 0;
$courseId  = $currentUser['course_id'] ?? 0;
$semester  = $currentUser['current_semester'] ?? 1;

// Handle File Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_assignment') {
    if (verify_csrf()) {
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);

        if ($assignment_id > 0 && isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $upload = handle_file_upload($_FILES['submission_file'], 'assignments', ['pdf', 'doc', 'docx', 'zip', 'png', 'jpg'], 15);

            if ($upload['success']) {
                $filename = $upload['filename'];

                try {
                    // Check if submission is late
                    $asgStmt = $pdo->prepare("SELECT due_date FROM assignments WHERE id = ?");
                    $asgStmt->execute([$assignment_id]);
                    $dueDate = $asgStmt->fetchColumn();

                    $status = (strtotime($dueDate) < time()) ? 'Late' : 'Submitted';

                    $stmt = $pdo->prepare("
                        INSERT INTO submissions (assignment_id, student_id, file_path, submission_date, status)
                        VALUES (?, ?, ?, NOW(), ?)
                        ON DUPLICATE KEY UPDATE 
                            file_path = VALUES(file_path),
                            submission_date = NOW(),
                            status = VALUES(status)
                    ");
                    $stmt->execute([$assignment_id, $studentId, $filename, $status]);
                    set_flash('success', 'Assignment turned in successfully!');
                } catch (Exception $e) {
                    set_flash('danger', 'Error submitting assignment: ' . $e->getMessage());
                }
            } else {
                set_flash('danger', 'Upload failed: ' . $upload['error']);
            }
        } else {
            set_flash('danger', 'Please choose a valid file to upload.');
        }
    }
    header('Location: ' . BASE_URL . 'student/assignments.php');
    exit;
}

// Fetch all assignments for this student's class with their submission status
$assignmentsStmt = $pdo->prepare("
    SELECT a.*, s.subject_name, s.subject_code,
           f.first_name as fac_first, f.last_name as fac_last,
           sub.id as submission_id, sub.file_path as sub_file, sub.submission_date,
           sub.status as sub_status, sub.marks_obtained, sub.feedback
    FROM assignments a
    JOIN subjects s ON a.subject_id = s.id
    JOIN faculty f ON a.faculty_id = f.id
    LEFT JOIN submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
    WHERE s.course_id = ? AND s.semester = ?
    ORDER BY a.due_date DESC
");
$assignmentsStmt->execute([$studentId, $courseId, $semester]);
$assignments = $assignmentsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Academic Assignments &amp; Tasks</h3>
                <p class="text-muted small mb-0">Download problem sheets, submit homework files, and review faculty evaluations</p>
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- Search Bar -->
        <div class="app-card p-3 mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" data-table-search="studentAssignmentsTable" 
                               placeholder="Search assignments by title, subject, or professor...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignments List -->
        <div class="app-card">
            <div class="table-responsive">
                <table class="table table-custom align-middle" id="studentAssignmentsTable">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Assignment Title &amp; Details</th>
                            <th>Faculty</th>
                            <th>Deadline</th>
                            <th>Max Marks</th>
                            <th>My Submission</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assignments)): ?>
                            <tr class="no-records-row">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-check display-5 d-block mb-2 text-success opacity-50"></i>
                                    No active assignments scheduled for Semester <?= e($semester) ?>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assignments as $a): ?>
                                <?php 
                                    $isOverdue = (strtotime($a['due_date']) < time());
                                    $hasSubmitted = !empty($a['submission_id']);
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary text-white font-monospace"><?= e($a['subject_code']) ?></span>
                                        <small class="text-muted d-block mt-1"><?= e($a['subject_name']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($a['title']) ?></div>
                                        <small class="text-muted d-block text-truncate" style="max-width: 260px;">
                                            <?= e($a['description']) ?>
                                        </small>
                                        <?php if (!empty($a['file_path'])): ?>
                                            <a href="<?= BASE_URL ?>uploads/assignments/<?= e($a['file_path']) ?>" target="_blank" class="badge bg-light text-primary border text-decoration-none mt-1">
                                                <i class="bi bi-download me-1"></i> Question Sheet
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark">Prof. <?= e($a['fac_first'] . ' ' . $a['fac_last']) ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold <?= ($isOverdue && !$hasSubmitted) ? 'text-danger' : 'text-dark' ?>">
                                            <?= format_datetime($a['due_date']) ?>
                                        </div>
                                        <?php if ($isOverdue && !$hasSubmitted): ?>
                                            <span class="badge bg-danger-subtle text-danger small">Overdue</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold"><?= e($a['max_marks']) ?> Pts</span>
                                    </td>
                                    <td>
                                        <?php if ($hasSubmitted): ?>
                                            <?= get_status_badge($a['sub_status']) ?>
                                            <div class="small text-muted mt-1">
                                                Turned in: <?= format_date($a['submission_date'], 'd M, h:i A') ?>
                                            </div>
                                            <?php if ($a['marks_obtained'] !== null): ?>
                                                <div class="mt-1 fw-bold text-success">
                                                    Score: <?= e($a['marks_obtained']) ?> / <?= e($a['max_marks']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($a['feedback'])): ?>
                                                <div class="small text-info mt-1" title="<?= e($a['feedback']) ?>">
                                                    <i class="bi bi-chat-quote-fill me-1"></i> <?= e($a['feedback']) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary">Not Submitted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($hasSubmitted): ?>
                                            <div class="d-flex flex-column align-items-end gap-1">
                                                <a href="<?= BASE_URL ?>uploads/assignments/<?= e($a['sub_file']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary px-2.5 py-1">
                                                    <i class="bi bi-eye me-1"></i> View My File
                                                </a>
                                                <?php if (!$isOverdue || $a['sub_status'] === 'Resubmission'): ?>
                                                    <button class="btn btn-sm btn-outline-primary px-2.5 py-0.5 rounded-pill small" 
                                                            onclick='openSubmitModal(<?= json_encode($a) ?>)'>
                                                        Re-upload
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-primary rounded-pill px-3.5 py-1.5" 
                                                    onclick='openSubmitModal(<?= json_encode($a) ?>)'>
                                                <i class="bi bi-cloud-arrow-up me-1"></i> Submit Work
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

<!-- Submit Assignment Modal -->
<div class="modal fade" id="submitAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="assignments.php" enctype="multipart/form-data">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="submit_assignment">
                <input type="hidden" name="assignment_id" id="modal_assign_id">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cloud-arrow-up-fill me-2"></i> Submit Assignment Work</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Assignment Title</label>
                        <h6 class="fw-bold text-dark" id="modal_assign_title">Title</h6>
                        <small class="text-muted" id="modal_assign_subject">Subject</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Instructions</label>
                        <div class="p-3 bg-light rounded text-muted small" id="modal_assign_desc">Instructions</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Upload Deliverable (PDF, ZIP, DOCX, PNG, max 15MB) *</label>
                        <input type="file" name="submission_file" class="form-control" required>
                        <small class="text-muted">Ensure your code files or reports are bundled cleanly before uploading.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Upload &amp; Turn In</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openSubmitModal(asg) {
    document.getElementById('modal_assign_id').value = asg.id;
    document.getElementById('modal_assign_title').textContent = asg.title;
    document.getElementById('modal_assign_subject').textContent = '[' + asg.subject_code + '] ' + asg.subject_name;
    document.getElementById('modal_assign_desc').textContent = asg.description;

    new bootstrap.Modal(document.getElementById('submitAssignmentModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
