//url
function url(url='')
{
  var siteUrl = $('meta[name="site-url"]').attr('content');
  return siteUrl+'/'+url;
}

//ajax url
function ajax_url(url='')
{
  var siteUrl = $('meta[name="site-url"]').attr('content');
  return siteUrl+'/ajax/'+url;
}


//api url
function api_url(url='')
{
  var siteUrl = $('meta[name="site-url"]').attr('content');
  return siteUrl+'/api/'+url;
}

$('.getSubCategoryData').change(function(e){

    var catId = $(".getSubCategoryData").val();
    if (catId !== "") {
        $.ajax({
            type: "GET",
            url: api_url('get-subcategory'),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: { catId: catId },
            cache: false,
            dataType: "html",
            beforeSend: function () {
                $(".SubCategoryData").html("Loading...");
                // $(".SubCategoryData").LoadingOverlay("show", {
                //     background: "rgba(165, 190, 100, 0.5)"
                // });
            },
            success: function (response) {
                $(".SubCategoryData").html(response);

            },
            complete: function (data) {
                // $(".SubCategoryData").LoadingOverlay("hide", true);
            }
        });
    }
});


$('.iSmartCheckBox').change(function(e){
    e.preventDefault();
    var el=$(this);
    var form = $(el).closest("form");
    var url = form.attr("action");
    var formData = new FormData(form[0]);

    $.ajax({
        type: "POST",
        url: url,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
        error: function (request, status, error) {
            sweetAlert("Oops...", error, "error");
        },
        complete:function(data){

         }
        });


});


function checkSubmitForm(formId, fields = null, submitId = "submitRequest", isJs = false, reloadePage = false) {
    $(document).on('click', '.'+submitId, function(e){
        e.preventDefault();
        var form = $('#'+formId);
        var url = form.attr("action");
        var okay=true;
        $("#"+formId+" :input").each(function(e){
            if(fields == null || !fields.includes(this.name)){
                if(this.type == "checkbox"){
                    if (!$('#'+this.id).is(':checked')){
                    swal({ title: "Error!!", text: "Kindly Check the "+this.name, timer: 2e3, showConfirmButton: !1 });
                    okay = false;
                    return false;
                    }
                }else if(this.value === "" && this.name != ""){
                    swal({ title: "Error!!", text: this.name+" can not be empty", timer: 2e3, showConfirmButton: !1 });
                    okay = false;
                    return false;
                }
            }
        });

        if(okay == true){
            $("."+submitId).attr("disabled", "disabled");　
            $("."+submitId).html("<i class='fa fa-spinner fa-spin'></i>  Processing...");　

            if(!isJs){
                form.submit();
            }else{
                var formData = new FormData(form[0]);
                $.ajax({
                    type: "POST",
                    url: url,
                    data: formData,
                    contentType: false,
                    processData: false,
                    cache: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    },
                    //dataType: "html",
                    beforeSend: function(){
                    $("."+submitId).attr("disabled", "disabled");　
                    $.LoadingOverlay("show");
                    $("."+submitId).html("<i class='fa fa-spinner fa-spin'></i>  Processing...");　
                    },
                    success: function(response){
                        if(!response.success){
                            sweetAlert("Oops...", response.message, "error");
                        }else{

                            sweetAlert("Yes...", response.message, "success");
                            if(response.nextUrl != null){
                                window.location = response.nextUrl;
                            }else if(reloadePage){
                                window.location.reload();
                            }


                        }
                    },
                    error: function (request, status, error) {
                        sweetAlert("Oops...", error, "error");
                    },
                    complete:function(data){
                    $("."+submitId).removeAttr("disabled");
                    $.LoadingOverlay("hide");　
                    $("."+submitId).html("Submit");　			}
                    });
            }//end of not js


        }

    });
}


