<?php
// All date/time operations in this file use IST (Asia/Kolkata) as set in utils.php
require_once("utils.php");

// Get all repositories
$response = githubApiRequest("GET", "/user/repos?per_page=100&sort=updated&direction=desc");

if ($response['status'] !== 200) {
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Repository Selection Error</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} .button{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;}</style>";
    echo "</head><body>";
    echo "<h1>❌ Repository Selection Error</h1>";
    echo "<div class='error'>Unable to load repositories. Please check your GitHub connection.</div>";
    echo "<a href='index.php' class='button'>← Back to Dashboard</a>";
    echo "</body></html>";
    exit;
}

$repos = $response['body'];
$currentlySelected = loadSelectedRepositories();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_repos'])) {
    $selectedRepos = $_POST['selected_repos'];
    
    // Save the selection
    saveSelectedRepositories($selectedRepos);
    
    if (!empty($selectedRepos)) {
        logMessage("Manual README update for selected repositories: " . implode(', ', $selectedRepos));
        
        // Include the README update logic
        include_once("update_readme.php");
        
        $updatedCount = 0;
        $failedCount = 0;
        $results = [];
        
        foreach ($selectedRepos as $repoName) {
            if (updateProjectReadme($repoName, false)) {
                $updatedCount++;
                $results[] = "✅ $repoName - Updated successfully";
            } else {
                $failedCount++;
                $results[] = "❌ $repoName - Update failed";
            }
        }
        
        logMessage("Selected repository update completed. Updated: $updatedCount, Failed: $failedCount");
        
        // Show results
        echo "<!DOCTYPE html>";
        echo "<html><head><title>Repository Update Results</title>";
        echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{background:#d4edda;padding:15px;border-radius:5px;margin:10px 0;} .error{background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} .info{background:#f8f9fa;padding:15px;border-radius:5px;margin:10px 0;} .button{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;} pre{background:#fff;padding:10px;border:1px solid #ccc;}</style>";
        echo "</head><body>";
        
        echo "<h1>📝 Selected Repository Update Results</h1>";
        
        echo "<div class='success'>";
        echo "<h3>✅ Update Summary</h3>";
        echo "<strong>Repositories selected:</strong> " . count($selectedRepos) . "<br>";
        echo "<strong>Successfully updated:</strong> $updatedCount<br>";
        echo "<strong>Failed to update:</strong> $failedCount<br>";
        echo "<strong>Update time:</strong> " . date('Y-m-d H:i:s') . "<br>";
        echo "<strong>Selection saved:</strong> ✅ These repositories will be updated automatically hourly<br>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h3>📋 Detailed Results:</h3>";
        echo "<pre>" . implode("\n", $results) . "</pre>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h3>Actions:</h3>";
        echo "<a href='select_repos.php' class='button'>Select More Repositories</a>";
        echo "<a href='index.php' class='button'>← Back to Dashboard</a>";
        echo "</div>";
        
        echo "</body></html>";
        exit;
    } else {
        // Clear selection
        saveSelectedRepositories([]);
        echo "<!DOCTYPE html>";
        echo "<html><head><title>Selection Cleared</title>";
        echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .info{background:#d1ecf1;padding:15px;border-radius:5px;margin:10px 0;} .button{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;}</style>";
        echo "</head><body>";
        echo "<h1>🗑️ Selection Cleared</h1>";
        echo "<div class='info'>";
        echo "<h3>No repositories selected</h3>";
        echo "<p>All repositories have been deselected. No automatic updates will occur.</p>";
        echo "<p>You can select repositories again to enable automatic hourly updates.</p>";
        echo "</div>";
        echo "<div class='info'>";
        echo "<h3>Actions:</h3>";
        echo "<a href='select_repos.php' class='button'>Select Repositories</a>";
        echo "<a href='index.php' class='button'>← Back to Dashboard</a>";
        echo "</div>";
        echo "</body></html>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repository Selection - GitHub Project Manager</title>
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
            font-size: 1.1rem;
        }

        .status-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
            color: #ed8936;
        }

        .selection-info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .selection-info h3 {
            color: #856404;
            margin-bottom: 10px;
        }

        .selection-info p {
            color: #856404;
            margin-bottom: 5px;
        }

        .selection-info .stats-highlight {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .select-all-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .select-all-checkbox {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .select-all-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #667eea;
        }

        .select-all-checkbox label {
            font-weight: 600;
            font-size: 1.1rem;
            color: #1a202c;
        }

        .repo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .repo-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .repo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .repo-card.selected {
            border: 2px solid #4f46e5;
            background: linear-gradient(135deg, #e0e7ff 60%, #f5f3ff 100%);
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.10);
            position: relative;
        }
        .repo-card.selected::before {
            content: "Selected";
            position: absolute;
            top: 12px;
            right: 16px;
            background: #4f46e5;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 2px 10px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.08);
            letter-spacing: 0.5px;
        }

        .repo-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2b6cb0;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
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
            font-size: 0.95rem;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .repo-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            color: #718096;
        }

        .repo-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .checkbox-container {
            margin-top: 15px;
        }

        .checkbox-container label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            color: #2d3748;
            cursor: pointer;
        }

        .checkbox-container input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #48bb78;
        }

        .form-actions {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
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

        .btn.success {
            background: linear-gradient(135deg, #48bb78, #38a169);
        }

        .btn.secondary {
            background: linear-gradient(135deg, #718096, #4a5568);
        }

        .btn.danger {
            background: linear-gradient(135deg, #f56565, #e53e3e);
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .repo-grid {
                grid-template-columns: 1fr;
            }
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
            padding: 40px 0 20px;
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
            font-size: 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .footer-logo span {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .footer-credits {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .footer-credits p {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .footer-credits p {
            color: #4a5568;
            font-size: 0.9rem;
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
                padding: 10px;
            }

            .header {
                padding: 20px;
                margin-bottom: 20px;
            }

            .header h1 {
                font-size: 1.8rem;
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }

            .header-subtitle {
                text-align: center;
                font-size: 1rem;
            }

            .status-card {
                padding: 20px;
            }

            .card-header h3 {
                font-size: 1.1rem;
            }

            .selection-info {
                padding: 15px;
            }

            .selection-info h3 {
                font-size: 1.1rem;
            }

            .selection-info p {
                font-size: 0.9rem;
            }

            .select-all-section {
                padding: 20px;
            }

            .select-all-checkbox {
                margin-bottom: 12px;
            }

            .select-all-checkbox label {
                font-size: 1rem;
            }

            .select-all-section p {
                font-size: 0.9rem;
            }

            .repo-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .repo-card {
                padding: 15px;
            }

            .repo-name {
                font-size: 1rem;
            }

            .repo-desc {
                font-size: 0.85rem;
            }

            .repo-meta {
                flex-direction: column;
                gap: 8px;
                font-size: 0.75rem;
            }

            .checkbox-container {
                margin-top: 12px;
            }

            .checkbox-container label {
                font-size: 0.9rem;
                align-items: flex-start;
                gap: 12px;
            }

            .checkbox-container input[type="checkbox"] {
                width: 18px;
                height: 18px;
                margin-top: 2px;
            }

            .form-actions {
                padding: 20px;
            }

            .form-actions h3 {
                font-size: 1.1rem;
            }

            .form-actions p {
                font-size: 0.9rem;
            }

            .form-actions ul {
                font-size: 0.85rem;
                margin: 12px 0;
                padding-left: 15px;
            }

            .form-actions .btn {
                width: 100%;
                justify-content: center;
                padding: 16px 20px;
                font-size: 1rem;
                min-height: 52px;
                margin-bottom: 10px;
            }

            .header-content {
                flex-direction: column;
                height: auto;
                padding: 15px 20px;
                gap: 15px;
            }

            .header-nav {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }

            .nav-link {
                font-size: 0.9rem;
                padding: 10px 14px;
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .header-status {
                padding: 8px 16px;
                font-size: 0.9rem;
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

            .footer-logo {
                justify-content: center;
            }

            .footer-logo span {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 8px;
            }

            .header {
                padding: 15px;
                margin-bottom: 15px;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .header-subtitle {
                font-size: 0.9rem;
            }

            .status-card {
                padding: 15px;
            }

            .card-header h3 {
                font-size: 1rem;
            }

            .selection-info {
                padding: 12px;
            }

            .selection-info h3 {
                font-size: 1rem;
            }

            .selection-info p {
                font-size: 0.85rem;
            }

            .select-all-section {
                padding: 15px;
            }

            .select-all-checkbox label {
                font-size: 0.95rem;
            }

            .select-all-section p {
                font-size: 0.85rem;
            }

            .repo-card {
                padding: 12px;
            }

            .repo-name {
                font-size: 0.95rem;
            }

            .repo-desc {
                font-size: 0.8rem;
            }

            .repo-meta {
                font-size: 0.7rem;
            }

            .checkbox-container label {
                font-size: 0.85rem;
            }

            .checkbox-container input[type="checkbox"] {
                width: 16px;
                height: 16px;
            }

            .form-actions {
                padding: 15px;
            }

            .form-actions h3 {
                font-size: 1rem;
            }

            .form-actions p {
                font-size: 0.85rem;
            }

            .form-actions ul {
                font-size: 0.8rem;
                margin: 10px 0;
                padding-left: 12px;
            }

            .form-actions .btn {
                padding: 14px 16px;
                font-size: 0.95rem;
                min-height: 48px;
            }

            .nav-link {
                font-size: 0.85rem;
                padding: 8px 12px;
                min-height: 40px;
            }

            .header-status {
                padding: 6px 12px;
                font-size: 0.85rem;
            }

            .footer-logo span {
                font-size: 1rem;
            }

            .footer-info p {
                font-size: 0.85rem;
            }

            .footer-credits p {
                font-size: 0.85rem;
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

            .select-all-checkbox input[type="checkbox"] {
                width: 20px;
                height: 20px;
            }
        }

        /* Landscape Mobile Optimizations */
        @media (max-width: 768px) and (orientation: landscape) {
            .header h1 {
                font-size: 1.6rem;
            }

            .repo-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }

            .form-actions .btn {
                width: auto;
                margin-right: 10px;
                margin-bottom: 0;
            }
        }

        /* Tablet Optimizations */
        @media (min-width: 769px) and (max-width: 1024px) {
            .container {
                padding: 20px;
            }

            .repo-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }

            .header h1 {
                font-size: 2.2rem;
            }

            .form-actions .btn {
                margin-right: 10px;
                margin-bottom: 0;
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
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
                <a href="select_repos.php" class="nav-link active">
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
                    Connected
                </div>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="container">
            <div class="header">
                <h1>
                    <i class="fas fa-cog"></i>
                    Repository Selection
                </h1>
                <p class="header-subtitle">
                    <i class="fas fa-target"></i>
                    Select repositories for automated hourly README updates
                </p>
            </div>

            <div class="status-card">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i>
                    <h3>Repository Selection Overview</h3>
                </div>
                <p><strong>Total repositories found:</strong> <span class="stats-highlight"><?php echo count($repos); ?></span></p>
                <p>Select the repositories you want to update with hourly README information.</p>
                <p><strong>Note:</strong> Selected repositories will be updated automatically every hour when you access the dashboard.</p>
            </div>

            <?php if (!empty($currentlySelected)): ?>
            <div class="selection-info">
                <h3><i class="fas fa-check-circle"></i> Currently Selected for Automatic Updates</h3>
                <p><strong>Selected repositories:</strong> <span class="stats-highlight"><?php echo count($currentlySelected); ?></span></p>
                <p><strong>Repositories:</strong> <?php echo implode(', ', $currentlySelected); ?></p>
                <p><em>These repositories will be updated automatically every hour when you access the dashboard.</em></p>
            </div>
            <?php else: ?>
            <div class="selection-info">
                <h3><i class="fas fa-exclamation-triangle"></i> No Repositories Selected</h3>
                <p>No repositories are currently selected for automatic updates.</p>
                <p><em>Select repositories below to enable automatic hourly README updates.</em></p>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="select-all-section">
                    <div class="select-all-checkbox">
                        <input type="checkbox" id="select-all" onclick="toggleAllRepos()">
                        <label for="select-all">Select All Repositories</label>
                    </div>
                    <p style="color: #718096; font-size: 0.95rem;">
                        <i class="fas fa-info-circle"></i>
                        This will update README files with current date, project status, and hourly activity information.
                    </p>
                </div>

                <div class="repo-grid">
                    <?php foreach ($repos as $repo): ?>
                    <div class="repo-card <?php echo in_array($repo['name'], $currentlySelected) ? 'selected' : ''; ?>">
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
                        <div class="checkbox-container">
                            <label>
                                <input type="checkbox" name="selected_repos[]" value="<?php echo htmlspecialchars($repo['name']); ?>" class="repo-checkbox" <?php echo in_array($repo['name'], $currentlySelected) ? 'checked' : ''; ?>>
                                Update README for this repository
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-actions">
                    <h3><i class="fas fa-rocket"></i> Ready to Update?</h3>
                    <p>Selected repositories will have their README files updated with:</p>
                    <ul style="margin: 15px 0; padding-left: 20px; color: #4a5568;">
                        <li>Current date and time</li>
                        <li>Hourly project status</li>
                        <li>Update history</li>
                        <li>Project metrics</li>
                    </ul>
                    <p><strong>Important:</strong> These selections will be saved and used for automatic hourly updates.</p>
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn success">
                            <i class="fas fa-save"></i>
                            Update Selected Repositories & Save Selection
                        </button>
                        <a href="index.php" class="btn secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </form>
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

    <script>
        function toggleAllRepos() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.repo-checkbox');
            const repoCards = document.querySelectorAll('.repo-card');
            
            checkboxes.forEach((checkbox, index) => {
                checkbox.checked = selectAll.checked;
                if (selectAll.checked) {
                    repoCards[index].classList.add('selected');
                } else {
                    repoCards[index].classList.remove('selected');
                }
            });
        }
        
        // Update select all when individual checkboxes change
        document.querySelectorAll('.repo-checkbox').forEach((checkbox, index) => {
            checkbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.repo-checkbox');
                const selectAll = document.getElementById('select-all');
                const repoCards = document.querySelectorAll('.repo-card');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                
                selectAll.checked = allChecked;
                selectAll.indeterminate = anyChecked && !allChecked;
                
                // Update card styling
                if (this.checked) {
                    repoCards[index].classList.add('selected');
                } else {
                    repoCards[index].classList.remove('selected');
                }
            });
        });
        
        // Set initial state of select all checkbox
        window.addEventListener('load', function() {
            const checkboxes = document.querySelectorAll('.repo-checkbox');
            const selectAll = document.getElementById('select-all');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            
            selectAll.checked = allChecked;
            selectAll.indeterminate = anyChecked && !allChecked;
        });
    </script>
</body>
</html> 