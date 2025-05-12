<?php

global $f3;

$f3->set('PAGE.title', 'E-Mail');
$f3->set('PAGE.slug', 'mail');
$f3->set('breadcrumbs', [
    [
        "name" => "E-Mail",
        "slug" => "mail"
    ]
]);


global $db;

$response = new Response;

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

            $mailHost = isset($schema['mailHost']) ? htmlspecialchars_decode($schema['mailHost']) : null;
            $mailPort = isset($schema['mailPort']) ? htmlspecialchars_decode($schema['mailPort']) : null;
            $mailScheme = isset($schema['mailScheme']) ? htmlspecialchars_decode($schema['mailScheme']) : null;
            $mailUsername = isset($schema['mailUsername']) ? htmlspecialchars_decode($schema['mailUsername']) : null;
            $mailPassword = isset($schema['mailPassword']) ? htmlspecialchars_decode($schema['mailPassword']) : null;

            $site = new DB\SQL\Mapper($db, $tableName);
            $site->load(array('id>?', 0));

            if ($site->dry()) {
                $response->json('error', 'Site was not found in our system.');
                exit;
            }

            $site->smtp_host = $mailHost;
            $site->smtp_mail = $mailUsername;
            $site->smtp_port = $mailPort;
            $site->smtp_scheme = $mailScheme;
            $site->smtp_user = $mailUsername;
            $site->smtp_pass = $mailPassword;

            if ($site->save()) {
                $response->json('success', 'Mail was successfully updated.');
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

