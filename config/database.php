<?php
/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Database Configuration & Connection Handler (PDO)
 * BCA 5th Semester Major Project 1
 */

// Define application root constants
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Set default timezone for academic operations
date_default_timezone_set('Asia/Kolkata');

// Database configuration settings
$db_host = 'localhost';
$db_name = 'smart_campusconnect';
$db_user = 'root';
$db_pass = '';
$db_port = 3306;
$db_charset = 'utf8mb4';

// Detect base URL dynamically for seamless portability (XAMPP htdocs or PHP built-in server)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Calculate relative path from document root
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    
    // Normalize path to application root
    if (strpos($script_dir, '/admin') !== false) {
        $app_path = substr($script_dir, 0, strpos($script_dir, '/admin'));
    } elseif (strpos($script_dir, '/faculty') !== false) {
        $app_path = substr($script_dir, 0, strpos($script_dir, '/faculty'));
    } elseif (strpos($script_dir, '/student') !== false) {
        $app_path = substr($script_dir, 0, strpos($script_dir, '/student'));
    } else {
        $app_path = rtrim($script_dir, '/');
    }
    
    $base_url = rtrim($protocol . $host . $app_path, '/') . '/';
    define('BASE_URL', $base_url);
}

// Establish PDO Connection
try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset={$db_charset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // If running in browser and connection fails, show professional setup guidance
    if (php_sapi_name() !== 'cli') {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Setup Required - Smart CampusConnect</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
            <style>
                body { background: #0f172a; color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; align-items: center; min-height: 100vh; }
                .setup-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); }
                .code-box { background: #090d16; border-radius: 6px; padding: 12px; font-family: monospace; color: #38bdf8; }
            </style>
        </head>
        <body class="p-3">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="setup-card p-4 p-md-5">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-danger bg-opacity-25 p-3 rounded-circle text-danger me-3">
                                    <i class="bi bi-database-exclamation fs-1"></i>
                                </div>
                                <div>
                                    <h3 class="mb-1 text-white">Database Connection Required</h3>
                                    <p class="text-secondary mb-0">Smart CampusConnect could not connect to MySQL server.</p>
                                </div>
                            </div>

                            <div class="alert alert-warning border-0">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <strong>Error Message:</strong> <?= htmlspecialchars($e->getMessage()) ?>
                            </div>

                            <h5 class="text-white mt-4 mb-3">Quick Setup Steps for XAMPP:</h5>
                            <ol class="text-light ps-3">
                                <li class="mb-2">Open the <strong>XAMPP Control Panel</strong> and click <strong>Start</strong> next to <strong>MySQL</strong>.</li>
                                <li class="mb-2">Open your web browser and navigate to <a href="http://localhost/phpmyadmin" target="_blank" class="text-info">http://localhost/phpmyadmin</a>.</li>
                                <li class="mb-2">Create a new database named: <code class="text-warning">smart_campusconnect</code> with collation <code class="text-warning">utf8mb4_unicode_ci</code>.</li>
                                <li class="mb-2">Click <strong>Import</strong> tab and select the file: <br>
                                    <div class="code-box mt-1">smart-campusconnect/database/database.sql</div>
                                </li>
                                <li class="mb-2">Click <strong>Go</strong> to import tables and demo accounts.</li>
                                <li>Refresh this page to load Smart CampusConnect!</li>
                            </ol>

                            <div class="mt-4 pt-3 border-top border-secondary text-center">
                                <a href="" class="btn btn-primary px-4 py-2"><i class="bi bi-arrow-clockwise me-1"></i> Retry Connection</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        throw new PDOException("Database connection error: " . $e->getMessage(), (int)$e->getCode());
    }
}
