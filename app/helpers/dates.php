<?php
function getCurrentDatetime()
{
    return date("Y-m-d H:i:s");
}

function format_date($date, $abrev = true)
{
    $moths = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    $date = explode("-", $date);
    if ($abrev == true) {
        $moths[$date[1] - 1] = substr($moths[$date[1] - 1], 0, 3);
    }
    return "{$moths[$date[1] - 1]} $date[2] del $date[0]";
}

function format_time($time)
{
    return date("g:i a", strtotime("0000-00-00 {$time}"));
}

function format_datetime($datetime, $abrev = true)
{
    $datetime = explode(" ", $datetime);
    return format_date($datetime[0], $abrev) . " - " . format_time($datetime[1]);
}
