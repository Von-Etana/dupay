function getOfferServicePlan(url) {
    $(document).on('change', '.offerServiceTypeData', function () {
        var serviceType = $(".offerServiceTypeData").val();
        if (serviceType !== "") {

            $.ajax({
                type: "GET",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { "service_type": serviceType},
                cache: false,
                dataType: "html",
                beforeSend: function () {
                    $(".offerPlanType").html("");
                    $(".offerPlanType").LoadingOverlay("show", {
                        background: "rgba(165, 190, 100, 0.5)"
                    });
                },
                success: function (response) {
                    $(".offerPlanType").html(response);

                },
                complete: function (data) {
                    $(".offerPlanType").LoadingOverlay("hide", true);
                }
            });
        }
    });
}


function get_plan(url) {
    $(document).on('change', '.networkData', function () {
        var network = $(".networkData").val();
        if (network !== "") {

            $.ajax({
                type: "GET",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { type: "dataPlanType", network: network },
                cache: false,
                dataType: "html",
                beforeSend: function () {
                    $(".dataPlan").html("");
                    $(".dataPlanType").LoadingOverlay("show", {
                        background: "rgba(165, 190, 100, 0.5)"
                    });
                },
                success: function (response) {
                    $(".dataPlanType").html(response);

                },
                complete: function (data) {
                    $(".dataPlanType").LoadingOverlay("hide", true);
                }
            });
        }
    });
}


function getArtimeType(url) {
    $(document).on('change', '.airtimeNetwork', function () {
        var network = $(".airtimeNetwork").val();
        if (network !== "") {
            $.ajax({
                type: "GET",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { type: "airtimeType", network: network },
                cache: false,
                dataType: "html",
                beforeSend: function () {

                    $(".airtimeType").LoadingOverlay("show", {
                        background: "rgba(165, 190, 100, 0.5)"
                    });
                },
                success: function (response) {
                    $(".airtimeType").html(response);

                },
                complete: function (data) {
                    $(".airtimeType").LoadingOverlay("hide", true);
                }
            });
        }
    });
}



function getNetworkDataType(url) {
    $(document).on('change', '.networkData', function () {
        var network = $(".networkData").val();
        if (network !== "") {
            $("#network_img").removeAttr("hidden", true);
            const networkJson = JSON.parse(network);
            var networkId = networkJson.id;
            var networkImage = networkJson.image;
            $("#network_img").attr("src", networkImage);

            $.ajax({
                type: "GET",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { type: "dataPlanType", network: networkId },
                cache: false,
                dataType: "html",
                beforeSend: function () {
                    $(".dataPlan").html("");
                    $(".dataPlanType").LoadingOverlay("show", {
                        background: "rgba(165, 190, 100, 0.5)"
                    });
                },
                success: function (response) {
                    $(".dataPlanType").html(response);

                },
                complete: function (data) {
                    $(".dataPlanType").LoadingOverlay("hide", true);
                }
            });
        } else {
            $("#network_img").attr("hidden", "hidden");
            $(".dataPlan").html("");
            $(".dataPlanType").html("");
        }
    });
}

function getDataPlan(url) {
    $(document).on('change', '.dataPlanType', function () {
        var dataPlanType = $(".dataPlanType").val();
        if (dataPlanType !== "") {
            const planTypeJson = JSON.parse(dataPlanType);
            var planTypeId = planTypeJson.id;
            $.ajax({
                type: "GET",
                url: url,
                data: { type: "dataPlan", dataTypeId: planTypeId },
                cache: false,
                dataType: "html",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function () {
                    $(".dataPlan").LoadingOverlay("show", {
                        background: "rgba(165, 190, 100, 0.5)"
                    });
                },
                success: function (response) {
                    $(".dataPlan").html(response);

                },
                complete: function (data) {
                    $(".dataPlan").LoadingOverlay("hide", true);
                }
            });
        }
    });
}



function checkboxStatusUpdate(){
    $(document).on('change', '.statusTypeUpdate', function(e){
        // get the data-id and data-url attributes from the checkbox
var id = $(this).data('id');
var url = $(this).data('url');
var column = $(this).data('column');
var model = $(this).data('model');

// build the data object to be sent with the AJAX request
var data = {
id: id,
column: column,
model: model,
value: $(this).prop('checked') == true ? "1" : "0",
checked: $(this).prop('checked')
};

// send the AJAX request
$.ajax({
type: 'PUT',
url: url,
data: data,
headers: {
'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
},

success: function(response) {
if(!response.success){
    sweetAlert("Oops...", response.message, "error");
}
},
error: function(jqXHR, textStatus, errorThrown) {
// handle error response
sweetAlert("Oops...", error, "error");
}
});
});
}


function iSmartPlaceEdit(){
    $(document).on('dblclick', '.iSmartInPlace', function(e){
        var $p = $(this);

         // get the data-id and data-url attributes from the paragraph
         var column = $p.data('column');
         var id = $p.data('id');
         var url = $p.data('url');
         var description = $p.data('title');
         var type = $p.data('type');
         var model = $(this).data('model');

      // create an input element with the same text as the paragraph
      var $input = $('<input type="'+type+'" name="'+column+'" placeholder="'+description+'" class="form-control">').val($p.text());

      // replace the paragraph with the input element
      $p.replaceWith($input);

      // focus the input element and select its text
      $input.focus().select();

      // bind an event listener to the input element
      $input.blur(function() {

        // build the data object to be sent with the AJAX request
        var data = {
          id: id,
          column:column,
          model:model,
          value: $input.val()
        };

        if($p.text() == $input.val()){
            $input.replaceWith($p);
            return;
        }

        // send the AJAX request
        $.ajax({
          type: 'PUT',
          url: url,
          data: data,
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
          },
          beforeSend: function(){
            $p.replaceWith("<i class='fa fa-spinner fa-spin'></i>");
            },
          success: function(response) {
            if(!response.success){
                sweetAlert("Oops...", response.message, "error");
            }else{
            // replace the input element with the updated value
            $input.replaceWith($('<a hre="javascript:void(0)" class="iSmartInPlace" id="'+id+'" column="'+column+'" url="'+url+'"  title="'+description+'" type="'+type+'">').text(response.message));
            }
          },
          error: function(jqXHR, textStatus, errorThrown) {
            // replace the input element with the original value
            $input.replaceWith($p);
            // handle error response
            sweetAlert("Oops...", error, "error");
          }
        });

      });
     });
}



function deleteItem(name){
      $(document).on('click', '.delete_item', function(e){
        e.preventDefault();
        var el=$(this);
        swal({
            title: "Are you sure to delete "+name+" ?",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "Delete",
            cancelButtonText: "Cancel",
            closeOnConfirm: false
        },
        function(){
            var __url = el.data('url');
            if (__url) {
                $.ajax({
                    url: __url,
                    type: 'POST',
                    dataType: 'json',
                    data: { _token: $('meta[name="csrf-token"]').attr('content'), _method: 'DELETE' },
                    success: function(r){
                        if (typeof ismart !== 'undefined' && ismart.ajax) { ismart.ajax.reload(null, false); }
                        swal('Deleted', (r && r.message) ? r.message : (name + ' deleted successfully.'), 'success');
                    },
                    error: function(xhr){
                        var m = 'Could not delete this ' + name.toLowerCase() + '.';
                        if (xhr.responseJSON && xhr.responseJSON.message) { m = xhr.responseJSON.message; }
                        else if (xhr.status === 404) { m = name + ' not found - it may already be deleted.'; }
                        else if (xhr.status === 405) { m = 'Delete route is not registered as DELETE.'; }
                        else if (xhr.status === 419) { m = 'Session expired. Refresh and try again.'; }
                        swal('Error', m, 'error');
                    }
                });
            } else {
                el.closest('form').submit();
            }
        });
    });
}





function rederTable(getGeneralAjaxUrl, Columns){

    // clone tfoot like thead
$("#item_table").append(
    $('<tfoot/>').append($("#item_table thead tr").clone())
);

// append tfoot after thead
$('#item_table tfoot').insertAfter($('#item_table tbody'));

    ismart = $('#item_table').DataTable({
        headerCallback:function(e, a, t, n, s) {
            e.getElementsByTagName("th")[0].innerHTML=`
            <div class="form-check form-check-primary d-block new-control">
                <input class="form-check-input chk-parent" type="checkbox" id="form-check-default">
            </div>`
        },
        // columnDefs:[ {
        //     targets:0, width:"30px", className:"", orderable:!1, render:function(e, a, t, n) {
        //         return `
        //         <div class="form-check form-check-primary d-block new-control">
        //             <input class="form-check-input child-chk" type="checkbox" id="form-check-default">
        //         </div>`
        //     }

        // }],
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
        "lengthMenu": [5, 10, 20, 50,100],
        "pageLength": 20 ,
        "processing": true,
        "serverSide": true,
        "bFilter":true,
        "bSort" : false,
        // "searching": false,
        "ajax": {
            url:getGeneralAjaxUrl,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
        },
        fixedHeader: true,
        "columns": Columns,


    });


    //$("#item_table_filter.dataTables_filter").append($("#filter"));

   $('#filter').on('change', function() {
    var filterValue = $(this).val();
     var search = $('#searchInput').val();

    // Update the AJAX URL with the selected parameter
    ismart.ajax.url(getGeneralAjaxUrl+'?filter=' + filterValue).load();
  });

   // Set up event listener for custom search button
   $('#ismartSearch').on('keyup', function() {
    var searchValue = $('#ismartSearch').val();
    ismart.ajax.url(getGeneralAjaxUrl+'?ismartSearch=' + searchValue).load();
  });

  iSmartMultiCheck(ismart);
}



function iSmartMultiCheck(tb_var) {
    tb_var.on("change", ".chk-parent", function() {
        var e = $(this).closest("table").find("td:first-child .child-chk"),
            a = $(this).is(":checked");

        $(e).each(function() {
            a ? ($(this).prop("checked", true), $(this).closest("tr").addClass("active")) :
                ($(this).prop("checked", false), $(this).closest("tr").removeClass("active"))
        });

        // Check if any checkbox is checked and toggle the button visibility accordingly
        toggleButtonVisibility();
    });

    tb_var.on("change", "tbody tr .new-control", function() {
        $(this).parents("tr").toggleClass("active");
        // Check if any checkbox is checked and toggle the button visibility accordingly
        toggleButtonVisibility();
    });

    // Function to toggle button visibility based on checked checkboxes
    function toggleButtonVisibility() {
        var checkedCheckboxes = $("#item_table").find('.child-chk:checked');
        if (checkedCheckboxes.length > 0) {
            $('.ismartDeleteBulk').removeAttr('hidden');
            $('.bulkUpdateAction').removeAttr('hidden');
        } else {
            $('.ismartDeleteBulk').attr('hidden', true);
            $('.bulkUpdateAction').attr('hidden', true);
        }
    }
}

// function iSmartMultiCheck(tb_var) {
//     tb_var.on("change", ".chk-parent", function() {
//         var e=$(this).closest("table").find("td:first-child .child-chk"), a=$(this).is(":checked");
//         $(e).each(function() {
//             a?($(this).prop("checked", !0), $(this).closest("tr").addClass("active")): ($(this).prop("checked", !1), $(this).closest("tr").removeClass("active"))
//         })
//     }),
//     tb_var.on("change", "tbody tr .new-control", function() {
       
//         $(this).parents("tr").toggleClass("active")
//     })
   
// }


function rederSearchableTable(getGeneralAjaxUrl, Columns){
 /*
        Individual Column Search
    */

    // Setup - add a text input to each footer cell
    $('#individual-col-search tfoot th').each( function () {
        var title = $(this).text();
        $(this).html( '<input type="text" class="form-control" placeholder="Search '+title+'" />' );
    } );

    // DataTable
    var table = $('#individual-col-search').DataTable({
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
        "stripeClasses": [],
        "lengthMenu": [7, 10, 20, 50,100],
        "pageLength": 20,
        "processing": true,
        "serverSide": true,
        "ajax": {
            url:getGeneralAjaxUrl,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
        },
        "columns": Columns,
    });

    // // Apply the search
    table.columns().every( function () {
        var that = this;

        $( 'input', this.footer() ).on( 'keyup change', function () {
            if ( that.search() !== this.value ) {
                that
                    .search( this.value )
                    .draw();
            }
        } );
    } );
};




//transactions chat
function chartList(chartId, url, type = "weekly"){
    var iSmartChart;

    $.ajax({
        url: url,
        type: "GET",
        data: { type: type },
        beforeSend: function () {
         $("#"+chartId).LoadingOverlay("show");
         $(".order-summary").LoadingOverlay("show");
        },
        success: function (response) {
            if(response.success){

                $('.summary-count.totalIncomeCount').text(response.data.income.total);
                $('.summary-count.totalProfitCount').text(response.data.profit.total);
                $('.summary-count.totalExpensesCount').text(response.data.expenses.total);

                $('#totalIncomeCountProgressBar').attr('style', 'width: '+response.data.income.percentage+'%');
                $('#totalProfitCountProgressBar').attr('style', 'width: '+response.data.profit.percentage+'%');
                $('#totalExpensesCountProgressBar').attr('style', 'width: '+response.data.expenses.percentage+'%');

                $('#totalIncomeCountProgressBar').attr('aria-valuenow', ''+response.data.income.percentage+'');
                $('#totalProfitCountProgressBar').attr('aria-valuenow', ''+response.data.profit.percentage+'');
                $('#totalExpensesCountProgressBar').attr('aria-valuenow', ''+response.data.expenses.percentage+'');


                iSmartChart = {
                    chart: {
                        fontFamily: 'Nunito, sans-serif',
                        height: 365,
                        type: 'area',
                        zoom: {
                            enabled: false
                        },
                        dropShadow: {
                            enabled: true,
                            opacity: 0.2,
                            blur: 10,
                            left: -7,
                            top: 22
                        },
                        toolbar: {
                            show: false
                        },
                    },
                    colors: ['#1b55e2', '#e7515a'],
                    dataLabels: {
                        enabled: false
                    },
                    markers: {
                        discrete: [{
                            seriesIndex: 0,
                            dataPointIndex: 7,
                            fillColor: '#000',
                            strokeColor: '#000',
                            size: 5
                        }, {
                            seriesIndex: 2,
                            dataPointIndex: 11,
                            fillColor: '#000',
                            strokeColor: '#000',
                            size: 4
                        }]
                    },
                    subtitle: {
                        text: response.data.subtitle,
                        align: 'left',
                        margin: 0,
                        offsetX: 120,
                        offsetY: 20,
                        floating: false,
                        style: {
                            fontSize: '18px',
                            color: '#4361ee'
                        }
                    },
                    stroke: {
                        show: true,
                        curve: 'smooth',
                        width: 2,
                        lineCap: 'square'
                    },
                    title: {
                        text: response.data.title,
                        align: 'left',
                        margin: 0,
                        offsetX: -10,
                        offsetY: 20,
                        floating: false,
                        style: {
                            fontSize: '18px',
                            color: '#0e1726'
                        },
                    },
                    series: response.data.series,
                    labels: response.data.labels,
                    xaxis: {
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        },
                        crosshairs: {
                            show: true
                        },
                        labels: {
                            offsetX: 0,
                            offsetY: 5,
                            style: {
                                fontSize: '12px',
                                fontFamily: 'Nunito, sans-serif',
                                cssClass: 'apexcharts-xaxis-title',
                            },
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: function(value, index) {
                                return (value / 1000) + 'K'
                            },
                            offsetX: -15,
                            offsetY: 0,
                            style: {
                                fontSize: '12px',
                                fontFamily: 'Nunito, sans-serif',
                                cssClass: 'apexcharts-yaxis-title',
                            },
                        }
                    },
                    grid: {
                        borderColor: '#e0e6ed',
                        strokeDashArray: 5,
                        xaxis: {
                            lines: {
                                show: true
                            }
                        },
                        yaxis: {
                            lines: {
                                show: false,
                            }
                        },
                        padding: {
                            top: -50,
                            right: 0,
                            bottom: 0,
                            left: 5
                        },
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'right',
                        offsetY: -50,
                        fontSize: '16px',
                        fontFamily: 'Quicksand, sans-serif',
                        markers: {
                            width: 10,
                            height: 10,
                            strokeWidth: 0,
                            strokeColor: '#fff',
                            fillColors: undefined,
                            radius: 12,
                            onClick: undefined,
                            offsetX: -5,
                            offsetY: 0
                        },
                        itemMargin: {
                            horizontal: 10,
                            vertical: 20
                        }

                    },
                    fill: {
                        type:"gradient",
                        gradient: {
                            type: "vertical",
                            shadeIntensity: 1,
                            inverseColors: !1,
                            opacityFrom: .19,
                            opacityTo: .05,
                            stops: [100, 100]
                        }
                    },
                    responsive: [{
                        breakpoint: 575,
                        options: {
                          legend: {
                              offsetY: -50,
                          },
                        },
                      }]

                }

                var chart = new ApexCharts(
                    document.querySelector("#"+chartId),
                    iSmartChart
                );

                chart.render();
            }
        },
        complete: function (data) {
            $("#"+chartId).LoadingOverlay("hide");
            $(".order-summary").LoadingOverlay("hide");
        },
    });


}

