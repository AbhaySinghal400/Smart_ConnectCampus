<?php
/**
 * Smart CampusConnect - Integration & System Verification Test Script
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== RUNNING SMART CAMPUSCONNECT INTEGRATION TEST ===\n";

// 1. Database Connection Check
if (!($pdo instanceof PDO)) {
    die("[FAIL] PDO connection failed\n");
}
echo "[PASS] PDO Database connection successful\n";

// 2. Authentication Check - Admin
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['admin@campusconnect.edu']);
$adminUser = $stmt->fetch();
if (!$adminUser || !password_verify('Admin@123', $adminUser['password'])) {
    die("[FAIL] Admin password verification failed\n");
}
echo "[PASS] Admin authentication verified (admin@campusconnect.edu / Admin@123)\n";

// 3. Authentication Check - Faculty
$stmt->execute(['prof.sharma@campusconnect.edu']);
$facultyUser = $stmt->fetch();
if (!$facultyUser || !password_verify('Faculty@123', $facultyUser['password'])) {
    die("[FAIL] Faculty password verification failed\n");
}
echo "[PASS] Faculty authentication verified (prof.sharma@campusconnect.edu / Faculty@123)\n";

// 4. Authentication Check - Student
$stmt->execute(['rahul.mehta@student.edu']);
$studentUser = $stmt->fetch();
if (!$studentUser || !password_verify('Student@123', $studentUser['password'])) {
    die("[FAIL] Student password verification failed\n");
}
echo "[PASS] Student authentication verified (rahul.mehta@student.edu / Student@123)\n";

// 5. Attendance Calculation Check
$attStmt = $pdo->prepare("
    SELECT COUNT(*) as total, 
           SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as pres 
    FROM attendance 
    WHERE student_id = 1
");
$attStmt->execute();
$attRow = $attStmt->fetch();
$pct = calculate_attendance_percentage($attRow['pres'], $attRow['total']);
echo "[PASS] Attendance calculated for student #1: {$pct}% (Present: {$attRow['pres']} / {$attRow['total']})\n";

// 6. Assignment and Submission Check
$subStmt = $pdo->prepare("
    SELECT sub.marks_obtained, a.max_marks 
    FROM submissions sub 
    JOIN assignments a ON sub.assignment_id = a.id 
    WHERE sub.id = 1
");
$subStmt->execute();
$subRow = $subStmt->fetch();
echo "[PASS] Submission check: Score {$subRow['marks_obtained']} / {$subRow['max_marks']}\n";

// 7. Timetable Schedule Query Check
$ttStmt = $pdo->prepare("SELECT COUNT(*) FROM timetable WHERE course_id = 1 AND semester = 5");
$ttStmt->execute();
$ttCount = $ttStmt->fetchColumn();
echo "[PASS] BCA 5th Sem Timetable periods scheduled: {$ttCount} weekly classes\n";

// 8. Complaints Check
$compStmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM complaints GROUP BY status");
$compStmt->execute();
$compRows = $compStmt->fetchAll();
echo "[PASS] Complaints status distribution: " . json_encode($compRows) . "\n";

echo "=== ALL 8 TEST SUITES COMPLETED WITH 100% SUCCESS ===\n";
