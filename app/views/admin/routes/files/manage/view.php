<?php

global $db;

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $response = new Response;

    $action = isset($_POST['action']) ? htmlspecialchars_decode($_POST['action']) : null;

    if (!empty($action)) {
        switch ($action) {
            case "rename":
                $file = isset($_POST['file']) ? htmlspecialchars_decode($_POST['file']) : null;
                $name = isset($_POST['name']) ? htmlspecialchars_decode($_POST['name']) : null;
                if ($file && $name) {
                    $baseDir = 'public/uploads/files/';
                    if (file_exists($baseDir . $file)) {
                        // Rename file function here
                        rename($baseDir . $file, $baseDir . $name);
                        $response->json('success', 'File name was successfully updated.');
                    } else {
                        $response->json('error', 'File was not found.');
                    }
                } else {
                    $response->json('error', 'Invalid request.');
                }
                break;
            case "delete":
                $file = isset($_POST['file']) ? htmlspecialchars_decode($_POST['file']) : null;
                if ($file) {
                    $baseDir = 'public/uploads/files/';
                    if (file_exists($baseDir . $file)) {
                        unlink($baseDir . $file);
                        $response->json('success', 'File was successfully deleted.');
                    } else {
                        $response->json('error', 'File was not found.');
                    }
                } else {
                    $response->json('error', 'Invalid request.');
                }
                break;
            case "upload":
                // Check if file is uploaded
                if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                    $response->json('error', 'No file uploaded or there was an error with the upload.');
                    exit;
                }
                $file = $_FILES['file'];
                $utils = new Utils;
                if (!$utils->uploadFile($file, 'public/uploads/files/')) {
                    $response->json('error', 'Failed to upload file.');
                } else {
                    $response->json('success', 'File uploaded was successfully uploaded.');
                }
                break;
            default:
                $response->json('error', 'Invalid request.');
        }
        exit;
    } else {
        $response->json('error', 'No data provided for the new entry.');
    }

    exit;
}
