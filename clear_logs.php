<?php
// All date/time operations in this file use IST (Asia/Kolkata) as set in utils.php
require_once("utils.php");

// Clear the log file
$logFile = LOG_FILE;
$success = false;
$message = "";

if (file_exists($logFile)) {
    // Clear the log file by writing an empty string
    if (file_put_contents($logFile, "") !== false) {
        $success = true;
        $message = "Log file cleared successfully.";
        logMessage("Log file cleared by user");
    } else {
        $message = "Failed to clear log file. Check file permissions.";
    }
} else {
    $message = "Log file does not exist.";
}

// Redirect back to dashboard with status
$status = $success ? "success" : "error";
header("Location: index.php?clear=$status&message=" . urlencode($message));
exit;
?> 