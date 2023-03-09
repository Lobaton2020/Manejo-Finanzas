<?php

function number_price($num, $small = false)
{
    $htmlvalue = '$ ' . number_format($num, 2, ".", ",") . " COP";
    if ($small) {
        $htmlvalue = '$ ' . number_format($num, 0, ".", ",") . " <small class='text-muted'>COP</small>";
    }
    if ($num < 0) {
        return "<span class='text-danger'>" . $htmlvalue . "</span>";
    }
    return $htmlvalue;
}

function number_price_without_html($num, $small = false)
{
    $htmlvalue = '$ ' . number_format($num, 2, ".", ",") ;
    if ($small) {
        $htmlvalue = '$ ' . number_format($num, 0, ".", ",");
    }
    return $htmlvalue;
}
