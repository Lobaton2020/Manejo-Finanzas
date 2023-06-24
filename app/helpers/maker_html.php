
<?php

function renderJumbotron($data, $title, $path = null)
{
    if (count($data) == 0) {
        $string = '    <div class="jumbotron jumbotron-fluid">';
        $string .= '    <div class="container">';
        $string .= '        <p class="display-4">Opps!</p>';
        $string .= '        <hr>';
        $string .= "        <h5 class=\"d-inline\"> {$title}";
        if (isset($path)) {
            $route = route($path);
            $string .= "     <a class=\"link-item text-primary\" style=\"text-decoration:underline\" href=\"{$route}\">Anadir </a>";
        }
        $string .= "</h5>";
        $string .= '    </div>';
        $string .= '</div>';
        return $string;
    }
}



//    $head = ["#", "Descripcion", "Total", "Fecha"];
//    $fillable = ["id_inflow", "description", "total", "create_at"];
//     make_table($head, $fillable, $inflows, ["redirect" => "inflow"]);
/**
 * $extra[redirect]
 * $extra[use]
 * $extra[btn-delete]
 * $extra[param-extra]
 * $extra[btn_delete_delete]
 * $extra[id]
 */
function make_table($head, $fillable, $data, $extra = null)
{
    // Compatible with Datatables
    // default config

    if (count($data) < 1) {
        return;
    }

    $html = "";
    $path_redirect = null;
    $actions = true;
    $show_id = true;
    $btn_edit = true;
    $btn_delete = true;
    $btn_delete_delete = null;
    $reditection = "";
    $tbn_delete = "Eliminar";
    $param_extra = "";

    if ($extra != null) {
        if (isset($extra["use"])) {

            switch ($extra["use"]) {
                case "edit":
                    $btn_delete = false;
                    break;
                case "delete":
                    $btn_edit = false;
                    break;
                case "none":
                    $btn_edit = false;
                    $btn_delete = false;
                    $actions = false;
                    break;
                case "custom":
                    $btn_edit = false;
                    $btn_delete = false;
                    $btn_delete_delete = false;
                    $actions = true;
                    break;
                case "btn_delete_delete":
                    $btn_edit = false;
                    $btn_delete = false;
                    $btn_delete_delete = true;
                    $actions = true;
                default;
            }
        }
        if (isset($extra["html"])) {
            $html = $extra["html"];
        }
        if (isset($extra["redirect"])) {
            $path_redirect = route($extra["redirect"]);
        }
        if (isset($extra["id"])) {
            $show_id = false;
        }
        if (isset($extra["btn-delete"])) {
            $tbn_delete = $extra["btn-delete"];
        }
        if (isset($extra["param-extra"])) {
            $param_extra = "/" . $extra["param-extra"];
        }
        if (isset($extra["btn_delete_delete"])) {
            $btn_delete_delete =  $extra["btn_delete_delete"];
        }
    }
    $reditections = [
        "edit" => "edit",
        "delete" => "delete",
        "disable" => "disable",
        "enable" => "enable"
    ];
    $reditection = $reditections["delete"];
    $attributes = [
        "id" => "datatable",
        "class" => "table table-striped table-bordered dt-responsive nowrap",
        "style" => "border-collapse: collapse; border-spacing: 0; width: 100%;"
    ];
    $attr = "";
    foreach ($attributes as $key => $value) {
        $attr .= "{$key}=\"{$value}\" ";
    }
    $string  = '<div class="table-responsive">';
    $string .= "<table {$attr}>";
    $string .= "<thead>";
    for ($i = 0; $i < 1; $i++) {
        $string .= "<tr>";
        for ($k = 0; $k < count($head); $k++) {
            if ($show_id && $k == 0) {
                continue;
            }
            $string .= "<th>{$head[$k]}</th>";
        }
        if ($actions) {
            $string .= "<th>Actions</th>";
        }
        $string .= "</tr>";
    }
    $string .= "</thead>";
    $string .= "<tbody>";
    for ($i = 0; $i <  count($data); $i++) {
        $data[$i] = (object)$data[$i];
        $string .= "<tr>";
        for ($k = 0; $k < count($head); $k++) {
            if ($show_id && $k == 0) {
                continue;
            }
            switch ($fillable[$k]) {
                case "create_at":
                case "update_at":
                case "updated_at":
                case "created_at":
                    $string .= "<td>" . format_datetime($data[$i]->{$fillable[$k]}) . "</td>";
                    break;
                case "set_date":
                    if (isset($extra["verify-date-before"])) {
                        $fecha_actual = strtotime(date("d-m-Y H:i:00", time()));
                        $fecha_entrada = strtotime("{$data[$i]->{$fillable[$k]}} 00:00:00");
                        $bg_expire = $fecha_actual > $fecha_entrada ? "alert-danger" : "alert-success";
                        $string .= "<td class=' alert {$bg_expire}'>" . format_date($data[$i]->{$fillable[$k]}) . "</td>";
                    } else {
                        $string .= "<td>" . format_date($data[$i]->{$fillable[$k]}) . "</td>";
                    }
                    break;
                case "total":
                case  "amount":
                case "total_amount":
                    $string .= "<td>" . number_price($data[$i]->{$fillable[$k]}) . "</td>";
                    break;
                case "status":
                    $type = "danger";
                    $name = "Inactivo";
                    $val = $data[$i]->{$fillable[$k]};
                    if ($val == 1) {
                        $type = "success";
                        $name = "Activo";
                    }
                    $string .= "<td class='text-center '><span class='size-text badge badge-{$type} font-19'>{$name}</span></td>";
                    break;
                default;
                    $string .= "<td>{$data[$i]->{$fillable[$k]}}</td>";
            }
        }
        if (!isset($extra["status"])) {

            if (isset($data[$i]->{"status"})) {
                if ($data[$i]->{"status"} == 0) {
                    $reditection = $reditections["enable"];
                    $tbn_delete = "Activar";
                } else {
                    $reditection = $reditections["disable"];
                    $tbn_delete = "Inactivar";
                }
            }
        }
        if ($actions) {

            $string .= "<td>";
            $string .= '  <div class="dropdown mo-mb-2 show mx-auto text-center">';
            $string .= "       <a class='btn btn-outline-secondary btn-sm' href='#' id='detail-{$data[$i]->{$fillable[0]}}' data-toggle='dropdown' aria-haspopup='true' aria-expanded='true'>";
            $string .= "          <i class='mdi mdi-settings' style='font-size:20px'></i>";
            $string .= "       </a>";
            $string .= "    <div class='dropdown-menu' aria-labelledby='detail-{$data[$i]->{$fillable[0]}}' x-placement='bottom-start' style='position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 33px, 0px);'>";
            if ($html) {
                $replacers = [];
                foreach ($extra["html-replace"] ?? [] as $item) {
                    $replacers[":" . $item] = $data[$i]->{$item};
                }
                $string .= strtr($extra["html"], $replacers);
            }
            if ($btn_edit) {
                $string .= "      <a class='dropdown-item' href='{$path_redirect}/{$reditections["edit"]}/{$data[$i]->{$fillable[0]}}{$param_extra}'>Editar</a>";
            }
            if ($btn_delete) {
                $string .= "      <a class='question dropdown-item' href='{$path_redirect}/{$reditection}/{$data[$i]->{$fillable[0]}}{$param_extra}'>{$tbn_delete}</a>";
            }
            if ($btn_delete_delete) {
                $string .= "      <a class='question dropdown-item' href='{$path_redirect}/{$btn_delete_delete}/{$data[$i]->{$fillable[0]}}{$param_extra}'>Eliminar</a>";
            }
            $string .= "  </div>";
            $string .= " </div>";
            $string .= '</td>';
        }
        $string .= "</tr>";
    }
    $string .= "</tbody>";
    $string .= "</table>";
    $string .= "</div>";
    return $string;
}


function wrapper_html($data, $card_body, $is_modal = false)
{
    /**
     * $data[title]
     * $data[subtitle]
     * $data[active_button][path]
     * $data[active_button][title]
     */
    // ---------------
    $data = (object)$data;
    $string = '<div class="content-page">';
    $string .= '<div class="content">';
    $string .=    '<div class="container-fluid">';
    $string .=        '<div class="page-title-box">';
    $string .=            '<div class="row align-items-center">';
    $string .=                '<div class="col-sm-6">';
    $string .=                    '<h4 class="page-title">Finanzas</h4>';
    $string .=                '</div>';
    $string .=                '<div class="col-sm-6">';
    $string .=                    '<ol class="breadcrumb float-right">';
    $string .=                        '<li class="breadcrumb-item"><a href="javascript:void(0);">Inicio</a></li>';
    $string .=                        "<li class='breadcrumb-item'><a href='javascript:void(0);'>{$data->subtitle}</a></li>";
    $string .=                        '<li class="breadcrumb-item active">Listado</li>';
    $string .=                    '</ol>';
    $string .=                '</div>';
    $string .=            '</div>';
    $string .=        '</div>';
    $string .=        '<div class="row">';
    $string .=            '<div class="col-12">';
    $string .=                '<div class="card m-b-30">';
    $string .=                    '<div class="m-3">';
    $string .=                        '<div class="float-left">';
    $string .=                            "<h4 class='mt-0  mb-3 header-title '>{$data->title}</h4>";
    $string .=                        '</div>';
    if (isset($data->active_button)) {
        if ($is_modal) {

            $string .= '<div class="float-right">';
            $string .= "<a data-target='#myModal' href='#' type='button' data-toggle='modal' class='btn btn-success float-right'><i class='mdi mdi-plus '></i>{$data->active_button["title"]}</a>";
            $string .= '</div>';
        } else {
            $string .= '<div class="float-right">';
            $string .= "<a  href='{$data->active_button["path"]}' class='btn btn-success float-right'><i class='mdi mdi-plus '></i>{$data->active_button["title"]}</a>";
            $string .= '</div>';
        }
    }
    $string .=                    '</div>';
    $string .=                    '<div class="card-body">';
    $string .=                          $card_body;
    $string .=                    '</div>';
    $string .=                '</div>';
    $string .=            '</div>';
    $string .=            '</div>';
    $string .=        '</div>';

    $string .= '</div>';
    $string .= file_get_contents(URL_APP . "views" . SEPARATOR . "layouts" . SEPARATOR . "footerbar.php");
    $string .= '</div>';
    return $string;
};

/**
 * $data[name_user]
 * $data[name_company]
 * $data[url_link]
 * $data[name_link]
 */
function structure_html_send_email($data)
{
    $data = (object)$data;
    $content = '<!DOCTYPE html>';
    $content .= '<html lang="en">';
    $content .= '<head>';
    $content .= '	<meta charset="UTF-8">';
    $content .= '	<title>Recuperacion Contraseña | SocialNet</title>';
    $content .= '	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
    $content .= '    <meta name="viewport" content="width=device-width, user-scalable=no" />';
    $content .= '    <meta http-equiv="X-UA-Compatible" content="IE=edge" /> ';
    $content .= '	   <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet" />';
    $content .= '</head>';
    $content .= '<body>';
    $content .= '<div class="container">';
    $content .= '  <p>Hola <b>' . $data->name_user . '.</b></p>';
    $content .= "  <p>Usted solicitó un restablecimiento de contraseña para su cuenta en {$data->name_company}.</p>";
    $content .= '<h4>Click en el siguiente link:</h4>';
    $content .= "<a href='{$data->url_link}'>>{$data->name_link}</a>";
    $content .= '   ';
    $content .= '     <div class="container-fluid nav navbar navbar-default fixed-bottom  bg-primary ">';
    $content .= '         <div class="container text-light">';
    $content .= "         	<p class='parrafo'>&#169; " . date('Y') . " {$data->name_company} </p>   ";
    $content .= '        </div>';
    $content .= '    </div>';
    $content .= '</div>';
    $content .= '  </body>';
    $content .= ' </html>';
    return $content;
}
