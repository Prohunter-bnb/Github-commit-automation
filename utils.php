<?php
date_default_timezone_set('Asia/Kolkata');
require_once("config.php");

function githubApiRequest($method, $url, $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, GITHUB_API_URL . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "ProjectCreatorBot");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token " . GITHUB_TOKEN,
        "Accept: application/vnd.github+json"
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    return [
        "status" => $info['http_code'],
        "body" => json_decode($response, true)
    ];
}

function logMessage($message) {
    // Create logs directory if it doesn't exist
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents(LOG_FILE, "[" . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND);
}

// Function to save selected repositories
function saveSelectedRepositories($selectedRepos) {
    $selectedFile = __DIR__ . "/logs/selected_repos.txt";
    $data = json_encode($selectedRepos);
    file_put_contents($selectedFile, $data);
    logMessage("Selected repositories saved: " . implode(', ', $selectedRepos));
}

// Function to load selected repositories
function loadSelectedRepositories() {
    $selectedFile = __DIR__ . "/logs/selected_repos.txt";
    if (file_exists($selectedFile)) {
        $data = file_get_contents($selectedFile);
        $selectedRepos = json_decode($data, true);
        if (is_array($selectedRepos)) {
            return $selectedRepos;
        }
    }
    return [];
}
?>
