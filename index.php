<?php
require_once("utils.php");

// Automatic README updates hourly
function shouldUpdateReadmeAutomatically() {
    $lastRunFile = __DIR__ . "/logs/last_readme_update.txt";
    $oneHourInSeconds = 60 * 60; // 1 hour in seconds
    
    if (!file_exists($lastRunFile)) {
        return true; // First time running
    }
    
    $lastRunTime = file_get_contents($lastRunFile);
    $currentTime = time();
    $timeSinceLastRun = $currentTime - intval($lastRunTime);
    
    return $timeSinceLastRun >= $oneHourInSeconds;
}

function updateLastReadmeUpdateTime() {
    $lastRunFile = __DIR__ . "/logs/last_readme_update.txt";
    file_put_contents($lastRunFile, time());
}

// Check if it's time to update README files automatically
if (shouldUpdateReadmeAutomatically()) {
    logMessage("Automatic README update triggered - 1 hour has passed");
    
    // Define constant to indicate this is an automatic run
    define('AUTO_INCLUDED', true);
    
    // Include the README update logic and call the function
    include_once("update_readme.php");
    $result = updateSelectedProjectReadmes(true);
    
    // Update the last run time
    updateLastReadmeUpdateTime();
    
    if ($result) {
        logMessage("Automatic README update completed successfully");
    } else {
        logMessage("Automatic README update failed");
    }
}

// check GitHub connection
$response = githubApiRequest("GET", "/user");
$isConnected = ($response['status'] === 200);

// Get repositories for selection
$repos = [];
if ($isConnected) {
    $repoResponse = githubApiRequest("GET", "/user/repos?per_page=100&sort=updated&direction=desc");
    if ($repoResponse['status'] === 200) {
        $repos = $repoResponse['body'];
    }
}

// Add a test log entry to verify logging is working
logMessage("Dashboard accessed - GitHub connection status: " . ($isConnected ? "Connected" : "Failed"));

// read logs with better error handling
$logs = "No logs found.";
$logFile = LOG_FILE;

if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    if ($logContent !== false && !empty(trim($logContent))) {
        $logs = $logContent;
    } else {
        $logs = "Log file exists but is empty. No activity recorded yet.";
    }
} else {
    $logs = "Log file not found at: " . $logFile;
}

// handle manual trigger
if (isset($_GET['run']) && $_GET['run'] === 'yes') {
    header("Location: create_project.php");
    exit;
}

// Handle clear logs status message
$clearStatus = "";
$clearMessage = "";
if (isset($_GET['clear']) && isset($_GET['message'])) {
    $clearStatus = $_GET['clear'];
    $clearMessage = urldecode($_GET['message']);
}

// Get next automatic run time
function getNextAutoRunTime() {
    $lastRunFile = __DIR__ . "/logs/last_readme_update.txt";
    if (!file_exists($lastRunFile)) {
        return "Ready to run";
    }
    
    $lastRunTime = intval(file_get_contents($lastRunFile));
    $nextRunTime = $lastRunTime + (60 * 60); // 1 hour later
    $currentTime = time();
    
    if ($currentTime >= $nextRunTime) {
        return "Ready to run";
    }
    
    $timeRemaining = $nextRunTime - $currentTime;
    $hours = floor($timeRemaining / 3600);
    $minutes = floor(($timeRemaining % 3600) / 60);
    
    return "in $hours hours, $minutes minutes";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Project Manager Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #2d3748;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header h1 i {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.8rem;
        }

        .header-subtitle {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 500;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .status-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .status-card.success {
            border-left: 4px solid #48bb78;
        }

        .status-card.error {
            border-left: 4px solid #f56565;
        }

        .status-card.info {
            border-left: 4px solid #4299e1;
        }

        .status-card.warning {
            border-left: 4px solid #ed8936;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .card-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a202c;
        }

        .card-header i {
            font-size: 1.5rem;
            width: 24px;
        }

        .success .card-header i { color: #48bb78; }
        .error .card-header i { color: #f56565; }
        .info .card-header i { color: #4299e1; }
        .warning .card-header i { color: #ed8936; }

        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .status-item:last-child {
            border-bottom: none;
        }

        .status-label {
            font-weight: 500;
            color: #4a5568;
        }

        .status-value {
            font-weight: 600;
            color: #1a202c;
        }

        .status-value.ready {
            color: #48bb78;
        }

        .status-value.connected {
            color: #48bb78;
        }

        .status-value.failed {
            color: #f56565;
        }

        .actions-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn.secondary {
            background: linear-gradient(135deg, #718096, #4a5568);
        }

        .btn.danger {
            background: linear-gradient(135deg, #f56565, #e53e3e);
        }

        .btn.success {
            background: linear-gradient(135deg, #48bb78, #38a169);
        }

        .repositories-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .repo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .repo-card {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .repo-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: #cbd5e0;
        }

        .repo-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2b6cb0;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .repo-name a {
            color: inherit;
            text-decoration: none;
        }

        .repo-name a:hover {
            text-decoration: underline;
        }

        .repo-desc {
            color: #4a5568;
            font-size: 0.9rem;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .repo-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.8rem;
            color: #718096;
        }

        .repo-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .repo-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .repo-actions .btn {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        .logs-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .logs-content {
            background: #1a202c;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 12px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace;
            font-size: 0.85rem;
            line-height: 1.6;
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #2d3748;
            position: relative;
        }

        .logs-content::-webkit-scrollbar {
            width: 8px;
        }

        .logs-content::-webkit-scrollbar-track {
            background: #2d3748;
            border-radius: 4px;
        }

        .logs-content::-webkit-scrollbar-thumb {
            background: #4a5568;
            border-radius: 4px;
        }

        .logs-content::-webkit-scrollbar-thumb:hover {
            background: #718096;
        }

        /* Enhanced Log Styling */
        .logs-content {
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            color: #e2e8f0;
            padding: 25px;
            border-radius: 12px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace;
            font-size: 0.9rem;
            line-height: 1.7;
            max-height: 450px;
            overflow-y: auto;
            border: 1px solid #4a5568;
            position: relative;
            box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        /* Log Entry Styling */
        .logs-content .log-entry {
            margin-bottom: 8px;
            padding: 4px 0;
            border-left: 3px solid transparent;
            padding-left: 12px;
            transition: all 0.2s ease;
        }

        .logs-content .log-entry:hover {
            background: rgba(255, 255, 255, 0.05);
            border-left-color: #667eea;
        }

        .logs-content .log-entry.success {
            border-left-color: #48bb78;
            color: #9ae6b4;
        }

        .logs-content .log-entry.error {
            border-left-color: #f56565;
            color: #feb2b2;
        }

        .logs-content .log-entry.warning {
            border-left-color: #ed8936;
            color: #fbd38d;
        }

        .logs-content .log-entry.info {
            border-left-color: #4299e1;
            color: #90cdf4;
        }

        /* Log Timestamp Styling */
        .logs-content .log-timestamp {
            color: #a0aec0;
            font-weight: 600;
        }

        .logs-content .log-message {
            color: #e2e8f0;
            margin-left: 8px;
        }

        /* Log Status Indicators */
        .logs-content .log-status {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
        }

        .logs-content .log-status.success {
            background: #48bb78;
        }

        .logs-content .log-status.error {
            background: #f56565;
        }

        .logs-content .log-status.warning {
            background: #ed8936;
        }

        .logs-content .log-status.info {
            background: #4299e1;
        }

        /* Empty Logs State */
        .logs-empty {
            text-align: center;
            padding: 40px 20px;
            color: #a0aec0;
            font-style: italic;
        }

        .logs-empty i {
            font-size: 2rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .status-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .repo-grid {
                grid-template-columns: 1fr;
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }

        .status-indicator.connected {
            background: #48bb78;
        }

        .status-indicator.failed {
            background: #f56565;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .stats-highlight {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Header Styles */
        .main-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #1a202c;
        }

        .header-logo h2 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-logo i {
            font-size: 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-nav {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            color: #4a5568;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            transform: translateY(-1px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .header-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: rgba(72, 187, 120, 0.1);
            border-radius: 20px;
            font-size: 0.85rem;
            color: #48bb78;
            font-weight: 500;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #48bb78;
            animation: pulse 2s infinite;
        }

        /* Footer Styles */
        .main-footer {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: 50px;
            padding: 30px 0;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-simple {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-logo i {
            font-size: 1.5rem;
            color: #667eea;
        }

        .footer-logo span {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1a202c;
        }

        .footer-info p {
            color: #4a5568;
            font-size: 0.9rem;
            margin: 0;
        }

        .footer-credits {
            text-align: right;
        }

        .footer-credits p {
            color: #4a5568;
            font-size: 0.9rem;
            margin: 0;
        }

        /* Adjust main container for header */
        .main-container {
            min-height: calc(100vh - 70px - 200px);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                height: auto;
                padding: 15px 20px;
                gap: 15px;
            }

            .header-nav {
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav-link {
                font-size: 0.9rem;
                padding: 6px 12px;
            }

            .footer-simple {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .footer-credits {
                text-align: center;
                align-items: center;
            }
        }

        /* Enhanced Mobile Optimizations */
        @media (max-width: 1024px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .status-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .actions-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 12px;
            }
            
            .repo-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .btn {
                padding: 14px 18px;
                font-size: 1rem;
                min-height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                padding: 15px;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .header p {
                font-size: 0.9rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .actions-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .btn {
                padding: 15px 20px;
                font-size: 1rem;
                justify-content: center;
            }

            .repo-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .repo-card {
                padding: 15px;
            }

            .repo-actions {
                flex-direction: column;
            }

            .repo-actions .btn {
                width: 100%;
                justify-content: center;
                padding: 12px;
            }

            .logs-section {
                padding: 20px;
            }

            .logs-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .logs-content {
                padding: 15px;
                font-size: 0.8rem;
                max-height: 300px;
            }

            .logs-content .log-entry {
                padding: 6px 0;
                padding-left: 8px;
            }

            .logs-content .log-timestamp {
                display: block;
                margin-bottom: 2px;
                font-size: 0.75rem;
            }

            .logs-content .log-message {
                margin-left: 0;
                display: block;
            }

            .footer-simple {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .footer-info {
                text-align: center;
            }

            .footer-credits {
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }

            .header {
                padding: 12px;
            }

            .header h1 {
                font-size: 1.3rem;
            }

            .stats-grid {
                gap: 12px;
            }

            .status-card {
                padding: 15px;
            }

            .actions-section {
                padding: 20px;
            }

            .repositories-section {
                padding: 20px;
            }

            .logs-section {
                padding: 15px;
            }

            .logs-content {
                padding: 12px;
                font-size: 0.75rem;
                max-height: 250px;
            }

            .btn {
                padding: 12px 16px;
                font-size: 0.9rem;
            }
        }

        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .btn:hover {
                transform: none;
            }

            .status-card:hover {
                transform: none;
            }

            .repo-card:hover {
                transform: none;
            }

            .nav-link:hover {
                transform: none;
            }

            /* Increase touch targets */
            .btn {
                min-height: 44px;
            }

            .nav-link {
                min-height: 44px;
            }

            .checkbox-container input[type="checkbox"] {
                width: 20px;
                height: 20px;
            }

            .repo-actions .btn {
                min-height: 40px;
            }
        }

        /* Landscape Mobile Optimizations */
        @media (max-width: 768px) and (orientation: landscape) {
            .header h1 {
                font-size: 1.6rem;
            }

            .status-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .actions-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }

            .repo-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }
        }

        /* Tablet Optimizations */
        @media (min-width: 769px) and (max-width: 1024px) {
            .container {
                padding: 20px;
            }

            .status-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .actions-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }

            .repo-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }

            .header h1 {
                font-size: 2.2rem;
            }
        }

        /* Mobile Optimizations for Selected Repositories */
        @media (max-width: 768px) {
            .repo-card.selected {
                border-width: 3px;
                background: linear-gradient(135deg, #e0e7ff 70%, #f5f3ff 100%);
                box-shadow: 0 6px 20px rgba(79, 70, 229, 0.15);
            }
            
            .repo-card.selected::before {
                top: 8px;
                right: 12px;
                font-size: 0.7rem;
                padding: 3px 8px;
                border-radius: 10px;
            }
        }

        @media (max-width: 480px) {
            .repo-card.selected {
                border-width: 2px;
                background: linear-gradient(135deg, #e0e7ff 80%, #f5f3ff 100%);
                box-shadow: 0 4px 12px rgba(79, 70, 229, 0.12);
            }
            
            .repo-card.selected::before {
                top: 6px;
                right: 10px;
                font-size: 0.65rem;
                padding: 2px 6px;
                border-radius: 8px;
            }
        }

        /* Touch Device Optimizations for Selected State */
        @media (hover: none) and (pointer: coarse) {
            .repo-card.selected {
                border-width: 3px;
                background: linear-gradient(135deg, #e0e7ff 75%, #f5f3ff 100%);
                box-shadow: 0 8px 24px rgba(79, 70, 229, 0.18);
            }
            
            .repo-card.selected::before {
                font-size: 0.75rem;
                padding: 4px 10px;
                border-radius: 12px;
            }
        }

        /* System Message / Alert Styling */
        .system-message {
            width: 100%;
            position: static;
            margin-bottom: 32px;
            z-index: 1;
            box-shadow: 0 4px 24px rgba(0,0,0,0.04);
            border-radius: 18px;
            background: rgba(255,255,255,0.98);
            padding: 28px 32px;
            display: flex;
            align-items: center;
            gap: 18px;
            font-size: 1.15rem;
            font-weight: 500;
            color: #2f855a;
            border: 1.5px solid #c6f6d5;
        }
        .system-message i {
            font-size: 2rem;
            color: #38a169;
        }
        @media (max-width: 768px) {
            .system-message {
                padding: 18px 12px;
                font-size: 1rem;
                margin-bottom: 20px;
            }
            .system-message i {
                font-size: 1.4rem;
            }
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-content">
            <a href="index.php" class="header-logo">
                <i class="fas fa-rocket"></i>
                <h2>GitHub Manager</h2>
            </a>
            
            <nav class="header-nav">
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
                <a href="select_repos.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    Manage Repos
                </a>
                <a href="update_readme.php" class="nav-link">
                    <i class="fas fa-sync"></i>
                    Update READMEs
                </a>
                <a href="create_project.php" class="nav-link">
                    <i class="fas fa-plus"></i>
                    New Project
                </a>
            </nav>
            
            <div class="header-actions">
                <div class="header-status">
                    <span class="status-dot"></span>
                    <?php echo $isConnected ? 'Connected' : 'Disconnected'; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="container">
            <div class="header">
                <h1>
                    <i class="fas fa-rocket"></i>
                    GitHub Project Manager
                </h1>
                <p class="header-subtitle">
                    <i class="fas fa-crown"></i>
                    Enterprise-grade repository management with intelligent automation
                </p>
            </div>

            <?php if (!empty($clearStatus) && !empty($clearMessage)): ?>
            <div class="system-message clearfix">
                <i class="fas fa-<?php echo $clearStatus === 'success' ? 'check-circle' : ($clearStatus === 'error' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                <div>
                    <strong>System Message</strong><br>
                    <?php echo htmlspecialchars($clearMessage); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="status-grid">
                <div class="status-card <?php echo $isConnected ? 'success' : 'error'; ?>">
                    <div class="card-header">
                        <i class="fas fa-<?php echo $isConnected ? 'check-circle' : 'times-circle'; ?>"></i>
                        <h3>GitHub Connection</h3>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Status</span>
                        <span class="status-value <?php echo $isConnected ? 'connected' : 'failed'; ?>">
                            <span class="status-indicator <?php echo $isConnected ? 'connected' : 'failed'; ?>"></span>
                            <?php echo $isConnected ? 'Connected' : 'Failed'; ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">API Response</span>
                        <span class="status-value"><?php echo $response['status']; ?></span>
                    </div>
                </div>

                <div class="status-card info">
                    <div class="card-header">
                        <i class="fas fa-sync-alt"></i>
                        <h3>Automatic Updates</h3>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Status</span>
                        <span class="status-value ready">
                            <span class="status-indicator connected"></span>
                            Active
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Schedule</span>
                        <span class="status-value">Every 1 hour</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Next Update</span>
                        <span class="status-value"><?php echo getNextAutoRunTime(); ?></span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Last Update</span>
                        <span class="status-value"><?php echo file_exists(__DIR__ . "/logs/last_readme_update.txt") ? date('Y-m-d H:i:s', intval(file_get_contents(__DIR__ . "/logs/last_readme_update.txt"))) : 'Never'; ?></span>
                    </div>
                </div>

                <div class="status-card warning">
                    <div class="card-header">
                        <i class="fas fa-repository"></i>
                        <h3>Repository Status</h3>
                    </div>
                    <?php 
                    $selectedRepos = [];
                    $selectedFile = __DIR__ . "/logs/selected_repos.txt";
                    if (file_exists($selectedFile)) {
                        $data = file_get_contents($selectedFile);
                        $selectedRepos = json_decode($data, true) ?: [];
                    }
                    ?>
                    <div class="status-item">
                        <span class="status-label">Total Repositories</span>
                        <span class="status-value">
                            <span class="stats-highlight"><?php echo count($repos); ?></span>
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Selected for Updates</span>
                        <span class="status-value">
                            <span class="stats-highlight"><?php echo count($selectedRepos); ?></span>
                        </span>
                    </div>
                    <?php if (!empty($selectedRepos)): ?>
                    <div class="status-item">
                        <span class="status-label">Selected Repos</span>
                        <span class="status-value"><?php echo implode(', ', array_slice($selectedRepos, 0, 2)); ?><?php echo count($selectedRepos) > 2 ? ' +' . (count($selectedRepos) - 2) . ' more' : ''; ?></span>
                    </div>
                    <?php endif; ?>
                </div>
    </div>

            <div class="actions-section">
                <div class="card-header">
                    <i class="fas fa-tools"></i>
                    <h3>Quick Actions</h3>
                </div>
                <div class="actions-grid">
                    <a href="?run=yes" class="btn">
                        <i class="fas fa-plus"></i>
                        Create New Project
                    </a>
                    <a href="update_readme.php" class="btn success">
                        <i class="fas fa-sync"></i>
                        Update Selected READMEs
                    </a>
                    <a href="select_repos.php" class="btn secondary">
                        <i class="fas fa-cog"></i>
                        Manage Repository Selection
                    </a>
                    <a href="clear_logs.php" class="btn danger">
                        <i class="fas fa-trash"></i>
                        Clear Logs
                    </a>
                </div>
            </div>

            <?php if (!empty($repos)): ?>
            <div class="repositories-section">
                <div class="card-header">
                    <i class="fas fa-folder"></i>
                    <h3>Your Repositories</h3>
                </div>
                <div class="repo-grid">
                    <?php foreach (array_slice($repos, 0, 12) as $repo): ?>
                    <div class="repo-card">
                        <div class="repo-name">
                            <i class="fas fa-<?php echo $repo['private'] ? 'lock' : 'globe'; ?>"></i>
                            <a href="https://github.com/<?php echo GITHUB_OWNER; ?>/<?php echo rawurlencode($repo['name']); ?>" target="_blank">
                                <?php echo htmlspecialchars($repo['name']); ?>
                            </a>
                        </div>
                        <div class="repo-desc">
                            <?php echo htmlspecialchars($repo['description'] ?: 'No description available'); ?>
                        </div>
                        <div class="repo-meta">
                            <span><i class="fas fa-calendar"></i> <?php echo date('M Y', strtotime($repo['created_at'])); ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo date('M d', strtotime($repo['updated_at'])); ?></span>
                            <span><i class="fas fa-<?php echo $repo['private'] ? 'lock' : 'unlock'; ?>"></i> <?php echo $repo['private'] ? 'Private' : 'Public'; ?></span>
                        </div>
                        <div class="repo-actions">
                            <a href="update_single_readme.php?repo=<?php echo urlencode($repo['name']); ?>" class="btn success">
                                <i class="fas fa-edit"></i>
                                Update README
                            </a>
                            <a href="https://github.com/<?php echo GITHUB_OWNER; ?>/<?php echo rawurlencode($repo['name']); ?>" target="_blank" class="btn secondary">
                                <i class="fas fa-external-link-alt"></i>
                                View
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($repos) > 12): ?>
                <div style="text-align: center; margin-top: 20px;">
                    <p style="color: #718096;">Showing 12 of <?php echo count($repos); ?> repositories</p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="logs-section">
                <div class="logs-header">
                    <div class="card-header">
                        <i class="fas fa-terminal"></i>
                        <h3>System Logs</h3>
                    </div>
                    <div class="repo-actions">
                        <a href="clear_logs.php" class="btn danger">
                            <i class="fas fa-trash"></i>
                            Clear Logs
                        </a>
                    </div>
                </div>
                <div class="logs-content">
                    <?php 
                    if (trim($logs) === "No logs found." || trim($logs) === "Log file exists but is empty. No activity recorded yet." || strpos($logs, "Log file not found") !== false) {
                        echo '<div class="logs-empty">';
                        echo '<i class="fas fa-file-alt"></i>';
                        echo '<p>No logs available yet</p>';
                        echo '<p style="font-size: 0.8rem; margin-top: 10px;">System activity will appear here once operations are performed</p>';
                        echo '</div>';
                    } else {
                        $logLines = explode("\n", trim($logs));
                        $logLines = array_reverse($logLines); // Show newest first
                        
                        foreach ($logLines as $line) {
                            if (trim($line) === '') continue;
                            
                            // Parse log line
                            if (preg_match('/^\[(.*?)\]\s*(.*)$/', $line, $matches)) {
                                $timestamp = $matches[1];
                                $message = $matches[2];
                                
                                // Determine log type
                                $logClass = 'info';
                                $statusClass = 'info';
                                
                                if (strpos($message, '✅') !== false || strpos($message, 'successfully') !== false) {
                                    $logClass = 'success';
                                    $statusClass = 'success';
                                } elseif (strpos($message, '❌') !== false || strpos($message, 'Failed') !== false || strpos($message, 'ERROR') !== false) {
                                    $logClass = 'error';
                                    $statusClass = 'error';
                                } elseif (strpos($message, '⚠️') !== false || strpos($message, 'Warning') !== false) {
                                    $logClass = 'warning';
                                    $statusClass = 'warning';
                                }
                                
                                echo '<div class="log-entry ' . $logClass . '">';
                                echo '<span class="log-status ' . $statusClass . '"></span>';
                                echo '<span class="log-timestamp">[' . htmlspecialchars($timestamp) . ']</span>';
                                echo '<span class="log-message">' . htmlspecialchars($message) . '</span>';
                                echo '</div>';
                            } else {
                                // Fallback for lines that don't match the pattern
                                echo '<div class="log-entry info">';
                                echo '<span class="log-status info"></span>';
                                echo '<span class="log-message">' . htmlspecialchars($line) . '</span>';
                                echo '</div>';
                            }
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-content">
            <div class="footer-simple">
                <div class="footer-info">
                    <div class="footer-logo">
                        <i class="fas fa-rocket"></i>
                        <span>GitHub Project Manager</span>
                    </div>
                    <p>Professional repository management with intelligent automation</p>
                </div>
                <div class="footer-credits">
                    <p>&copy; <?php echo date('Y'); ?> Developed by <strong>Utkarsh Singh</strong></p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
