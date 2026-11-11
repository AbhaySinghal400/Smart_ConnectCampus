<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Student Self-Registration Module
 * BCA 5th Semester Major Project 1
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'student/dashboard.php');
    exit;
}

$error = '';
$success = '';

// Fetch all available courses for dropdown
try {
    $coursesStmt = $pdo->query("SELECT id, course_name, course_code, total_semesters FROM courses ORDER BY course_name ASC");
    $courses = $coursesStmt->fetchAll();
} catch (Exception $e) {
    $courses = [];
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $first_name       = sanitize($_POST['first_name'] ?? '');
        $last_name        = sanitize($_POST['last_name'] ?? '');
        $username         = strtolower(sanitize($_POST['username'] ?? ''));
        $enrollment_no    = strtoupper(sanitize($_POST['enrollment_no'] ?? ''));
        $email            = strtolower(sanitize($_POST['email'] ?? ''));
        $phone            = sanitize($_POST['phone'] ?? '');
        $gender           = sanitize($_POST['gender'] ?? 'Male');
        $dob              = sanitize($_POST['dob'] ?? '');
        $course_id        = (int)($_POST['course_id'] ?? 0);
        $current_semester = (int)($_POST['current_semester'] ?? 1);
        $academic_year    = sanitize($_POST['academic_year'] ?? '2026-2027');
        $address          = sanitize($_POST['address'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($first_name) || empty($last_name) || empty($username) || empty($enrollment_no) || empty($email) || empty($password)) {
            $error = 'Please fill in all mandatory fields marked with an asterisk (*).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match. Please re-enter carefully.';
        } elseif ($course_id <= 0) {
            $error = 'Please select a valid academic course program.';
        } else {
            try {
                // Check if email, username, or enrollment number already exists
                $checkUser = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                $checkUser->execute([$email, $username]);
                if ($checkUser->rowCount() > 0) {
                    $error = 'A user account with this email address or username already exists.';
                } else {
                    $checkEnroll = $pdo->prepare("SELECT id FROM students WHERE enrollment_no = ?");
                    $checkEnroll->execute([$enrollment_no]);
                    if ($checkEnroll->rowCount() > 0) {
                        $error = 'This enrollment number is already registered in the university records.';
                    } else {
                        // Start Database Transaction for Atomic Multi-Table Insertion
                        $pdo->beginTransaction();

                        // 1. Insert into users table with password_hash
                        $password_hash = password_hash($password, PASSWORD_BCRYPT);
                        $userStmt = $pdo->prepare("
                            INSERT INTO users (username, email, password, role, status) 
                            VALUES (?, ?, ?, 'student', 'active')
                        ");
                        $userStmt->execute([$username, $email, $password_hash]);
                        $new_user_id = $pdo->lastInsertId();

                        // 2. Insert into students table
                        $studentStmt = $pdo->prepare("
                            INSERT INTO students (user_id, enrollment_no, first_name, last_name, email, phone, gender, dob, course_id, current_semester, academic_year, address, profile_pic)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'default_student.png')
                        ");
                        $studentStmt->execute([
                            $new_user_id, $enrollment_no, $first_name, $last_name, $email,
                            $phone, $gender, !empty($dob) ? $dob : null, $course_id,
                            $current_semester, $academic_year, $address
                        ]);

                        $pdo->commit();

                        set_flash('success', 'Registration successful! You may now sign in to your student dashboard.');
                        header('Location: ' . BASE_URL . 'login.php');
                        exit;
                    }
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Registration failed: ' . $e->getMessage();
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
    <title>Student Registration | Smart CampusConnect</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="auth-body">

    <div class="auth-card my-4" style="max-width: 960px;">
        <div class="p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Student Account Enrolment</h3>
                    <p class="text-muted small mb-0">Join Smart CampusConnect with your university registration details</p>
                </div>
                <a href="<?= BASE_URL ?>login.php" class="btn btn-outline-primary btn-sm rounded-pill">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Already Registered? Sign In
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div><?= e($error) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <?php csrf_field(); ?>

                <h6 class="fw-bold text-primary text-uppercase mb-3" style="letter-spacing: 0.5px; font-size: 0.8rem;">
                    1. Personal Information
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="first_name" required placeholder="e.g. Rahul" value="<?= e($_POST['first_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="last_name" required placeholder="e.g. Sharma" value="<?= e($_POST['last_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Gender <span class="text-danger">*</span></label>
                        <select class="form-select" name="gender" required>
                            <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= (($_POST['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Date of Birth</label>
                        <input type="date" class="form-control" name="dob" value="<?= e($_POST['dob'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Mobile Number</label>
                        <input type="tel" class="form-control" name="phone" placeholder="+91 9876543210" value="<?= e($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Residential Address</label>
                        <input type="text" class="form-control" name="address" placeholder="City, Campus Hostel / Flat" value="<?= e($_POST['address'] ?? '') ?>">
                    </div>
                </div>

                <h6 class="fw-bold text-primary text-uppercase mb-3" style="letter-spacing: 0.5px; font-size: 0.8rem;">
                    2. Academic Details
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Enrollment No. <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="enrollment_no" required placeholder="e.g. EN2024BCA101" value="<?= e($_POST['enrollment_no'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Course / Degree Program <span class="text-danger">*</span></label>
                        <select class="form-select" name="course_id" required>
                            <option value="">-- Choose Program --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= (($_POST['course_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                                    <?= e($c['course_name']) ?> (<?= e($c['course_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-dark">Current Sem <span class="text-danger">*</span></label>
                        <select class="form-select" name="current_semester" required>
                            <?php for ($s = 1; $s <= 8; $s++): ?>
                                <option value="<?= $s ?>" <?= (($_POST['current_semester'] ?? 1) == $s) ? 'selected' : '' ?>>Sem <?= $s ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-dark">Academic Year</label>
                        <input type="text" class="form-control" name="academic_year" value="2026-2027" readonly>
                    </div>
                </div>

                <h6 class="fw-bold text-primary text-uppercase mb-3" style="letter-spacing: 0.5px; font-size: 0.8rem;">
                    3. Account Credentials
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required placeholder="name@student.edu" value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" required placeholder="Choose a username (e.g. bca_rahul)" value="<?= e($_POST['username'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control border-end-0" id="reg_pass" name="password" required placeholder="Min 6 characters">
                            <button class="btn btn-outline-secondary border-start-0 bg-white toggle-password-btn" type="button" data-target="reg_pass">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control border-end-0" id="reg_cpass" name="confirm_password" required placeholder="Confirm password">
                            <button class="btn btn-outline-secondary border-start-0 bg-white toggle-password-btn" type="button" data-target="reg_cpass">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="termsCheck" required>
                    <label class="form-check-label text-muted small" for="termsCheck">
                        I certify that the information entered above is accurate and complies with university guidelines.
                    </label>
                </div>

                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= BASE_URL ?>login.php" class="btn btn-light px-4 py-2 border">Cancel</a>
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> Register Student Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
