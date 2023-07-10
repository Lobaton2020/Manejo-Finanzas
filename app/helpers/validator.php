<?php
/*
* @param  $validate Array a comparar
* @param  $object Object
*
*/
function arrayEmpty($validate, $object)
{
    $array = (array)$object;
    $res = false;
    foreach ($validate as $key) {
        if (array_key_exists($key, $array)) {
            if (empty($array[$key]) && strlen($array[$key]) < 1) {
                return true;
            }
        } else {
            return false;
        }
    }
    return false;
}

function is_correct($string)
{
    if (strlen($string) == null || strlen($string) == 0) {
        return null;
    } else {
        return $string;
    }
}

function valid_date_or_today($date)
{
    if (isset($date)) {
        return $date;
    }
    return getCurrentDatetime();
}