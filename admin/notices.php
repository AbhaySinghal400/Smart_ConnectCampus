<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Notice & Announcement Board Management (Admin)
 * BCA 5th Semester Major Project 1
 */

$page_title = 'Notice Management';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

// 1. Handle Add Notice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_notice') {
    if (verify_csrf()) {
        $title     = sanitize($_POST['title'] ?? '');
        $content   = sanitize($_POST['content'] ?? '');
        $audience  = sanitize($_POST['target_audience'] ?? 'all');
        $category  = sanitize($_POST['category'] ?? 'General');
        $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
        $attachment_path = null;

        // Handle attachment upload if present
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploadRes = handle_file_upload($_FILES['attachment'], 'notices', ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'], 10);
            if ($uploadRes['success']) {
                $attachment_path = $uploadRes['filename'];
            } else {
                set_flash('danger', 'Notice upload failed: ' . $uploadRes['error']);
                header('Location: ' . BASE_URL . 'admin/notices.php');
                exit;
            }
        }

        if (!empty($title) && !empty($content)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO notices (title, content, target_audience, category, posted_by_user_id, attachment_path, is_pinned)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $content, $audience, $category, current_user_id(), $attachment_path, $is_pinned]);
                set_flash('success', 'Announcement published to campus portal.');
            } catch (Exception $e) {
                set_flash('danger', 'Error publishing notice: ' . $e->getMessage());
            }
        } else {
            set_flash('danger', 'Notice title and content cannot be empty.');
        }
    }
    header('Location: ' . BASE_URL . 'admin/notices.php');
    exit;
}

// 2. Handle Edit Notice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_notice') {
    if (verify_csrf()) {
        $notice_id = (int)($_POST['notice_id'] ?? 0);
        $title     = sanitize($_POST['title'] ?? '');
        $content   = sanitize($_POST['content'] ?? '');
        $audience  = sanitize($_POST['target_audience'] ?? 'all');
        $category  = sanitize($_POST['category'] ?? 'General');
        $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;

        if ($notice_id > 0 && !empty($title) && !empty($content)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE notices 
                    SET title = ?, content = ?, target_audience = ?, category = ?, is_pinned = ?
                    WHERE id = ?
                ");
                $stmt->execute([$title, $content, $audience, $category, $is_pinned, $notice_id]);
                set_flash('success', 'Notice updated successfully.');
            } catch (Exception $e) {
                set_flash('danger', 'Error updating notice: ' . $e->getMessage());
            }
        }
    }
    header('Location: ' . BASE_URL . 'admin/notices.php');
    exit;
}

// 3. Handle Delete Notice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_notice') {
    if (verify_csrf()) {
        $notice_id = (int)($_POST['notice_id'] ?? 0);
        if ($notice_id > 0) {
            $del = $pdo->prepare("DELETE FROM notices WHERE id = ?");
            $del->execute([$notice_id]);
            set_flash('success', 'Notice deleted from board.');
        }
    }
    header('Location: ' . BASE_URL . 'admin/notices.php');
    exit;
}

// Fetch all notices with author username
$noticesStmt = $pdo->query("
    SELECT n.*, u.username as posted_by, u.role as author_role
    FROM notices n
    JOIN users u ON n.posted_by_user_id = u.id
    ORDER BY n.is_pinned DESC, n.created_at DESC
");
$notices = $noticesStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Campus Announcements &amp; Circulars</h3>
                <p class="text-muted small mb-0">Broadcast critical circulars to students, faculty, or the entire campus</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addNoticeModal">
                <i class="bi bi-megaphone-fill me-2"></i> Publish Announcement
            </button>
        </div>

        <?php display_flash(); ?>

        <!-- Search Bar -->
        <div class="app-card p-3 mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" data-table-search="noticesTable" 
                               placeholder="Instant search notices by title, category, or audience...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Notices Grid / Table -->
        <div class="app-card">
            <div class="table-responsive">
                <table class="table table-custom align-middle" id="noticesTable">
                    <thead>
                        <tr>
                            <th>Category &amp; Pin</th>
                            <th>Notice Title &amp; Description</th>
                            <th>Audience</th>
                            <th>Posted By</th>
                            <th>Date</th>
                            <th>Attachment</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notices)): ?>
                            <tr class="no-records-row">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-bell-slash display-5 d-block mb-2 opacity-50"></i>
                                    No announcements posted yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($notices as $n): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-1.5">
                                            <?php if ($n['is_pinned']): ?>
                                                <i class="bi bi-pin-angle-fill text-danger" title="Pinned Announcement"></i>
                                            <?php endif; ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                <?= e($n['category']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($n['title']) ?></div>
                                        <small class="text-muted d-block text-truncate" style="max-width: 320px;">
                                            <?= e($n['content']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($n['target_audience'] === 'all'): ?>
                                            <span class="badge bg-secondary">Campus-wide (All)</span>
                                        <?php elseif ($n['target_audience'] === 'students'): ?>
                                            <span class="badge bg-info text-dark">Students Only</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Faculty Only</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark"><?= e($n['posted_by']) ?></div>
                                        <small class="text-muted text-uppercase" style="font-size: 0.7rem;"><?= e($n['author_role']) ?></small>
                                    </td>
                                    <td>
                                        <div class="small text-dark"><?= format_date($n['created_at']) ?></div>
                                        <small class="text-muted"><?= time_ago($n['created_at']) ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($n['attachment_path'])): ?>
                                            <a href="<?= BASE_URL ?>uploads/notices/<?= e($n['attachment_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary px-2.5 py-1">
                                                <i class="bi bi-paperclip me-1"></i> View Doc
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary rounded-circle" title="Edit" 
                                                onclick='openEditNoticeModal(<?= json_encode($n) ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="notices.php" class="d-inline" onsubmit="return confirm('Delete this announcement?');">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_notice">
                                            <input type="hidden" name="notice_id" value="<?= $n['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

<!-- Add Notice Modal -->
<div class="modal fade" id="addNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="notices.php" enctype="multipart/form-data">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add_notice">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-megaphone-fill me-2"></i> Publish Campus Announcement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Notice Title *</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Winter 2026 Examination Schedule">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Category *</label>
                            <select name="category" class="form-select" required>
                                <option value="Academic">Academic</option>
                                <option value="Examination">Examination</option>
                                <option value="Event">Event / Hackathon</option>
                                <option value="Urgent">Urgent / Important</option>
                                <option value="General" selected>General Circular</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Audience *</label>
                            <select name="target_audience" class="form-select" required>
                                <option value="all">Campus-Wide (Everyone)</option>
                                <option value="students">Students Only</option>
                                <option value="faculty">Faculty Only</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Announcement Content *</label>
                        <textarea name="content" class="form-control" rows="5" required placeholder="Write the full description and guidelines for students and faculty..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Upload Document Attachment (PDF/DOCX/PNG, max 10MB)</label>
                        <input type="file" name="attachment" class="form-control">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_pinned" id="add_pinned" value="1">
                        <label class="form-check-label small fw-semibold text-danger" for="add_pinned">
                            <i class="bi bi-pin-angle-fill"></i> Pin this notice to the top of student and faculty boards
                        </label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Publish Notice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Notice Modal -->
<div class="modal fade" id="editNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="notices.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="edit_notice">
                <input type="hidden" name="notice_id" id="edit_notice_id">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit Notice</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Notice Title *</label>
                        <input type="text" name="title" id="edit_notice_title" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Category *</label>
                            <select name="category" id="edit_notice_category" class="form-select" required>
                                <option value="Academic">Academic</option>
                                <option value="Examination">Examination</option>
                                <option value="Event">Event / Hackathon</option>
                                <option value="Urgent">Urgent / Important</option>
                                <option value="General">General Circular</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Audience *</label>
                            <select name="target_audience" id="edit_notice_audience" class="form-select" required>
                                <option value="all">Campus-Wide (Everyone)</option>
                                <option value="students">Students Only</option>
                                <option value="faculty">Faculty Only</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Announcement Content *</label>
                        <textarea name="content" id="edit_notice_content" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_pinned" id="edit_notice_pinned" value="1">
                        <label class="form-check-label small fw-semibold text-danger" for="edit_notice_pinned">
                            <i class="bi bi-pin-angle-fill"></i> Pin this notice to top
                        </label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditNoticeModal(notice) {
    document.getElementById('edit_notice_id').value = notice.id;
    document.getElementById('edit_notice_title').value = notice.title;
    document.getElementById('edit_notice_category').value = notice.category;
    document.getElementById('edit_notice_audience').value = notice.target_audience;
    document.getElementById('edit_notice_content').value = notice.content;
    document.getElementById('edit_notice_pinned').checked = (notice.is_pinned == 1);

    new bootstrap.Modal(document.getElementById('editNoticeModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
