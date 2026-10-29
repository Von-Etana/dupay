(function($){

    "use strict";

    //   // //active
    // $('#staff').addClass('active');
    // $('#staff_roles').addClass('active');
    // $('#staff_roles').addClass('menu-open');

     //delete staff
     $(document).on('click', '.delete_item', function(e){
        e.preventDefault();
        var el=$(this);
        swal({
            title: "Are you sure to delete role ?",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "Delete",
            cancelButtonText: "Cancel",
            closeOnConfirm: false
        },
        function(){
            $(el).parent().submit();
        });
    });


    $('.chk-parent').on('click',function(){
        alert("hello");
       $('#item_table').find('input[type=checkbox]').prop('checked',true);
    });


    $('.select_all_modules').on('click',function(){
        $('input[type=checkbox]').prop('checked',true);
    });

    $('.deselect_all_modules').on('click',function(){
        $('input[type=checkbox]').prop('checked',false);
    });

    $('.select_module').on('click',function(){
        $(this).parent().parent().parent().find('.card-body').find('input[type=checkbox]').prop('checked',true);
    });

    $('.deselect_module').on('click',function(){
        $(this).parent().parent().parent().find('.card-body').find('input[type=checkbox]').prop('checked',false);
    });


     $('.select2').select2();


    ismart = $('#item_table').DataTable({
        headerCallback:function(e, a, t, n, s) {
            e.getElementsByTagName("th")[0].innerHTML=`
            <div class="form-check form-check-primary d-block new-control">
                <input class="form-check-input chk-parent" type="checkbox" id="form-check-default">
            </div>`
        },
        columnDefs:[ {
            targets:0, width:"30px", className:"", orderable:!1, render:function(e, a, t, n) {
                return `
                <div class="form-check form-check-primary d-block new-control">
                    <input class="form-check-input child-chk" type="checkbox" id="form-check-default">
                </div>`
            }

        }],
        "dom": "<'dt--top-section'<'row'<'col-12 col-sm-6 d-flex justify-content-sm-start justify-content-center'l><'col-12 col-sm-6 d-flex justify-content-sm-end justify-content-center mt-sm-0 mt-3'f>>>" +
    "<'table-responsive'tr>" +
    "<'dt--bottom-section d-sm-flex justify-content-sm-between text-center'<'dt--pages-count  mb-sm-0 mb-3'i><'dt--pagination'p>>",
        "oLanguage": {
            "oPaginate": { "sPrevious": '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>', "sNext": '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>' },
            "sInfo": "Showing page _PAGE_ of _PAGES_",
            "sSearch": '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
            "sSearchPlaceholder": "Search...",
           "sLengthMenu": "Results :  _MENU_",
        },
        "lengthMenu": [5, 10, 20, 50],
        "pageLength": 10 ,
        "processing": true,
        "serverSide": true,
        //"bSort" : false,
        "ajax": {
            url:url("admin/get-planfeature"),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

            },
        },
        fixedHeader: true,
        "columns": [
            {data:"bulk_checkbox",searchable:false,sortable:false,orderable:false},
            {data:"id",sortable:true,orderable:true},
            {data:"name",sortable:true,orderable:true},
            {data:"frequency",sortable:true,orderable:true},
            {data:"action",sortable:false,searchable:false,orderable:false, className:"text-center"}
          ],

    });


    multiCheck(ismart);
})(jQuery);



