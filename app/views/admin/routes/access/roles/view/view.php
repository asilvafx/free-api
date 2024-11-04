<?php

$f3->set('PAGE.title', 'Not Found');
$f3->set('PAGE.slug', 'access');
$f3->set('breadcrumbs', [
    [
        "name" => "Access",
        "slug" => "access"
    ],
    [
        "name" => "Roles",
        "slug" => "access/roles"
    ],
    [
        "name" => "New Role",
        "slug" => "access"
    ]
]);

global $db;

$query = !empty($f3->get('GET.id')) ? htmlspecialchars_decode($f3->get('GET.id')) : null;

if (!empty($query)) {

    $role = new DB\SQL\Mapper($db, 'roles');
    $role->load(array('id=?', $query));

    if ($role->dry()) {
        $f3->reroute('/' . $f3->get('SITE.uri_backend') . '/access');
        return false;
    } else {
        $roleName = $role->name;
        $f3->set('PAGE.title', 'Role: ' . $roleName);

        $f3->set('role.id', $query);
        $f3->set('role.name', $roleName);
        $f3->set('role.description', $role->description);
        $f3->set('role.access', $role->access);
    }
} else {
    $f3->set('notfound', true);
    $f3->reroute('/' . $f3->get('SITE.uri_backend') . '/access');
    return false;
}
