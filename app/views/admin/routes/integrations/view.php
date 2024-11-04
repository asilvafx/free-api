<?php

$f3->set('PAGE.title', 'Integrations');
$f3->set('PAGE.slug', 'integrations');
$f3->set('breadcrumbs', [
    [
        "name" => "Integrations",
        "slug" => "integrations"
    ]
]);

$integrationsPath = $f3->get('INTEGRATIONS');
$viewIntegration = isset($_GET['view']) ? htmlspecialchars_decode($_GET['view']) : null;
$response = new Response;
$utils = new Utils;

$uploadIntegration = isset($_GET['upload']) ? true : false;

if ($uploadIntegration) {
    // Check if file is uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $response->json('error', 'No file uploaded or there was an error with the upload.');
        exit;
    }

    $file = $_FILES['file'];

    // Ensure the uploaded file is a ZIP file
    if (mime_content_type($file['tmp_name']) !== 'application/zip') {
        $response->json('error', 'Invalid file type. Only .zip files are accepted.');
        exit;
    }

    $uploadedFile = $utils->uploadFile($file, 'public/uploads/');

    if ($uploadedFile !== FALSE) {
        $zip = new ZipArchive;
        $res = false;
        try {
            $res = $zip->open($uploadedFile);
        } catch (Exception $e) {
            $response->json('error', 'Error: ' . $e);
        }

        if ($res === TRUE) {
            // Check if the ZIP contains both .ini and .php files
            $foundIni = false;
            $foundPhp = false;
            $pluginName = null;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $fileName = basename($stat['name']);
                $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                $nameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);

                if ($fileExt === 'ini') {
                    $foundIni = true;
                    $pluginName = $nameWithoutExt;
                } elseif ($fileExt === 'php' && $pluginName === $nameWithoutExt) {
                    $foundPhp = true;
                }
            }

            if (!$foundIni || !$foundPhp) {
                $zip->close();
                unlink($uploadedFile); // Delete the uploaded zip file
                $response->json('error', 'Invalid package.');
                exit;
            }

            // Extract files if valid
            $zip->extractTo($integrationsPath);
            $zip->close();

            // Delete the uploaded zip file
            unlink($uploadedFile);

            $response->json('success', 'File was successfully extracted.');
        } else {
            unlink($uploadedFile); // Delete the uploaded zip file
            $response->json('error', 'Failed to extract file.');
        }
    } else {
        $response->json('error', 'Failed to upload file.');
    }

    exit;
} else 
if ($viewIntegration) {

    $f3->set('integration.enabled', true);
    $integrationsFile = $integrationsPath . $viewIntegration . '/' . $viewIntegration . '.php';
    $integrationsIni = $integrationsPath . $viewIntegration . '/' . $viewIntegration . '.ini';
    $integrationPath =  $f3->get('INTEGRATIONS') . $viewIntegration . '/view/index.htm';

    $ini = null;
    if (file_exists($integrationsIni)) {
        $ini = parse_ini_file($integrationsIni);
        $appTitle = $ini['app_title'] ?? null;
        $appDescription = $ini['app_description'] ?? 'No description available';
        $appIcon = $ini['app_icon'] ?? $f3->get('PUBLIC') . '/integrations/' . $viewIntegration . '.png';

        if (file_exists($integrationsFile)) {
            $integrationScript = null;

            if (file_exists($integrationPath)) {
                $integrationScript = 'integrations/' . $viewIntegration . '/view/index.php';
                if (file_exists(ROOT . 'views/admin/' . $integrationScript)) {
                    require_once(ROOT . 'views/admin/' . $integrationScript);
                }
                $integrationPath = 'integrations/' . $viewIntegration . '/view/index.htm';
            } else {
                $integrationPath = null;
            }

            $f3->set('PAGE.title', $appTitle . ' - Integrations');
            $f3->set('integration.title', $appTitle);
            $f3->set('integration.description', $appDescription);
            $f3->set('viewIntegration', $viewIntegration);
            $f3->set('viewIntegrationPath', $integrationPath);

            $f3->set('breadcrumbs', [
                [
                    "name" => "Integrations",
                    "slug" => "integrations"
                ],
                [
                    "name" => $appTitle,
                    "slug" => "integrations"
                ]
            ]);
        }
    } else {
        $f3->reroute($f3->get('SITE.uri_backend') . '/integrations');
    }
} else {
    $integrationsArr = array();
    if ($utils->isDir($integrationsPath)) {
        $integrations = scandir($integrationsPath);

        foreach ($integrations as $plugin) {
            // Skip current (.) and parent (..) directories
            if ($plugin === '.' || $plugin === '..') {
                continue;
            }

            // Construct the path to the plugin PHP file
            $pluginPath = $integrationsPath . '/' . $plugin . '/' . $plugin . '.php';
            $pluginIni = $integrationsPath . '/' . $plugin . '/' . $plugin . '.ini';

            // Check if the plugin file exists and add to the array
            if (file_exists($pluginPath) && file_exists($pluginIni)) {
                $ini = parse_ini_file($pluginIni);
                $appTitle = $ini['app_title'] ?? 'Unknown Title';
                $appDescription = $ini['app_description'] ?? 'No description available';
                $appIcon = $ini['app_icon'] ?? $f3->get('PUBLIC') . '/integrations/' . $plugin . '.png';

                $integrationsArr[] = [
                    'id' => $plugin,
                    'title' => $appTitle,
                    'description' => $appDescription,
                    'icon' => $appIcon
                ];
            }
        }
    }

    // Set the listIntegrations variable
    $f3->set('listIntegrations', $integrationsArr);
}
