<?php

$utils = new Utils;

$sidenav_menu = '[
    {
        "title": "Access",
        "route": "access",
        "icon": "group"
    },
    {
        "title": "Database",
        "route": "database",
        "icon": "storage"
    },
    {
        "title": "API",
        "route": "api",
        "icon": "bolt"
    }, 
    {
        "title": "Files",
        "route": "files",
        "icon": "folder-open"
    },
    {
        "title": "Integrations",
        "route": "integrations",
        "icon": "puzzle"
    },
    {
        "title": "Settings",
        "route": "settings",
        "icon": "cog"
    },
    {
        "title": "Maintenance & Support",
        "route": "support",
        "icon": "life-ring"
    }
]';
$sideMenuItems = json_decode($sidenav_menu, true);

$f3->set('MENU.sidenav', $sideMenuItems);

$app_menu_json = '[]';
$f3->set('app_menu_json', $app_menu_json);


function addMenuItem($title, $route, $icon, $hasNew=false)
{
    global $f3;

    try {
        // Decode the JSON string into a PHP array
        $jsonFile = $f3->get('app_menu_json');
        $menuItems = json_decode($jsonFile, true);

        $newMenuItem = [
            "title" => $title,
            "route" => $route,
            "icon" => $icon ?? 'circle',
            "hasNew" => $hasNew
        ];

        $menuItems[] = $newMenuItem;
        $f3->set('app_menu_json', json_encode($menuItems, JSON_PRETTY_PRINT));
    } catch (Exception $e) {
        return $e;
    }
}

$arrIntegrationsPath = INTEGRATIONS;
if ($utils->isDir($arrIntegrationsPath)) {
    $integrations = scandir($arrIntegrationsPath);
    foreach ($integrations as $plugin) {
        $pluginPath = $arrIntegrationsPath . '/' . $plugin . '/' . $plugin . '.php';
        if (file_exists($pluginPath)) {
            require_once($pluginPath);
        }
    }
} else {
    mkdir($arrIntegrationsPath, 0755, true);
}
$f3->set('INTEGRATIONS', $arrIntegrationsPath);

// Decode the JSON string into a PHP array
$menuItems = json_decode($f3->get('app_menu_json'), true);

// Set the menu items array in the F3 framework
$f3->set('MENU.appnav', $menuItems);
$f3->set('MENU.appcount', count($menuItems));


function getRoleNameById($roleid)
{
    $query = new Query;
    $response = $query->lookup('roles', 'id=', $roleid);
    if (isset($response->name)) {
        return $response->name;
    }
    return null;
}

function getUserNameById($userid)
{
    $query = new Query;
    $response = $query->lookup('users', 'user_id=', $userid);
    if (isset($response->displayName)) {
        return $response->displayName;
    }
    return null;
}

if (empty($f3->get('SESSION.token'))) {
    $csrf = md5(uniqid(mt_rand(), true));
    $f3->set('SESSION.token', $csrf);
}

$f3->set('TOKEN', $f3->get('SESSION.token'));
