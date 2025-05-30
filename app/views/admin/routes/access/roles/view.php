<?php

$f3->set('PAGE.title', 'Roles');
$f3->set('PAGE.slug', 'access');
$f3->set('breadcrumbs', [
    [
        "name" => "Access",
        "slug" => "access"
    ],
    [
        "name" => "Roles",
        "slug" => "roles"
    ]
]);
 
$query = new Query;

$offsetView = !empty($f3->get('GET.page')) ? (int)$f3->get('GET.page') : 1;
$limitItemsPerView = 15;
$offsetValue = ($offsetView * $limitItemsPerView) - $limitItemsPerView;
$sqlView = 'ORDER BY id DESC LIMIT ' . $limitItemsPerView . ' OFFSET ' . $offsetValue;
$roles_list = $query->load('roles', $sqlView);
$roles_all = $query->load('roles');
$totalItems = count($roles_all);

$pagination = ceil($totalItems / $limitItemsPerView);

$paginationArr = array();
$i = 0;

while ($i < $pagination) {
    $i++;
    $paginationArr[] = $i;
}

$f3->set('rolesList', $roles_list);
$f3->set('rolesAll', $totalItems);
$f3->set('rolesPagination', $paginationArr);
$f3->set('rolesCurrentPage', $offsetView);
