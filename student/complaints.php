<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Student Grievance Redressal & Complaint Tracker
 * BCA 5th Semester Major Project 1
 */

$page_title = 'My Complaints';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('student');

$studentId = $currentUser['student_id'] ?? 0;

// Handle Submit New Complaint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'lodge_complaint') {
    if (verify_csrf()) {
        $category    = sanitize($_POST['category'] ?? 'Other');
        $subject     = sanitize($_POST['subject'] ?? '');
        $description = sanitize($_POST['description'] ?? '');

        if (!empty($subject) && !empty($description)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO complaints (student_id, category, subject, description, status)
                    VALUES (?, ?, ?, ?, 'Pending')
                ");
                $stmt->execute([$studentId, $category, $subject, $description]);
                set_flash('success', 'Your grievance ticket has been registered. The administration will investigate and respond.');
            } catch (Exception $e) {
                set_flash('danger', 'Error submitting complaint: ' . $e->getMessage());
            }
        } else {
            set_flash('danger', 'Please provide both grievance subject and description.');
        }
    }
    header('Location: ' . BASE_URL . 'student/complaints.php');
    exit;
}

// Fetch complaints submitted by this student
$complaintsStmt = $pdo->prepare("
    SELECT * FROM complaints 
    WHERE student_id = ? 
    ORDER BY created_at DESC
");
$complaintsStmt->execute([$studentId]);
$myComplaints = $complaintsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Student Grievance &amp; Helpdesk</h3>
                <p class="text-muted small mb-0">Report campus issues, track ticket progress, and receive official administrative redressal</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#newComplaintModal">
                <i class="bi bi-chat-left-dots-fill me-2"></i> Lodge New Grievance
            </button>
        </div>

        <?php display_flash(); ?>

        <!-- Grievance Tickets List -->
        <div class="app-card">
            <div class="app-card-header">
                <h6 class="app-card-title"><i class="bi bi-clock-history text-primary"></i> My Grievance Tickets</h6>
                <span class="badge bg-light text-dark border"><?= count($myComplaints) ?> Total Tickets</span>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Category</th>
                            <th>Subject &amp; Details</th>
                            <th>Current Status</th>
                            <th>Logged Date</th>
                            <th>Resolution &amp; Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($myComplaints)): ?>
                            <tr class="no-records-row">
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-patch-check display-5 d-block mb-2 text-success opacity-50"></i>
                                    You have not submitted any grievance tickets.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($myComplaints as $c): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace">
                                            #GR-<?= str_pad($c['id'], 4, '0', STR_PAD_LEFT) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            <?= e($c['category']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($c['subject']) ?></div>
                                        <small class="text-muted d-block text-truncate" style="max-width: 320px;" title="<?= e($c['description']) ?>">
                                            <?= e($c['description']) ?>
                                        </small>
                                    </td>
                                    <td><?= get_status_badge($c['status']) ?></td>
                                    <td>
                                        <div class="small fw-semibold"><?= format_date($c['created_at']) ?></div>
                                        <small class="text-muted"><?= time_ago($c['created_at']) ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['admin_remarks'])): ?>
                                            <div class="small text-success bg-success-subtle p-2 rounded border border-success-subtle">
                                                <i class="bi bi-check2-circle me-1 fw-bold"></i><strong>Admin Response:</strong> <?= e($c['admin_remarks']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="bi bi-hourglass-split me-1"></i> Awaiting administrative review</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

<!-- Lodge New Complaint Modal -->
<div class="modal fade" id="newComplaintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="complaints.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="lodge_complaint">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-chat-left-dots-fill me-2"></i> Lodge Grievance / Complaint</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Complaint Category *</label>
                            <select name="category" class="form-select" required>
                                <option value="Computer Lab">Computer Lab (Hardware / Software / Network)</option>
                                <option value="Classroom">Classroom (Projector / Seating / Audio)</option>
                                <option value="Infrastructure">Infrastructure (Electricity / Water / Wi-Fi)</option>
                                <option value="Library">Library (Book Availability / Digital Access)</option>
                                <option value="Faculty">Faculty &amp; Academic</option>
                                <option value="Other" selected>Other Campus Issue</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Brief Subject / Issue Summary *</label>
                            <input type="text" name="subject" class="form-control" required placeholder="e.g. Wi-Fi signal drop in Lab 3 or Projector flicker">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Detailed Description of the Issue *</label>
                            <textarea name="description" class="form-control" rows="5" required placeholder="Describe the location, problem encountered, workstation number, and any recurring patterns..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4">Submit Grievance Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
