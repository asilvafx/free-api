<?php

$f3->set('PAGE.title', 'Not Found');
$f3->set('PAGE.slug', 'access');
$f3->set('breadcrumbs', [
    [
        "name" => "Access",
        "slug" => "access"
    ],
    [
        "name" => "Users",
        "slug" => "access/users"
    ],
    [
        "name" => "Edit User",
        "slug" => "access"
    ]
]);

global $db;

$query = !empty($f3->get('GET.id')) ? htmlspecialchars_decode($f3->get('GET.id')) : null;

if (isset($_GET['password']) && $_SERVER['REQUEST_METHOD'] === "POST") {
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
        $tableName = 'users';

        $user_id = isset($schema['userId']) ? htmlspecialchars_decode($schema['userId']) : null;
        $user_crypt = isset($schema['crypt']) ? base64_decode($schema['crypt']) : null;

        if (empty($user_id) || empty($user_crypt)) {
            $response->json('error', 'Missing Data. Please fill all required fields and try again.');
            exit;
        }

        $user = new DB\SQL\Mapper($db, $tableName);
        $user->load(array('user_id=?', $user_id));

        if ($user->dry()) {
            $response->json('error', 'User was not found in our system.');
            exit;
        }

        try {
            $crypt = new Crypt;

            $new_password = $crypt->generate($user_crypt);
            // Execute the query   
            $user->crypt = $new_password;
            $user->save();

            $response->json('success', 'User password was successfully updated.');
        } catch (Exception $e) {
            // Handle any SQL errors
            $response->json('error', 'Error adding entry: ' . $e->getMessage());
        }
    } else {
        $response->json('error', 'No data provided for the new entry.');
    }

    exit;
} else 
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
        $tableName = 'users';

        $query = isset($schema['userId']) ? htmlspecialchars_decode($schema['userId']) : null;

        if (empty($query)) {
            $response->json('error', 'Missing Data. Please fill all required fields and try again.');
            exit;
        }

        $user = new DB\SQL\Mapper($db, $tableName);
        $user->load(array('user_id=?', $query));

        if ($user->dry()) {
            $response->json('error', 'User was not found in our system.');
            exit;
        }

        try {
            // Execute the query   
            $user->erase();

            $response->json('success', 'User delete successfully.');
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
        $tableName = 'users';

        $user_id = isset($schema['userId']) ? htmlspecialchars_decode($schema['userId']) : null;
        $displayName = isset($schema['displayName']) ? htmlspecialchars_decode($schema['displayName']) : null;
        $email = isset($schema['email']) ? filter_var($schema['email'], FILTER_SANITIZE_EMAIL) : null;
        $role = $schema['role'];
        $is_admin = $schema['is_admin'];

        if (empty($email) || empty($displayName)) {
            $response->json('error', 'Missing Data. Please fill all required fields and try again.');
            exit;
        }

        $user = new DB\SQL\Mapper($db, $tableName);
        $user->load(array('user_id=?', $user_id));

        if ($user->dry()) {
            $response->json('error', 'User was not found in our system.');
            exit;
        }

        if ($f3->get('CXT')->user_id === $user_id) {
            if ($displayName != $user->displayName || $email != $user->email ||  $is_admin != $user->is_admin || $role != $user->role) {

                $response->json('error', 'Action cannot be applied on yourself.');
                exit;
            }
        }

        try {

            // Execute the query  
            $user->displayName = $displayName;
            $user->email = $email;
            $user->role = $role;
            $user->is_admin = $is_admin;
            $user->save();

            $response->json('success', 'User updated successfully.');
        } catch (Exception $e) {
            // Handle any SQL errors
            $response->json('error', 'Error adding entry: ' . $e->getMessage());
        }
    } else {
        $response->json('error', 'No data provided for the new entry.');
    }

    exit;
}


if (!empty($query)) {

    $user = new DB\SQL\Mapper($db, 'users');
    $user->load(array('user_id=?', $query));

    if ($user->dry()) {
        $f3->reroute('/' . $f3->get('SITE.uri_backend') . '/access');
        return false;
    } else {

        $f3->set('PAGE.title', 'Edit User');

 
        $f3->set('USER', $user);  

        $userId = $f3->get('CXT')->user_id;

        if ($userId === $query) {
            $f3->set('userSelf', true);
        } else {
            $f3->set('userSelf', false);
        }

        $roles_all = $db->exec('SELECT * FROM roles');

        $f3->set('rolesAll', $roles_all); 

    }
} else {
    $f3->set('notfound', true);
    $f3->reroute('/' . $f3->get('SITE.uri_backend') . '/access');
    return false;
}
