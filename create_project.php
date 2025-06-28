<?php
// All date/time operations in this file use IST (Asia/Kolkata) as set in utils.php
require_once("utils.php");

// Function to generate random alphanumeric string
function generateRandomProjectName($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $projectName = '';
    for ($i = 0; $i < $length; $i++) {
        $projectName .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $projectName;
}

// Generate a unique project name
function generateUniqueProjectName($repos) {
    $maxAttempts = 10; // Prevent infinite loops
    $attempts = 0;
    
    do {
        $projectName = generateRandomProjectName();
        $nameExists = false;
        
        // Check if this name already exists
        foreach ($repos as $repo) {
            if ($repo['name'] === $projectName) {
                $nameExists = true;
                break;
            }
        }
        
        $attempts++;
        if ($attempts >= $maxAttempts) {
            // If we can't find a unique name after max attempts, add timestamp
            $projectName = generateRandomProjectName(8) . time();
            break;
        }
    } while ($nameExists);
    
    return $projectName;
}

// 1. list repos to find existing projects
$response = githubApiRequest("GET", "/user/repos?per_page=100&sort=created&direction=desc");

if ($response['status'] !== 200) {
    logMessage("ERROR: Unable to list repos - Status: " . $response['status']);
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Project Creation Error</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} .button{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;}</style>";
    echo "</head><body>";
    echo "<h1>❌ Project Creation Error</h1>";
    echo "<div class='error'>Unable to list repositories. Please check your GitHub connection.</div>";
    echo "<a href='index.php' class='button'>← Back to Dashboard</a>";
    echo "</body></html>";
    exit;
}

$repos = $response['body'];
$existingProjectNames = [];

logMessage("Searching through " . count($repos) . " repositories for existing projects...");

foreach ($repos as $repo) {
    logMessage("Checking repo: " . $repo['name']);
    $existingProjectNames[] = $repo['name'];
}

logMessage("Found " . count($existingProjectNames) . " existing repositories");

// Generate unique project name
$newRepoName = generateUniqueProjectName($repos);
logMessage("Generated unique project name: $newRepoName");

// 2. create repo
$repoData = [
    "name" => $newRepoName,
    "description" => "Auto-created project with random name: $newRepoName",
    "private" => true
];

$createResp = githubApiRequest("POST", "/user/repos", $repoData);

if ($createResp['status'] !== 201) {
    logMessage("ERROR: Failed to create repo $newRepoName - Status: " . $createResp['status']);
    if (isset($createResp['body']['message'])) {
        logMessage("GitHub API Error: " . $createResp['body']['message']);
    }
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Project Creation Failed</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;} .info{background:#f8f9fa;padding:15px;border-radius:5px;margin:10px 0;} .button{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;}</style>";
    echo "</head><body>";
    echo "<h1>❌ Project Creation Failed</h1>";
    echo "<div class='error'>Failed to create repository: $newRepoName</div>";
    echo "<div class='info'>";
    echo "<strong>Status Code:</strong> " . $createResp['status'] . "<br>";
    if (isset($createResp['body']['message'])) {
        echo "<strong>Error Message:</strong> " . htmlspecialchars($createResp['body']['message']) . "<br>";
    }
    echo "</div>";
    echo "<a href='index.php' class='button'>← Back to Dashboard</a>";
    echo "</body></html>";
    exit;
}

logMessage("$newRepoName created successfully.");

// 3. add README.md
$templatePath = __DIR__ . "/README_templates/default.md";
$readmeContent = "";

if (file_exists($templatePath)) {
    $readmeContent = file_get_contents($templatePath);
    if ($readmeContent === false || empty(trim($readmeContent))) {
        logMessage("WARNING: README template is empty or unreadable, using fallback content");
        $readmeContent = "# Project $newRepoName\n\nThis is an auto-generated project created on " . date('Y-m-d H:i:s') . ".\n\n## Getting Started\n\n1. Clone this repository\n2. Add your project files\n3. Start coding!";
    } else {
        // Replace placeholders with random project name
        $readmeContent = str_replace("{N}", $newRepoName, $readmeContent);
        $readmeContent = str_replace("{DATE}", date('Y-m-d H:i:s'), $readmeContent);
        logMessage("README template loaded and placeholders replaced successfully");
    }
} else {
    logMessage("WARNING: README template file not found, using fallback content");
    // Fallback content if template doesn't exist
    $readmeContent = "# Project $newRepoName\n\nThis is an auto-generated project created on " . date('Y-m-d H:i:s') . ".\n\n## Getting Started\n\n1. Clone this repository\n2. Add your project files\n3. Start coding!";
}

logMessage("README content length: " . strlen($readmeContent) . " characters");

$encodedContent = base64_encode($readmeContent);
logMessage("Base64 encoded content length: " . strlen($encodedContent) . " characters");

$fileData = [
    "message" => "Add README",
    "content" => $encodedContent,
    "branch" => DEFAULT_BRANCH
];

logMessage("Attempting to upload README.md to $newRepoName...");
$putResp = githubApiRequest("PUT", "/repos/" . GITHUB_OWNER . "/" . rawurlencode($newRepoName) . "/contents/README.md", $fileData);

$readmeStatus = "";
$readmeError = "";
if ($putResp['status'] == 201) {
    logMessage("README.md added to $newRepoName successfully.");
    $readmeStatus = "✅ README.md added successfully";
} else {
    logMessage("ERROR: Failed to upload README.md to $newRepoName - Status: " . $putResp['status']);
    if (isset($putResp['body']['message'])) {
        $errorMsg = $putResp['body']['message'];
        logMessage("GitHub API Error: " . $errorMsg);
        $readmeError = $errorMsg;
    }
    if (isset($putResp['body']['errors'])) {
        logMessage("GitHub API Errors: " . json_encode($putResp['body']['errors']));
        $readmeError .= " | Errors: " . json_encode($putResp['body']['errors']);
    }
    $readmeStatus = "❌ README.md upload failed (Status: " . $putResp['status'] . ")";
}

logMessage("Project creation process completed for $newRepoName");

// Display success page with results
echo "<!DOCTYPE html>";
echo "<html><head><title>Project Created Successfully</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{background:#d4edda;padding:15px;border-radius:5px;margin:10px 0;} .info{background:#f8f9fa;padding:15px;border-radius:5px;margin:10px 0;} .button{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;} .github-link{background:#28a745;}</style>";
echo "</head><body>";

echo "<h1>🎉 Project Created Successfully!</h1>";

echo "<div class='success'>";
echo "<h3>✅ Repository Created</h3>";
echo "<strong>Repository Name:</strong> $newRepoName<br>";
echo "<strong>Repository URL:</strong> <a href='https://github.com/" . GITHUB_OWNER . "/" . rawurlencode($newRepoName) . "' target='_blank'>https://github.com/" . GITHUB_OWNER . "/" . rawurlencode($newRepoName) . "</a><br>";
echo "<strong>Created:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "<strong>README Status:</strong> $readmeStatus<br>";
if (!empty($readmeError)) {
    echo "<strong>README Error:</strong> <span style='color: #dc3545;'>" . htmlspecialchars($readmeError) . "</span><br>";
}
echo "</div>";

echo "<div class='info'>";
echo "<h3>Project Analysis:</h3>";
echo "<strong>Total repositories found:</strong> " . count($repos) . "<br>";
echo "<strong>Project name type:</strong> Random alphanumeric (10 characters)<br>";
echo "<strong>Generated name:</strong> $newRepoName<br>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>Actions:</h3>";
echo "<a href='https://github.com/" . GITHUB_OWNER . "/" . rawurlencode($newRepoName) . "' class='button github-link' target='_blank'>View on GitHub</a>";
echo "<a href='create_project.php' class='button'>Create Next Project</a>";
echo "<a href='index.php' class='button'>← Back to Dashboard</a>";
echo "</div>";

echo "</body></html>";
?>
