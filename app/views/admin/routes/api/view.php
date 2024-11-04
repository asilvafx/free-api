<?php

$f3->set('PAGE.title', 'API');
$f3->set('PAGE.slug', 'api');
$f3->set('breadcrumbs', [
    [
        "name" => "API",
        "slug" => "api"
    ]
]);

global $db;


if (isset($_GET['enable']) && $_SERVER['REQUEST_METHOD'] === "POST") {

    $response = new Response;

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

        // Define Table Name
        $tableName = 'site';

        $value = isset($schema['value']) ? htmlspecialchars_decode($schema['value']) : null;

        if (!$value && $value != 0) {
            $response->json('error', 'Invalid request. Please try again later.');
            exit;
        }

        $collection = new DB\SQL\Mapper($db, $tableName);
        $collection->load(array('id>=?', 0));

        if ($collection->dry()) {
            $response->json('error', 'Your request mismatch. Please try again later.');
            exit;
        }

        $d = time();

        try {
            $collection->enable_api = $value;
            $collection->save();

            $response->json('success', null);
        } catch (Exception $e) {
            // Handle any SQL errors
            $response->json('error', 'Error adding entry: ' . $e->getMessage());
        }
    } else {
        $response->json('error', 'No data provided for the new entry.');
    }

    exit;
}

$api_all = $db->exec('SELECT * FROM api');
$api_list = $db->exec('SELECT * FROM api ORDER BY id DESC LIMIT 5');

$f3->set('apiAll', $api_all);
$f3->set('apiList', $api_list);
