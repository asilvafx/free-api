<?php

class Report extends \Prefab
{

    function error($f3)
    {
        $log = new Log('error.log');
        if (empty($error)) {
            $error = $f3->get('ERROR.text');
        }

        $log->write($error);
    }
}
