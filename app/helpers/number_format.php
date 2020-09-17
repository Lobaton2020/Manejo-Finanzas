<?php

function number_price($num, $small = false)
{
    if ($small) {
        return '$ ' . number_format($num, 0, ".", ",") . " <small class='text-muted'>COP</small>";
    } else {
        return '$ ' . number_format($num, 2, ".", ",") . " COP";
    }
}
