<?php

$f3->set('PAGE.title', 'New Key');
$f3->set('PAGE.slug', 'api');
$f3->set('breadcrumbs', [
    [
        "name" => "API",
        "slug" => "api"
    ],
    [
        "name" => "New Key",
        "slug" => "api"
    ],
]);

global $db;


if (isset($_GET['key']) && $_SERVER['REQUEST_METHOD'] === "POST") {

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
        $tableName = 'api';

        $name = isset($schema['name']) ? htmlspecialchars_decode($schema['name']) : null;

        if (!$name) {
            $response->json('error', 'Invalid API Name. Please enter a different email and try again.');
            exit;
        }

        $collection = new DB\SQL\Mapper($db, $tableName);
        $collection->load(array('api_slug=?', $name));

        if (!$collection->dry()) {
            $response->json('error', 'API name ' . $name . ' already exists in our system.');
            exit;
        }
        $crypt = new Crypt;
        $d = time();
        $api_key = $crypt->generateRandomString(26);

        try {
            $collection->reset();
            $collection->api_slug = $name;
            $collection->api_key = $api_key;
            $collection->api_usage = 0;
            $collection->api_allowed_domains = "";
            $collection->created_by = $f3->get('USER.id');
            $collection->created_at = $d;
            $collection->save();

            $response->json('success', 'API Key was created successfully.');
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
