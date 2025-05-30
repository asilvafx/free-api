<?php

$f3->set('PAGE.title', 'Access');
$f3->set('PAGE.slug', 'access');
$f3->set('breadcrumbs', [
    [
        "name" => "Access",
        "slug" => "access"
    ]
]); 

$query = new Query;

$users_all = $query->load('users');
$users_list = $query->load('users', 'ORDER BY id DESC LIMIT 5');

$roles_all = $query->load('roles');
$roles_list = $query->load('roles', 'ORDER BY id DESC LIMIT 5');

$f3->set('rolesAll', $roles_all);
$f3->set('rolesList', $roles_list);
$f3->set('usersAll', $users_all);
$f3->set('usersList', $users_list);
