<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Role-Based Dynamic Sidebar Navigation
 * BCA 5th Semester Major Project 1
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require_once APP_ROOT . '/includes/auth.php';

$currentRole = current_user_role();
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Helper to determine active menu item
function is_active($targetScript, $currentScript) {
    return ($targetScript === $currentScript) ? 'active' : '';
}
?>

<!-- Sidebar Navigation -->
<aside class="sidebar" id="appSidebar">
    <!-- Brand / Logo -->
    <a href="<?= BASE_URL ?>" class="sidebar-brand">
        <img src="<?= BASE_URL ?>assets/images/logo.svg" alt="CampusConnect Logo">
        <div>
            <div class="brand-title">CampusConnect</div>
            <span class="brand-badge"><?= strtoupper(e($currentRole)) ?> PORTAL</span>
        </div>
    </a>

    <!-- Navigation Links -->
    <nav class="sidebar-nav">
        <?php if ($currentRole === 'admin'): ?>
            <!-- ADMIN NAVIGATION -->
            <div class="nav-section-title">Core Management</div>
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="nav-link <?= is_active('dashboard.php', $currentScript) ?>">
                <i class="bi bi-grid-1x2"></i>
                <span>Admin Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>admin/students.php" class="nav-link <?= is_active('students.php', $currentScript) ?>">
                <i class="bi bi-mortarboard"></i>
                <span>Students</span>
            </a>
            <a href="<?= BASE_URL ?>admin/faculty.php" class="nav-link <?= is_active('faculty.php', $currentScript) ?>">
                <i class="bi bi-person-video3"></i>
                <span>Faculty Directory</span>
            </a>
            <a href="<?= BASE_URL ?>admin/courses.php" class="nav-link <?= is_active('courses.php', $currentScript) ?>">
                <i class="bi bi-book"></i>
                <span>Departments & Courses</span>
            </a>

            <div class="nav-section-title">Academic Operations</div>
            <a href="<?= BASE_URL ?>admin/attendance.php" class="nav-link <?= is_active('attendance.php', $currentScript) ?>">
                <i class="bi bi-calendar-check"></i>
                <span>Attendance Logs</span>
            </a>
            <a href="<?= BASE_URL ?>admin/timetable.php" class="nav-link <?= is_active('timetable.php', $currentScript) ?>">
                <i class="bi bi-clock-history"></i>
                <span>Class Timetable</span>
            </a>
            <a href="<?= BASE_URL ?>admin/notices.php" class="nav-link <?= is_active('notices.php', $currentScript) ?>">
                <i class="bi bi-megaphone"></i>
                <span>Campus Notices</span>
            </a>
            <a href="<?= BASE_URL ?>admin/complaints.php" class="nav-link <?= is_active('complaints.php', $currentScript) ?>">
                <i class="bi bi-headset"></i>
                <span>Grievance Desk</span>
            </a>

        <?php elseif ($currentRole === 'faculty'): ?>
            <!-- FACULTY NAVIGATION -->
            <div class="nav-section-title">Instruction & Teaching</div>
            <a href="<?= BASE_URL ?>faculty/dashboard.php" class="nav-link <?= is_active('dashboard.php', $currentScript) ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Faculty Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>faculty/attendance.php" class="nav-link <?= is_active('attendance.php', $currentScript) ?>">
                <i class="bi bi-calendar-check"></i>
                <span>Mark Attendance</span>
            </a>
            <a href="<?= BASE_URL ?>faculty/assignments.php" class="nav-link <?= is_active('assignments.php', $currentScript) ?>">
                <i class="bi bi-file-earmark-code"></i>
                <span>Assignments</span>
            </a>
            <a href="<?= BASE_URL ?>faculty/submissions.php" class="nav-link <?= is_active('submissions.php', $currentScript) ?>">
                <i class="bi bi-cloud-arrow-down"></i>
                <span>Submissions & Grading</span>
            </a>

        <?php elseif ($currentRole === 'student'): ?>
            <!-- STUDENT NAVIGATION -->
            <div class="nav-section-title">My Academics</div>
            <a href="<?= BASE_URL ?>student/dashboard.php" class="nav-link <?= is_active('dashboard.php', $currentScript) ?>">
                <i class="bi bi-house-door"></i>
                <span>Student Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>student/profile.php" class="nav-link <?= is_active('profile.php', $currentScript) ?>">
                <i class="bi bi-person-badge"></i>
                <span>My Profile</span>
            </a>
            <a href="<?= BASE_URL ?>student/attendance.php" class="nav-link <?= is_active('attendance.php', $currentScript) ?>">
                <i class="bi bi-pie-chart"></i>
                <span>Attendance Record</span>
            </a>
            <a href="<?= BASE_URL ?>student/timetable.php" class="nav-link <?= is_active('timetable.php', $currentScript) ?>">
                <i class="bi bi-calendar3"></i>
                <span>Weekly Schedule</span>
            </a>

            <div class="nav-section-title">Tasks & Services</div>
            <a href="<?= BASE_URL ?>student/assignments.php" class="nav-link <?= is_active('assignments.php', $currentScript) ?>">
                <i class="bi bi-journal-check"></i>
                <span>Assignments & Tasks</span>
            </a>
            <a href="<?= BASE_URL ?>student/notices.php" class="nav-link <?= is_active('notices.php', $currentScript) ?>">
                <i class="bi bi-bell"></i>
                <span>Notices & Events</span>
            </a>
            <a href="<?= BASE_URL ?>student/complaints.php" class="nav-link <?= is_active('complaints.php', $currentScript) ?>">
                <i class="bi bi-chat-left-dots"></i>
                <span>Student Complaints</span>
            </a>
        <?php endif; ?>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-user-footer">
        <a href="<?= BASE_URL ?>logout.php" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-box-arrow-right"></i>
            <span>Log Out</span>
        </a>
    </div>
</aside>
