<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Administrator Dashboard
 * BCA 5th Semester Major Project 1
 */

$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce admin role
require_role('admin');

// Fetch Live Real Database Statistics
try {
    // 1. Total Students
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();

    // 2. Total Faculty
    $totalFaculty = $pdo->query("SELECT COUNT(*) FROM faculty")->fetchColumn();

    // 3. Total Courses & Departments
    $totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    $totalDepts = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();

    // 4. Complaints Breakdown
    $totalComplaints = $pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
    $pendingComplaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Pending'")->fetchColumn();
    $inProgressComplaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'In Progress'")->fetchColumn();
    $resolvedComplaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetchColumn();

    // 5. Total Attendance Breakdown
    $totalAttendanceCount = $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
    $presentCount = $pdo->query("SELECT COUNT(*) FROM attendance WHERE status = 'Present'")->fetchColumn();
    $absentCount = $pdo->query("SELECT COUNT(*) FROM attendance WHERE status = 'Absent'")->fetchColumn();
    $lateCount = $pdo->query("SELECT COUNT(*) FROM attendance WHERE status = 'Late'")->fetchColumn();
    $overallAttendancePct = calculate_attendance_percentage($presentCount, $totalAttendanceCount);

    // 6. Recent Complaints
    $recentComplaintsStmt = $pdo->query("
        SELECT c.*, s.first_name, s.last_name, s.enrollment_no, cr.course_code
        FROM complaints c
        JOIN students s ON c.student_id = s.id
        JOIN courses cr ON s.course_id = cr.id
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $recentComplaints = $recentComplaintsStmt->fetchAll();

    // 7. Latest Notices
    $latestNoticesStmt = $pdo->query("
        SELECT n.*, u.username as posted_by
        FROM notices n
        JOIN users u ON n.posted_by_user_id = u.id
        ORDER BY n.is_pinned DESC, n.created_at DESC
        LIMIT 4
    ");
    $latestNotices = $latestNoticesStmt->fetchAll();

    // 8. Recent Attendance Activity
    $recentAttendanceStmt = $pdo->query("
        SELECT a.*, s.first_name as st_first, s.last_name as st_last, s.enrollment_no,
               sub.subject_name, sub.subject_code,
               f.first_name as fac_first, f.last_name as fac_last
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        JOIN subjects sub ON a.subject_id = sub.id
        JOIN faculty f ON a.faculty_id = f.id
        ORDER BY a.attendance_date DESC, a.created_at DESC
        LIMIT 5
    ");
    $recentAttendance = $recentAttendanceStmt->fetchAll();

} catch (Exception $e) {
    die("Error fetching dashboard analytics: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <!-- Welcome Banner & Quick Action Buttons -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Administrative Overview</h3>
                <p class="text-muted small mb-0">Welcome back, <strong><?= e($currentUser['username']) ?></strong> &bull; Centralized University Operations</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL ?>admin/students.php" class="btn btn-primary btn-sm rounded-pill px-3 py-2">
                    <i class="bi bi-person-plus me-1"></i> Add Student
                </a>
                <a href="<?= BASE_URL ?>admin/faculty.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-2">
                    <i class="bi bi-person-badge me-1"></i> Add Faculty
                </a>
                <a href="<?= BASE_URL ?>admin/notices.php" class="btn btn-teal btn-sm rounded-pill px-3 py-2">
                    <i class="bi bi-megaphone me-1"></i> Publish Notice
                </a>
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- Statistics KPI Cards -->
        <div class="row g-3 mb-4">
            <!-- Total Students -->
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label">Total Students</div>
                            <div class="stat-value text-primary mt-1"><?= number_format($totalStudents) ?></div>
                            <small class="text-muted mt-1 d-block"><i class="bi bi-mortarboard text-primary me-1"></i> Active Enrolments</small>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Faculty -->
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label">Faculty Members</div>
                            <div class="stat-value text-info mt-1"><?= number_format($totalFaculty) ?></div>
                            <small class="text-muted mt-1 d-block"><i class="bi bi-diagram-3 text-info me-1"></i> <?= $totalDepts ?> Departments</small>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-person-workspace"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Courses -->
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label">Programs / Courses</div>
                            <div class="stat-value text-success mt-1"><?= number_format($totalCourses) ?></div>
                            <small class="text-muted mt-1 d-block"><i class="bi bi-book text-success me-1"></i> Degree Curricula</small>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grievance Desk -->
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label">Student Complaints</div>
                            <div class="stat-value text-danger mt-1"><?= number_format($totalComplaints) ?></div>
                            <small class="text-danger mt-1 d-block fw-semibold"><i class="bi bi-exclamation-circle me-1"></i> <?= $pendingComplaints ?> Pending Action</small>
                        </div>
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-headset"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Interactive Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Attendance Overview Chart -->
            <div class="col-lg-6">
                <div class="app-card h-100">
                    <div class="app-card-header">
                        <h6 class="app-card-title">
                            <i class="bi bi-pie-chart text-primary"></i> Attendance Distribution Overview
                        </h6>
                        <span class="badge bg-primary-subtle text-primary"><?= $overallAttendancePct ?>% Present Rate</span>
                    </div>
                    <div class="app-card-body d-flex flex-column align-items-center justify-content-center">
                        <div style="max-width: 320px; width: 100%; height: 260px;">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                        <div class="d-flex gap-4 mt-3 text-center">
                            <div>
                                <small class="text-muted d-block">Present</small>
                                <strong class="text-success"><?= $presentCount ?></strong>
                            </div>
                            <div>
                                <small class="text-muted d-block">Absent</small>
                                <strong class="text-danger"><?= $absentCount ?></strong>
                            </div>
                            <div>
                                <small class="text-muted d-block">Late</small>
                                <strong class="text-warning"><?= $lateCount ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Complaint Redressal Status Chart -->
            <div class="col-lg-6">
                <div class="app-card h-100">
                    <div class="app-card-header">
                        <h6 class="app-card-title">
                            <i class="bi bi-bar-chart text-teal"></i> Grievance Redressal Status
                        </h6>
                        <a href="<?= BASE_URL ?>admin/complaints.php" class="text-decoration-none small fw-semibold text-primary">Manage Desk &rarr;</a>
                    </div>
                    <div class="app-card-body d-flex flex-column align-items-center justify-content-center">
                        <div style="max-width: 340px; width: 100%; height: 260px;">
                            <canvas id="complaintsChart"></canvas>
                        </div>
                        <div class="d-flex gap-4 mt-3 text-center">
                            <div>
                                <small class="text-muted d-block">Pending</small>
                                <strong class="text-danger"><?= $pendingComplaints ?></strong>
                            </div>
                            <div>
                                <small class="text-muted d-block">In Progress</small>
                                <strong class="text-warning"><?= $inProgressComplaints ?></strong>
                            </div>
                            <div>
                                <small class="text-muted d-block">Resolved</small>
                                <strong class="text-success"><?= $resolvedComplaints ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Tables & Activity -->
        <div class="row g-4">
            <!-- Recent Student Complaints -->
            <div class="col-lg-7">
                <div class="app-card h-100">
                    <div class="app-card-header">
                        <h6 class="app-card-title">
                            <i class="bi bi-chat-left-quote text-danger"></i> Recent Student Complaints
                        </h6>
                        <a href="<?= BASE_URL ?>admin/complaints.php" class="btn btn-outline-secondary btn-sm rounded-pill py-1 px-3">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Category</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentComplaints)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No student grievances recorded.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentComplaints as $c): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-dark"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></div>
                                                <small class="text-muted"><?= e($c['enrollment_no']) ?></small>
                                            </td>
                                            <td><span class="badge bg-light text-dark border"><?= e($c['category']) ?></span></td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 180px;" title="<?= e($c['subject']) ?>">
                                                    <?= e($c['subject']) ?>
                                                </div>
                                            </td>
                                            <td><?= get_status_badge($c['status']) ?></td>
                                            <td class="text-muted small"><?= format_date($c['created_at'], 'd M') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Latest Announcements -->
            <div class="col-lg-5">
                <div class="app-card h-100">
                    <div class="app-card-header">
                        <h6 class="app-card-title">
                            <i class="bi bi-megaphone text-info"></i> Latest Campus Notices
                        </h6>
                        <a href="<?= BASE_URL ?>admin/notices.php" class="btn btn-outline-secondary btn-sm rounded-pill py-1 px-3">Publish</a>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (empty($latestNotices)): ?>
                            <div class="p-4 text-center text-muted">No notices published yet.</div>
                        <?php else: ?>
                            <?php foreach ($latestNotices as $n): ?>
                                <div class="list-group-item p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle small"><?= e($n['category']) ?></span>
                                        <small class="text-muted"><?= time_ago($n['created_at']) ?></small>
                                    </div>
                                    <h6 class="fw-semibold text-dark mb-1"><?= e($n['title']) ?></h6>
                                    <p class="text-muted small mb-0 text-truncate"><?= e($n['content']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

<!-- Chart.js Initialization for Admin Analytics -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Attendance Donut Chart
    const attCtx = document.getElementById('attendanceChart');
    if (attCtx) {
        new Chart(attCtx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent', 'Late'],
                datasets: [{
                    data: [<?= (int)$presentCount ?>, <?= (int)$absentCount ?>, <?= (int)$lateCount ?>],
                    backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '70%'
            }
        });
    }

    // Complaints Status Bar/Doughnut Chart
    const compCtx = document.getElementById('complaintsChart');
    if (compCtx) {
        new Chart(compCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'In Progress', 'Resolved'],
                datasets: [{
                    data: [<?= (int)$pendingComplaints ?>, <?= (int)$inProgressComplaints ?>, <?= (int)$resolvedComplaints ?>],
                    backgroundColor: ['#ef4444', '#f59e0b', '#10b981'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '70%'
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
