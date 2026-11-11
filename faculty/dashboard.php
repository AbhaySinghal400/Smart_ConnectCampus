<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Faculty Dashboard
 * BCA 5th Semester Major Project 1
 */

$page_title = 'Faculty Dashboard';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('faculty');

$facultyId = $currentUser['faculty_id'] ?? 0;
$todayDay = date('l'); // e.g. Monday, Tuesday...
$todayDate = date('Y-m-d');

try {
    // 1. Assigned Subjects
    $assignedStmt = $pdo->prepare("
        SELECT fs.*, s.id as subject_id, s.subject_name, s.subject_code, s.semester, s.type,
               c.course_name, c.course_code,
               (SELECT COUNT(*) FROM students st WHERE st.course_id = s.course_id AND st.current_semester = s.semester) as student_count
        FROM faculty_subjects fs
        JOIN subjects s ON fs.subject_id = s.id
        JOIN courses c ON s.course_id = c.id
        WHERE fs.faculty_id = ?
        ORDER BY c.course_code, s.semester
    ");
    $assignedStmt->execute([$facultyId]);
    $assignedSubjects = $assignedStmt->fetchAll();
    $totalAssignedCount = count($assignedSubjects);

    // 2. Total Students Under Instruction
    $totalStudentsEnrolled = 0;
    foreach ($assignedSubjects as $as) {
        $totalStudentsEnrolled += (int)$as['student_count'];
    }

    // 3. Today's Classes from Timetable
    $todayClassesStmt = $pdo->prepare("
        SELECT t.*, s.subject_name, s.subject_code, c.course_code
        FROM timetable t
        JOIN subjects s ON t.subject_id = s.id
        JOIN courses c ON t.course_id = c.id
        WHERE t.faculty_id = ? AND t.day_of_week = ?
        ORDER BY t.start_time ASC
    ");
    $todayClassesStmt->execute([$facultyId, $todayDay]);
    $todayClasses = $todayClassesStmt->fetchAll();

    // 4. Assignments Created by this Faculty
    $assignStmt = $pdo->prepare("
        SELECT a.*, s.subject_name, s.subject_code,
               (SELECT COUNT(*) FROM submissions sub WHERE sub.assignment_id = a.id) as submission_count,
               (SELECT COUNT(*) FROM submissions sub WHERE sub.assignment_id = a.id AND sub.status = 'Submitted') as pending_grading_count
        FROM assignments a
        JOIN subjects s ON a.subject_id = s.id
        WHERE a.faculty_id = ?
        ORDER BY a.created_at DESC
    ");
    $assignStmt->execute([$facultyId]);
    $myAssignments = $assignStmt->fetchAll();
    $totalAssignments = count($myAssignments);

    $pendingGradingTotal = 0;
    foreach ($myAssignments as $ma) {
        $pendingGradingTotal += (int)$ma['pending_grading_count'];
    }

    // 5. Recent Submissions
    $recentSubmissionsStmt = $pdo->prepare("
        SELECT sub.*, a.title as assignment_title, a.max_marks,
               s.first_name, s.last_name, s.enrollment_no
        FROM submissions sub
        JOIN assignments a ON sub.assignment_id = a.id
        JOIN students s ON sub.student_id = s.id
        WHERE a.faculty_id = ?
        ORDER BY sub.submission_date DESC
        LIMIT 5
    ");
    $recentSubmissionsStmt->execute([$facultyId]);
    $recentSubmissions = $recentSubmissionsStmt->fetchAll();

} catch (Exception $e) {
    die("Error fetching faculty dashboard: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <!-- Welcome Banner -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Faculty Academic Workspace</h3>
                <p class="text-muted small mb-0">
                    Welcome, <strong><?= e($currentUser['full_name']) ?></strong> &bull; <?= e($currentUser['designation']) ?> (<?= e($currentUser['dept_name']) ?>)
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL ?>faculty/attendance.php" class="btn btn-primary btn-sm rounded-pill px-3 py-2">
                    <i class="bi bi-calendar-check me-1"></i> Mark Today's Attendance
                </a>
                <a href="<?= BASE_URL ?>faculty/assignments.php" class="btn btn-teal btn-sm rounded-pill px-3 py-2">
                    <i class="bi bi-plus-circle me-1"></i> Create Assignment
                </a>
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- KPI Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label">Assigned Subjects</div>
                            <div class="stat-value text-primary mt-1"><?= $totalAssignedCount ?></div>
                            <small class="text-muted mt-1 d-block"><i class="bi bi-book me-1 text-primary"></i> Current Semester</small>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label">Today's Classes</div>
                            <div class="stat-value text-info mt-1"><?= count($todayClasses) ?></div>
                            <small class="text-muted mt-1 d-block"><i class="bi bi-clock me-1 text-info"></i> <?= $todayDay ?> Schedule</small>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label">Assignments Posted</div>
                            <div class="stat-value text-success mt-1"><?= $totalAssignments ?></div>
                            <small class="text-muted mt-1 d-block"><i class="bi bi-file-earmark-check me-1 text-success"></i> Tasks & Projects</small>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label">Pending Grading</div>
                            <div class="stat-value text-warning mt-1"><?= $pendingGradingTotal ?></div>
                            <small class="text-warning mt-1 d-block fw-semibold"><i class="bi bi-hourglass-split me-1"></i> Awaiting marks & review</small>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-cloud-arrow-down-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Today's Lecture Schedule -->
            <div class="col-lg-5">
                <div class="app-card h-100">
                    <div class="app-card-header">
                        <h6 class="app-card-title">
                            <i class="bi bi-calendar-day text-primary"></i> Today's Class Routine (<?= $todayDay ?>)
                        </h6>
                    </div>
                    <div class="p-3">
                        <?php if (empty($todayClasses)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-cup-hot display-5 text-muted opacity-50 d-block mb-2"></i>
                                No lectures scheduled for today. Enjoy your research and grading day!
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($todayClasses as $tc): ?>
                                    <div class="p-3 rounded-3 border bg-light d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="badge bg-primary text-white font-monospace"><?= e($tc['subject_code']) ?></span>
                                                <span class="badge bg-secondary"><?= e($tc['course_code']) ?> Sem <?= e($tc['semester']) ?></span>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1"><?= e($tc['subject_name']) ?></h6>
                                            <small class="text-muted"><i class="bi bi-geo-alt me-1"></i> <?= e($tc['room_no']) ?></small>
                                        </div>
                                        <div class="text-end">
                                            <div class="badge bg-white text-dark border px-2 py-1 font-monospace">
                                                <?= format_time($tc['start_time']) ?> - <?= format_time($tc['end_time']) ?>
                                            </div>
                                            <div class="mt-2">
                                                <a href="<?= BASE_URL ?>faculty/attendance.php?subject_id=<?= $tc['subject_id'] ?>" class="btn btn-sm btn-primary py-1 px-2.5 rounded-pill small">
                                                    Mark Attendance &rarr;
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

            <!-- Assigned Teaching Subjects -->
            <div class="col-lg-7">
                <div class="app-card h-100">
                    <div class="app-card-header">
                        <h6 class="app-card-title">
                            <i class="bi bi-book-half text-teal"></i> My Assigned Teaching Courses
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>Course &amp; Sem</th>
                                    <th>Subject Details</th>
                                    <th>Type</th>
                                    <th>Students</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($assignedSubjects)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No subjects currently assigned by the administrator.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($assignedSubjects as $as): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= e($as['course_code']) ?> Sem <?= e($as['semester']) ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= e($as['subject_name']) ?></div>
                                                <small class="text-muted font-monospace"><?= e($as['subject_code']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?= ($as['type'] === 'Practical') ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary' ?>">
                                                    <?= e($as['type']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?= $as['student_count'] ?> Enrolled</div>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>faculty/attendance.php?subject_id=<?= $as['subject_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5">
                                                    <i class="bi bi-calendar-check me-1"></i> Attendance
                                                </a>
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

        <!-- Recent Submissions to Review -->
        <div class="app-card">
            <div class="app-card-header">
                <h6 class="app-card-title">
                    <i class="bi bi-cloud-arrow-down text-warning"></i> Recent Student Assignment Submissions
                </h6>
                <a href="<?= BASE_URL ?>faculty/submissions.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">View All Submissions</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Assignment Title</th>
                            <th>Submitted On</th>
                            <th>File Attached</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentSubmissions)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No submissions logged for your assignments yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentSubmissions as $sub): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($sub['first_name'] . ' ' . $sub['last_name']) ?></div>
                                        <small class="text-muted font-monospace"><?= e($sub['enrollment_no']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($sub['assignment_title']) ?></div>
                                        <small class="text-muted">Max Marks: <?= e($sub['max_marks']) ?></small>
                                    </td>
                                    <td>
                                        <div class="small"><?= format_date($sub['submission_date']) ?></div>
                                        <small class="text-muted"><?= format_time($sub['submission_date']) ?></small>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>uploads/assignments/<?= e($sub['file_path']) ?>" target="_blank" class="btn btn-sm btn-light border px-2 py-1">
                                            <i class="bi bi-download me-1"></i> Download
                                        </a>
                                    </td>
                                    <td><?= get_status_badge($sub['status']) ?></td>
                                    <td>
                                        <?php if ($sub['marks_obtained'] !== null): ?>
                                            <span class="fw-bold text-success"><?= e($sub['marks_obtained']) ?> / <?= e($sub['max_marks']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Ungraded</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>faculty/submissions.php?assignment_id=<?= $sub['assignment_id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                            Evaluate
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
