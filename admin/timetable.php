<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Centralized Class Timetable Management (Admin)
 * BCA 5th Semester Major Project 1
 */

$page_title = 'Timetable Management';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

// 1. Handle Add Schedule Slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_slot') {
    if (verify_csrf()) {
        $course_id   = (int)($_POST['course_id'] ?? 0);
        $semester    = (int)($_POST['semester'] ?? 1);
        $subject_id  = (int)($_POST['subject_id'] ?? 0);
        $faculty_id  = (int)($_POST['faculty_id'] ?? 0);
        $day_of_week = sanitize($_POST['day_of_week'] ?? 'Monday');
        $start_time  = sanitize($_POST['start_time'] ?? '');
        $end_time    = sanitize($_POST['end_time'] ?? '');
        $room_no     = sanitize($_POST['room_no'] ?? '');

        if ($course_id > 0 && $subject_id > 0 && $faculty_id > 0 && !empty($start_time) && !empty($end_time) && !empty($room_no)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO timetable (course_id, semester, subject_id, faculty_id, day_of_week, start_time, end_time, room_no)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$course_id, $semester, $subject_id, $faculty_id, $day_of_week, $start_time, $end_time, $room_no]);
                set_flash('success', "Timetable lecture slot scheduled successfully.");
            } catch (Exception $e) {
                set_flash('danger', 'Error adding timetable slot: ' . $e->getMessage());
            }
        } else {
            set_flash('danger', 'Please provide all slot details.');
        }
    }
    header("Location: " . BASE_URL . "admin/timetable.php?course_id={$course_id}&semester={$semester}");
    exit;
}

// 2. Handle Delete Slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_slot') {
    if (verify_csrf()) {
        $slot_id = (int)($_POST['slot_id'] ?? 0);
        $return_course = (int)($_POST['return_course'] ?? 1);
        $return_sem = (int)($_POST['return_sem'] ?? 1);

        if ($slot_id > 0) {
            $del = $pdo->prepare("DELETE FROM timetable WHERE id = ?");
            $del->execute([$slot_id]);
            set_flash('success', 'Class slot removed from schedule.');
        }
        header("Location: " . BASE_URL . "admin/timetable.php?course_id={$return_course}&semester={$return_sem}");
        exit;
    }
}

// Fetch Courses, Faculty, Subjects for forms and filters
$courses = $pdo->query("SELECT id, course_name, course_code FROM courses ORDER BY course_name ASC")->fetchAll();
$facultyList = $pdo->query("SELECT id, first_name, last_name, designation FROM faculty ORDER BY first_name ASC")->fetchAll();
$subjectsList = $pdo->query("SELECT id, subject_name, subject_code, course_id, semester FROM subjects ORDER BY subject_name ASC")->fetchAll();

// Current View Filter
$selected_course_id = (int)($_GET['course_id'] ?? ($courses[0]['id'] ?? 1));
$selected_sem = (int)($_GET['semester'] ?? 5);

// Fetch Timetable Slots for selected class
$ttStmt = $pdo->prepare("
    SELECT t.*, s.subject_name, s.subject_code, s.type as subject_type,
           f.first_name, f.last_name, f.designation
    FROM timetable t
    JOIN subjects s ON t.subject_id = s.id
    JOIN faculty f ON t.faculty_id = f.id
    WHERE t.course_id = ? AND t.semester = ?
    ORDER BY FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), t.start_time ASC
");
$ttStmt->execute([$selected_course_id, $selected_sem]);
$slots = $ttStmt->fetchAll();

// Group by Day of Week
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$scheduleByDay = [];
foreach ($days as $day) {
    $scheduleByDay[$day] = [];
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
                <h3 class="fw-bold text-dark mb-1">Class Timetable Scheduler</h3>
                <p class="text-muted small mb-0">Organize weekly lecture routines, lab periods, and classroom allocation</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addSlotModal">
                <i class="bi bi-clock-fill me-2"></i> Schedule Class Period
            </button>
        </div>

        <?php display_flash(); ?>

        <!-- Class Selector Form -->
        <div class="app-card p-3 mb-4">
            <form method="GET" action="timetable.php" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted">Select Degree Program</label>
                    <select name="course_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($selected_course_id == $c['id']) ? 'selected' : '' ?>>
                                <?= e($c['course_name']) ?> (<?= e($c['course_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Select Semester</label>
                    <select name="semester" class="form-select" onchange="this.form.submit()">
                        <?php for ($s = 1; $s <= 8; $s++): ?>
                            <option value="<?= $s ?>" <?= ($selected_sem == $s) ? 'selected' : '' ?>>Semester <?= $s ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4 text-md-end">
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print Timetable
                    </button>
                </div>
            </form>
        </div>

        <!-- Weekly Timetable View -->
        <div class="row g-4">
            <?php foreach ($days as $day): ?>
                <div class="col-lg-6">
                    <div class="app-card h-100">
                        <div class="app-card-header bg-light">
                            <h6 class="app-card-title">
                                <i class="bi bi-calendar2-day text-primary"></i> <?= $day ?>
                            </h6>
                            <span class="badge bg-secondary-subtle text-secondary"><?= count($scheduleByDay[$day]) ?> Classes</span>
                        </div>
                        <div class="p-3">
                            <?php if (empty($scheduleByDay[$day])): ?>
                                <div class="text-center py-4 text-muted small">
                                    <i class="bi bi-cup-hot display-6 text-muted opacity-25 d-block mb-1"></i>
                                    No lectures scheduled for <?= $day ?>.
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach ($scheduleByDay[$day] as $item): ?>
                                        <div class="p-3 rounded-3 border bg-white shadow-sm d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="badge bg-primary text-white font-monospace"><?= e($item['subject_code']) ?></span>
                                                    <span class="badge <?= ($item['subject_type'] === 'Practical') ? 'bg-info-subtle text-info' : 'bg-light text-dark border' ?>">
                                                        <?= e($item['subject_type']) ?>
                                                    </span>
                                                </div>
                                                <h6 class="fw-bold text-dark mb-1"><?= e($item['subject_name']) ?></h6>
                                                <div class="small text-muted">
                                                    <i class="bi bi-person me-1"></i> <?= e($item['first_name'] . ' ' . $item['last_name']) ?> &bull; 
                                                    <i class="bi bi-geo-alt me-1"></i> <?= e($item['room_no']) ?>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="badge bg-light text-dark border px-2.5 py-1.5 mb-2 font-monospace">
                                                    <?= format_time($item['start_time']) ?> - <?= format_time($item['end_time']) ?>
                                                </div>
                                                <div>
                                                    <form method="POST" action="timetable.php" class="d-inline" onsubmit="return confirm('Delete this class slot?');">
                                                        <?php csrf_field(); ?>
                                                        <input type="hidden" name="action" value="delete_slot">
                                                        <input type="hidden" name="slot_id" value="<?= $item['id'] ?>">
                                                        <input type="hidden" name="return_course" value="<?= $selected_course_id ?>">
                                                        <input type="hidden" name="return_sem" value="<?= $selected_sem ?>">
                                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Delete Slot">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
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

<!-- Add Timetable Slot Modal -->
<div class="modal fade" id="addSlotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="timetable.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add_slot">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-calendar-plus-fill me-2"></i> Schedule Class Period</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Program *</label>
                            <select name="course_id" class="form-select" required>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($selected_course_id == $c['id']) ? 'selected' : '' ?>>
                                        <?= e($c['course_name']) ?> (<?= e($c['course_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Semester *</label>
                            <select name="semester" class="form-select" required>
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s ?>" <?= ($selected_sem == $s) ? 'selected' : '' ?>>Semester <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Subject *</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">-- Choose Subject --</option>
                                <?php foreach ($subjectsList as $sub): ?>
                                    <option value="<?= $sub['id'] ?>">
                                        <?= e($sub['subject_code']) ?> - <?= e($sub['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Faculty Instructor *</label>
                            <select name="faculty_id" class="form-select" required>
                                <option value="">-- Choose Instructor --</option>
                                <?php foreach ($facultyList as $fac): ?>
                                    <option value="<?= $fac['id'] ?>">
                                        <?= e($fac['first_name'] . ' ' . $fac['last_name']) ?> (<?= e($fac['designation']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Day of Week *</label>
                            <select name="day_of_week" class="form-select" required>
                                <?php foreach ($days as $day): ?>
                                    <option value="<?= $day ?>"><?= $day ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Start Time *</label>
                            <input type="time" name="start_time" class="form-control" value="09:00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">End Time *</label>
                            <input type="time" name="end_time" class="form-control" value="10:00" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Classroom / Laboratory Number *</label>
                            <input type="text" name="room_no" class="form-control" required placeholder="e.g. DCA Lab 3 (Web Tech Lab) or Room 304">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Add Schedule Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
