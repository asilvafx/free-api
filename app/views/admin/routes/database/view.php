<?php

$f3->set('PAGE.title', 'Database');
$f3->set('PAGE.slug', 'database');
$f3->set('breadcrumbs', [
    [
        "name" => "Database",
        "slug" => "database"
    ]
]);

global $siteDb;

$collections = array();
if ($siteDb) {
    $collections = $siteDb->exec(
        'SELECT * FROM sqlite_master ' .
            'WHERE type="table"'
    );
}
$f3->set('collectionsList', $collections);
