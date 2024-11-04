<?php

class Utils
{


    // Helper function to format the file size in a readable way
    function formatBytes($bytes)
    {
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f", $bytes / pow(1024, $factor)) . " " . $sizes[$factor];
    }

    // Get directory size
    function GetDirectorySize($path)
    {
        $bytestotal = 0;
        $path = realpath($path);
        if ($path !== false && $path != '' && file_exists($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                $bytestotal += $object->getSize();
            }
        }
        return $bytestotal;
    }

    // Delete full directory
    function deleteDirectory($dir)
    {
        // Ensure the directory exists
        if (!$this->isDir($dir)) {
            return false;
        }

        // Iterate over the directory's contents
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            $itemPath = $dir . DIRECTORY_SEPARATOR . $item;

            // If it's a directory, recursively delete its contents
            if (is_dir($itemPath)) {
                $this->deleteDirectory($itemPath);
            } else {
                // If it's a file, delete the file
                unlink($itemPath);
            }
        }

        // Finally, delete the empty directory
        return rmdir($dir);
    }

    // Check if path is folder and return true/false
    function isDir($path)
    {
        if (is_dir($path)) {
            return true;
        } else {
            return false;
        }
    }

    // Returns a file size limit in bytes based on the PHP upload_max_filesize
    // and post_max_size
    function file_upload_max_size()
    {
        static $max_size = -1;

        if ($max_size < 0) {
            // Start with post_max_size.
            $post_max_size = $this->parse_size(ini_get('post_max_size'));
            if ($post_max_size > 0) {
                $max_size = $post_max_size;
            }

            // If upload_max_size is less, then reduce. Except if upload_max_size is
            // zero, which indicates no limit.
            $upload_max = $this->parse_size(ini_get('upload_max_filesize'));
            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }
        return $max_size;
    }

    function parse_size($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }

    // Upload file function ($file, $upload path)
    function uploadFile($file, $uploadDir, $fileRename = null, $overwrite = true)
    {
        // Validate file size (10MB limit)
        $maxFileSize = $this->file_upload_max_size();
        if ($file['size'] > $maxFileSize) {
            return false;
            exit;
        }

        // Check if the upload directory exists, if not create it
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return false; // Return false if the directory could not be created
            }
        }

        // Create a unique file name to avoid overwriting
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_name = pathinfo($file['name'], PATHINFO_FILENAME);

        $file_save = $file_name . '-' . uniqid() . '.' . $file_ext;
        if ($fileRename) {
            $fileRename = htmlspecialchars_decode($fileRename);
            $file_save = $fileRename . '.' . $file_ext;
        }

        $uploadPath = $uploadDir . $file_save;

        if (!$overwrite) {
            if (file_exists($uploadPath)) {
                return false;
            }
        }

        // Move the uploaded file to the destination folder
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return $uploadPath;
        } else {
            return false;
        }
    }



    // Function to create a backup
    function createBackup($backupDir)
    {
        global $f3;

        try {
            // Clear previous backups
            array_map('unlink', glob($backupDir . 'backup-*.zip'));

            // Create a new backup
            $newBackup = $backupDir . 'backup-' . time() . '.zip';
            $zip = new ZipArchive;

            if ($zip->open($newBackup, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $rootPath = realpath(BASE_PATH);

                // Backing up files from root directory
                $dirIterator = new DirectoryIterator($rootPath);
                foreach ($dirIterator as $file) {
                    if ($file->isDot() || $file->isDir()) continue;
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($rootPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }

                // Backup additional directories
                $this->backupAdditionalDirectories($zip, ROOT, 'app');
                $this->backupAdditionalDirectories($zip, 'ui', 'ui');
                //$this->backupAdditionalDirectories($zip, 'public', 'public');

                $zip->close();
                return true;
            } else {
                throw new Exception('Could not open the zip file.');
            }
        } catch (Exception $e) {
            // Log any errors
            error_log('Backup error: ' . $e->getMessage());
            return false;
        }
    }

    // Function to backup additional directories
    function backupAdditionalDirectories($zip, $dirPath, $zipPath)
    {
        $sitePath = realpath($dirPath);

        // Ensure directory exists
        if (!is_dir($sitePath)) {
            return; // Skip if directory doesn't exist
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sitePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Skip directories and ensure only files are added
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipPath . '/' . substr($filePath, strlen($sitePath) + 1);

                // Add the file to the zip
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
}
