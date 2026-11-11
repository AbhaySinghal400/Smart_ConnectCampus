<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Student Notices & Circular Board
 * BCA 5th Semester Major Project 1
 */

$page_title = 'Notice Board';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('student');

$category_filter = sanitize($_GET['category'] ?? '');

$query = "
    SELECT n.*, u.username as posted_by
    FROM notices n
    JOIN users u ON n.posted_by_user_id = u.id
    WHERE n.target_audience = 'all' OR n.target_audience = 'students'
";
$params = [];

if (!empty($category_filter)) {
    $query .= " AND n.category = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY n.is_pinned DESC, n.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$notices = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Campus Announcements &amp; Circulars</h3>
                <p class="text-muted small mb-0">Official academic notifications, examination dates, hackathons, and administrative advisories</p>
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- Category Filter & Search -->
        <div class="app-card p-3 mb-4">
            <form method="GET" action="notices.php" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" data-table-search="noticesContainer" 
                               placeholder="Filter announcements by title, content, or category...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">-- All Categories --</option>
                        <option value="Academic" <?= ($category_filter === 'Academic') ? 'selected' : '' ?>>Academic</option>
                        <option value="Examination" <?= ($category_filter === 'Examination') ? 'selected' : '' ?>>Examination</option>
                        <option value="Event" <?= ($category_filter === 'Event') ? 'selected' : '' ?>>Events &amp; Competitions</option>
                        <option value="Urgent" <?= ($category_filter === 'Urgent') ? 'selected' : '' ?>>Urgent Circulars</option>
                        <option value="General" <?= ($category_filter === 'General') ? 'selected' : '' ?>>General</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <?php if (!empty($category_filter)): ?>
                        <a href="notices.php" class="btn btn-outline-secondary w-100">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Notices Cards List -->
        <div class="row g-4" id="noticesContainer">
            <?php if (empty($notices)): ?>
                <div class="col-12">
                    <div class="app-card p-5 text-center text-muted">
                        <i class="bi bi-bell-slash display-4 d-block mb-2 opacity-50"></i>
                        No announcements posted under this category.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($notices as $n): ?>
                    <div class="col-lg-6 notice-card-item">
                        <div class="app-card h-100 <?= $n['is_pinned'] ? 'border-danger border-opacity-50' : '' ?>">
                            <div class="app-card-header <?= $n['is_pinned'] ? 'bg-danger bg-opacity-10' : '' ?>">
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($n['is_pinned']): ?>
                                        <i class="bi bi-pin-angle-fill text-danger fs-5" title="Pinned Announcement"></i>
                                    <?php endif; ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <?= e($n['category']) ?>
                                    </span>
                                </div>
                                <small class="text-muted"><?= format_date($n['created_at']) ?></small>
                            </div>
                            <div class="p-4 d-flex flex-column justify-content-between h-100">
                                <div>
                                    <h5 class="fw-bold text-dark mb-2"><?= e($n['title']) ?></h5>
                                    <p class="text-muted small mb-3" style="line-height: 1.6;">
                                        <?= nl2br(e($n['content'])) ?>
                                    </p>
                                </div>
                                <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-person-circle me-1"></i> Posted by <strong><?= e($n['posted_by']) ?></strong>
                                    </small>
                                    <?php if (!empty($n['attachment_path'])): ?>
                                        <a href="<?= BASE_URL ?>uploads/notices/<?= e($n['attachment_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="bi bi-file-earmark-arrow-down me-1"></i> Download Doc
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
