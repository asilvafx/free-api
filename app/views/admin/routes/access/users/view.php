<?php

$f3->set('PAGE.title', 'Users');
$f3->set('PAGE.slug', 'access');
$f3->set('breadcrumbs', [
    [
        "name" => "Access",
        "slug" => "access"
    ],
    [
        "name" => "Users",
        "slug" => "users"
    ]
]);

global $db;

$offsetView = !empty($f3->get('GET.page')) ? (int)$f3->get('GET.page') : 1;
$limitItemsPerView = 15;
$offsetValue = ($offsetView * $limitItemsPerView) - $limitItemsPerView;
$sqlView = 'SELECT * FROM users ORDER BY id DESC LIMIT ' . $limitItemsPerView . ' OFFSET ' . $offsetValue;
$users_list = $db->exec($sqlView);

$users_all = $db->exec('SELECT * FROM users');
$totalItems = count($users_all);

$pagination = ceil($totalItems / $limitItemsPerView);

$paginationArr = array();
$i = 0;

while ($i < $pagination) {
    $i++;
    $paginationArr[] = $i;
}

$f3->set('usersList', $users_list);
$f3->set('usersAll', $totalItems);
$f3->set('usersPagination', $paginationArr);
$f3->set('usersCurrentPage', $offsetView);
