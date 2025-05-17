<?php

global $f3;
global $db;

$f3->set('PAGE.title', 'Payments');
$f3->set('PAGE.slug', 'pay');
$f3->set('breadcrumbs', [
    [
        "name" => "Payments",
        "slug" => "pay"
    ]
]);

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

            $stripeStatus = isset($schema['stripe']) ? htmlspecialchars_decode($schema['stripe']) : null;
            $stripePk = isset($schema['stripe_pk']) ? htmlspecialchars_decode($schema['stripe_pk']) : null;
            $stripeSk = isset($schema['stripe_sk']) ? base64_decode($schema['stripe_sk']) : null;

            $site = new DB\SQL\Mapper($db, $tableName);
            $site->load(array('id>?', 0));

            if ($site->dry()) {
                $response->json('error', 'Site was not found in our system.');
                exit;
            }

            $site->stripe_status = (int)$stripeStatus;
            $site->stripe_pk = $stripePk;
            $site->stripe_sk = $stripeSk;

            if ($site->save()) {
                $response->json('success', 'Payment was successfully updated.');
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