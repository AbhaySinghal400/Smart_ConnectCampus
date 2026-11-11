<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Common Helper Functions & Utilities
 * BCA 5th Semester Major Project 1
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

/**
 * Escape HTML output to prevent Cross-Site Scripting (XSS)
 *
 * @param mixed $string
 * @return string
 */
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize text inputs
 *
 * @param string $data
 * @return string
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Set a session-based flash message
 *
 * @param string $type success|danger|warning|info
 * @param string $message
 */
function set_flash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

/**
 * Display the flash message if present and clear it
 */
function display_flash() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'] ?? 'info';
        $message = $_SESSION['flash_message'];
        
        $icons = [
            'success' => 'bi-check-circle-fill',
            'danger'  => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            'info'    => 'bi-info-circle-fill'
        ];
        $icon = $icons[$type] ?? 'bi-info-circle-fill';

        echo '<div class="alert alert-' . e($type) . ' alert-dismissible fade show d-flex align-items-center shadow-sm mb-4" role="alert">';
        echo '  <i class="bi ' . $icon . ' me-2 fs-5 flex-shrink-0"></i>';
        echo '  <div class="flex-grow-1">' . e($message) . '</div>';
        echo '  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';

        unset($_SESSION['flash_type'], $_SESSION['flash_message']);
    }
}

/**
 * Format date for friendly human reading
 */
function format_date($date, $format = 'd M Y') {
    if (empty($date) || $date === '0000-00-00') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime for friendly human reading
 */
function format_datetime($datetime, $format = 'd M Y, h:i A') {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    return date($format, strtotime($datetime));
}

/**
 * Format time (HH:MM:SS to 12-hour AM/PM)
 */
function format_time($time) {
    if (empty($time)) return 'N/A';
    return date('h:i A', strtotime($time));
}

/**
 * Human readable relative time (e.g., 2 hours ago)
 */
function time_ago($datetime) {
    if (empty($datetime)) return 'N/A';
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = round($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = round($diff / 3600);
        return $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = round($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('d M Y', $time);
    }
}

/**
 * Calculate attendance percentage
 */
function calculate_attendance_percentage($present, $total) {
    if ($total <= 0) return 0;
    return round(($present / $total) * 100, 1);
}

/**
 * Render visual badge for complaint and assignment statuses
 */
function get_status_badge($status) {
    switch (strtolower(trim($status))) {
        case 'present':
        case 'resolved':
        case 'graded':
        case 'active':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5"><i class="bi bi-check2-circle me-1"></i>' . e($status) . '</span>';
        
        case 'in progress':
        case 'submitted':
        case 'late':
            return '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1.5"><i class="bi bi-clock-history me-1"></i>' . e($status) . '</span>';
            
        case 'pending':
        case 'absent':
        case 'inactive':
        case 'resubmission':
            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5"><i class="bi bi-exclamation-circle me-1"></i>' . e($status) . '</span>';

        default:
            return '<span class="badge bg-secondary-subtle text-secondary px-2.5 py-1.5">' . e($status) . '</span>';
    }
}

/**
 * Handle secure file upload
 *
 * @param array $file $_FILES['input_name']
 * @param string $subfolder Subfolder inside uploads/
 * @param array $allowed_extensions
 * @param int $max_size_mb
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function handle_file_upload($file, $subfolder = 'assignments', $allowed_extensions = ['pdf', 'doc', 'docx', 'zip', 'png', 'jpg', 'jpeg'], $max_size_mb = 10) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
        ];
        $msg = $error_messages[$file['error']] ?? 'Unknown upload error occurred.';
        return ['success' => false, 'error' => $msg];
    }

    $file_size = $file['size'];
    $file_tmp  = $file['tmp_name'];
    $file_name = basename($file['name']);
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Size check
    if ($file_size > ($max_size_mb * 1024 * 1024)) {
        return ['success' => false, 'error' => "File size cannot exceed {$max_size_mb}MB."];
    }

    // Extension check
    if (!in_array($file_ext, $allowed_extensions)) {
        return ['success' => false, 'error' => "Invalid file format. Allowed: " . implode(', ', $allowed_extensions)];
    }

    // Generate safe unique filename
    $target_dir = APP_ROOT . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $subfolder;
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $unique_name = time() . '_' . bin2hex(random_bytes(6)) . '.' . $file_ext;
    $target_path = $target_dir . DIRECTORY_SEPARATOR . $unique_name;

    if (move_uploaded_file($file_tmp, $target_path)) {
        return ['success' => true, 'filename' => $unique_name];
    } else {
        return ['success' => false, 'error' => 'Failed to move uploaded file. Please check folder permissions.'];
    }
}
