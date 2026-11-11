<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Student Academic Profile & Security Settings
 * BCA 5th Semester Major Project 1
 */

$page_title = 'My Profile';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('student');

$studentId = $currentUser['student_id'] ?? 0;
$userId = current_user_id();

// 1. Handle Update Profile Info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (verify_csrf()) {
        $phone   = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');

        try {
            $stmt = $pdo->prepare("UPDATE students SET phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$phone, $address, $studentId]);
            set_flash('success', 'Profile contact details updated successfully.');
        } catch (Exception $e) {
            set_flash('danger', 'Error updating profile: ' . $e->getMessage());
        }
    }
    header('Location: ' . BASE_URL . 'student/profile.php');
    exit;
}

// 2. Handle Change Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (verify_csrf()) {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass     = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
            set_flash('danger', 'Please fill in all password fields.');
        } elseif (strlen($new_pass) < 6) {
            set_flash('danger', 'New password must be at least 6 characters long.');
        } elseif ($new_pass !== $confirm_pass) {
            set_flash('danger', 'New passwords do not match. Please re-enter.');
        } else {
            try {
                // Verify current password
                $userStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $userStmt->execute([$userId]);
                $userRow = $userStmt->fetch();

                if ($userRow && password_verify($current_pass, $userRow['password'])) {
                    $newHash = password_hash($new_pass, PASSWORD_BCRYPT);
                    $upStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upStmt->execute([$newHash, $userId]);
                    set_flash('success', 'Password changed successfully! Keep your new credentials secure.');
                } else {
                    set_flash('danger', 'Current password entered is incorrect.');
                }
            } catch (Exception $e) {
                set_flash('danger', 'Error changing password: ' . $e->getMessage());
            }
        }
    }
    header('Location: ' . BASE_URL . 'student/profile.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <div class="page-content">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Academic Profile &amp; Account</h3>
                <p class="text-muted small mb-0">Review university registration records and update security preferences</p>
            </div>
        </div>

        <?php display_flash(); ?>

        <div class="row g-4">
            <!-- Left Profile Badge Card -->
            <div class="col-lg-4">
                <div class="app-card text-center p-4">
                    <div class="mx-auto mb-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?= strtoupper(substr($currentUser['first_name'], 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold text-dark mb-1"><?= e($currentUser['full_name']) ?></h5>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-3 py-1 mb-2">
                        <?= e($currentUser['enrollment_no']) ?>
                    </span>
                    <p class="text-muted small mb-3"><?= e($currentUser['course_name']) ?> &bull; Sem <?= e($currentUser['current_semester']) ?></p>

                    <div class="border-top pt-3 text-start small">
                        <div class="d-flex justify-content-between py-1.5 border-bottom">
                            <span class="text-muted">Username:</span>
                            <span class="fw-semibold text-dark"><?= e($currentUser['username']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1.5 border-bottom">
                            <span class="text-muted">Department:</span>
                            <span class="fw-semibold text-dark"><?= e($currentUser['dept_name']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1.5 border-bottom">
                            <span class="text-muted">Session:</span>
                            <span class="fw-semibold text-dark"><?= e($currentUser['academic_year']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1.5">
                            <span class="text-muted">Account Status:</span>
                            <span><?= get_status_badge($currentUser['status']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Profile Information & Password Form -->
            <div class="col-lg-8">
                <!-- Academic Credentials Details -->
                <div class="app-card mb-4">
                    <div class="app-card-header">
                        <h6 class="app-card-title"><i class="bi bi-person-lines-fill text-primary"></i> Academic Details</h6>
                    </div>
                    <div class="p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-muted d-block">Full Legal Name</label>
                                <div class="fw-semibold text-dark"><?= e($currentUser['full_name']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted d-block">Enrolment Number</label>
                                <div class="fw-semibold text-dark font-monospace"><?= e($currentUser['enrollment_no']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted d-block">Official University Email</label>
                                <div class="fw-semibold text-dark"><?= e($currentUser['email']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted d-block">Gender &bull; Date of Birth</label>
                                <div class="fw-semibold text-dark"><?= e($currentUser['gender']) ?> &bull; <?= format_date($currentUser['dob']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted d-block">Degree Course Program</label>
                                <div class="fw-semibold text-dark"><?= e($currentUser['course_name']) ?> (<?= e($currentUser['course_code']) ?>)</div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted d-block">Current Semester</label>
                                <div class="fw-semibold text-dark">Semester <?= e($currentUser['current_semester']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Contact Information Form -->
                <div class="app-card mb-4">
                    <div class="app-card-header">
                        <h6 class="app-card-title"><i class="bi bi-pencil-square text-teal"></i> Update Contact Details</h6>
                    </div>
                    <div class="p-4">
                        <form method="POST" action="profile.php">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="update_profile">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Mobile Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" value="<?= e($currentUser['phone']) ?>" placeholder="+91 9876543210">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Residential / Hostel Address</label>
                                    <input type="text" name="address" class="form-control" value="<?= e($currentUser['address']) ?>" placeholder="Campus Hostel Room / City Address">
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-teal px-4 text-white">Save Contact Info</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password Form -->
                <div class="app-card">
                    <div class="app-card-header">
                        <h6 class="app-card-title"><i class="bi bi-shield-lock text-danger"></i> Change Account Password</h6>
                    </div>
                    <div class="p-4">
                        <form method="POST" action="profile.php">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="change_password">

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold">Current Password *</label>
                                    <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">New Password (Min 6 chars) *</label>
                                    <input type="password" name="new_password" class="form-control" required placeholder="New password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Confirm New Password *</label>
                                    <input type="password" name="confirm_password" class="form-control" required placeholder="Confirm new password">
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-primary px-4">Update Password</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
