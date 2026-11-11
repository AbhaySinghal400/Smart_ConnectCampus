<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Authentication and Access Control Layer
 * BCA 5th Semester Major Project 1
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configure session security parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if a user is currently authenticated
 *
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Return current user role ('admin', 'faculty', 'student')
 *
 * @return string|null
 */
function current_user_role() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Return current user primary key ID from users table
 *
 * @return int|null
 */
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Enforce authentication for protected pages
 */
function require_login() {
    if (!is_logged_in()) {
        set_flash('warning', 'Please sign in to access your portal.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/**
 * Enforce role-based authorization
 *
 * @param array|string $allowed_roles Role or array of allowed roles
 */
function require_role($allowed_roles) {
    require_login();

    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    $role = current_user_role();
    if (!in_array($role, $allowed_roles)) {
        set_flash('danger', 'Access denied: You do not have permission to view that page.');
        
        // Redirect to their respective authorized home
        if ($role === 'admin') {
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
        } elseif ($role === 'faculty') {
            header('Location: ' . BASE_URL . 'faculty/dashboard.php');
        } elseif ($role === 'student') {
            header('Location: ' . BASE_URL . 'student/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . 'login.php');
        }
        exit;
    }
}

/**
 * Get comprehensive logged-in user profile details based on role
 *
 * @param PDO $pdo
 * @return array|null
 */
function get_logged_in_user($pdo) {
    if (!is_logged_in()) return null;

    $user_id = current_user_id();
    $role = current_user_role();

    if ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT id, username, email, role, status, created_at, 'Administrator' as full_name, 'Campus Administrator' as designation, 'default_avatar.png' as profile_pic FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } elseif ($role === 'faculty') {
        $stmt = $pdo->prepare("
            SELECT u.id as user_id, u.username, u.email, u.role, u.status,
                   f.id as faculty_id, f.employee_id, f.first_name, f.last_name,
                   CONCAT(f.first_name, ' ', f.last_name) as full_name,
                   f.phone, f.designation, f.qualification, f.profile_pic,
                   d.id as department_id, d.dept_name, d.dept_code
            FROM users u
            JOIN faculty f ON u.id = f.user_id
            JOIN departments d ON f.department_id = d.id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } elseif ($role === 'student') {
        $stmt = $pdo->prepare("
            SELECT u.id as user_id, u.username, u.email, u.role, u.status,
                   s.id as student_id, s.enrollment_no, s.first_name, s.last_name,
                   CONCAT(s.first_name, ' ', s.last_name) as full_name,
                   s.phone, s.gender, s.dob, s.current_semester, s.academic_year, s.address, s.profile_pic,
                   c.id as course_id, c.course_name, c.course_code,
                   d.id as department_id, d.dept_name
            FROM users u
            JOIN students s ON u.id = s.user_id
            JOIN courses c ON s.course_id = c.id
            JOIN departments d ON c.dept_id = d.id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }

    return null;
}

/**
 * Generate and retrieve CSRF token
 *
 * @return string
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden CSRF input tag
 */
function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify CSRF token from POST
 *
 * @return bool
 */
function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            set_flash('danger', 'Security validation failed (Invalid CSRF token). Please try again.');
            return false;
        }
    }
    return true;
}
