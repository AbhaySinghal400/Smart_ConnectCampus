<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Student Submissions & Grading Desk (Faculty)
 * BCA 5th Semester Major Project 1
 */

$page_title = 'Submissions & Grading';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('faculty');

$facultyId = $currentUser['faculty_id'] ?? 0;

// Fetch faculty's assignments for filter
$assignmentsStmt = $pdo->prepare("
    SELECT a.id, a.title, a.max_marks, s.subject_name, s.subject_code, c.course_code, s.semester
    FROM assignments a
    JOIN subjects s ON a.subject_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE a.faculty_id = ?
    ORDER BY a.created_at DESC
");
$assignmentsStmt->execute([$facultyId]);
$myAssignments = $assignmentsStmt->fetchAll();

// Handle Grade Submission POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'grade_submission') {
    if (verify_csrf()) {
        $submission_id  = (int)($_POST['submission_id'] ?? 0);
        $marks_obtained = (float)($_POST['marks_obtained'] ?? 0);
        $max_marks      = (float)($_POST['max_marks'] ?? 100);
        $feedback       = sanitize($_POST['feedback'] ?? '');
        $status         = sanitize($_POST['status'] ?? 'Graded');

        if ($submission_id > 0) {
            if ($marks_obtained < 0 || $marks_obtained > $max_marks) {
                set_flash('danger', "Marks obtained must be between 0 and {$max_marks}.");
            } else {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE submissions sub
                        JOIN assignments a ON sub.assignment_id = a.id
                        SET sub.marks_obtained = ?, sub.feedback = ?, sub.status = ?, sub.updated_at = NOW()
                        WHERE sub.id = ? AND a.faculty_id = ?
                    ");
                    $stmt->execute([$marks_obtained, $feedback, $status, $submission_id, $facultyId]);
                    set_flash('success', "Submission successfully evaluated and graded ({$marks_obtained}/{$max_marks}).");
                } catch (Exception $e) {
                    set_flash('danger', 'Error saving evaluation: ' . $e->getMessage());
                }
            }
        }
    }
    $ret_asg = (int)($_POST['return_assignment_id'] ?? 0);
    header("Location: " . BASE_URL . "faculty/submissions.php?assignment_id={$ret_asg}");
    exit;
}

// Current Filter
$selected_asg_id = (int)($_GET['assignment_id'] ?? ($myAssignments[0]['id'] ?? 0));
$currentAsg = null;
foreach ($myAssignments as $asg) {
    if ($asg['id'] == $selected_asg_id) {
        $currentAsg = $asg;
        break;
    }
}

// Fetch submissions for selected assignment
$submissions = [];
if ($currentAsg) {
    $subStmt = $pdo->prepare("
        SELECT sub.*, s.first_name, s.last_name, s.enrollment_no, s.email as student_email,
               c.course_code, s.current_semester
        FROM submissions sub
        JOIN students s ON sub.student_id = s.id
        JOIN courses c ON s.course_id = c.id
        WHERE sub.assignment_id = ?
        ORDER BY sub.submission_date DESC
    ");
    $subStmt->execute([$selected_asg_id]);
    $submissions = $subStmt->fetchAll();
}

// Calculate summary stats
$totalSub = count($submissions);
$gradedSub = 0;
$pendingSub = 0;
$totalMarksSum = 0;
foreach ($submissions as $sb) {
    if ($sb['status'] === 'Graded' && $sb['marks_obtained'] !== null) {
        $gradedSub++;
        $totalMarksSum += (float)$sb['marks_obtained'];
    } else {
        $pendingSub++;
    }
}
$avgScore = ($gradedSub > 0) ? round($totalMarksSum / $gradedSub, 1) : 0;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Student Submissions &amp; Grading</h3>
                <p class="text-muted small mb-0">Evaluate student work, award scores, and provide qualitative feedback</p>
            </div>
            <a href="<?= BASE_URL ?>faculty/assignments.php" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to Assignments
            </a>
        </div>

        <?php display_flash(); ?>

        <!-- Assignment Filter -->
        <div class="app-card p-3 mb-4">
            <form method="GET" action="submissions.php" class="row g-2 align-items-center">
                <div class="col-md-7">
                    <label class="form-label small fw-semibold text-muted">Select Assignment to Evaluate</label>
                    <select name="assignment_id" class="form-select" onchange="this.form.submit()">
                        <?php if (empty($myAssignments)): ?>
                            <option value="">No assignments created yet</option>
                        <?php else: ?>
                            <?php foreach ($myAssignments as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= ($selected_asg_id == $a['id']) ? 'selected' : '' ?>>
                                    [<?= e($a['course_code']) ?> Sem <?= $a['semester'] ?>] <?= e($a['title']) ?> (Max: <?= e($a['max_marks']) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted">Search in Submissions</label>
                    <input type="text" class="form-control" data-table-search="submissionsTable" placeholder="Filter by student name or roll...">
                </div>
            </form>
        </div>

        <?php if ($currentAsg): ?>
            <!-- Metrics Row -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">Total Submissions</div>
                        <div class="stat-value text-primary mt-1"><?= $totalSub ?></div>
                        <small class="text-muted">Turned in deliverables</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">Evaluated / Graded</div>
                        <div class="stat-value text-success mt-1"><?= $gradedSub ?></div>
                        <small class="text-muted">Scores awarded</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">Pending Review</div>
                        <div class="stat-value text-warning mt-1"><?= $pendingSub ?></div>
                        <small class="text-warning fw-semibold">Requires grading</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">Average Score</div>
                        <div class="stat-value text-info mt-1"><?= $avgScore ?> <small class="text-muted fs-6">/ <?= $currentAsg['max_marks'] ?></small></div>
                        <small class="text-muted">Class performance</small>
                    </div>
                </div>
            </div>

            <!-- Submissions Table -->
            <div class="app-card">
                <div class="table-responsive">
                    <table class="table table-custom align-middle" id="submissionsTable">
                        <thead>
                            <tr>
                                <th>Student Roll &amp; Name</th>
                                <th>Program</th>
                                <th>Submission Date</th>
                                <th>Deliverable File</th>
                                <th>Status</th>
                                <th>Score Awarded</th>
                                <th class="text-end">Evaluation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($submissions)): ?>
                                <tr class="no-records-row">
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox display-5 d-block mb-2 opacity-50"></i>
                                        No student submissions received for this assignment yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($submissions as $s): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></div>
                                            <small class="text-muted font-monospace"><?= e($s['enrollment_no']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= e($s['course_code']) ?> Sem <?= e($s['current_semester']) ?></span>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold"><?= format_date($s['submission_date']) ?></div>
                                            <small class="text-muted"><?= format_time($s['submission_date']) ?></small>
                                        </td>
                                        <td>
                                            <a href="<?= BASE_URL ?>uploads/assignments/<?= e($s['file_path']) ?>" target="_blank" class="btn btn-sm btn-light border px-2.5 py-1">
                                                <i class="bi bi-file-earmark-arrow-down me-1 text-primary"></i> Download
                                            </a>
                                        </td>
                                        <td><?= get_status_badge($s['status']) ?></td>
                                        <td>
                                            <?php if ($s['marks_obtained'] !== null): ?>
                                                <span class="badge bg-success text-white fs-6">
                                                    <?= e($s['marks_obtained']) ?> / <?= e($currentAsg['max_marks']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary">Ungraded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-primary rounded-pill px-3" 
                                                    onclick='openGradeModal(<?= json_encode($s) ?>, <?= json_encode($currentAsg) ?>)'>
                                                <i class="bi bi-check2-circle me-1"></i> Grade
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>

    </div>

<!-- Grading Modal -->
<div class="modal fade" id="gradeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="submissions.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="grade_submission">
                <input type="hidden" name="submission_id" id="modal_sub_id">
                <input type="hidden" name="return_assignment_id" value="<?= $selected_asg_id ?>">
                <input type="hidden" name="max_marks" id="modal_max_marks">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-award-fill me-2"></i> Grade Submission</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Student Name &amp; Enrollment</label>
                        <div class="fw-bold text-dark fs-6" id="modal_student_info">Student Name</div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Marks Awarded *</label>
                            <div class="input-group">
                                <input type="number" step="0.5" name="marks_obtained" id="modal_marks_input" class="form-control" required min="0">
                                <span class="input-group-text bg-light" id="modal_max_display">/ 50</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Submission Status *</label>
                            <select name="status" id="modal_status_select" class="form-select" required>
                                <option value="Graded">Graded (Accepted)</option>
                                <option value="Resubmission">Request Resubmission</option>
                                <option value="Late">Graded (Late Penalty)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Faculty Feedback &amp; Suggestions</label>
                        <textarea name="feedback" id="modal_feedback_input" class="form-control" rows="3" placeholder="Provide constructive criticism, praise strong architecture, or specify revisions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Submit Evaluation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openGradeModal(submission, assignment) {
    document.getElementById('modal_sub_id').value = submission.id;
    document.getElementById('modal_student_info').textContent = submission.first_name + ' ' + submission.last_name + ' (' + submission.enrollment_no + ')';
    document.getElementById('modal_max_marks').value = assignment.max_marks;
    document.getElementById('modal_max_display').textContent = '/ ' + assignment.max_marks;
    document.getElementById('modal_marks_input').max = assignment.max_marks;
    document.getElementById('modal_marks_input').value = (submission.marks_obtained !== null) ? submission.marks_obtained : '';
    document.getElementById('modal_feedback_input').value = submission.feedback || '';
    document.getElementById('modal_status_select').value = (submission.status === 'Submitted') ? 'Graded' : submission.status;

    new bootstrap.Modal(document.getElementById('gradeModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
