<?php

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
                $f3->set('CXT', $user);  
                $f3->set('USER.id', $user->user_id);  
            }
        }
    }
}
