<?php
// All date/time operations in this file use IST (Asia/Kolkata) as set in utils.php
require_once("utils.php");

// Check if repository name is provided
if (!isset($_GET['repo'])) {
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Repository Update Error</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} .button{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;}</style>";
    echo "</head><body>";
    echo "<h1>❌ Repository Update Error</h1>";
    echo "<div class='error'>No repository specified. Please select a repository to update.</div>";
    echo "<a href='index.php' class='button'>← Back to Dashboard</a>";
    echo "</body></html>";
    exit;
}

$repoName = $_GET['repo'];

// Include the README update logic
include_once("update_readme.php");

// Update the single repository
logMessage("Manual README update for single repository: $repoName");

$result = updateProjectReadme($repoName, false);

if ($result) {
    logMessage("Single repository README update completed successfully for: $repoName");
    
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Repository Updated Successfully</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{background:#d4edda;padding:15px;border-radius:5px;margin:10px 0;} .info{background:#f8f9fa;padding:15px;border-radius:5px;margin:10px 0;} .button{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;} .github-link{background:#28a745;}</style>";
    echo "</head><body>";
    
    echo "<h1>✅ Repository Updated Successfully!</h1>";
    
    echo "<div class='success'>";
    echo "<h3>📝 README Update Complete</h3>";
    echo "<strong>Repository:</strong> $repoName<br>";
    echo "<strong>Update time:</strong> " . date('Y-m-d H:i:s') . "<br>";
    echo "<strong>Status:</strong> ✅ Successfully updated<br>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>What was updated:</h3>";
    echo "<ul>";
    echo "<li>Daily date and time information</li>";
    echo "<li>Project status and activity</li>";
    echo "<li>Update history table</li>";
    echo "<li>Project metrics</li>";
    echo "<li>Maintenance status</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>Actions:</h3>";
    echo "<a href='https://github.com/" . GITHUB_OWNER . "/" . rawurlencode($repoName) . "' class='button github-link' target='_blank'>View Updated Repository</a>";
    echo "<a href='index.php' class='button'>← Back to Dashboard</a>";
    echo "<a href='select_repos.php' class='button'>Update More Repositories</a>";
    echo "</div>";
    
    echo "</body></html>";
} else {
    logMessage("Single repository README update failed for: $repoName");
    
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Repository Update Failed</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} .info{background:#f8f9fa;padding:15px;border-radius:5px;margin:10px 0;} .button{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;}</style>";
    echo "</head><body>";
    
    echo "<h1>❌ Repository Update Failed</h1>";
    
    echo "<div class='error'>";
    echo "<h3>📝 README Update Failed</h3>";
    echo "<strong>Repository:</strong> $repoName<br>";
    echo "<strong>Update time:</strong> " . date('Y-m-d H:i:s') . "<br>";
    echo "<strong>Status:</strong> ❌ Update failed<br>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>Possible reasons:</h3>";
    echo "<ul>";
    echo "<li>Repository doesn't exist or is private</li>";
    echo "<li>GitHub API connection issues</li>";
    echo "<li>Insufficient permissions</li>";
    echo "<li>Repository is archived or disabled</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>Actions:</h3>";
    echo "<a href='index.php' class='button'>← Back to Dashboard</a>";
    echo "<a href='select_repos.php' class='button'>Try Different Repository</a>";
    echo "</div>";
    
    echo "</body></html>";
}
?> 