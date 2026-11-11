<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Student Attendance History & Subject Analytics
 * BCA 5th Semester Major Project 1
 */

$page_title = 'Attendance Record';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('student');

$studentId = $currentUser['student_id'] ?? 0;
$courseId  = $currentUser['course_id'] ?? 0;
$semester  = $currentUser['current_semester'] ?? 1;

// 1. Overall Metrics
$overallStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_lectures,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count
    FROM attendance
    WHERE student_id = ?
");
$overallStmt->execute([$studentId]);
$overall = $overallStmt->fetch();
$totalLectures = (int)($overall['total_lectures'] ?? 0);
$presentCount  = (int)($overall['present_count'] ?? 0);
$absentCount   = (int)($overall['absent_count'] ?? 0);
$lateCount     = (int)($overall['late_count'] ?? 0);
$overallPct    = calculate_attendance_percentage($presentCount, $totalLectures);

// 2. Subject-wise Attendance Breakdown
$subBreakdownStmt = $pdo->prepare("
    SELECT s.id, s.subject_name, s.subject_code, s.credits, s.type,
           COUNT(a.id) as total_delivered,
           SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as attended,
           SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as missed,
           SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late
    FROM subjects s
    LEFT JOIN attendance a ON s.id = a.subject_id AND a.student_id = ?
    WHERE s.course_id = ? AND s.semester = ?
    GROUP BY s.id
    ORDER BY s.subject_name ASC
");
$subBreakdownStmt->execute([$studentId, $courseId, $semester]);
$subjectBreakdown = $subBreakdownStmt->fetchAll();

// 3. Complete Date-wise Attendance Logs with Filter
$subject_filter = (int)($_GET['subject_id'] ?? 0);
$status_filter = sanitize($_GET['status'] ?? '');

$logQuery = "
    SELECT a.*, s.subject_name, s.subject_code,
           f.first_name as fac_first, f.last_name as fac_last
    FROM attendance a
    JOIN subjects s ON a.subject_id = s.id
    JOIN faculty f ON a.faculty_id = f.id
    WHERE a.student_id = ?
";
$params = [$studentId];

if ($subject_filter > 0) {
    $logQuery .= " AND a.subject_id = ?";
    $params[] = $subject_filter;
}
if (!empty($status_filter)) {
    $logQuery .= " AND a.status = ?";
    $params[] = $status_filter;
}

$logQuery .= " ORDER BY a.attendance_date DESC, a.id DESC";
$logStmt = $pdo->prepare($logQuery);
$logStmt->execute($params);
$attendanceLogs = $logStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">My Attendance &amp; Analytics</h3>
                <p class="text-muted small mb-0">Track real-time subject-wise percentage and monitor examination threshold (75%)</p>
            </div>
            <button class="btn btn-outline-secondary rounded-pill px-3" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print Attendance Report
            </button>
        </div>

        <?php display_flash(); ?>

        <!-- KPI Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Aggregate Attendance</div>
                    <div class="stat-value <?= ($overallPct >= 75) ? 'text-success' : 'text-danger' ?> mt-1">
                        <?= $overallPct ?>%
                    </div>
                    <small class="text-muted"><?= ($overallPct >= 75) ? 'Eligible for Exams' : 'Shortage Warning (<75%)' ?></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Lectures Attended</div>
                    <div class="stat-value text-primary mt-1"><?= $presentCount ?></div>
                    <small class="text-muted">Out of <?= $totalLectures ?> total conducted</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Absences</div>
                    <div class="stat-value text-danger mt-1"><?= $absentCount ?></div>
                    <small class="text-muted">Missed class sessions</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Late Admissions</div>
                    <div class="stat-value text-warning mt-1"><?= $lateCount ?></div>
                    <small class="text-muted">Admitted with delay</small>
                </div>
            </div>
        </div>

        <!-- Subject-wise Compliance Table -->
        <div class="app-card mb-4">
            <div class="app-card-header">
                <h6 class="app-card-title">
                    <i class="bi bi-bar-chart-line-fill text-primary"></i> Subject-Wise Attendance Breakdown
                </h6>
                <span class="badge bg-light text-dark border">Semester <?= e($semester) ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Delivered</th>
                            <th>Attended</th>
                            <th>Missed</th>
                            <th>Attendance %</th>
                            <th>Progress Bar</th>
                            <th class="text-end">Exam Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjectBreakdown as $sb): ?>
                            <?php 
                                $pct = calculate_attendance_percentage($sb['attended'], $sb['total_delivered']);
                                $isSafe = ($pct >= 75 || $sb['total_delivered'] == 0);
                            ?>
                            <tr>
                                <td class="font-monospace fw-bold text-dark"><?= e($sb['subject_code']) ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($sb['subject_name']) ?></div>
                                    <small class="text-muted"><?= e($sb['credits']) ?> Credits &bull; <?= e($sb['type']) ?></small>
                                </td>
                                <td><?= $sb['total_delivered'] ?></td>
                                <td class="text-success fw-bold"><?= $sb['attended'] ?></td>
                                <td class="text-danger fw-bold"><?= $sb['missed'] ?></td>
                                <td>
                                    <strong class="<?= $isSafe ? 'text-success' : 'text-danger' ?>"><?= $pct ?>%</strong>
                                </td>
                                <td style="min-width: 130px;">
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar <?= $isSafe ? 'bg-success' : 'bg-danger' ?>" role="progressbar" style="width: <?= $pct ?>%"></div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <?php if ($sb['total_delivered'] == 0): ?>
                                        <span class="badge bg-secondary-subtle text-secondary">No classes yet</span>
                                    <?php elseif ($pct >= 75): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle me-1"></i> Eligible
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="bi bi-exclamation-triangle me-1"></i> Shortage
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Filter & Date-wise Log History -->
        <div class="app-card">
            <div class="app-card-header">
                <h6 class="app-card-title"><i class="bi bi-calendar3 text-teal"></i> Detailed Lecture Attendance Log</h6>
                <form method="GET" action="attendance.php" class="d-flex gap-2">
                    <select name="subject_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Subjects --</option>
                        <?php foreach ($subjectBreakdown as $sb): ?>
                            <option value="<?= $sb['id'] ?>" <?= ($subject_filter == $sb['id']) ? 'selected' : '' ?>>
                                <?= e($sb['subject_code']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Status --</option>
                        <option value="Present" <?= ($status_filter === 'Present') ? 'selected' : '' ?>>Present</option>
                        <option value="Absent" <?= ($status_filter === 'Absent') ? 'selected' : '' ?>>Absent</option>
                        <option value="Late" <?= ($status_filter === 'Late') ? 'selected' : '' ?>>Late</option>
                    </select>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Subject</th>
                            <th>Faculty Instructor</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendanceLogs)): ?>
                            <tr class="no-records-row">
                                <td colspan="6" class="text-center py-5 text-muted">No attendance entries recorded for this filter.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendanceLogs as $log): ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?= format_date($log['attendance_date']) ?></td>
                                    <td class="text-muted small"><?= date('l', strtotime($log['attendance_date'])) ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($log['subject_name']) ?></div>
                                        <small class="text-muted font-monospace"><?= e($log['subject_code']) ?></small>
                                    </td>
                                    <td>
                                        <div class="small">Prof. <?= e($log['fac_first'] . ' ' . $log['fac_last']) ?></div>
                                    </td>
                                    <td><?= get_status_badge($log['status']) ?></td>
                                    <td>
                                        <small class="text-muted"><?= !empty($log['remarks']) ? e($log['remarks']) : '-' ?></small>
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
