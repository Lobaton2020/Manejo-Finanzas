<?php
function time_ago($timestamp)
{
    date_default_timezone_set('America/Bogota');
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes = round($seconds / 60); // value 60 is seconds
    $hours = round($seconds / 3600); //value 3600 is 60 minutes * 60 sec
    $days = round($seconds / 86400); //86400 = 24 * 60 * 60;
    $weeks = round($seconds / 604800); // 7*24*60*60;
    $months = round($seconds / 2629440); //((365+365+365+365+366)/5/12)*24*60*60
    $years = round($seconds / 31553280); //(365+365+365+365+366)/5 * 24 * 60 * 60
    if ($seconds <= 60) {
        return 'Hace un momento';
    } else if ($minutes <= 60) {
        if ($minutes == 1) {
            return 'Hace un minuto';
        } else {
            return "Hace $minutes minutos";
        }
    } else if ($hours <= 24) {
        if ($hours == 1) {
            return "Hace una hora";
        } else {
            return "Hace $hours horas";
        }
    } else if ($days <= 7) {
        if ($days == 1) {
            return "Ayer";
        } else {
            return "hace $days días";
        }
    } else if ($weeks <= 4.3) //4.3 == 52/12
    {
        if ($weeks == 1) {
            return "Hace una semana";
        } else {
            return "Hace $weeks semanas";
        }
    } else if ($months <= 12) {
        if ($months == 1) {
            return "Hace un mes";
        } else {
            return "Hace $months meses";
        }
    } else {
        if ($years == 1) {
            return "Hace un año";
        } else {
            return "Hace $years años";
        }
    }
}
