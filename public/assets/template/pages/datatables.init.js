/*
 Template Name: Stexo - Responsive Bootstrap 4 Admin Dashboard
 Author: Themesdesign
 Website: www.themesdesign.in
 File: Datatable js
 */

const InitDataTables = () => {
    let options = {
        order: [[0, 'DESC']],
        pageLength: 50
    }
    if (document.querySelector("[data-type='datatable-state-asc']")) {
        console.log(document.querySelector("[data-type='datatable-state-asc']"))
        options = {
            order: [[6, 'asc']],
            pageLength: 50
        }
    }
    $('#datatable').DataTable(options);

    //Buttons examples
    var table = $('#datatable-buttons').DataTable({
        lengthChange: false,
        buttons: ['copy', 'excel', 'pdf', 'colvis']
    });

    table.buttons().container()
        .appendTo('#datatable-buttons_wrapper .col-md-6:eq(0)');
};

$(document).ready(function () {
    InitDataTables()
});