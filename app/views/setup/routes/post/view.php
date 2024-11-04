<?php

use YoHang88\LetterAvatar\LetterAvatar;

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    try {
        // Initialize response class (assuming you have one)
        $response = new Response();

        // Fetch the database instance (assuming you use Fat-Free or similar)
        global $db;

        // Retrieve and decode the JSON payload
        $inputData = json_decode(file_get_contents('php://input'), true);

        // Retrieve the form data sent via POST
        $siteName = isset($inputData['siteName']) ? htmlspecialchars($inputData['siteName']) : null;
        $siteUrl = isset($inputData['siteUrl']) ? htmlspecialchars($inputData['siteUrl']) : null;
        $siteLogo = !isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK ? null : $_FILES['file'];
        $smtpScheme = isset($inputData['smtpScheme']) ? htmlspecialchars($inputData['smtpScheme']) : null;
        $smtpPort = isset($inputData['smtpPort']) ? htmlspecialchars($inputData['smtpPort']) : null;
        $smtpHost = isset($inputData['smtpHost']) ? htmlspecialchars($inputData['smtpHost']) : null;
        $smtpUser = isset($inputData['smtpUser']) ? htmlspecialchars($inputData['smtpUser']) : null;
        $smtpPassword = isset($inputData['smtpPassword']) ? htmlspecialchars($inputData['smtpPassword']) : null;
        $userId = isset($inputData['userId']) ? htmlspecialchars($inputData['userId']) : null;
        $userName = isset($inputData['userName']) ? htmlspecialchars($inputData['userName']) : null;
        $userEmail = isset($inputData['userEmail']) ? htmlspecialchars($inputData['userEmail']) : null;
        $userPassword = isset($inputData['userPassword']) ? htmlspecialchars($inputData['userPassword']) : null;
        $createdAt = isset($inputData['createdAt']) ? htmlspecialchars($inputData['createdAt']) : null;

        // Validate required fields
        if (!$siteName || !$siteUrl || !$smtpHost || !$smtpUser || !$smtpPassword || !$userName || !$userEmail || !$userPassword) {
            $response->json('error', 'Missing required fields.');
            exit;
        }

        // Crypt user password
        $crypt = new Crypt;
        $userPasswordCrypt = $crypt->generate($userPassword);

        // Prepare the database query (Site)
        $site = new DB\SQL\Mapper($db, 'site');
        $site->reset();

        // Set the form data into the database fields
        $site->site_name = $siteName;
        $site->site_title = 'Powered by F3';
        $site->site_description = 'Discover F3, a versatile backend framework that seamlessly integrates with any frontend application. Learn how this CMS framework can elevate your web development projects with an example.';
        $site->site_keywords = 'f3, framework, cms, example';
        $site->smtp_scheme = $smtpScheme;
        $site->smtp_port = $smtpPort;
        $site->smtp_host = $smtpHost;
        $site->smtp_user = $smtpUser;
        $site->smtp_mail = $smtpUser;
        $site->smtp_pass = $smtpPassword;
        $site->setup_wizzard = 1;
        $site->enable_frontend = 0;
        $site->enable_api = 0;
        $site->enable_register = 0;
        $site->uri_backend = 'cp-admin';
        $site->uri_auth = 'auth';
        $site->color_primary = '#3b2ef5';
        $site->color_primary_text = '#ffffff';
        $site->color_dark = '#1d222b';
        $site->color_light = '#dfe2e7';
        $site->color_dark_secondary = '#272c34';
        $site->color_light_secondary = '#f9fafb';

        $logoPath = 'public/assets/img/icon.png';
        if(!empty($siteLogo)){
        $utils = new Utils;
        $uploadedLogo = $utils->uploadFile($file, 'public/assets/img/');
        if (!$uploadedLogo) {
            $response->json('error', 'Failed to upload logo file.');
            exit;
        } else {
            $logoPath = $uploadedLogo; 
        }
        }

        // Save logo path in Db
        $site->site_logo = $logoPath;

        // Set up manifest files
        $manifestDir = 'public/manifest/';

        $utils->deleteDirectory($manifestDir);
        if (!file_exists($manifestDir)) {
            mkdir($manifestDir, 0755, true);
        }
        $fav = new Favicon($logoPath);

        $fav->setCompression(Favicon::COMPRESSION_VERYHIGH);

        $fav->setConfig(array(
            'apple-background'    => Favicon::COLOR_BLUE,
            'apple-margin'        => 15,
            'android-background'  => Favicon::COLOR_GREEN,
            'android-margin'      => 15,
            'android-name'        => $siteName,
            'android-url'         => $f3->get('SCHEME') . '://' .$f3->get('HOST'),
            'android-orientation' => Favicon::ANDROID_PORTRAIT,
            'ms-background'       => Favicon::COLOR_GREEN,
        ));

        $fav->createAllAndGetHtml();

        // Save the data to the database
        $site->save();

        // Prepare the database query (Users)
        $users = new DB\SQL\Mapper($db, 'users');
        $users->reset();
        $d = time();

        if (empty($userId)) {
            $rand = $crypt->generateRandomString(3);
            $userId = 'us_' . $d . $rand;
        }

        if (empty($createdAt)) {
            $createdAt = $d;
        }

        $avatar = new LetterAvatar($userName, 'square', 100);
        $user_avatar = $avatar->setColor($f3->get('SITE.color_primary'), $f3->get('SITE.color_primary_text'));

        $users->user_id = $userId;
        $users->displayName = $userName;
        $users->avatar = $user_avatar;
        $users->email = $userEmail;
        $users->crypt = $userPasswordCrypt;
        $users->confirmed = 1;
        $users->is_admin = 1;
        $users->status = 1;
        $users->created_at = $createdAt;
        $users->role = null;
        $users->is_admin = 1;
        $users->is_super_admin = 1;
        $users->is_developer = 1;

        // Save the data to the database
        $users->save();

        // Send a success response
        $response->json('success', (!empty($f3->get('SITE.uri_auth')) ? $f3->get('SITE.uri_auth') : 'auth'));
        exit;
    } catch (Exception $e) {
        // Send error message if something went wrong
        $response->json('error', 'Unable to process your request. Error: ' . $e->getMessage());
    }
} else {
    // If the request is not POST, redirect or return an error
    $f3->reroute('/');
}
exit;
