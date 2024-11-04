<?php

$f3->set('PAGE.title', 'Add User');
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
        "name" => "New User",
        "slug" => "access"
    ]
]);

global $db;

$query = !empty($f3->get('GET.view')) ? htmlspecialchars_decode($f3->get('GET.view')) : null;


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

        $displayName = isset($schema['displayName']) ? htmlspecialchars_decode($schema['displayName']) : null;
        $email = isset($schema['email']) ? filter_var($schema['email'], FILTER_SANITIZE_EMAIL) : null;
        $password = isset($schema['crypt']) ? htmlspecialchars_decode($schema['crypt']) : null;
        $role = $schema['role'];
        $is_admin = $schema['is_admin'];
        $is_super_admin = $schema['is_super_admin'];
        $is_developer = $schema['is_developer'];

        if (!$email) {
            $response->json('error', 'Invalid Email. Please enter a different email and try again.');
            exit;
        }

        $user = new DB\SQL\Mapper($db, $tableName);
        $user->load(array('email=?', $email));

        if (!$user->dry()) {
            $response->json('error', 'Email address already exists in our system.');
            exit;
        }

        try {
            $crypt = new Crypt;

            $d = time();
            $rand = $crypt->generateRandomString(3);
            $user_id = 'us_' . $d . $rand;
            $twofactor = new TwoFactor;


            // Execute the query
            $user->reset();
            $user->user_id = $user_id;
            $user->displayName = $displayName;
            $user->email = $email;
            $user->crypt = $crypt->generate($password);
            $user->role = $role;
            $user->is_admin = $is_admin;
            $user->is_super_admin = $is_super_admin;
            $user->is_developer = $is_developer;
            $user->confirmed = 1;
            $user->status = 1;
            $user->twofactor = 0;
            $user->twofactor_sk = $twofactor->generateSecretKey();
            $user->created_at = time();
            $user->save();

            $response->json('success', 'New user was successfully created.');
        } catch (Exception $e) {
            // Handle any SQL errors
            $response->json('error', 'Error adding entry: ' . $e->getMessage());
        }
    } else {
        $response->json('error', 'No data provided for the new entry.');
    }

    exit;
}

$roles_all = $db->exec('SELECT * FROM roles');

$f3->set('rolesAll', $roles_all);
