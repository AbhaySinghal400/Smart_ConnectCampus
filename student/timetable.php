<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Student Weekly Class Timetable
 * BCA 5th Semester Major Project 1
 */

$page_title = 'My Timetable';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('student');

$courseId = $currentUser['course_id'] ?? 0;
$semester = $currentUser['current_semester'] ?? 1;
$todayDay = date('l');

// Fetch weekly schedule for this student's class
$ttStmt = $pdo->prepare("
    SELECT t.*, s.subject_name, s.subject_code, s.type as subject_type,
           f.first_name as fac_first, f.last_name as fac_last, f.designation
    FROM timetable t
    JOIN subjects s ON t.subject_id = s.id
    JOIN faculty f ON t.faculty_id = f.id
    WHERE t.course_id = ? AND t.semester = ?
    ORDER BY FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), t.start_time ASC
");
$ttStmt->execute([$courseId, $semester]);
$slots = $ttStmt->fetchAll();

// Group by Day
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$scheduleByDay = [];
foreach ($days as $d) {
    $scheduleByDay[$d] = [];
}
foreach ($slots as $slot) {
    $scheduleByDay[$slot['day_of_week']][] = $slot;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Weekly Class Schedule</h3>
                <p class="text-muted small mb-0">
                    <?= e($currentUser['course_name']) ?> &bull; Semester <?= e($semester) ?> &bull; Academic Year <?= e($currentUser['academic_year']) ?>
                </p>
            </div>
            <button class="btn btn-outline-secondary rounded-pill px-3" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print Timetable
            </button>
        </div>

        <?php display_flash(); ?>

        <!-- Weekly Timetable Cards Grid -->
        <div class="row g-4">
            <?php foreach ($days as $day): ?>
                <?php $isToday = ($day === $todayDay); ?>
                <div class="col-lg-6">
                    <div class="app-card h-100 <?= $isToday ? 'border-primary shadow-sm' : '' ?>">
                        <div class="app-card-header <?= $isToday ? 'bg-primary text-white' : 'bg-light' ?>">
                            <h6 class="app-card-title <?= $isToday ? 'text-white' : 'text-dark' ?>">
                                <i class="bi bi-calendar2-week <?= $isToday ? 'text-white' : 'text-primary' ?>"></i> 
                                <?= $day ?>
                                <?php if ($isToday): ?>
                                    <span class="badge bg-white text-primary small ms-2">Today's Schedule</span>
                                <?php endif; ?>
                            </h6>
                            <span class="badge <?= $isToday ? 'bg-white text-dark' : 'bg-secondary-subtle text-secondary' ?>">
                                <?= count($scheduleByDay[$day]) ?> Classes
                            </span>
                        </div>
                        <div class="p-3">
                            <?php if (empty($scheduleByDay[$day])): ?>
                                <div class="text-center py-4 text-muted small">
                                    <i class="bi bi-cup-hot display-6 text-muted opacity-25 d-block mb-1"></i>
                                    No lectures scheduled on <?= $day ?>.
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2.5">
                                    <?php foreach ($scheduleByDay[$day] as $item): ?>
                                        <div class="p-3 rounded-3 border bg-white d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="badge bg-primary text-white font-monospace"><?= e($item['subject_code']) ?></span>
                                                    <span class="badge <?= ($item['subject_type'] === 'Practical') ? 'bg-info-subtle text-info' : 'bg-light text-dark border' ?>">
                                                        <?= e($item['subject_type']) ?>
                                                    </span>
                                                </div>
                                                <h6 class="fw-bold text-dark mb-1"><?= e($item['subject_name']) ?></h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-person me-1"></i> Prof. <?= e($item['fac_first'] . ' ' . $item['fac_last']) ?> &bull; 
                                                    <i class="bi bi-geo-alt me-1"></i> <?= e($item['room_no']) ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-light text-dark border px-2.5 py-1.5 font-monospace">
                                                    <?= format_time($item['start_time']) ?> - <?= format_time($item['end_time']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
