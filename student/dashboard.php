<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Student Dashboard
 * BCA 5th Semester Major Project 1
 */

$page_title = 'Student Dashboard';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('student');

$studentId = $currentUser['student_id'] ?? 0;
$courseId = $currentUser['course_id'] ?? 0;
$semester = $currentUser['current_semester'] ?? 1;
$todayDay = date('l');

try {
    // 1. Overall Attendance Calculation
    $attStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_lectures,
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count
        FROM attendance
        WHERE student_id = ?
    ");
    $attStmt->execute([$studentId]);
    $attStats = $attStmt->fetch();
    $totalLectures = (int)($attStats['total_lectures'] ?? 0);
    $presentCount = (int)($attStats['present_count'] ?? 0);
    $absentCount = (int)($attStats['absent_count'] ?? 0);
    $lateCount = (int)($attStats['late_count'] ?? 0);
    $overallAttendancePct = calculate_attendance_percentage($presentCount, $totalLectures);

    // 2. Assignments Overview (Total for class, submitted, pending)
    $classAssignStmt = $pdo->prepare("
        SELECT a.id, a.title, a.due_date, a.max_marks, s.subject_name, s.subject_code,
               sub.id as submission_id, sub.status as sub_status, sub.marks_obtained
        FROM assignments a
        JOIN subjects s ON a.subject_id = s.id
        LEFT JOIN submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
        WHERE s.course_id = ? AND s.semester = ?
        ORDER BY a.due_date ASC
    ");
    $classAssignStmt->execute([$studentId, $courseId, $semester]);
    $allAssignments = $classAssignStmt->fetchAll();

    $totalAssignments = count($allAssignments);
    $submittedCount = 0;
    $pendingAssignments = [];

    foreach ($allAssignments as $asg) {
        if (!empty($asg['submission_id'])) {
            $submittedCount++;
        } else {
            $pendingAssignments[] = $asg;
        }
    }
    $pendingCount = count($pendingAssignments);

    // 3. Today's Classes from Timetable
    $todayClassesStmt = $pdo->prepare("
        SELECT t.*, s.subject_name, s.subject_code, s.type as subject_type,
               f.first_name, f.last_name
        FROM timetable t
        JOIN subjects s ON t.subject_id = s.id
        JOIN faculty f ON t.faculty_id = f.id
        WHERE t.course_id = ? AND t.semester = ? AND t.day_of_week = ?
        ORDER BY t.start_time ASC
    ");
    $todayClassesStmt->execute([$courseId, $semester, $todayDay]);
    $todayClasses = $todayClassesStmt->fetchAll();

    // 4. Latest Notices
    $noticesStmt = $pdo->prepare("
        SELECT * FROM notices 
        WHERE target_audience = 'all' OR target_audience = 'students'
        ORDER BY is_pinned DESC, created_at DESC 
        LIMIT 4
    ");
    $noticesStmt->execute();
    $latestNotices = $noticesStmt->fetchAll();

} catch (Exception $e) {
    die("Error fetching student dashboard: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <!-- Welcome Banner -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Student Academic Portal</h3>
                <p class="text-muted small mb-0">
                    Welcome, <strong><?= e($currentUser['full_name']) ?></strong> &bull; <?= e($currentUser['course_name']) ?> (Sem <?= e($currentUser['current_semester']) ?>) &bull; Enrolment: <span class="font-monospace text-dark"><?= e($currentUser['enrollment_no']) ?></span>
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL ?>student/assignments.php" class="btn btn-primary btn-sm rounded-pill px-3 py-2">
                    <i class="bi bi-upload me-1"></i> Submit Assignment
                </a>
                <a href="<?= BASE_URL ?>student/complaints.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-2">
                    <i class="bi bi-chat-left-dots me-1"></i> Lodge Complaint
                </a>
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- Attendance Warning Alert if < 75% -->
        <?php if ($totalLectures > 0 && $overallAttendancePct < 75): ?>
            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning"></i>
                <div>
                    <h6 class="fw-bold mb-0">Attendance Shortage Alert!</h6>
                    <small>Your aggregate attendance is <strong><?= $overallAttendancePct ?>%</strong>, which is below the mandatory university threshold of 75%. Please ensure regular attendance to remain eligible for examinations.</small>
                </div>
            </div>
        <?php endif; ?>

        <!-- KPI Metrics -->
        <div class="row g-3 mb-4">
            <!-- Attendance Percentage Card -->
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label">My Attendance</div>
                            <div class="stat-value <?= ($overallAttendancePct >= 75) ? 'text-success' : 'text-danger' ?> mt-1">
                                <?= $overallAttendancePct ?>%
                            </div>
                            <small class="text-muted mt-1 d-block"><i class="bi bi-check-circle me-1 text-success"></i> <?= $presentCount ?> / <?= $totalLectures ?> Attended</small>
                        </div>
                        <div class="stat-icon <?= ($overallAttendancePct >= 75) ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' ?>">
                            <i class="bi bi-pie-chart-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Assignments -->
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label">Total Assignments</div>
                            <div class="stat-value text-primary mt-1"><?= $totalAssignments ?></div>
                            <small class="text-muted mt-1 d-block"><i class="bi bi-journal-text me-1 text-primary"></i> <?= $submittedCount ?> Turned In</small>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-journal-check"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Assignments -->
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label">Pending Tasks</div>
                            <div class="stat-value <?= ($pendingCount > 0) ? 'text-warning' : 'text-success' ?> mt-1">
                                <?= $pendingCount ?>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <?= ($pendingCount > 0) ? 'Awaiting your submission' : 'All assignments caught up!' ?>
                            </small>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Schedule -->
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label">Today's Classes</div>
                            <div class="stat-value text-info mt-1"><?= count($todayClasses) ?></div>
                            <small class="text-muted mt-1 d-block"><i class="bi bi-calendar3 me-1 text-info"></i> <?= $todayDay ?> Routine</small>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Today's Class Routine -->
            <div class="col-lg-5">
                <div class="app-card h-100">
                    <div class="app-card-header">
                        <h6 class="app-card-title">
                            <i class="bi bi-calendar2-day text-primary"></i> Today's Lectures (<?= $todayDay ?>)
                        </h6>
                        <a href="<?= BASE_URL ?>student/timetable.php" class="text-decoration-none small fw-semibold text-primary">Full Schedule &rarr;</a>
                    </div>
                    <div class="p-3">
                        <?php if (empty($todayClasses)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-cup-hot display-5 text-muted opacity-50 d-block mb-2"></i>
                                No scheduled lectures today. You can focus on self-study and assignments!
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($todayClasses as $tc): ?>
                                    <div class="p-3 rounded-3 border bg-light d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="badge bg-primary text-white font-monospace"><?= e($tc['subject_code']) ?></span>
                                                <span class="badge <?= ($tc['subject_type'] === 'Practical') ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary' ?>">
                                                    <?= e($tc['subject_type']) ?>
                                                </span>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1"><?= e($tc['subject_name']) ?></h6>
                                            <small class="text-muted">
                                                <i class="bi bi-person me-1"></i> Prof. <?= e($tc['first_name'] . ' ' . $tc['last_name']) ?> &bull; 
                                                <i class="bi bi-geo-alt me-1"></i> <?= e($tc['room_no']) ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-white text-dark border px-2 py-1 font-monospace">
                                                <?= format_time($tc['start_time']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Pending Assignments Awaiting Action -->
            <div class="col-lg-7">
                <div class="app-card h-100">
                    <div class="app-card-header">
                        <h6 class="app-card-title">
                            <i class="bi bi-journal-arrow-up text-warning"></i> Action Required: Pending Assignments
                        </h6>
                        <a href="<?= BASE_URL ?>student/assignments.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Assignment</th>
                                    <th>Due Date</th>
                                    <th>Max Marks</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pendingAssignments)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="bi bi-check-circle-fill text-success fs-4 d-block mb-1"></i>
                                            Awesome! You have submitted all active assignments.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pendingAssignments as $pa): ?>
                                        <?php $isOverdue = (strtotime($pa['due_date']) < time()); ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-light text-dark border font-monospace"><?= e($pa['subject_code']) ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= e($pa['title']) ?></div>
                                            </td>
                                            <td>
                                                <small class="fw-semibold <?= $isOverdue ? 'text-danger' : 'text-dark' ?>">
                                                    <?= format_datetime($pa['due_date']) ?>
                                                </small>
                                                <?php if ($isOverdue): ?>
                                                    <span class="badge bg-danger-subtle text-danger d-block">Overdue</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="fw-bold"><?= e($pa['max_marks']) ?> Pts</span>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>student/assignments.php" class="btn btn-sm btn-primary rounded-pill px-3">
                                                    Submit &rarr;
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

        <!-- Latest Campus Announcements -->
        <div class="app-card">
            <div class="app-card-header">
                <h6 class="app-card-title">
                    <i class="bi bi-megaphone-fill text-info"></i> Latest Campus Notices &amp; Circulars
                </h6>
                <a href="<?= BASE_URL ?>student/notices.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Notice Board</a>
            </div>
            <div class="row g-0">
                <?php if (empty($latestNotices)): ?>
                    <div class="p-4 text-center text-muted col-12">No recent announcements posted.</div>
                <?php else: ?>
                    <?php foreach ($latestNotices as $n): ?>
                        <div class="col-md-6 border-bottom border-end p-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle small"><?= e($n['category']) ?></span>
                                <small class="text-muted"><?= time_ago($n['created_at']) ?></small>
                            </div>
                            <h6 class="fw-bold text-dark mb-1"><?= e($n['title']) ?></h6>
                            <p class="text-muted small mb-2 text-truncate"><?= e($n['content']) ?></p>
                            <?php if (!empty($n['attachment_path'])): ?>
                                <a href="<?= BASE_URL ?>uploads/notices/<?= e($n['attachment_path']) ?>" target="_blank" class="btn btn-sm btn-light border py-0 px-2 small">
                                    <i class="bi bi-paperclip me-1 text-primary"></i> Attachment
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
