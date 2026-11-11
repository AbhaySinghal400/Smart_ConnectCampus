<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Common Portal Header & Topbar Navigation
 * BCA 5th Semester Major Project 1
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/functions.php';

// Verify authentication for layout inclusion
require_login();

$currentUser = get_logged_in_user($pdo);
$pageTitle = $page_title ?? 'Portal';
$currentRole = current_user_role();

// Fetch latest 3 notices for header dropdown notification bell
try {
    $noticesStmt = $pdo->prepare("
        SELECT id, title, category, created_at 
        FROM notices 
        WHERE target_audience = 'all' OR target_audience = ?
        ORDER BY is_pinned DESC, created_at DESC 
        LIMIT 3
    ");
    $targetParam = ($currentRole === 'faculty') ? 'faculty' : 'students';
    $noticesStmt->execute([$targetParam]);
    $recentNotices = $noticesStmt->fetchAll();
} catch (Exception $e) {
    $recentNotices = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Smart CampusConnect</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Custom Portal CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<!-- Top Navigation Bar -->
<header class="topbar">
    <div class="d-flex align-items-center gap-3">
        <!-- Sidebar Toggle for Mobile -->
        <button class="btn btn-light d-lg-none p-2 border" id="sidebarToggle" aria-label="Toggle navigation">
            <i class="bi bi-list fs-5"></i>
        </button>
        
        <div>
            <h5 class="mb-0 fw-bold text-dark d-none d-sm-block"><?= e($pageTitle) ?></h5>
            <small class="text-muted d-none d-md-block">Academic Session 2026-2027</small>
        </div>
    </div>

    <!-- Right Header Elements -->
    <div class="d-flex align-items-center gap-3">
        <!-- Live Academic Notices Dropdown -->
        <div class="dropdown">
            <button class="btn btn-light position-relative p-2 rounded-circle border" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Campus Notices">
                <i class="bi bi-bell text-secondary fs-5"></i>
                <?php if (!empty($recentNotices)): ?>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">New notices</span>
                    </span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0" style="width: 320px;">
                <div class="p-3 bg-primary text-white rounded-top d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-megaphone me-1"></i> Announcements</span>
                    <span class="badge bg-white text-primary"><?= count($recentNotices) ?> New</span>
                </div>
                <div class="list-group list-group-flush" style="max-height: 280px; overflow-y: auto;">
                    <?php if (empty($recentNotices)): ?>
                        <div class="p-3 text-center text-muted small">No new announcements</div>
                    <?php else: ?>
                        <?php foreach ($recentNotices as $notice): ?>
                            <div class="list-group-item list-group-item-action p-3">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <span class="badge bg-info-subtle text-info border border-info-subtle small"><?= e($notice['category']) ?></span>
                                    <small class="text-muted"><?= time_ago($notice['created_at']) ?></small>
                                </div>
                                <h6 class="mb-1 text-dark fs-6 fw-semibold text-truncate"><?= e($notice['title']) ?></h6>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="p-2 border-top text-center bg-light rounded-bottom">
                    <?php 
                        $noticePage = ($currentRole === 'admin') ? BASE_URL . 'admin/notices.php' : (($currentRole === 'faculty') ? BASE_URL . 'faculty/dashboard.php' : BASE_URL . 'student/notices.php');
                    ?>
                    <a href="<?= $noticePage ?>" class="text-decoration-none small fw-semibold text-primary">View All Notices &rarr;</a>
                </div>
            </div>
        </div>

        <!-- User Profile Dropdown -->
        <div class="dropdown">
            <button class="btn btn-light d-flex align-items-center gap-2 border px-2.5 py-1.5 rounded-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 0.85rem;">
                    <?= strtoupper(substr($currentUser['full_name'] ?? $currentUser['username'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="text-start d-none d-md-block pe-1">
                    <div class="fw-bold text-dark small line-height-1 mb-0"><?= e($currentUser['full_name'] ?? $currentUser['username']) ?></div>
                    <small class="text-muted" style="font-size: 0.72rem;"><?= strtoupper(e($currentRole)) ?></small>
                </div>
                <i class="bi bi-chevron-down text-muted small"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2" style="min-width: 220px;">
                <li class="px-3 py-2 border-bottom mb-1">
                    <p class="mb-0 fw-bold text-dark"><?= e($currentUser['full_name'] ?? $currentUser['username']) ?></p>
                    <small class="text-muted"><?= e($currentUser['email']) ?></small>
                </li>
                <?php if ($currentRole === 'student'): ?>
                    <li><a class="dropdown-item py-2 rounded" href="<?= BASE_URL ?>student/profile.php"><i class="bi bi-person me-2 text-primary"></i> My Academic Profile</a></li>
                <?php endif; ?>
                <li><a class="dropdown-item py-2 rounded text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
            </ul>
        </div>
    </div>
</header>
