<?php

$f3->set('PAGE.title', 'Permissions');
$f3->set('PAGE.slug', 'access');
$f3->set('breadcrumbs', [
    [
        "name" => "Access",
        "slug" => "access"
    ],
    [
        "name" => "Permissions",
        "slug" => "access"
    ]
]);

global $db;


if (isset($_GET['delete']) && $_SERVER['REQUEST_METHOD'] === "POST") {
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
        $tableName = 'permissions';

        $id = isset($schema['id']) ? htmlspecialchars_decode($schema['id']) : null;

        if (!$id) {
            $response->json('error', 'Invalid Name. Please enter a different email and try again.');
            exit;
        }

        $collection = new DB\SQL\Mapper($db, $tableName);
        $collection->load(array('id=?', $id));

        if ($collection->dry()) {
            $response->json('error', 'Permission was not found in our system.');
            exit;
        }

        if ($collection->default === 1) {
            $response->json('error', 'Permission protected by system defaults.');
            exit;
        }

        try {
            $collection->erase();

            $response->json('success', 'Permission was successfully deleted.');
        } catch (Exception $e) {
            // Handle any SQL errors
            $response->json('error', 'Error adding entry: ' . $e->getMessage());
        }
    } else {
        $response->json('error', 'No data provided for the new entry.');
    }

    exit;
} else 
if (isset($_GET['add']) && $_SERVER['REQUEST_METHOD'] === "POST") {

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
        $tableName = 'permissions';

        $name = isset($schema['name']) ? htmlspecialchars_decode($schema['name']) : null;

        if (!$name) {
            $response->json('error', 'Invalid Name. Please enter a different email and try again.');
            exit;
        }

        $collection = new DB\SQL\Mapper($db, $tableName);
        $collection->load(array('name=?', $name));

        if (!$collection->dry()) {
            $response->json('error', 'Permission ' . $name . ' already exists in our system.');
            exit;
        }

        try {
            $collection->reset();
            $collection->name = $name;
            $collection->default = 0;
            $collection->save();

            $response->json('success', 'New permission was successfully created.');
        } catch (Exception $e) {
            // Handle any SQL errors
            $response->json('error', 'Error adding entry: ' . $e->getMessage());
        }
    } else {
        $response->json('error', 'No data provided for the new entry.');
    }

    exit;
}

$permissionsAll = $db->exec('SELECT * FROM "permissions"');
$permissionsList = $db->exec('SELECT * FROM "permissions" ORDER BY id DESC');
$f3->set('permissionsAll', $permissionsAll);
$f3->set('permissionsList', $permissionsList);
