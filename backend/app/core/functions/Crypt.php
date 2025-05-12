<?php

class Crypt
{

    function generate($string)
    {

        $hash = password_hash(
            $string,
            PASSWORD_DEFAULT
        );
        return $hash;
    }
    function verify($string, $hash)
    {

        return password_verify($string, $hash);
    }
    function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
