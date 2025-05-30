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
 
$query = new Query;

$offsetView = !empty($f3->get('GET.page')) ? (int)$f3->get('GET.page') : 1;
$limitItemsPerView = 15;
$offsetValue = ($offsetView * $limitItemsPerView) - $limitItemsPerView;
$sqlView = 'ORDER BY id DESC LIMIT ' . $limitItemsPerView . ' OFFSET ' . $offsetValue;
$users_list = $query->load('users', $sqlView);
$users_all = $query->load('users');
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
