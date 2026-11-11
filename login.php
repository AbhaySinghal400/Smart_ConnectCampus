<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Authentication System - Secure Login
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

$error = '';
$login_identifier = '';

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $login_identifier = sanitize($_POST['login_identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($login_identifier) || empty($password)) {
            $error = 'Please enter both email/username and password.';
        } else {
            try {
                // Find user by either email or username
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1");
                $stmt->execute([$login_identifier, $login_identifier]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Check if account is active
                    if ($user['status'] !== 'active') {
                        $error = 'Your account has been deactivated. Please contact the campus administrator.';
                    } else {
                        // Password correct - Regenerate session ID to prevent session fixation
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_email'] = $user['email'];

                        // Update last_login timestamp
                        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        $updateStmt->execute([$user['id']]);

                        set_flash('success', "Welcome back, " . htmlspecialchars($user['username']) . "!");

                        // Redirect based on user role
                        if ($user['role'] === 'admin') {
                            header('Location: ' . BASE_URL . 'admin/dashboard.php');
                        } elseif ($user['role'] === 'faculty') {
                            header('Location: ' . BASE_URL . 'faculty/dashboard.php');
                        } elseif ($user['role'] === 'student') {
                            header('Location: ' . BASE_URL . 'student/dashboard.php');
                        } else {
                            header('Location: ' . BASE_URL . 'index.php');
                        }
                        exit;
                    }
                } else {
                    $error = 'Invalid credentials. Please verify your email/username and password.';
                }
            } catch (Exception $e) {
                $error = 'Database query failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Smart CampusConnect</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom Portal CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="auth-body">

    <div class="auth-card">
        <div class="row g-0">
            <!-- Left Info Sidebar -->
            <div class="col-lg-5 auth-sidebar d-none d-lg-flex">
                <div>
                    <a href="<?= BASE_URL ?>" class="d-flex align-items-center gap-2 text-white text-decoration-none mb-4">
                        <img src="<?= BASE_URL ?>assets/images/logo.svg" alt="CampusConnect Logo" width="42" height="42">
                        <span class="fs-4 fw-bold">CampusConnect</span>
                    </a>
                    <div class="badge bg-teal-subtle text-white bg-opacity-25 border border-light border-opacity-25 px-3 py-1 mb-3">
                        Unified Academic Portal
                    </div>
                    <h3 class="fw-bold mb-3 text-white">Centralized Campus Administration</h3>
                    <p class="text-white-50 small mb-4">
                        Seamlessly connecting Students, Faculty and Administrative staff for streamlined attendance tracking, assignments, timetable scheduling, and grievance resolution.
                    </p>
                </div>

                <div class="border-top border-light border-opacity-25 pt-3">
                    <small class="text-white-50 d-block mb-2">BCA 5th Semester Major Project 1</small>
                    <div class="small text-white opacity-75">Developed by Team of 4 Members</div>
                </div>
            </div>

            <!-- Right Login Form -->
            <div class="col-lg-7 auth-form-side">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">Sign In to Your Account</h4>
                        <p class="text-muted small mb-0">Select or enter your credentials below</p>
                    </div>
                    <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary btn-sm rounded-pill" title="Back to Home">
                        <i class="bi bi-house"></i>
                    </a>
                </div>

                <?php display_flash(); ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?= e($error) ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- One-Click Quick Demo Login Switcher (Designed for BCA Viva Evaluation) -->
                <div class="p-3 bg-light rounded-3 border mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-bold text-muted text-uppercase" style="font-size: 0.72rem;">Quick Demo Fill:</span>
                        <span class="badge bg-primary-subtle text-primary small">Evaluator Helper</span>
                    </div>
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="fillCredentials('admin@campusconnect.edu', 'Admin@123')">
                            <i class="bi bi-shield-lock me-1"></i> Admin
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="fillCredentials('prof.sharma@campusconnect.edu', 'Faculty@123')">
                            <i class="bi bi-person-video3 me-1"></i> Faculty
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="fillCredentials('rahul.mehta@student.edu', 'Student@123')">
                            <i class="bi bi-mortarboard me-1"></i> Student
                        </button>
                    </div>
                </div>

                <form method="POST" action="login.php" id="loginForm">
                    <?php csrf_field(); ?>

                    <div class="mb-3">
                        <label for="login_identifier" class="form-label fw-semibold text-dark small">Email Address or Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="login_identifier" name="login_identifier" 
                                   value="<?= e($login_identifier) ?>" placeholder="e.g. admin@campusconnect.edu or username" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label fw-semibold text-dark small mb-0">Password</label>
                            <span class="text-muted small" style="font-size: 0.75rem;">(Default: Admin@123 / Faculty@123 / Student@123)</span>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control border-start-0 border-end-0 ps-0" id="password" name="password" 
                                   placeholder="Enter your secret password" required>
                            <button class="btn btn-outline-secondary border-start-0 bg-white toggle-password-btn" type="button" data-target="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rememberMe">
                            <label class="form-check-label text-muted small" for="rememberMe">
                                Remember this session
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold rounded-3 shadow-sm mb-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Authenticate &amp; Enter Portal
                    </button>

                    <div class="text-center">
                        <span class="text-muted small">Are you a new student? </span>
                        <a href="<?= BASE_URL ?>register.php" class="text-primary fw-semibold small text-decoration-none">Create Student Account</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/main.js"></script>
    <script>
        function fillCredentials(identifier, pass) {
            document.getElementById('login_identifier').value = identifier;
            document.getElementById('password').value = pass;
        }
    </script>
</body>
</html>
