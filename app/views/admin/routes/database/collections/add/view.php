<?php

$f3->set('PAGE.title', 'New Collection');
$f3->set('PAGE.slug', 'database');
$f3->set('breadcrumbs', [
    [
        "name" => "Database",
        "slug" => "database"
    ],
    [
        "name" => "New Collection",
        "slug" => "database"
    ]
]);

$f3->set('TOKEN', $f3->get('SESSION.token'));

global $siteDb;

if (isset($_GET['save']) && $_SERVER['REQUEST_METHOD'] === "POST") {

    $response = new Response;

    $body = json_decode(file_get_contents('php://input'), true);

    // Validate CSRF Token
    $csrf = (isset($body['token']) ? $body['token'] : $f3->get('POST.token'));

    if ($csrf != $f3->get('SESSION.token')) {
        $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
    } else {
        $tableName = (isset($body['name']) ? $body['name'] : $f3->get('POST.name'));

        if (empty($tableName)) {
            $f3->set('error', 'Table name required.');
        } else {
            $schema = (isset($body['schema']) ? $body['schema'] : $f3->get('POST.schema'));

            // Prepare and execute the SQL query to create the table
            if (empty($schema)) {
                $sql = "CREATE TABLE `$tableName` ('')";
            } else {
                $sql = "CREATE TABLE `$tableName` ($schema)";
            }

            try {
                // Fetch the database instance (assuming you use Fat-Free or similar)
                $check_db = $siteDb->exec(
                    'SELECT * FROM sqlite_master ' .
                        'WHERE type="table" AND name="' . $tableName . '"'
                );
                if (count($check_db) > 0) {
                    $response->json('error', 'Table name already exists in our records.');
                } else {
                    $siteDb->exec($sql);
                    $response->json('success', $tableName);
                }
            } catch (Exception $e) {
                // Send error message if something went wrong
                $response->json('error', 'Unable to process your request. Error: ' . $e->getMessage());
            }
        }
    }
    exit;
} else {
    $token_gen = md5(uniqid(mt_rand(), true));
    $f3->set('TOKEN', $token_gen);
    $f3->set('SESSION.token', $f3->get('TOKEN'));
}
