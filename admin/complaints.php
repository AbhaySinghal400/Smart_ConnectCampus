<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Student Grievance Redressal & Complaint Desk (Admin)
 * BCA 5th Semester Major Project 1
 */

$page_title = 'Grievance Desk';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

// Handle Update Complaint Status & Remarks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (verify_csrf()) {
        $complaint_id  = (int)($_POST['complaint_id'] ?? 0);
        $status        = sanitize($_POST['status'] ?? 'Pending');
        $admin_remarks = sanitize($_POST['admin_remarks'] ?? '');

        if ($complaint_id > 0) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE complaints 
                    SET status = ?, admin_remarks = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$status, $admin_remarks, $complaint_id]);
                set_flash('success', "Grievance status updated to '{$status}'.");
            } catch (Exception $e) {
                set_flash('danger', 'Error updating grievance: ' . $e->getMessage());
            }
        }
    }
    header('Location: ' . BASE_URL . 'admin/complaints.php');
    exit;
}

// Filter parameters
$status_filter = sanitize($_GET['status'] ?? '');
$category_filter = sanitize($_GET['category'] ?? '');

$query = "
    SELECT c.*, s.first_name, s.last_name, s.enrollment_no, s.email as student_email, s.phone as student_phone,
           cr.course_code
    FROM complaints c
    JOIN students s ON c.student_id = s.id
    JOIN courses cr ON s.course_id = cr.id
    WHERE 1=1
";
$params = [];

if (!empty($status_filter)) {
    $query .= " AND c.status = ?";
    $params[] = $status_filter;
}
if (!empty($category_filter)) {
    $query .= " AND c.category = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY FIELD(c.status, 'Pending', 'In Progress', 'Resolved'), c.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// KPI counts
$totalComplaints = $pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
$pendingCount = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Pending'")->fetchColumn();
$inProgressCount = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'In Progress'")->fetchColumn();
$resolvedCount = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Student Grievance Redressal Desk</h3>
                <p class="text-muted small mb-0">Monitor campus issue tickets, investigate root causes, and provide official resolutions</p>
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- KPI Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Total Grievances</div>
                    <div class="stat-value text-primary mt-1"><?= $totalComplaints ?></div>
                    <small class="text-muted">All categories</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Pending Review</div>
                    <div class="stat-value text-danger mt-1"><?= $pendingCount ?></div>
                    <small class="text-danger fw-semibold">Requires immediate attention</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Under Investigation</div>
                    <div class="stat-value text-warning mt-1"><?= $inProgressCount ?></div>
                    <small class="text-muted">In Progress</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Successfully Resolved</div>
                    <div class="stat-value text-success mt-1"><?= $resolvedCount ?></div>
                    <small class="text-muted">Resolution documented</small>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="app-card p-3 mb-4">
            <form method="GET" action="complaints.php" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" data-table-search="complaintsTable" 
                               placeholder="Search tickets by student name, enrollment no, or subject...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">-- All Categories --</option>
                        <option value="Infrastructure" <?= ($category_filter === 'Infrastructure') ? 'selected' : '' ?>>Infrastructure</option>
                        <option value="Classroom" <?= ($category_filter === 'Classroom') ? 'selected' : '' ?>>Classroom</option>
                        <option value="Faculty" <?= ($category_filter === 'Faculty') ? 'selected' : '' ?>>Faculty</option>
                        <option value="Library" <?= ($category_filter === 'Library') ? 'selected' : '' ?>>Library</option>
                        <option value="Computer Lab" <?= ($category_filter === 'Computer Lab') ? 'selected' : '' ?>>Computer Lab</option>
                        <option value="Other" <?= ($category_filter === 'Other') ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">-- All Statuses --</option>
                        <option value="Pending" <?= ($status_filter === 'Pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="In Progress" <?= ($status_filter === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                        <option value="Resolved" <?= ($status_filter === 'Resolved') ? 'selected' : '' ?>>Resolved</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <?php if (!empty($category_filter) || !empty($status_filter)): ?>
                        <a href="complaints.php" class="btn btn-outline-secondary w-100">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Complaints Table -->
        <div class="app-card">
            <div class="table-responsive">
                <table class="table table-custom align-middle" id="complaintsTable">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Student</th>
                            <th>Category</th>
                            <th>Subject &amp; Details</th>
                            <th>Status</th>
                            <th>Logged On</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($complaints)): ?>
                            <tr class="no-records-row">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-patch-check display-5 d-block mb-2 text-success opacity-50"></i>
                                    No student grievances matching the selected filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($complaints as $c): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace">
                                            #GR-<?= str_pad($c['id'], 4, '0', STR_PAD_LEFT) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></div>
                                        <small class="text-muted font-monospace"><?= e($c['enrollment_no']) ?> (<?= e($c['course_code']) ?>)</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            <?= e($c['category']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($c['subject']) ?></div>
                                        <small class="text-muted d-block text-truncate" style="max-width: 320px;">
                                            <?= e($c['description']) ?>
                                        </small>
                                        <?php if (!empty($c['admin_remarks'])): ?>
                                            <div class="mt-1 small text-success bg-success-subtle p-1.5 rounded border border-success-subtle">
                                                <i class="bi bi-reply-fill me-1"></i><strong>Resolution:</strong> <?= e($c['admin_remarks']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= get_status_badge($c['status']) ?></td>
                                    <td>
                                        <div class="small text-dark"><?= format_date($c['created_at']) ?></div>
                                        <small class="text-muted"><?= time_ago($c['created_at']) ?></small>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-primary rounded-pill px-3" 
                                                onclick='openRedressalModal(<?= json_encode($c) ?>)'>
                                            <i class="bi bi-pencil-square me-1"></i> Resolve
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

<!-- Complaint Resolution Modal -->
<div class="modal fade" id="redressalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="complaints.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="complaint_id" id="modal_complaint_id">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-shield-check me-2"></i> Update Grievance Ticket</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Subject</label>
                        <div class="fw-bold text-dark" id="modal_subject">Subject text</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Student Grievance Description</label>
                        <div class="p-3 bg-light rounded text-muted small" id="modal_description">Description</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Update Ticket Status *</label>
                        <select name="status" id="modal_status" class="form-select" required>
                            <option value="Pending">Pending (Awaiting Action)</option>
                            <option value="In Progress">In Progress (Under Review)</option>
                            <option value="Resolved">Resolved (Completed)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Official Admin Remarks / Resolution Notes</label>
                        <textarea name="admin_remarks" id="modal_remarks" class="form-control" rows="3" placeholder="Enter findings, steps taken, or instructions for the student..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Update Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRedressalModal(c) {
    document.getElementById('modal_complaint_id').value = c.id;
    document.getElementById('modal_subject').textContent = c.subject;
    document.getElementById('modal_description').textContent = c.description;
    document.getElementById('modal_status').value = c.status;
    document.getElementById('modal_remarks').value = c.admin_remarks || '';

    new bootstrap.Modal(document.getElementById('redressalModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
