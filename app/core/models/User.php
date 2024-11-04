<?php

use YoHang88\LetterAvatar\LetterAvatar;

class User extends \Prefab
{

    function Info($f3)
    {
        if ($f3->get('SESSION.loggedin') === true) {
            $username = $f3->get('SESSION.username');
            global $db;
            $user = new DB\SQL\Mapper($db, 'users');
            $user->load(array('email=?', $username));

            if ($user->dry()) {
                $f3->reroute('/auth/logout');
                return false;
            } else {
                $is_admin = false;
                if ($user->is_admin === 1) {
                    $is_admin = true;
                }

                // Square Shape, Size 64px
                if (empty($user->avatar)) {
                    $avatar = new LetterAvatar($user->displayName, 'square', 100);
                    $user_avatar = $avatar->setColor($f3->get('SITE.color_primary'), $f3->get('SITE.color_primary_text'));
                    //$avatar->saveAs('public/uploads', LetterAvatar::MIME_TYPE_JPEG);
                    //$user_avatar = $f3->get('SITE.base_url').'/public/assets/img/noavatar.jpg';
                    $user->avatar = $user_avatar;
                    $user->save();
                }

                $f3->set('CXT', $user);  
                $f3->set('USER.id', $user->user_id);  
            }
        }
    }
}
