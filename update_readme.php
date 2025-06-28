<?php
// All date/time operations in this file use IST (Asia/Kolkata) as set in utils.php
require_once("utils.php");

// Function to update README content with daily information
function generateUpdatedReadmeContent($repoName, $originalContent = "") {
    $currentDate = date('Y-m-d H:i:s');
    $dayOfWeek = date('l');
    $weekNumber = date('W');
    $month = date('F');
    $year = date('Y');
    
    // Create new README content with daily updates
    $newContent = "# $repoName\n\n";
    $newContent .= "## 📅 Daily Update - $currentDate\n\n";
    $newContent .= "**Today's Information:**\n";
    $newContent .= "- **Date:** $currentDate\n";
    $newContent .= "- **Day:** $dayOfWeek\n";
    $newContent .= "- **Week:** Week $weekNumber of $year\n";
    $newContent .= "- **Month:** $month $year\n";
    $newContent .= "- **Last Updated:** $currentDate\n\n";
    
    $newContent .= "## 📊 Project Status\n\n";
    $newContent .= "This project is actively maintained and updated daily.\n\n";
    
    $newContent .= "## 🚀 Recent Activity\n\n";
    $newContent .= "- README updated automatically on $currentDate\n";
    $newContent .= "- Project status: Active\n";
    $newContent .= "- Maintenance: Daily updates enabled\n\n";
    
    $newContent .= "## 📝 Update History\n\n";
    $newContent .= "| Date | Update Type | Description |\n";
    $newContent .= "|------|-------------|-------------|\n";
    $newContent .= "| $currentDate | Daily Update | README refreshed with current date and status |\n";
    
    // Add some previous entries for context
    $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
    $newContent .= "| $yesterday | Daily Update | Previous daily update |\n";
    
    $newContent .= "\n## 🔧 Getting Started\n\n";
    $newContent .= "1. Clone this repository\n";
    $newContent .= "2. Check the daily updates above\n";
    $newContent .= "3. Start working on your project\n\n";
    
    $newContent .= "## 📈 Project Metrics\n\n";
    $newContent .= "- **Created:** Auto-generated\n";
    $newContent .= "- **Last Modified:** $currentDate\n";
    $newContent .= "- **Update Frequency:** Daily\n";
    $newContent .= "- **Status:** Active and maintained\n\n";
    
    $newContent .= "---\n\n";
    $newContent .= "*This README is automatically updated daily to keep project information current.*\n";
    $newContent .= "*Last automated update: $currentDate*\n";
    
    return $newContent;
}

// Function to update a single project's README
function updateProjectReadme($repoName, $isAutomatic = false) {
    logMessage("Updating README for repository: $repoName");
    
    // Get current README content first
    $getResp = githubApiRequest("GET", "/repos/" . GITHUB_OWNER . "/" . rawurlencode($repoName) . "/contents/README.md");
    
    $currentSha = "";
    if ($getResp['status'] === 200 && isset($getResp['body']['sha'])) {
        $currentSha = $getResp['body']['sha'];
        logMessage("Found existing README with SHA: $currentSha");
    } else {
        logMessage("No existing README found or error getting current content");
    }
    
    // Generate new README content
    $newReadmeContent = generateUpdatedReadmeContent($repoName);
    $encodedContent = base64_encode($newReadmeContent);
    
    logMessage("Generated new README content (" . strlen($newReadmeContent) . " characters)");
    
    // Prepare update data
    $updateData = [
        "message" => "Daily README update - " . date('Y-m-d H:i:s') . ($isAutomatic ? " (Automated)" : ""),
        "content" => $encodedContent,
        "branch" => DEFAULT_BRANCH
    ];
    
    // Add SHA if we have existing content (required for updates)
    if (!empty($currentSha)) {
        $updateData["sha"] = $currentSha;
    }
    
    // Update the README
    $putResp = githubApiRequest("PUT", "/repos/" . GITHUB_OWNER . "/" . rawurlencode($repoName) . "/contents/README.md", $updateData);
    
    if ($putResp['status'] == 200 || $putResp['status'] == 201) {
        logMessage("✅ README updated successfully for $repoName");
        return true;
    } else {
        logMessage("❌ Failed to update README for $repoName - Status: " . $putResp['status']);
        if (isset($putResp['body']['message'])) {
            logMessage("GitHub API Error: " . $putResp['body']['message']);
        }
        return false;
    }
}

// Function to update selected project READMEs
function updateSelectedProjectReadmes($isAutomatic = false) {
    $selectedRepos = loadSelectedRepositories();
    
    if (empty($selectedRepos)) {
        logMessage("No repositories selected for automatic updates");
        if (!defined('AUTO_INCLUDED')) {
            echo "<!DOCTYPE html>";
            echo "<html><head><title>No Repositories Selected</title>";
            echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .info{background:#d1ecf1;padding:15px;border-radius:5px;margin:10px 0;} .button{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;}</style>";
            echo "</head><body>";
            echo "<h1>📝 No Repositories Selected</h1>";
            echo "<div class='info'>";
            echo "<h3>No repositories are currently selected for automatic updates.</h3>";
            echo "<p>Please select repositories to update using the repository selection page.</p>";
            echo "</div>";
            echo "<div class='info'>";
            echo "<h3>Actions:</h3>";
            echo "<a href='select_repos.php' class='button'>Select Repositories</a>";
            echo "<a href='index.php' class='button'>← Back to Dashboard</a>";
            echo "</div>";
            echo "</body></html>";
        }
        return true;
    }
    
    logMessage("Starting README update for " . count($selectedRepos) . " selected repositories");
    
    $updatedCount = 0;
    $failedCount = 0;
    $updatedRepos = [];
    $failedRepos = [];
    
    foreach ($selectedRepos as $repoName) {
        logMessage("Processing selected repository: $repoName");
        
        if (updateProjectReadme($repoName, $isAutomatic)) {
            $updatedCount++;
            $updatedRepos[] = $repoName;
        } else {
            $failedCount++;
            $failedRepos[] = $repoName;
        }
    }
    
    logMessage("Selected repository update completed. Updated: $updatedCount, Failed: $failedCount");
    
    // Only show HTML output if not included from index.php
    if (!defined('AUTO_INCLUDED')) {
        echo "<!DOCTYPE html>";
        echo "<html><head><title>Selected Repository Update Results</title>";
        echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{background:#d4edda;padding:15px;border-radius:5px;margin:10px 0;} .error{background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} .info{background:#f8f9fa;padding:15px;border-radius:5px;margin:10px 0;} .button{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;}</style>";
        echo "</head><body>";
        
        echo "<h1>📝 Selected Repository Update Results</h1>";
        
        echo "<div class='success'>";
        echo "<h3>✅ Update Summary</h3>";
        echo "<strong>Selected repositories:</strong> " . count($selectedRepos) . "<br>";
        echo "<strong>Successfully updated:</strong> $updatedCount<br>";
        echo "<strong>Failed to update:</strong> $failedCount<br>";
        echo "<strong>Update time:</strong> " . date('Y-m-d H:i:s') . "<br>";
        echo "</div>";
        
        if (!empty($updatedRepos)) {
            echo "<div class='info'>";
            echo "<h3>✅ Successfully Updated:</h3>";
            echo "<ul>";
            foreach ($updatedRepos as $repo) {
                echo "<li><a href='https://github.com/" . GITHUB_OWNER . "/" . rawurlencode($repo) . "' target='_blank'>$repo</a></li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
        if (!empty($failedRepos)) {
            echo "<div class='error'>";
            echo "<h3>❌ Failed to Update:</h3>";
            echo "<ul>";
            foreach ($failedRepos as $repo) {
                echo "<li>$repo</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
        echo "<div class='info'>";
        echo "<h3>Actions:</h3>";
        echo "<a href='update_readme.php' class='button'>Update Selected Again</a>";
        echo "<a href='select_repos.php' class='button'>Change Selection</a>";
        echo "<a href='index.php' class='button'>← Back to Dashboard</a>";
        echo "</div>";
        
        echo "</body></html>";
    }
    
    return $failedCount === 0;
}

// Function to update all project READMEs (for manual use)
function updateAllProjectReadmes($isAutomatic = false) {
    // Get all repositories
    $response = githubApiRequest("GET", "/user/repos?per_page=100&sort=updated&direction=desc");
    
    if ($response['status'] !== 200) {
        logMessage("ERROR: Unable to list repos - Status: " . $response['status']);
        echo "<!DOCTYPE html>";
        echo "<html><head><title>README Update Error</title>";
        echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} .button{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;}</style>";
        echo "</head><body>";
        echo "<h1>❌ README Update Error</h1>";
        echo "<div class='error'>Unable to list repositories. Please check your GitHub connection.</div>";
        echo "<a href='index.php' class='button'>← Back to Dashboard</a>";
        echo "</body></html>";
        return false;
    }
    
    $repos = $response['body'];
    $updatedCount = 0;
    $failedCount = 0;
    $updatedRepos = [];
    $failedRepos = [];
    
    logMessage("Found " . count($repos) . " repositories to update");
    
    foreach ($repos as $repo) {
        $repoName = $repo['name'];
        logMessage("Processing repository: $repoName");
        
        if (updateProjectReadme($repoName, $isAutomatic)) {
            $updatedCount++;
            $updatedRepos[] = $repoName;
        } else {
            $failedCount++;
            $failedRepos[] = $repoName;
        }
    }
    
    logMessage("README update process completed. Updated: $updatedCount, Failed: $failedCount");
    
    // Only show HTML output if not included from index.php
    if (!defined('AUTO_INCLUDED')) {
        echo "<!DOCTYPE html>";
        echo "<html><head><title>README Update Results</title>";
        echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{background:#d4edda;padding:15px;border-radius:5px;margin:10px 0;} .error{background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} .info{background:#f8f9fa;padding:15px;border-radius:5px;margin:10px 0;} .button{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;}</style>";
        echo "</head><body>";
        
        echo "<h1>📝 README Update Results</h1>";
        
        echo "<div class='success'>";
        echo "<h3>✅ Update Summary</h3>";
        echo "<strong>Total repositories processed:</strong> " . count($repos) . "<br>";
        echo "<strong>Successfully updated:</strong> $updatedCount<br>";
        echo "<strong>Failed to update:</strong> $failedCount<br>";
        echo "<strong>Update time:</strong> " . date('Y-m-d H:i:s') . "<br>";
        echo "</div>";
        
        if (!empty($updatedRepos)) {
            echo "<div class='info'>";
            echo "<h3>✅ Successfully Updated:</h3>";
            echo "<ul>";
            foreach ($updatedRepos as $repo) {
                echo "<li><a href='https://github.com/" . GITHUB_OWNER . "/" . rawurlencode($repo) . "' target='_blank'>$repo</a></li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
        if (!empty($failedRepos)) {
            echo "<div class='error'>";
            echo "<h3>❌ Failed to Update:</h3>";
            echo "<ul>";
            foreach ($failedRepos as $repo) {
                echo "<li>$repo</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
        echo "<div class='info'>";
        echo "<h3>Actions:</h3>";
        echo "<a href='update_readme.php' class='button'>Update All Again</a>";
        echo "<a href='index.php' class='button'>← Back to Dashboard</a>";
        echo "</div>";
        
        echo "</body></html>";
    }
    
    return $failedCount === 0;
}

// Only run if accessed directly (not included)
if (!defined('AUTO_INCLUDED')) {
    updateSelectedProjectReadmes(false);
}
?> 