<?php

function encrypt($password, $type = PASSWORD_BCRYPT)
{
    return password_hash($password, $type);
}


function verify($password, $hash)
{
    return password_verify($password, $hash);
}

function token($long = null)
{
    $local_long = 0;
    if (isset($long)) {
        if ($long < 50) {
            $long = 50;
        }
        $local_long = $long;
    } else {
        $local_long = rand(50, 100);
    }
    return bin2hex(openssl_random_pseudo_bytes(($local_long - ($local_long % 2)) / 2));
}

function token_($num = 60)
{
    if ($num < 50) {
        $num = 50;
    }
    $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $token = substr(str_shuffle($permitted_chars), 0, $num);
    return $token;
}
