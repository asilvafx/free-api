<?php
$f3->set('PAGE.title', 'Maintenance & Support');
$f3->set('PAGE.slug', 'support');
$f3->set('breadcrumbs', [
    [
        "name" => "Maintenance & Support",
        "slug" => "support"
    ]
]);


$phpVersion = phpversion();
$serverSoftware = $_SERVER['SERVER_SOFTWARE'];
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$maxUpload = ini_get('upload_max_filesize');

$f3->set('phpVersion', $phpVersion);
$f3->set('serverSoftware', $serverSoftware);
$f3->set('docRoot', $docRoot);
$f3->set('maxUpload', $maxUpload);

// Errors Log Logic
$logFilePath = ROOT . 'logs/error.log';
$errorLogs = '';

if (file_exists($logFilePath)) {
    $errorLogs = file_get_contents($logFilePath);
    // Limit the log size to avoid large file issues
    $errorLogs = strlen($errorLogs) > 5000 ? substr($errorLogs, -5000) : $errorLogs;
} else {
    $errorLogs = 'Error log file not found.';
}
$f3->set('errorLogs', htmlspecialchars_decode($errorLogs));

// Clear Logs File
if (isset($_GET['clear-logs']) && $_SERVER['REQUEST_METHOD'] === "POST") {
    $response = new Response;

    // Validate CSRF token
    $body = json_decode(file_get_contents('php://input'), true);
    $csrf = $body['token'] ?? null;

    if ($csrf !== $f3->get('SESSION.token')) {
        $response->json('error', 'Invalid CSRF token.');
        return;
    }

    // Path to the log file
    $logFilePath = 'app/logs/error.log';

    // Check if the file exists and is writable
    if (file_exists($logFilePath) && is_writable($logFilePath)) {
        // Clear the file content by truncating it
        file_put_contents($logFilePath, '');
        $response->json('success', 'Log file cleared successfully.');
    } else {
        $response->json('error', 'Failed to clear the log file. Please check file permissions.');
    }

    exit;
}

// Backups Logic
$backupDir = ROOT . 'data/backup/';
$backupFiles = glob($backupDir . 'backup-*.zip');

// Determine if a backup file exists
if (!empty($backupFiles)) {
    // Sort the backups by creation time, get the latest one
    usort($backupFiles, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $latestBackup = basename($backupFiles[0]);
    $backupTime = date('Y-m-d H:i:s', filemtime($backupFiles[0]));
    $f3->set('noBackup', false);
    $f3->set('lastBackup', $latestBackup);
    $f3->set('lastBackupTime', $backupTime);
    $f3->set('backupPath', ROOT . 'data/backup/' . $latestBackup); // Use relative path for downloading
} else {
    $f3->set('noBackup', true);
}

// Download current backup
if (isset($_GET['download-backup'])) {
    $response = new Response;
    $csrf = $_GET['tkn'] ?? null;

    if (!$csrf || $csrf !== $f3->get('SESSION.token')) {
        $response->json('error', 'Invalid CSRF token.');
        exit;
    }
    if (!empty($f3->get('backupPath'))) {
        $downloadFile = $f3->get('backupPath');
        if (file_exists($downloadFile)) {
            // Set headers to force download of a zip file
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($downloadFile) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($downloadFile));

            // Read and output the zip file to the user
            readfile($downloadFile);
        } else {
            $response->json('error', 'Backup not found for download.');
        }
    } else {
        $response->json('error', 'Invalid Backup file.');
    }
    exit;
}
// Create new backup
if (isset($_GET['create-backup']) && $_SERVER['REQUEST_METHOD'] === "POST") {
    $response = new Response;
    $utils = new Utils;

    set_time_limit(0);

    // Check if FastCGI is available
    if (isset($_SERVER['FCGI_ROLE']) && $_SERVER['FCGI_ROLE'] === 'RESPONDER') {
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            ob_start();
            $response->json('success', 'Backup process initiated.');
            header('Content-Encoding: none');
            header('Content-Length: ' . ob_get_length());
            header('Connection: close');
            ob_end_flush();
            @ob_flush();
            flush();
            fastcgi_finish_request();
            // Start backup process in the background
            $cron_file = ROOT . "cronjob.php";
            exec("php " . $cron_file . " > /dev/null 2>&1 &", $output, $return_var);
            $report = new Report;
            if ($return_var !== 0) {
                $report->error('Failed to execute cronjob.php: ' . implode("\n", $output));
            }
        } else {
            forceBackup($backupDir);
        }
    } else {
        forceBackup($backupDir);
    }

    exit;
}

function forceBackup($backupDir)
{
    $response = new response;
    $utils = new Utils;
    if ($utils->createBackup($backupDir)) {
        $response->json('success', 'Backup created successfully.');
    } else {
        $response->json('error', 'Failed to create new backup.');
    }
}

// Clean All Cache Logic
if (isset($_GET['clean-all-cache']) && $_SERVER['REQUEST_METHOD'] === "POST") {
    $response = new Response;

    // Validate CSRF token
    $body = json_decode(file_get_contents('php://input'), true);
    $csrf = $body['token'] ?? null;

    if ($csrf !== $f3->get('SESSION.token')) {
        $response->json('error', 'Invalid CSRF token.');
        return;
    }

    // Define directories to clean
    $cacheDir = ROOT . 'tmp/cache/';
    $sessionDir = ROOT . 'tmp/session/';

    // Clean cache and session folders, skipping hidden files
    cleanDirectory($cacheDir);
    cleanDirectory($sessionDir);

    $response->json('success', 'All cache cleaned successfully.');
    exit;
}

// Clean Temporary Files Logic
if (isset($_GET['clean-temp-files']) && $_SERVER['REQUEST_METHOD'] === "POST") {
    $response = new Response;

    // Validate CSRF token
    $body = json_decode(file_get_contents('php://input'), true);
    $csrf = $body['token'] ?? null;

    if ($csrf !== $f3->get('SESSION.token')) {
        $response->json('error', 'Invalid CSRF token.');
        return;
    }

    // Define cache directory
    $cacheDir = ROOT . 'tmp/cache/';

    // Clean only the cache folder, skipping hidden files
    cleanDirectory($cacheDir);

    $response->json('success', 'Temporary files cleaned successfully.');
    exit;
}

// Clean User Sessions Logic
if (isset($_GET['clean-user-sessions']) && $_SERVER['REQUEST_METHOD'] === "POST") {
    $response = new Response;

    // Validate CSRF token
    $body = json_decode(file_get_contents('php://input'), true);
    $csrf = $body['token'] ?? null;

    if ($csrf !== $f3->get('SESSION.token')) {
        $response->json('error', 'Invalid CSRF token.');
        return;
    }

    // Define session directory
    $sessionDir = ROOT . 'tmp/session/';

    // Clean only the session folder, skipping hidden files
    cleanDirectory($sessionDir);

    $response->json('success', 'User sessions cleaned successfully.');
    exit;
}

// Helper function to clean directories while skipping hidden files
function cleanDirectory($dir)
{
    foreach (new DirectoryIterator($dir) as $fileInfo) {
        if ($fileInfo->isDot() || $fileInfo->isDir() || $fileInfo->getFilename()[0] === '.') {
            continue;
        }
        unlink($fileInfo->getRealPath());
    }
}
