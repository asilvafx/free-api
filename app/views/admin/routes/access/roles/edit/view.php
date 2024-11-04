<?php

$f3->set('PAGE.title', 'Not Found');
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
        "name" => "Edit Role",
        "slug" => "access"
    ]
]);

global $db;

$query = !empty($f3->get('GET.id')) ? htmlspecialchars_decode($f3->get('GET.id')) : null;

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
        $tableName = 'roles';

        $query = isset($schema['roleId']) ? htmlspecialchars_decode($schema['roleId']) : null;

        if (empty($query)) {
            $response->json('error', 'Missing Data. Please fill all required fields and try again.');
            exit;
        }

        $role = new DB\SQL\Mapper($db, $tableName);
        $role->load(array('id=?', $query));

        if ($role->dry()) {
            $response->json('error', 'Role was not found in our system.');
            exit;
        }

        if ($role->default === 1) {
            $response->json('error', 'Role protected by system defaults.');
            exit;
        }

        try {
            // Execute the query   
            $role->erase();

            $response->json('success', 'Role delete successfully.');
        } catch (Exception $e) {
            // Handle any SQL errors
            $response->json('error', 'Error adding entry: ' . $e->getMessage());
        }
    } else {
        $response->json('error', 'No data provided for the new entry.');
    }

    exit;
} else 
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

        $id = isset($schema['id']) ? htmlspecialchars_decode($schema['id']) : null;
        $name = isset($schema['name']) ? htmlspecialchars_decode($schema['name']) : null;
        $description = isset($schema['description']) ? htmlspecialchars_decode($schema['description']) : null;
        $access = isset($schema['access']) ? (is_array($schema['access']) ? htmlspecialchars_decode(implode(',', $schema['access'])) : htmlspecialchars_decode($schema['access'])) : null;
        $color = null;

        if (empty($id) || empty($name) || empty($access)) {
            $response->json('error', 'Invalid information. Fill all the required fields and try again.');
            exit;
        }

        $role = new DB\SQL\Mapper($db, $tableName);
        $role->load(array('id=?', $id));

        if ($role->dry()) {
            $response->json('error', 'Role was not found in our system.');
            exit;
        }

        try {
            // Execute the query 
            $role->name = $name;
            $role->description = $description;
            $role->access = $access;
            $role->save();

            $response->json('success', 'New role was successfully updated.');
        } catch (Exception $e) {
            // Handle any SQL errors
            $response->json('error', 'Error adding entry: ' . $e->getMessage());
        }
    } else {
        $response->json('error', 'No data provided for the new entry.');
    }

    exit;
}



$role = new DB\SQL\Mapper($db, 'roles');
$role->load(array('id=?', $query));

if ($role->dry()) {
    $f3->set('notfound', true);
    $f3->reroute('/' . $f3->get('SITE.uri_backend') . '/access');
    return false;
} else {

    $f3->set('PAGE.title', 'Edit Role');

    $f3->set('role.id', $query);
    $f3->set('role.name', $role->name);
    $f3->set('role.description', $role->description);
    $f3->set('role.access', $role->access);

    $permissions_all = $db->exec('SELECT * FROM permissions');

    $f3->set('permissionsAll', $permissions_all);
}
