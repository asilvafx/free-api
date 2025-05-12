<?php

$f3->set('PAGE.title', 'Files');
$f3->set('PAGE.slug', 'files');
$f3->set('breadcrumbs', [
    [
        "name" => "Files",
        "slug" => "files"
    ]
]);

$utils = new Utils;

$f3->set('UTILS', $utils);

// Base directory where the files are stored
$baseDir = 'public/uploads/files/';
$f3->set('FILES.baseDir', $baseDir);

// Scan directory and remove "." and ".." from the array
$files = scandir($baseDir);
$files = array_diff($files, ['.', '..']);

$fileDetails = [];

// Loop through the files to get their size and last modification date
foreach ($files as $file) {
    $filePath = $baseDir . $file;
    if (is_file($filePath)) { // Ensure it's a file, not a directory
        $fileDetails[] = [
            'name' => $file,
            'size' => $utils->formatBytes(filesize($filePath)), // Get formatted file size
            'last_modified' => date('Y-m-d H:i:s', filemtime($filePath)), // Get last modified date
        ];
    }
}

// Set the files array with details for the template
$f3->set('files', $fileDetails);

// Set total directory size
$f3->set('totalSize', $utils->GetDirectorySize($baseDir));
$f3->set('totalSpace', disk_total_space($baseDir));
$f3->set('totalFree', disk_free_space($baseDir));

// Optionally set a message if passed in query string
$f3->set('message', $f3->get('GET.message'));
