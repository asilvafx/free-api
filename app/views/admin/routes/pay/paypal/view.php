<?php

global $f3;
global $db;

$f3->set('PAGE.title', 'Paypal');
$f3->set('PAGE.slug', 'pay');
$f3->set('breadcrumbs', [
    [
        "name" => "Payments",
        "slug" => "pay"
    ],
    [
        "name" => "Paypal",
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

            $paypalStatus = isset($schema['paypal']) ? htmlspecialchars_decode($schema['paypal']) : null;
            $paypalPk = isset($schema['paypal_pk']) ? htmlspecialchars_decode($schema['paypal_pk']) : null;
            $paypalSk = isset($schema['paypal_sk']) ? base64_decode($schema['paypal_sk']) : null;

            $site = new DB\SQL\Mapper($db, $tableName);
            $site->load(array('id>?', 0));

            if ($site->dry()) {
                $response->json('error', 'Site was not found in our system.');
                exit;
            }

            $site->paypal_status = (int)$paypalStatus;
            $site->paypal_pk = $paypalPk;
            $site->paypal_sk = $paypalSk;

            if ($site->save()) {
                $response->json('success', 'Paypal was successfully updated.');
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