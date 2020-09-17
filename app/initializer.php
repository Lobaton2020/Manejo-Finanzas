<?php

require_once "config/config.php";
require_once "helpers/dates.php";
require_once "helpers/encrypt.php";
require_once "helpers/json.php";
require_once "helpers/testing.php";
require_once "helpers/message.php";
require_once "helpers/render_views.php";
require_once "helpers/route.php";
require_once "helpers/validator.php";
require_once "helpers/execute_method_post.php";
require_once "helpers/redirect.php";
require_once "helpers/number_format.php";
require_once "helpers/time.php";
require_once "helpers/maker_html.php";
require_once "helpers/countVisits.php";

date_default_timezone_set('America/Bogota');

spl_autoload_register(function ($basename) {
    require "core/" . $basename . ".php";
});
