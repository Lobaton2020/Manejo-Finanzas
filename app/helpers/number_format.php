<?php

function number_price($num = 0, $small = false)
{
    try {

        $htmlvalue = '$ ' . number_format($num, 2, ".", ",") . " COP";
        if ($small) {
            $htmlvalue = '$ ' . number_format($num, 0, ".", ",") . " <small class='text-muted'>COP</small>";
        }
        if ($num < 0) {
            return "<span class='text-danger'>" . $htmlvalue . "</span>";
        }
        return $htmlvalue;
    } catch (Exception $e) {
        exit($e->getMessage());
    }
}

function number_price_without_html($num = 0, $small = false)
{
    try {
        $htmlvalue = '$ ' . number_format($num, 2, ".", ",");
        if ($small) {
            $htmlvalue = '$ ' . number_format($num, 0, ".", ",");
        }
        return $htmlvalue;
    } catch (Exception $e) {
        exit($e->getMessage());
    }
}
function number_percentage($num = 0, $small = false)
{
    try {
        return number_format($num, 1, ".", ",") . '%';
    } catch (Exception $e) {
        exit($e->getMessage());
    }
}

