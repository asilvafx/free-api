<?php

$f3->set('PAGE.title', 'Collections');
$f3->set('PAGE.slug', 'database');
$f3->set('breadcrumbs', [
    [
        "name" => "Database",
        "slug" => "database"
    ],
    [
        "name" => "Collections",
        "slug" => "database"
    ]
]);

global $siteDb;

$query = !empty($f3->get('GET.view')) ? htmlspecialchars_decode($f3->get('GET.view')) : null;

$f3->set('TOKEN', $f3->get('SESSION.token'));

if (isset($_GET['add-entry']) && $_SERVER['REQUEST_METHOD'] === "POST") {

    $response = new Response;

    $body = json_decode(file_get_contents('php://input'), true);

    // Validate CSRF Token
    $csrf = (isset($body['token']) ? $body['token'] : $f3->get('POST.token'));

    if ($csrf != $f3->get('SESSION.token')) {
        $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
        exit;
    }

    // Check table name
    $tableName = (isset($body['collection']) ? $body['collection'] : $f3->get('POST.collection'));

    if (empty($tableName)) {
        $response->json('error', 'An error has occurred, please try again later.');
        exit;
    }


    // Retrieve the schema data
    $schema = (isset($body['schema']) ? $body['schema'] : $f3->get('POST.schema'));

    if (!empty($schema)) {
        // Prepare columns and values
        $columns = [];
        $values = [];

        foreach ($schema as $field => $value) {
            // Add the column name
            $columns[] = "`$field`";

            // Handle value types
            if ($value === '') {
                // If value is an empty string, insert NULL
                $values[] = "NULL";
            } elseif (is_numeric($value)) {
                // If it's a number, insert as-is
                $values[] = $value;
            } else {
                // Escape and wrap strings in single quotes
                $values[] = "'" . addslashes($value) . "'";
            }
        }

        // Join columns and values into strings
        $columns_str = implode(", ", $columns);
        $values_str = implode(", ", $values);

        // Build the SQL query
        $sql = "INSERT INTO `$tableName` ($columns_str) VALUES ($values_str)";

        try {
            // Execute the query
            $siteDb->exec($sql);
            $response->json('success', 'New entry was successfully added to this collection.');
        } catch (Exception $e) {
            // Handle any SQL errors
            $response->json('error', 'Error adding entry: ' . $e->getMessage());
        }
    } else {
        $response->json('error', 'No data provided for the new entry.');
    }

    exit;
} else {
    $token_gen = md5(uniqid(mt_rand(), true));
    $f3->set('TOKEN', $token_gen);
    $f3->set('SESSION.token', $f3->get('TOKEN'));

    if (empty($query)) {
        $f3->set('notfound', true);
    } else {
        $tableName = $query;
        // Check if collection exists
        $check_db = $siteDb->exec(
            'SELECT * FROM sqlite_master ' .
                'WHERE type="table" AND name="' . $tableName . '"'
        );
        if (count($check_db) == 0) {
            $f3->set('notfound', true);
        } else {

            // Get the table schema (column names)
            $sql = "PRAGMA table_info(`$tableName`)";
            $schema = $siteDb->exec($sql);

            // Prepare an array to store the field names
            $fields = [];
            foreach ($schema as $column) {
                $fields[] = ['name' => $column['name'], 'type' => $column['type'], 'notnull' => $column['notnull'], 'default' => $column['dflt_value'], 'key' => $column['pk']]; // Extract the field/column 
            }

            // Get the table data
            $sql = "SELECT * FROM `$tableName`";
            $data = $siteDb->exec($sql);

            // If the table has no data, return just the field names
            if (empty($data)) {
                $f3->set('collection.fields', $fields);
                $f3->set('collection.data', null);
            } else {
                // Return both the field names and the data
                $f3->set('collection.fields', $fields);
                $f3->set('collection.data', $data);
            }


            $f3->set('PAGE.title', 'Collection - ' . $tableName);
            $f3->set('collection.name', $tableName);
            $f3->set('collection.fieldsCount', count($fields));
            $f3->set('collection.dataCount', $data ? count($data) : 0);
        }
    }
}
