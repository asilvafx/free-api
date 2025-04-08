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

$response = new Response;

$query = !empty($f3->get('GET.view')) ? htmlspecialchars_decode($f3->get('GET.view')) : null;

// Export functionality
if (isset($_GET['export-collection']) && $_SERVER['REQUEST_METHOD'] === "GET") {

    // Validate CSRF Token
    $csrf = $f3->get('GET.tkn');
    if ($csrf != $f3->get('SESSION.token')) {
        $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
        exit;
    }

    $tableName = $f3->get('GET.collection');

    if (empty($tableName)) {
        $response->json('error', 'Collection name is required.');
        exit;
    }

    // Check if collection exists
    $check_db = $siteDb->exec(
        'SELECT * FROM sqlite_master ' .
        'WHERE type="table" AND name="' . $tableName . '"'
    );

    if (count($check_db) == 0) {
        $response->json('error', 'Collection not found.');
        exit;
    }

    try {
        // Get the table schema
        $sql = "PRAGMA table_info(`$tableName`)";
        $schema = $siteDb->exec($sql);

        // Get the table data
        $sql = "SELECT * FROM `$tableName`";
        $data = $siteDb->exec($sql);

        // Prepare export data
        $export = [
            'collection' => $tableName,
            'schema' => $schema,
            'data' => $data
        ];

        // Set headers for download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $tableName . '_export_' . date('Y-m-d') . '.json"');

        // Output JSON
        echo json_encode($export, JSON_PRETTY_PRINT);
        exit;
    } catch (Exception $e) {
        $response->json('error', 'Error exporting collection: ' . $e->getMessage());
        exit;
    }
}

// Import functionality
if (isset($_GET['import-collection']) && $_SERVER['REQUEST_METHOD'] === "POST") {

    // Validate CSRF Token
    $csrf = $f3->get('POST.token');
    if ($csrf != $f3->get('SESSION.token')) {
        $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
        exit;
    }

    // Check if file was uploaded
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        $response->json('error', 'No file uploaded or upload error occurred.');
        exit;
    }

    // Check file type
    $fileType = mime_content_type($_FILES['import_file']['tmp_name']);
    if ($fileType !== 'application/json' && $fileType !== 'text/plain') {
        $response->json('error', 'Invalid file type. Please upload a JSON file.');
        exit;
    }

    try {
        // Read and decode the JSON file
        $importData = json_decode(file_get_contents($_FILES['import_file']['tmp_name']), true);

        // Validate import data structure
        if (!isset($importData['collection']) || !isset($importData['schema']) || !isset($importData['data'])) {
            $response->json('error', 'Invalid import file format.');
            exit;
        }

        $tableName = $importData['collection'];
        $schema = $importData['schema'];
        $data = $importData['data'];

        // Import strategy (from form)
        $importStrategy = $f3->get('POST.import_strategy');

        // Check if table exists
        $check_db = $siteDb->exec(
            'SELECT * FROM sqlite_master ' .
            'WHERE type="table" AND name="' . $tableName . '"'
        );

        $tableExists = (count($check_db) > 0);

        // Handle based on import strategy
        if ($importStrategy == 'replace' || !$tableExists) {
            // Drop table if it exists
            if ($tableExists) {
                $siteDb->exec("DROP TABLE IF EXISTS `$tableName`");
            }

            // Create new table
            $createSql = "CREATE TABLE `$tableName` (";
            $columns = [];

            foreach ($schema as $column) {
                $colDef = "`{$column['name']}` {$column['type']}";

                if ($column['notnull'] == 1) {
                    $colDef .= " NOT NULL";
                }

                if ($column['dflt_value'] !== null) {
                    $colDef .= " DEFAULT " . $column['dflt_value'];
                }

                if ($column['pk'] == 1) {
                    $colDef .= " PRIMARY KEY";
                }

                $columns[] = $colDef;
            }

            $createSql .= implode(", ", $columns) . ")";
            $siteDb->exec($createSql);

            // Import data
            if (!empty($data)) {
                foreach ($data as $row) {
                    $columns = array_keys($row);
                    $colStr = '`' . implode('`, `', $columns) . '`';

                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } elseif (is_numeric($value)) {
                            $values[] = $value;
                        } else {
                            $values[] = "'" . addslashes($value) . "'";
                        }
                    }

                    $valStr = implode(', ', $values);
                    $insertSql = "INSERT INTO `$tableName` ($colStr) VALUES ($valStr)";
                    $siteDb->exec($insertSql);
                }
            }

            $response->json('success', 'Collection imported successfully. The collection has been replaced with the imported data.');
        } elseif ($importStrategy == 'append') {
            // Append data to existing table
            if (!empty($data)) {
                foreach ($data as $row) {
                    $columns = array_keys($row);
                    $colStr = '`' . implode('`, `', $columns) . '`';

                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } elseif (is_numeric($value)) {
                            $values[] = $value;
                        } else {
                            $values[] = "'" . addslashes($value) . "'";
                        }
                    }

                    $valStr = implode(', ', $values);
                    $insertSql = "INSERT INTO `$tableName` ($colStr) VALUES ($valStr)";
                    $siteDb->exec($insertSql);
                }

                $response->json('success', 'Data appended to the existing collection successfully.');
            } else {
                $response->json('success', 'No data to import. The collection structure is maintained.');
            }
        } else {
            $response->json('error', 'Invalid import strategy specified.');
        }
    } catch (Exception $e) {
        $response->json('error', 'Error importing collection: ' . $e->getMessage());
    }

    exit;
}

if (isset($_GET['delete-table']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $token = $body['token'];
    $collection = $body['collection'];

    // Validate CSRF token
    if ($token != $f3->get('SESSION.token')) {
        $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
        exit;
    }

    try {
        $siteDb->exec("DROP TABLE `$collection`");
        $response->json('success', 'Table deleted successfully.');
    } catch(Exception $e) {
        $response->json('error', "Error deleting table: " . $e->getMessage());
    }
    exit;
}

if (isset($_GET['rename-table']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $token = $body['token'];
    $oldTableName = $body['old_table_name'];
    $newTableName = $body['new_table_name'];

    // Validate CSRF token
    if ($token != $f3->get('SESSION.token')) {
        $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
        exit;
    }

    if(empty($newTableName)){
        $response->json('error', 'New table name required.');
        exit;
    }

    try {
        $siteDb->exec("ALTER TABLE `$oldTableName` RENAME TO `$newTableName`");
        $response->json('success', 'Table renamed successfully.');
    } catch(Exception $e) {
        $response->json('error', "Error renaming table: " . $e->getMessage());
    }
    exit;
}

if (isset($_GET['update-fields']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $body = json_decode(file_get_contents('php://input'), true);
    $token = $body['token'];
    $collection = $body['collection'];
    $fields = $body['fields'];

    // Validate CSRF Token
    $csrf = ($body['token'] ?? $f3->get('POST.token'));

    if ($csrf != $f3->get('SESSION.token')) {
        $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
        exit;
    }

    foreach ($fields as $field) {
        $original = $field['original'];
        $newName = $field['name'];
        $newType = strtoupper($field['type']) === 'INTEGER' ? 'INTEGER' : 'TEXT';

        if ($original && $original !== $newName) {
            // Rename existing column
            $siteDb->exec("ALTER TABLE `$collection` RENAME COLUMN `$original` TO `$newName`");
            $siteDb->exec("ALTER TABLE `$collection` ALTER COLUMN `$newName` TYPE $newType");
        } elseif ($original) {
            // Just change type of existing field
            $siteDb->exec("ALTER TABLE `$collection` ALTER COLUMN `$original` TYPE $newType");
        } elseif (!$original && $newName) {
            // New field
            $siteDb->exec("ALTER TABLE `$collection` ADD COLUMN `$newName` $newType");
        }
    }

    $response->json('success', 'Fields updated successfully.');
    exit;
}

if (isset($_GET['delete-entry']) && $_SERVER['REQUEST_METHOD'] === "POST") {

    $body = json_decode(file_get_contents('php://input'), true);

    // Validate CSRF Token
    $csrf = ($body['token'] ?? $f3->get('POST.token'));

    if ($csrf != $f3->get('SESSION.token')) {
        $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
        exit;
    }

    // Check table name
    $tableName = ($body['collection'] ?? $f3->get('POST.collection'));

    if (empty($tableName)) {
        $response->json('error', 'An error has occurred, please try again later.');
        exit;
    }

    // Get the ID of the entry to delete
    $id = ($body['id'] ?? $f3->get('POST.id'));

    if (empty($id)) {
        $response->json('error', 'Entry ID is required for deletion.');
        exit;
    }

    // Find the primary key column name
    $sql = "PRAGMA table_info(`$tableName`)";
    $tableInfo = $siteDb->exec($sql);
    $primaryKeyColumn = null;

    foreach ($tableInfo as $column) {
        if ($column['pk'] == 1) {
            $primaryKeyColumn = $column['name'];
            break;
        }
    }

    // If no primary key is found, use the first column or 'id' as default
    if (!$primaryKeyColumn) {
        $primaryKeyColumn = !empty($tableInfo) ? $tableInfo[0]['name'] : 'id';
    }

    // Build the SQL query
    $sql = "DELETE FROM `$tableName` WHERE `$primaryKeyColumn` = '$id'";

    try {
        // Execute the query
        $siteDb->exec($sql);
        $response->json('success', 'Entry was successfully deleted.');
    } catch (Exception $e) {
        // Handle any SQL errors
        $response->json('error', 'Error deleting entry: ' . $e->getMessage());
    }

    exit;
}

if (isset($_GET['update-entry']) && $_SERVER['REQUEST_METHOD'] === "POST") {

    $body = json_decode(file_get_contents('php://input'), true);

    // Validate CSRF Token
    $csrf = ($body['token'] ?? $f3->get('POST.token'));

    if ($csrf != $f3->get('SESSION.token')) {
        $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
        exit;
    }

    // Check table name
    $tableName = ($body['collection'] ?? $f3->get('POST.collection'));

    if (empty($tableName)) {
        $response->json('error', 'An error has occurred, please try again later.');
        exit;
    }

    // Get the ID of the entry to update
    $id = ($body['id'] ?? $f3->get('POST.id'));

    if (empty($id)) {
        $response->json('error', 'Entry ID is required for update.');
        exit;
    }

    // Retrieve the schema data
    $schema = ($body['schema'] ?? $f3->get('POST.schema'));

    if (!empty($schema)) {
        // Find the primary key column name
        $sql = "PRAGMA table_info(`$tableName`)";
        $tableInfo = $siteDb->exec($sql);
        $primaryKeyColumn = null;

        foreach ($tableInfo as $column) {
            if ($column['pk'] == 1) {
                $primaryKeyColumn = $column['name'];
                break;
            }
        }

        // If no primary key is found, use the first column or 'id' as default
        if (!$primaryKeyColumn) {
            $primaryKeyColumn = !empty($tableInfo) ? $tableInfo[0]['name'] : 'id';
        }

        // Prepare SET clause for UPDATE statement
        $setClause = [];

        foreach ($schema as $field => $value) {
            // Skip the ID field in the update
            if ($field === 'id') continue;

            // Handle value types
            if ($value === '') {
                // If value is an empty string, set to NULL
                $setClause[] = "`$field` = NULL";
            } elseif (is_numeric($value)) {
                // If it's a number, set as-is
                $setClause[] = "`$field` = $value";
            } else {
                // Escape and wrap strings in single quotes
                $setClause[] = "`$field` = '" . addslashes($value) . "'";
            }
        }

        // Join SET clause items
        $setClauseStr = implode(", ", $setClause);

        // Build the SQL query
        $sql = "UPDATE `$tableName` SET $setClauseStr WHERE `$primaryKeyColumn` = '$id'";

        try {
            // Execute the query
            $siteDb->exec($sql);
            $response->json('success', 'Entry was successfully updated.');
        } catch (Exception $e) {
            // Handle any SQL errors
            $response->json('error', 'Error updating entry: ' . $e->getMessage());
        }
    } else {
        $response->json('error', 'No data provided for the update.');
    }

    exit;
}

if (isset($_GET['add-entry']) && $_SERVER['REQUEST_METHOD'] === "POST") {

    $body = json_decode(file_get_contents('php://input'), true);

    // Validate CSRF Token
    $csrf = ($body['token'] ?? $f3->get('POST.token'));

    if ($csrf != $f3->get('SESSION.token')) {
        $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
        exit;
    }

    // Check table name
    $tableName = ($body['collection'] ?? $f3->get('POST.collection'));

    if (empty($tableName)) {
        $response->json('error', 'An error has occurred, please try again later.');
        exit;
    }


    // Retrieve the schema data
    $schema = ($body['schema'] ?? $f3->get('POST.schema'));

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
            $f3->set('collection.fields', $fields);
            if (empty($data)) {
                $f3->set('collection.data', null);
            } else {
                // Return both the field names and the data
                $f3->set('collection.data', $data);
            }


            $f3->set('PAGE.title', 'Collection - ' . $tableName);
            $f3->set('collection.name', $tableName);
            $f3->set('collection.fieldsCount', count($fields));
            $f3->set('collection.dataCount', $data ? count($data) : 0);
        }
    }
}
