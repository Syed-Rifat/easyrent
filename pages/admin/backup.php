<?php
// Session check and authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once "../../database/config.php";

// Create necessary directories
$backup_dir = "../../backups";
$logs_dir = "../../logs";

if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

if (!file_exists($logs_dir)) {
    mkdir($logs_dir, 0777, true);
}

// Function to create database backup
function createDatabaseBackup($conn) {
    global $backup_dir, $logs_dir;
    
    $tables = array();
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    $backup = "";
    
    // Get create table statements and data for each table
    foreach ($tables as $table) {
        $result = $conn->query("SHOW CREATE TABLE $table");
        $row = $result->fetch_row();
        $backup .= "\n\n" . $row[1] . ";\n\n";
        
        $result = $conn->query("SELECT * FROM $table");
        while ($row = $result->fetch_assoc()) {
            $backup .= "INSERT INTO $table VALUES(";
            $values = array();
            foreach ($row as $value) {
                $value = addslashes($value);
                $value = str_replace("\n", "\\n", $value);
                $values[] = "'$value'";
            }
            $backup .= implode(",", $values);
            $backup .= ");\n";
        }
    }
    
    // Save backup file
    $backup_file = $backup_dir . "/backup_" . date("Y-m-d_H-i-s") . ".sql";
    file_put_contents($backup_file, $backup);
    
    // Log the backup creation
    $log_message = "[" . date("Y-m-d H:i:s") . "] Backup: Database backup created successfully\n";
    file_put_contents($logs_dir . "/system.log", $log_message, FILE_APPEND);
    
    return $backup_file;
}

// Handle backup request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    try {
        $backup_file = createDatabaseBackup($conn);
        $_SESSION['backup_message'] = '<div class="alert alert-success">Database backup created successfully!</div>';
    } catch (Exception $e) {
        $_SESSION['backup_message'] = '<div class="alert alert-danger">Failed to create backup: ' . $e->getMessage() . '</div>';
        
        // Log the error
        $log_message = "[" . date("Y-m-d H:i:s") . "] Error: Failed to create backup - " . $e->getMessage() . "\n";
        file_put_contents($logs_dir . "/system.log", $log_message, FILE_APPEND);
    }
    header("Location: settings.php");
    exit();
}

// Handle log clearing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    $log_file = $logs_dir . "/system.log";
    if (file_exists($log_file)) {
        // Create a backup of the current log
        $backup_log = $logs_dir . "/system_" . date("Y-m-d_H-i-s") . ".log";
        copy($log_file, $backup_log);
        
        // Clear the log file
        file_put_contents($log_file, "");
        
        // Add a new entry
        $log_message = "[" . date("Y-m-d H:i:s") . "] System: Logs cleared by admin\n";
        file_put_contents($log_file, $log_message);
        
        $_SESSION['backup_message'] = '<div class="alert alert-success">System logs cleared successfully!</div>';
    } else {
        $_SESSION['backup_message'] = '<div class="alert alert-danger">Log file not found!</div>';
    }
    header("Location: settings.php");
    exit();
}

// Handle download request
if (isset($_GET['download'])) {
    $file = $_GET['download'];
    $filepath = $backup_dir . "/" . $file;
    
    if (file_exists($filepath)) {
        // Log the download
        $log_message = "[" . date("Y-m-d H:i:s") . "] Backup: Database backup downloaded - " . $file . "\n";
        file_put_contents($logs_dir . "/system.log", $log_message, FILE_APPEND);
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit();
    }
}

// Get list of existing backups
$backups = array();
if (file_exists($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file != "." && $file != ".." && pathinfo($file, PATHINFO_EXTENSION) == "sql") {
            $backups[] = $file;
        }
    }
    rsort($backups); // Sort backups by date (newest first)
}
?> 