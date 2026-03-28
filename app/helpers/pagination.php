<?php

function make_pagination($currentPage, $totalPages, $baseUrl, $perPage, $totalRecords = null)
{
    if ($totalPages < 1) {
        $totalPages = 1;
    }

    $html = '<div class="row mt-3">';
    $html .= '<div class="col-md-6">';
    $html .= '<div class="dataTables_info">';
    
    if ($totalRecords !== null) {
        $start = ($currentPage - 1) * $perPage + 1;
        $end = min($currentPage * $perPage, $totalRecords);
        $html .= "Mostrando del {$start} al {$end} de {$totalRecords} registros";
    }
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="col-md-6">';
    $html .= '<div class="dataTables_paginate paging_simple_numbers">';
    $html .= '<ul class="pagination">';

    $prevDisabled = $currentPage <= 1 ? 'disabled' : '';
    $prevPage = $currentPage - 1;
    $html .= '<li class="paginate_button page-item ' . $prevDisabled . '">';
    $html .= '<a href="' . build_pagination_url($baseUrl, $prevPage, $perPage) . '" class="page-link">Anterior</a>';
    $html .= '</li>';

    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);

    if ($startPage > 1) {
        $html .= '<li class="paginate_button page-item">';
        $html .= '<a href="' . build_pagination_url($baseUrl, 1, $perPage) . '" class="page-link">1</a>';
        $html .= '</li>';
        if ($startPage > 2) {
            $html .= '<li class="paginate_button page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= '<li class="paginate_button page-item ' . $active . '">';
        $html .= '<a href="' . build_pagination_url($baseUrl, $i, $perPage) . '" class="page-link">' . $i . '</a>';
        $html .= '</li>';
    }

    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="paginate_button page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="paginate_button page-item">';
        $html .= '<a href="' . build_pagination_url($baseUrl, $totalPages, $perPage) . '" class="page-link">' . $totalPages . '</a>';
        $html .= '</li>';
    }

    $nextDisabled = $currentPage >= $totalPages ? 'disabled' : '';
    $nextPage = $currentPage + 1;
    $html .= '<li class="paginate_button page-item ' . $nextDisabled . '">';
    $html .= '<a href="' . build_pagination_url($baseUrl, $nextPage, $perPage) . '" class="page-link">Siguiente</a>';
    $html .= '</li>';

    $html .= '</ul>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function build_pagination_url($baseUrl, $page, $perPage, $filters = [])
{
    $params = ['page' => $page, 'length' => $perPage];
    
    $currentParams = $_GET;
    unset($currentParams['page']);
    unset($currentParams['length']);
    
    foreach ($currentParams as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $v) {
                $params[$key][] = $v;
            }
        } elseif ($value !== '') {
            $params[$key] = $value;
        }
    }

    $separator = strpos($baseUrl, '?') !== false ? '&' : '?';
    
    $queryString = http_build_query($params);
    
    return $baseUrl . $separator . $queryString;
}

function make_length_menu($baseUrl, $currentLength, $currentPage = 1, $options = [10, 25, 50, 100])
{
    $html = '<div class="dataTables_length d-flex align-items-center">';
    $html .= '<label class="mb-0 mr-2">Mostrar</label>';
    $html .= '<select class="form-control form-control-sm" style="width: auto;" onchange="window.location.href=this.value">';
    
    foreach ($options as $option) {
        $selected = $option == $currentLength ? 'selected' : '';
        $url = build_pagination_url($baseUrl, $currentPage, $option);
        $html .= '<option value="' . $url . '" ' . $selected . '>' . $option . '</option>';
    }
    
    $html .= '</select>';
    $html .= '<label class="mb-0 ml-2">registros</label>';
    $html .= '</div>';
    
    return $html;
}