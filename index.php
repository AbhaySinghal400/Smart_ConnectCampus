<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Public Landing Page
 * BCA 5th Semester Major Project 1
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect to respective dashboard
if (is_logged_in()) {
    $role = current_user_role();
    if ($role === 'admin') header('Location: ' . BASE_URL . 'admin/dashboard.php');
    elseif ($role === 'faculty') header('Location: ' . BASE_URL . 'faculty/dashboard.php');
    elseif ($role === 'student') header('Location: ' . BASE_URL . 'student/dashboard.php');
    exit;
}

// Fetch public pinned announcements from database
try {
    $noticesStmt = $pdo->query("
        SELECT id, title, content, category, created_at 
        FROM notices 
        WHERE is_pinned = 1 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $pinnedNotices = $noticesStmt->fetchAll();
} catch (Exception $e) {
    $pinnedNotices = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart CampusConnect - Unified Campus Management Portal</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1e3a8a;
            --primary-dark: #0f172a;
            --accent: #0d9488;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        .hero-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #0d9488 100%);
            color: #ffffff;
            padding: 5rem 0 6rem 0;
            position: relative;
            overflow: hidden;
        }
        .hero-badge {
            background-color: rgba(13, 148, 136, 0.25);
            border: 1px solid rgba(13, 148, 136, 0.6);
            color: #5eead4;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .feature-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 2rem 1.75rem;
            transition: all 0.25s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }
        .feature-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 1.25rem;
        }
        .portal-pill {
            background: #ffffff;
            border-radius: 14px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
        }
        .portal-pill:hover {
            border-color: #2563eb;
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.1);
        }
    </style>
</head>
<body>

    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3" style="background-color: #0f172a !important;">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= BASE_URL ?>">
                <img src="<?= BASE_URL ?>assets/images/logo.svg" alt="Logo" width="36" height="36">
                <span>Smart CampusConnect</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto align-items-center gap-2 mt-3 mt-lg-0">
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="#portals">Portals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="#notices">Announcements</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-light btn-sm px-3 py-1.5 rounded-pill" href="<?= BASE_URL ?>register.php">
                            <i class="bi bi-person-plus me-1"></i> Student Register
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm px-3.5 py-1.5 rounded-pill fw-semibold" href="<?= BASE_URL ?>login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="hero-badge mb-3">
                        <i class="bi bi-mortarboard-fill"></i> BCA 5th Semester Major Project 1
                    </div>
                    <h1 class="display-4 fw-extrabold mb-3 line-height-1 text-white">
                        Unified Campus Management <br><span class="text-info">&amp; Academic Portal</span>
                    </h1>
                    <p class="lead text-light text-opacity-80 mb-4" style="max-width: 620px;">
                        A centralized, database-driven digital platform connecting <strong>Students</strong>, <strong>Faculty</strong>, and <strong>Administrators</strong> with real-time attendance tracking, assignment submissions, timetables, and grievance redressal.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?= BASE_URL ?>login.php" class="btn btn-primary btn-lg px-4 py-2.5 rounded-pill fw-bold shadow">
                            <i class="bi bi-door-open-fill me-2"></i> Access Portal
                        </a>
                        <a href="<?= BASE_URL ?>register.php" class="btn btn-light btn-lg px-4 py-2.5 rounded-pill fw-bold text-dark">
                            <i class="bi bi-person-plus-fill me-2 text-primary"></i> Student Registration
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="p-4 rounded-4" style="background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.15);">
                        <h5 class="text-white mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-shield-lock-fill text-warning"></i> Quick Demo Access
                        </h5>
                        <p class="text-light text-opacity-75 small">Test each role instantly with pre-seeded demo accounts:</p>
                        
                        <div class="d-flex flex-column gap-2 mb-3">
                            <div class="p-2.5 bg-dark bg-opacity-50 rounded border border-light border-opacity-10 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-danger text-white">Admin</span>
                                    <div class="small text-light mt-1">admin@campusconnect.edu</div>
                                </div>
                                <code class="text-warning small">Admin@123</code>
                            </div>
                            <div class="p-2.5 bg-dark bg-opacity-50 rounded border border-light border-opacity-10 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary text-white">Faculty</span>
                                    <div class="small text-light mt-1">prof.sharma@campusconnect.edu</div>
                                </div>
                                <code class="text-warning small">Faculty@123</code>
                            </div>
                            <div class="p-2.5 bg-dark bg-opacity-50 rounded border border-light border-opacity-10 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-success text-white">Student</span>
                                    <div class="small text-light mt-1">rahul.mehta@student.edu</div>
                                </div>
                                <code class="text-warning small">Student@123</code>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>login.php" class="btn btn-info w-100 fw-bold text-dark py-2">
                            Proceed to Sign In &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Role Portals Section -->
    <section class="py-5 bg-white" id="portals">
        <div class="container">
            <div class="text-center mb-5">
                <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1.5 mb-2">3 Dedicated Roles</span>
                <h2 class="fw-bold">Role-Based Academic Portals</h2>
                <p class="text-muted">Separate secure environments customized for each stakeholder</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <a href="<?= BASE_URL ?>login.php" class="portal-pill">
                        <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                            <i class="bi bi-mortarboard-fill fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Student Portal</h5>
                            <p class="text-muted small mb-0">View attendance %, download assignments, check timetable, submit grievances.</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?= BASE_URL ?>login.php" class="portal-pill">
                        <div class="bg-info text-dark rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                            <i class="bi bi-person-video3 fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Faculty Desk</h5>
                            <p class="text-muted small mb-0">Mark daily attendance, create assignments, evaluate student submissions.</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?= BASE_URL ?>login.php" class="portal-pill">
                        <div class="bg-dark text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                            <i class="bi bi-shield-check fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Administrator Control</h5>
                            <p class="text-muted small mb-0">Manage courses, students, faculty, timetables, and monitor complaints.</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Features Section -->
    <section class="py-5" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <span class="badge bg-teal-subtle text-success fw-bold px-3 py-1.5 mb-2" style="background-color: #ccfbf1; color: #0f766e;">Integrated Modules</span>
                <h2 class="fw-bold">Everything You Need for Campus Governance</h2>
                <p class="text-muted">Designed for efficiency, academic transparency, and institutional excellence</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Smart Attendance Tracking</h5>
                        <p class="text-muted small">Faculty mark daily attendance in seconds; students calculate attendance percentages with real-time shortage alerts.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-cloud-arrow-up"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Assignment Workflow</h5>
                        <p class="text-muted small">Faculty upload problem statements with deadlines; students upload solution files with instant status & grading feedback.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Interactive Timetable</h5>
                        <p class="text-muted small">Dynamic weekly class schedule mapped to classrooms, professors, and subjects with day-by-day views.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-megaphone"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Circulars & Announcements</h5>
                        <p class="text-muted small">Targeted broadcast notices for all, students only, or faculty only with file attachments and priority pins.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-headset"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Grievance Redressal Desk</h5>
                        <p class="text-muted small">Students lodge complaints across infrastructure, lab, or academic categories and track resolution progress.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon bg-dark bg-opacity-10 text-dark">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Enterprise Security</h5>
                        <p class="text-muted small">Built with Bcrypt password hashing, PDO prepared statements, CSRF protection, and role-based session isolation.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest Pinned Notices -->
    <?php if (!empty($pinnedNotices)): ?>
    <section class="py-5 bg-white border-top border-bottom" id="notices">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-pin-angle-fill text-danger me-2"></i>Campus Announcements</h3>
                    <p class="text-muted mb-0 small">Official updates from college administration</p>
                </div>
                <a href="<?= BASE_URL ?>login.php" class="btn btn-outline-primary btn-sm rounded-pill">View All in Portal</a>
            </div>

            <div class="row g-3">
                <?php foreach ($pinnedNotices as $n): ?>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 h-100 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary-subtle text-primary"><?= e($n['category']) ?></span>
                                <small class="text-muted"><?= format_date($n['created_at']) ?></small>
                            </div>
                            <h6 class="fw-bold text-dark"><?= e($n['title']) ?></h6>
                            <p class="text-muted small mb-0"><?= e(substr($n['content'], 0, 110)) ?>...</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 border-top border-secondary">
        <div class="container text-center text-md-start">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="d-flex align-items-center gap-2 justify-content-center justify-content-md-start">
                        <img src="<?= BASE_URL ?>assets/images/logo.svg" alt="Logo" width="28" height="28">
                        <span class="fw-bold">Smart CampusConnect</span>
                    </div>
                    <p class="text-secondary small mb-0 mt-1">BCA 5th Semester Major Project 1 &bull; Academic Year 2026-2027</p>
                </div>
                <div class="col-md-6 text-md-end text-center">
                    
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
