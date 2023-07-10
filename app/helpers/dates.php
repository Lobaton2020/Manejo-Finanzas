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

function date_diff_in_months($date1, $date2)
{
    $fecha1 = new DateTime($date1);
    $fecha2 = new DateTime($date2);
    $intervalo = $fecha1->diff($fecha2);
    $totalMeses = ($intervalo->y * 12) + $intervalo->m + ($intervalo->d / 30);
    return number_format($totalMeses, 1);
}