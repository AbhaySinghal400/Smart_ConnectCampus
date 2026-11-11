<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Mark and Update Attendance (Faculty)
 * BCA 5th Semester Major Project 1
 */

$page_title = 'Mark Attendance';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('faculty');

$facultyId = $currentUser['faculty_id'] ?? 0;

// Fetch faculty's assigned subjects
$subjectsStmt = $pdo->prepare("
    SELECT s.id, s.subject_name, s.subject_code, s.semester, s.course_id,
           c.course_name, c.course_code
    FROM faculty_subjects fs
    JOIN subjects s ON fs.subject_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE fs.faculty_id = ?
    ORDER BY c.course_code, s.semester, s.subject_name
");
$subjectsStmt->execute([$facultyId]);
$mySubjects = $subjectsStmt->fetchAll();

// Selected parameters
$selected_subject_id = (int)($_GET['subject_id'] ?? ($mySubjects[0]['id'] ?? 0));
$attendance_date = sanitize($_GET['date'] ?? date('Y-m-d'));

// Find subject details
$currentSubject = null;
foreach ($mySubjects as $sub) {
    if ($sub['id'] == $selected_subject_id) {
        $currentSubject = $sub;
        break;
    }
}

// Handle Form Submission (Save or Update Attendance)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_attendance') {
    if (verify_csrf()) {
        $post_subject_id = (int)($_POST['subject_id'] ?? 0);
        $post_date       = sanitize($_POST['attendance_date'] ?? date('Y-m-d'));
        $attendance_data = $_POST['attendance'] ?? []; // student_id => status
        $remarks_data    = $_POST['remarks'] ?? [];    // student_id => remarks

        if ($post_subject_id > 0 && !empty($post_date) && !empty($attendance_data)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO attendance (student_id, subject_id, faculty_id, attendance_date, status, remarks)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        status = VALUES(status),
                        remarks = VALUES(remarks),
                        faculty_id = VALUES(faculty_id)
                ");

                foreach ($attendance_data as $studentId => $status) {
                    $studentId = (int)$studentId;
                    $status = in_array($status, ['Present', 'Absent', 'Late']) ? $status : 'Present';
                    $remark = sanitize($remarks_data[$studentId] ?? '');

                    $stmt->execute([$studentId, $post_subject_id, $facultyId, $post_date, $status, $remark]);
                }

                $pdo->commit();
                set_flash('success', "Attendance for " . format_date($post_date) . " saved successfully!");
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                set_flash('danger', 'Error saving attendance: ' . $e->getMessage());
            }
        }
        header("Location: " . BASE_URL . "faculty/attendance.php?subject_id={$post_subject_id}&date={$post_date}");
        exit;
    }
}

// Fetch enrolled students for this subject's course & semester
$students = [];
$existingAttendance = [];

if ($currentSubject) {
    $studentsStmt = $pdo->prepare("
        SELECT s.id, s.enrollment_no, s.first_name, s.last_name, s.email, s.gender
        FROM students s
        WHERE s.course_id = ? AND s.current_semester = ?
        ORDER BY s.enrollment_no ASC
    ");
    $studentsStmt->execute([$currentSubject['course_id'], $currentSubject['semester']]);
    $students = $studentsStmt->fetchAll();

    // Check if attendance is already recorded for this date
    $attStmt = $pdo->prepare("
        SELECT student_id, status, remarks
        FROM attendance
        WHERE subject_id = ? AND attendance_date = ?
    ");
    $attStmt->execute([$selected_subject_id, $attendance_date]);
    $existingRows = $attStmt->fetchAll();
    foreach ($existingRows as $row) {
        $existingAttendance[$row['student_id']] = [
            'status' => $row['status'],
            'remarks' => $row['remarks']
        ];
    }
}

// Recent attendance lecture dates for this subject
$historyStmt = $pdo->prepare("
    SELECT attendance_date,
           COUNT(*) as total_students,
           SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
           SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
           SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count
    FROM attendance
    WHERE subject_id = ? AND faculty_id = ?
    GROUP BY attendance_date
    ORDER BY attendance_date DESC
    LIMIT 6
");
$historyStmt->execute([$selected_subject_id, $facultyId]);
$recentLectures = $historyStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Class Attendance Register</h3>
                <p class="text-muted small mb-0">Record lecture attendance, mark student participation, and update records</p>
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- Subject & Date Selection Bar -->
        <div class="app-card p-3 mb-4">
            <form method="GET" action="attendance.php" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted">Teaching Subject *</label>
                    <select name="subject_id" class="form-select" onchange="this.form.submit()">
                        <?php if (empty($mySubjects)): ?>
                            <option value="">No subjects assigned</option>
                        <?php else: ?>
                            <?php foreach ($mySubjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>" <?= ($selected_subject_id == $sub['id']) ? 'selected' : '' ?>>
                                    [<?= e($sub['course_code']) ?> Sem <?= $sub['semester'] ?>] <?= e($sub['subject_code']) ?> - <?= e($sub['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Attendance Date *</label>
                    <input type="date" name="date" class="form-control" value="<?= e($attendance_date) ?>" max="<?= date('Y-m-d') ?>" onchange="this.form.submit()">
                </div>
                <div class="col-md-4 text-md-end">
                    <?php if (!empty($existingAttendance)): ?>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle p-2">
                            <i class="bi bi-pencil-fill me-1"></i> Modifying Saved Attendance (<?= count($existingAttendance) ?> entries)
                        </span>
                    <?php else: ?>
                        <span class="badge bg-info-subtle text-info border border-info-subtle p-2">
                            <i class="bi bi-clock-history me-1"></i> New Attendance Sheet
                        </span>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if (!$currentSubject): ?>
            <div class="alert alert-warning">Please choose a valid assigned subject to mark attendance.</div>
        <?php else: ?>

            <div class="row g-4">
                <!-- Attendance Sheet Table -->
                <div class="col-lg-8">
                    <div class="app-card">
                        <div class="app-card-header">
                            <div>
                                <h6 class="app-card-title mb-1">
                                    <i class="bi bi-person-check-fill text-primary"></i> 
                                    <?= e($currentSubject['subject_name']) ?>
                                </h6>
                                <small class="text-muted">
                                    <?= e($currentSubject['course_code']) ?> Semester <?= e($currentSubject['semester']) ?> &bull; Date: <?= format_date($attendance_date) ?>
                                </small>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-success" onclick="markAll('Present')">
                                    <i class="bi bi-check-all me-1"></i> All Present
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="markAll('Absent')">
                                    <i class="bi bi-x me-1"></i> All Absent
                                </button>
                            </div>
                        </div>

                        <?php if (empty($students)): ?>
                            <div class="p-5 text-center text-muted">
                                <i class="bi bi-people display-4 d-block mb-2 opacity-50"></i>
                                No students currently registered in <?= e($currentSubject['course_code']) ?> Semester <?= e($currentSubject['semester']) ?>.
                            </div>
                        <?php else: ?>
                            <form method="POST" action="attendance.php">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="save_attendance">
                                <input type="hidden" name="subject_id" value="<?= $selected_subject_id ?>">
                                <input type="hidden" name="attendance_date" value="<?= e($attendance_date) ?>">

                                <div class="table-responsive">
                                    <table class="table table-custom align-middle">
                                        <thead>
                                            <tr>
                                                <th style="width: 140px;">Roll / Enrolment</th>
                                                <th>Student Name</th>
                                                <th class="text-center" style="width: 250px;">Status</th>
                                                <th>Remarks / Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $st): ?>
                                                <?php 
                                                    $currentStatus = $existingAttendance[$st['id']]['status'] ?? 'Present';
                                                    $currentRemark = $existingAttendance[$st['id']]['remarks'] ?? '';
                                                ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-light text-dark border font-monospace">
                                                            <?= e($st['enrollment_no']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-dark"><?= e($st['first_name'] . ' ' . $st['last_name']) ?></div>
                                                        <small class="text-muted"><?= e($st['email']) ?></small>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="btn-group" role="group">
                                                            <input type="radio" class="btn-check status-radio" name="attendance[<?= $st['id'] ?>]" id="pres_<?= $st['id'] ?>" value="Present" <?= ($currentStatus === 'Present') ? 'checked' : '' ?>>
                                                            <label class="btn btn-sm btn-outline-success" for="pres_<?= $st['id'] ?>">Present</label>

                                                            <input type="radio" class="btn-check status-radio" name="attendance[<?= $st['id'] ?>]" id="abs_<?= $st['id'] ?>" value="Absent" <?= ($currentStatus === 'Absent') ? 'checked' : '' ?>>
                                                            <label class="btn btn-sm btn-outline-danger" for="abs_<?= $st['id'] ?>">Absent</label>

                                                            <input type="radio" class="btn-check status-radio" name="attendance[<?= $st['id'] ?>]" id="late_<?= $st['id'] ?>" value="Late" <?= ($currentStatus === 'Late') ? 'checked' : '' ?>>
                                                            <label class="btn btn-sm btn-outline-warning" for="late_<?= $st['id'] ?>">Late</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="remarks[<?= $st['id'] ?>]" class="form-control form-control-sm" placeholder="Optional notes" value="<?= e($currentRemark) ?>">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
                                    <div class="text-muted small">
                                        <i class="bi bi-info-circle me-1"></i> Total Class Strength: <strong><?= count($students) ?> students</strong>
                                    </div>
                                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                                        <i class="bi bi-check2-circle me-2"></i> Save Attendance Sheet
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Attendance Logs for this Subject -->
                <div class="col-lg-4">
                    <div class="app-card h-100">
                        <div class="app-card-header">
                            <h6 class="app-card-title">
                                <i class="bi bi-clock-history text-teal"></i> Recent Lecture Logs
                            </h6>
                        </div>
                        <div class="p-3">
                            <?php if (empty($recentLectures)): ?>
                                <div class="text-center py-4 text-muted small">
                                    No prior lectures recorded for this subject yet.
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2.5">
                                    <?php foreach ($recentLectures as $rl): ?>
                                        <?php 
                                            $rate = calculate_attendance_percentage($rl['present_count'], $rl['total_students']);
                                        ?>
                                        <div class="p-3 rounded-3 border bg-light d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold text-dark"><?= format_date($rl['attendance_date']) ?></div>
                                                <small class="text-muted"><?= date('l', strtotime($rl['attendance_date'])) ?></small>
                                                <div class="small text-muted mt-1">
                                                    <span class="text-success fw-semibold"><?= $rl['present_count'] ?> Present</span> &bull; 
                                                    <span class="text-danger fw-semibold"><?= $rl['absent_count'] ?> Absent</span>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary fs-6"><?= $rate ?>%</span>
                                                <div class="mt-2">
                                                    <a href="attendance.php?subject_id=<?= $selected_subject_id ?>&date=<?= $rl['attendance_date'] ?>" class="btn btn-sm btn-link p-0 text-primary small">
                                                        Edit Sheet &rarr;
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>

<script>
function markAll(status) {
    const radios = document.querySelectorAll('.status-radio[value="' + status + '"]');
    radios.forEach(r => {
        r.checked = true;
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
