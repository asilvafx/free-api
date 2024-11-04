<?php

$f3->set('PAGE.title', 'Private Keys');
$f3->set('PAGE.slug', 'api');
$f3->set('breadcrumbs', [
    [
        "name" => "API",
        "slug" => "api"
    ],
    [
        "name" => "Private Keys",
        "slug" => "api"
    ],
]);
global $db;

$offsetView = !empty($f3->get('GET.page')) ? (int)$f3->get('GET.page') : 1;
$limitItemsPerView = 15;
$offsetValue = ($offsetView * $limitItemsPerView) - $limitItemsPerView;
$sqlView = 'SELECT * FROM api ORDER BY id DESC LIMIT ' . $limitItemsPerView . ' OFFSET ' . $offsetValue;
$keys_list = $db->exec($sqlView);

$keys_all = $db->exec('SELECT * FROM api');
$totalItems = count($keys_all);

$pagination = ceil($totalItems / $limitItemsPerView);

$paginationArr = array();
$i = 0;

while ($i < $pagination) {
    $i++;
    $paginationArr[] = $i;
}

$f3->set('keysList', $keys_list);
$f3->set('keysAll', $totalItems);
$f3->set('keysPagination', $paginationArr);
$f3->set('keysCurrentPage', $offsetView);
