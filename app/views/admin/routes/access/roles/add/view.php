<?php

$f3->set('PAGE.title', 'Add Role');
$f3->set('PAGE.slug', 'access');
$f3->set('breadcrumbs', [
    [
        "name" => "Access",
        "slug" => "access"
    ],
    [
        "name" => "Roles",
        "slug" => "access/roles"
    ],
    [
        "name" => "New Role",
        "slug" => "access"
    ]
]);

global $db;

if (isset($_GET['save']) && $_SERVER['REQUEST_METHOD'] === "POST") {

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
        $tableName = 'roles';

        $name = isset($schema['name']) ? htmlspecialchars_decode($schema['name']) : null;
        $description = isset($schema['description']) ? htmlspecialchars_decode($schema['description']) : null;
        $access = isset($schema['access']) ? (is_array($schema['access']) ? htmlspecialchars_decode(implode(',', $schema['access'])) : htmlspecialchars_decode($schema['access'])) : null;
        $color = null;

        if (!$name || !$access) {
            $response->json('error', 'Invalid information. Fill all the required fields and try again.');
            exit;
        }

        $role = new DB\SQL\Mapper($db, $tableName);
        $role->load(array('name=?', $name));

        if (!$role->dry()) {
            $response->json('error', 'Role ' . $name . ' already exists in our system.');
            exit;
        }

        try {
            // Execute the query
            $role->reset();
            $role->name = $name;
            $role->description = $description;
            $role->access = $access;
            $role->color = $color;
            $role->save();

            $response->json('success', 'New role was successfully created.');
        } catch (Exception $e) {
            // Handle any SQL errors
            $response->json('error', 'Error adding entry: ' . $e->getMessage());
        }
    } else {
        $response->json('error', 'No data provided for the new entry.');
    }

    exit;
}

$roles_all = $db->exec('SELECT * FROM permissions');

$f3->set('permissionsAll', $roles_all);
