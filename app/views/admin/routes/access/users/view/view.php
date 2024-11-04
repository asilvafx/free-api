<?php

use YoHang88\LetterAvatar\LetterAvatar;

$f3->set('PAGE.title', 'Not Found');
$f3->set('PAGE.slug', 'access');
$f3->set('breadcrumbs', [
    [
        "name" => "Access",
        "slug" => "access"
    ],
    [
        "name" => "Users",
        "slug" => "access/users"
    ],
    [
        "name" => "View User",
        "slug" => "access"
    ]
]);

global $db;

$query = !empty($f3->get('GET.id')) ? htmlspecialchars_decode($f3->get('GET.id')) : null;

if ($query) {

    $user = new DB\SQL\Mapper($db, 'users');
    $user->load(array('user_id=?', $query));

    if ($user->dry()) {
        $f3->set('notfound', true);
        return false;
    }

    $displayName = $user->displayName;
    $user_avatar = $user->avatar;
    if (empty($user_avatar)) {
        $avatar = new LetterAvatar($displayName, 'square', 100);
        $user_avatar = $avatar->setColor($f3->get('SITE.color_primary'), $f3->get('SITE.color_primary_text'));
    }
    $f3->set('user.avatar', $user_avatar);

    $f3->set('PAGE.title', 'User: ' . $displayName);

    $f3->set('user.id', $query);
    $f3->set('user.displayName', $displayName);
    $f3->set('user.email', $user->email);
    $f3->set('user.phone', $user->phone);
    $f3->set('user.country', $user->phone);
    $f3->set('user.address', $user->address);
    $f3->set('user.dob', $user->dob);
    $f3->set('user.bio', $user->bio);
    $f3->set('user.role', $user->role);
    $f3->set('user.confirmed', $user->confirmed);
    $f3->set('user.status', $user->status);
    $f3->set('user.is_admin', $user->is_admin);
    $f3->set('user.created_at', $user->created_at);
} else {
    $f3->set('notfound', true);
}
