<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Centralized Attendance Reports & Auditing (Admin)
 * BCA 5th Semester Major Project 1
 */

$page_title = 'Attendance Reports';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

// Fetch Courses and Subjects for filter
$courses = $pdo->query("SELECT id, course_name, course_code FROM courses ORDER BY course_name ASC")->fetchAll();
$subjects = $pdo->query("
    SELECT s.id, s.subject_name, s.subject_code, c.course_code, s.semester
    FROM subjects s
    JOIN courses c ON s.course_id = c.id
    ORDER BY c.course_code, s.semester, s.subject_name
")->fetchAll();

// Filter parameters
$course_id  = (int)($_GET['course_id'] ?? 0);
$subject_id = (int)($_GET['subject_id'] ?? 0);
$status     = sanitize($_GET['status'] ?? '');
$from_date  = sanitize($_GET['from_date'] ?? '');
$to_date    = sanitize($_GET['to_date'] ?? '');

$query = "
    SELECT a.*, s.first_name, s.last_name, s.enrollment_no, s.current_semester,
           c.course_name, c.course_code,
           sub.subject_name, sub.subject_code,
           f.first_name as fac_first, f.last_name as fac_last
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN courses c ON s.course_id = c.id
    JOIN subjects sub ON a.subject_id = sub.id
    JOIN faculty f ON a.faculty_id = f.id
    WHERE 1=1
";
$params = [];

if ($course_id > 0) {
    $query .= " AND s.course_id = ?";
    $params[] = $course_id;
}
if ($subject_id > 0) {
    $query .= " AND a.subject_id = ?";
    $params[] = $subject_id;
}
if (!empty($status)) {
    $query .= " AND a.status = ?";
    $params[] = $status;
}
if (!empty($from_date)) {
    $query .= " AND a.attendance_date >= ?";
    $params[] = $from_date;
}
if (!empty($to_date)) {
    $query .= " AND a.attendance_date <= ?";
    $params[] = $to_date;
}

$query .= " ORDER BY a.attendance_date DESC, a.id DESC LIMIT 300";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$attendanceLogs = $stmt->fetchAll();

// Calculate metrics for current filtered selection
$totalCount = count($attendanceLogs);
$presentCount = 0;
$absentCount = 0;
$lateCount = 0;
foreach ($attendanceLogs as $log) {
    if ($log['status'] === 'Present') $presentCount++;
    elseif ($log['status'] === 'Absent') $absentCount++;
    elseif ($log['status'] === 'Late') $lateCount++;
}
$avgPct = calculate_attendance_percentage($presentCount, $totalCount);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Campus Attendance Audit</h3>
                <p class="text-muted small mb-0">Monitor institutional lecture attendance compliance &amp; student engagement</p>
            </div>
            <button class="btn btn-outline-secondary rounded-pill px-3" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print Report
            </button>
        </div>

        <?php display_flash(); ?>

        <!-- KPI Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Total Logs Analyzed</div>
                    <div class="stat-value text-primary mt-1"><?= $totalCount ?></div>
                    <small class="text-muted">Student-lecture entries</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Overall Present Rate</div>
                    <div class="stat-value text-success mt-1"><?= $avgPct ?>%</div>
                    <small class="text-muted"><?= $presentCount ?> attended lectures</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Absences Count</div>
                    <div class="stat-value text-danger mt-1"><?= $absentCount ?></div>
                    <small class="text-muted">Recorded absent entries</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Late Arrivals</div>
                    <div class="stat-value text-warning mt-1"><?= $lateCount ?></div>
                    <small class="text-muted">Admitted with delay</small>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="app-card p-3 mb-4">
            <form method="GET" action="attendance.php" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Program / Degree</label>
                    <select name="course_id" class="form-select form-select-sm">
                        <option value="">-- All Programs --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($course_id == $c['id']) ? 'selected' : '' ?>>
                                <?= e($c['course_code']) ?> - <?= e($c['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Subject / Module</label>
                    <select name="subject_id" class="form-select form-select-sm">
                        <option value="">-- All Subjects --</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($subject_id == $s['id']) ? 'selected' : '' ?>>
                                [<?= e($s['course_code']) ?> S<?= $s['semester'] ?>] <?= e($s['subject_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- All Statuses --</option>
                        <option value="Present" <?= ($status === 'Present') ? 'selected' : '' ?>>Present</option>
                        <option value="Absent" <?= ($status === 'Absent') ? 'selected' : '' ?>>Absent</option>
                        <option value="Late" <?= ($status === 'Late') ? 'selected' : '' ?>>Late</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="<?= e($from_date) ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="attendance.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        <!-- Attendance Logs Table -->
        <div class="app-card">
            <div class="app-card-header">
                <h6 class="app-card-title"><i class="bi bi-table text-primary"></i> Attendance Records</h6>
                <input type="text" class="form-control form-control-sm w-auto" placeholder="Search in logs..." data-table-search="attendanceLogTable">
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle" id="attendanceLogTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Program &amp; Sem</th>
                            <th>Subject</th>
                            <th>Instructor</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendanceLogs)): ?>
                            <tr class="no-records-row">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x display-5 d-block mb-2 opacity-50"></i>
                                    No attendance entries matching the selected criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendanceLogs as $log): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= format_date($log['attendance_date']) ?></div>
                                        <small class="text-muted"><?= date('l', strtotime($log['attendance_date'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($log['first_name'] . ' ' . $log['last_name']) ?></div>
                                        <small class="text-muted font-monospace"><?= e($log['enrollment_no']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= e($log['course_code']) ?></span>
                                        <small class="text-muted d-block">Sem <?= e($log['current_semester']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($log['subject_name']) ?></div>
                                        <small class="text-muted font-monospace"><?= e($log['subject_code']) ?></small>
                                    </td>
                                    <td>
                                        <div class="small text-dark"><?= e($log['fac_first'] . ' ' . $log['fac_last']) ?></div>
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
