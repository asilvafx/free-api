<?php

$f3->set('PAGE.title', 'Settings');
$f3->set('PAGE.slug', 'settings');
$f3->set('breadcrumbs', [
    [
        "name" => "Settings",
        "slug" => "settings"
    ]
]);

global $db;

$response = new Response;
$utils = new Utils;
$crypt = new Crypt;


if (isset($_GET['confirm-update']) && $_SERVER['REQUEST_METHOD'] === "POST") {

    // Validate CSRF Token
    $csrf = (isset($body['token']) ? $body['token'] : $f3->get('POST.token'));

    if ($csrf != $f3->get('SESSION.token')) {
        $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
        exit;
    }

    $cryptInput = isset($_POST['crypt']) ? base64_decode($_POST['crypt']) : null;

    $users = new DB\SQL\Mapper($db, 'users');
    $users->load(array('user_id=?', $f3->get('USER.id')));

    if ($crypt->verify($cryptInput, $users->crypt)) {
        $response->json('success', null);
    } else {
        $response->json('error', 'Password does not match with our records. Please try again.');
    }
    exit;
}

if (isset($_GET['update']) && $_SERVER['REQUEST_METHOD'] === "POST") {

    $body = json_decode(file_get_contents('php://input'), true);

    // Validate CSRF Token
    $csrf = (isset($body['token']) ? $body['token'] : $f3->get('POST.token'));

    if ($csrf != $f3->get('SESSION.token')) {
        $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
        exit;
    }

    // Retrieve the schema data
    $schema = (isset($body['schema']) ? $body['schema'] : $f3->get('POST.schema'));

    if (!empty($schema)) {

        try {
            // Execute the query 

            $tableName = 'site';

            $siteName = isset($schema['siteName']) ? htmlspecialchars_decode($schema['siteName']) : null;
            $siteTitle = isset($schema['siteTitle']) ? htmlspecialchars_decode($schema['siteTitle']) : null;
            $siteDescription = isset($schema['siteDescription']) ? htmlspecialchars_decode($schema['siteDescription']) : null;
            $siteKeywords = isset($schema['siteKeywords']) ? htmlspecialchars_decode($schema['siteKeywords']) : null;
            $siteFrontend = isset($schema['siteFrontend']) ? htmlspecialchars_decode($schema['siteFrontend']) : null;
            $siteRegister = isset($schema['siteRegister']) ? htmlspecialchars_decode($schema['siteRegister']) : null;
            $mailHost = isset($schema['mailHost']) ? htmlspecialchars_decode($schema['mailHost']) : null;
            $mailPort = isset($schema['mailPort']) ? htmlspecialchars_decode($schema['mailPort']) : null;
            $mailScheme = isset($schema['mailScheme']) ? htmlspecialchars_decode($schema['mailScheme']) : null;
            $mailUsername = isset($schema['mailUsername']) ? htmlspecialchars_decode($schema['mailUsername']) : null;
            $mailPassword = isset($schema['mailPassword']) ? htmlspecialchars_decode($schema['mailPassword']) : null;
            $colorPrimary = isset($schema['colorPrimary']) ? htmlspecialchars_decode($schema['colorPrimary']) : null;
            $colorPrimaryText = isset($schema['colorPrimary']) ? htmlspecialchars_decode($schema['colorPrimaryText']) : null;
            $colorLight = isset($schema['colorLight']) ? htmlspecialchars_decode($schema['colorLight']) : null;
            $colorLightSecondary = isset($schema['colorLightSecondary']) ? htmlspecialchars_decode($schema['colorLightSecondary']) : null;
            $colorDark = isset($schema['colorDark']) ? htmlspecialchars_decode($schema['colorDark']) : null;
            $colorDarkSecondary = isset($schema['colorDarkSecondary']) ? htmlspecialchars_decode($schema['colorDarkSecondary']) : null;
            $uriBackend = isset($schema['uriBackend']) ? htmlspecialchars_decode($schema['uriBackend']) : null;
            $uriAuth = isset($schema['uriAuth']) ? htmlspecialchars_decode($schema['uriAuth']) : null;

            if (empty($siteName)) {
                $response->json('error', 'Invalid information. Fill all the required fields and try again.');
                exit;
            }

            $site = new DB\SQL\Mapper($db, $tableName);
            $site->load(array('id>?', 0));

            if ($site->dry()) {
                $response->json('error', 'Site was not found in our system.');
                exit;
            }

            $logoPath = $site->site_logo;

            if (isset($_FILES['file'])) {
                $logo = $_FILES['file'];

                $oldLogo = $logoPath;

                $logoPath = $utils->uploadFile($logo, 'public/assets/img/');

                if (!$logoPath) {
                    $response->json('error', 'Failed to upload logo.');
                    exit;
                }

                if (file_exists($oldLogo)) {
                    unlink($oldLogo);
                }
            }

            $site->site_name = $siteName;
            $site->site_title = $siteTitle;
            $site->site_description = $siteDescription;
            $site->site_keywords = $siteKeywords;
            $site->site_logo = $logoPath;
            $site->enable_frontend = $siteFrontend;
            $site->enable_register = $siteRegister;
            $site->smtp_host = $mailHost;
            $site->smtp_mail = $mailUsername;
            $site->smtp_port = $mailPort;
            $site->smtp_scheme = $mailScheme;
            $site->smtp_user = $mailUsername;
            $site->smtp_pass = $mailPassword;
            $site->uri_backend = $uriBackend;
            $site->uri_auth = $uriAuth;
            $site->color_primary = $colorPrimary;
            $site->color_primary_text = $colorPrimaryText;
            $site->color_dark = $colorDark;
            $site->color_light = $colorLight;
            $site->color_dark_secondary = $colorDarkSecondary;
            $site->color_light_secondary = $colorLightSecondary;

            if ($site->save()) {

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
                    'android-url'         => $f3->get('SITE.base_url'),
                    'android-orientation' => Favicon::ANDROID_PORTRAIT,
                    'ms-background'       => Favicon::COLOR_GREEN,
                ));

                $fav->createAllAndGetHtml();

                $response->json('success', 'Settings was successfully updated.');
            } else {
                $response->json('error', 'There was an error processing your request. Please, try again.');
            }
        } catch (Exception $e) {
            // Handle any SQL errors
            $response->json('error', 'Error fetching: ' . $e->getMessage());
        }
    } else {
        $response->json('error', 'No data entry provided.');
    }

    exit;
}
