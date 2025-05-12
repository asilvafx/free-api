<?php

$f3->set('PAGE.title', 'Access');
$f3->set('PAGE.slug', 'access');
$f3->set('breadcrumbs', [
    [
        "name" => "Access",
        "slug" => "access"
    ]
]);

global $db;

$users_all = $db->exec('SELECT * FROM users');
$users_list = $db->exec('SELECT * FROM users ORDER BY id DESC LIMIT 5');

$roles_all = $db->exec('SELECT * FROM roles');
$roles_list = $db->exec('SELECT * FROM roles ORDER BY id DESC LIMIT 5');

$f3->set('rolesAll', $roles_all);
$f3->set('rolesList', $roles_list);
$f3->set('usersAll', $users_all);
$f3->set('usersList', $users_list);
