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


function buyData() {
    $(document).on('click', '.submitRequest', function () {

        swal({
            title: "Secure Transaction",
            text: "Transaction Pin!!",
            type: "input",
            showCancelButton: !0,
            closeOnConfirm: !1,
            animation: "slide-from-top",
            inputPlaceholder: "Enter Your Transaction Pin",
            inputType: "password"
        },
            function (transaction_pin) {
                if (!1 !== transaction_pin && "" === transaction_pin) {
                    return (swal.showInputError("Transaction Pin is require!"), !1)
                } else {

                    var form = $('.submitRequest').parents('form');
                    var url = form.attr("action");
                    // var wallet_id = $('.wallet_id').val();
                    // var network_id = $('.networkData').val();
                    // var dataPlanType = $('.dataPlanType').val();
                    // var dataPlan = $('.dataPlan').val();
                    // var phone_number = $('#phone_number').val();
                    var wallet_id = form[0]["wallet_id"].value;
                    var network_id = form[0]["network_id"].value;
                    var dataPlanType = form[0]["type_id"].value;
                    var dataPlan = form[0]["plan_id"].value;
                    var phone_number = form[0]["phone_number"].value;
                    var ported_number = $('#ported_number:checked').val();
                    if (network_id == "" || dataPlanType == "" || phone_number == "" || transaction_pin == "") {
                        swal({ title: "Error!!", text: "Please fill the required fields!!", timer: 2e3, showConfirmButton: !1 });
                    } else {
                        const planTypeJson = JSON.parse(dataPlanType);
                        var planTypeId = planTypeJson.id;
                        var planTypeName = planTypeJson.name;
                        var networkImage = planTypeJson.network_image;
                        var networkName = planTypeJson.network_name;

                        const planJson = JSON.parse(dataPlan);
                        var planeId = planJson.id;
                        var planName = planJson.name;
                        var planAmount = planJson.price;

                        const networkJson = JSON.parse(network_id);
                        network_id = networkJson.id;

                        var Message = "You're about to send " + networkName + " [ " + planName + " ] for " + planAmount + " Naira to " + phone_number;

                        swal({
                            title: "Confirm Transaction",
                            text: Message,
                            //type: "info",
                            showCancelButton: !0,
                            closeOnConfirm: !1,
                            showLoaderOnConfirm: !0,
                            //imageUrl: networkImage,
                            html: !0
                        }, function () {
                            $.ajax({
                                type: "POST",
                                url: url,
                                data: {
                                    wallet_id: wallet_id,
                                    phone_number: phone_number,
                                    network_id: network_id,
                                    plan_id: planeId,
                                    dataType_id: planTypeId,
                                    transaction_pin: transaction_pin,
                                    ported_number: ported_number
                                },
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                cache: false,
                                beforeSend: function () {
                                    $(".submitRequest").attr("disabled", "disabled");
                                    $('.submitRequest').html("<i class='fa fa-spinner fa-spin'></i> processing...");
                                },
                                success: function (response) {
                                    if (!response.success && response.data == null) {
                                        sweetAlert("Oops...", response.message, "error");
                                    } else {
                                        window.location.replace('transactions/' + response.data.reference);
                                    }
                                },
                                error: function (request, status, error) {
                                    sweetAlert("Oops...", error, "error");
                                },
                                complete: function (data) {
                                    $(".submitRequest").removeAttr("disabled");
                                    $(".submitRequest").html("Submit");
                                },
                            });
                        })

                    }



                }

                //transaction pin
            });

    });

}



function getArtimeNetworkType(url) {
    $(document).on('change', '.airtimeNetwork', function () {
        var network = $(".airtimeNetwork").val();
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
                data: { type: "airtimeType", network: networkId },
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
        } else {
            $("#network_img").attr("hidden", "hidden");
            $(".airtimeType").html("");
        }
    });
}


function buyAirtime() {
    $(document).on('click', '.submitRequest', function () {

        swal({
            title: "Secure Transaction",
            text: "Transaction Pin!!",
            type: "input",
            showCancelButton: !0,
            closeOnConfirm: !1,
            animation: "slide-from-top",
            inputPlaceholder: "Enter Your Transaction Pin",
            inputType: "password"


        },
            function (transaction_pin) {
                if (!1 !== transaction_pin && "" === transaction_pin) {
                    return (swal.showInputError("Transaction Pin is require!"), !1)
                } else {


                    var form = $('.submitRequest').parents('form');
                    var url = form.attr("action");
                    // var wallet_id = $('.wallet_id').val();
                    // var network_id = $('.airtimeNetwork').val();
                    // var type = $('.airtimeType').val();
                    // var amount = $('#amount').val();
                    // var phone_number = $('#phone_number').val();
                    // var ported_number = $('#ported_number:checked').val();

                    var wallet_id = form[0]["wallet_id"].value;
                    var network_id = form[0]["network_id"].value;
                    var type = form[0]["type_id"].value;
                    var amount = form[0]["amount"].value;
                    var phone_number = form[0]["phone_number"].value;
                    var ported_number = $('#ported_number:checked').val();

                    if (network_id == "" || type == "" || phone_number == "" || amount == "") {
                        swal({ title: "Error!!", text: "Please fill the required fields now!!", timer: 2e3, showConfirmButton: !1 });
                    } else {
                        const typeJson = JSON.parse(type);
                        var typeId = typeJson.id;
                        var typeName = typeJson.name;
                        var networkImage = typeJson.network_image;
                        var networkName = typeJson.network_name;

                        const networkJson = JSON.parse(network_id);
                        network_id = networkJson.id;

                        var Message = "You're about to send " + networkName + "  " + typeName + " " + amount + " Naira to " + phone_number;

                        swal({
                            title: "Confirm Transaction",
                            text: Message,
                            showCancelButton: !0,
                            closeOnConfirm: !1,
                            showLoaderOnConfirm: !0,
                            //imageUrl: networkImage,
                            //html: !0
                        }, function () {
                            $.ajax({
                                type: "POST",
                                url: url,
                                data: {
                                    wallet_id: wallet_id,
                                    phone_number: phone_number,
                                    network_id: network_id,
                                    type_id: typeId,
                                    amount: amount,
                                    transaction_pin: transaction_pin,
                                    ported_number: ported_number
                                },
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                cache: false,
                                beforeSend: function () {
                                    $(".submitRequest").attr("disabled", "disabled");
                                    $('.submitRequest').html("<i class='fa fa-spinner fa-spin'></i> processing...");
                                },
                                success: function (response) {
                                    if (!response.success && response.data == null) {
                                        sweetAlert("Oops...", response.message, "error");
                                    } else {
                                        window.location.replace('transactions/' + response.data.reference);
                                    }
                                },
                                complete: function (xhr, textStatus) {
                                    $(".submitRequest").removeAttr("disabled");
                                    $(".submitRequest").html("<i class='fab fa-opencart'></i> Submit");
                                },
                            });
                        })

                    }

                    //transaction pin
                }
            });
        //transaction pin
    });

}


//airtime to cash
function ismartAirtimeToCash() {
    $(document).on('change', '.airtimeNetwork', function () {
        var network = $(".airtimeNetwork").val();
        if (network !== "") {
            $("#network_img").removeAttr("hidden", true);
            const networkJson = JSON.parse(network);
            var networkId = networkJson.id;
            var networkImage = networkJson.image;
            $("#network_img").attr("src", networkImage);
        } else {
            $("#network_img").attr("hidden", "hidden");
            $(".pin-input").attr("hidden", "hidden");
        }
    });
}


function displayFields() {
    $(document).on('keyup', '#amount', function () {
        var amount = $('#amount').val();
        var mode = $('#mode').val();
        if (amount != "") {
            if (mode != "") {
                if (mode == "1") {
                    $("#airtimeDetails-label").html("Recharge Pin");
                    $("#airtimeDetails").attr("placeholder", "Enter recharge pin of the selected network");
                    $(".pin-input").removeAttr("hidden");
                } else if (mode == "2") {
                    $("#airtimeDetails-label").html("Phone Number");
                    $("#airtimeDetails").attr("placeholder", "Enter the phone number you're sending from");
                    $(".pin-input").removeAttr("hidden");
                }

            }
        } else {
            $(".pin-input").attr("hidden", "hidden");
        }
    })
}

function displayAmt() {
    $(document).on('change', '#mode', function () {
        var mode = $('#mode').val();
        $("#amount").val("");
        if (mode != "") {
            $("#amt-div").removeAttr("hidden");
            $(".pin-input").attr("hidden", "hidden");
        } else {
            $("#amt-div").attr("hidden", "hidden");
        }
    })
}


function convert_airtime() {
    $(document).on('click', '.submitRequest', function () {

        swal({
            title: "Secure Transaction",
            text: "Transaction Pin!!",
            type: "input",
            showCancelButton: !0,
            closeOnConfirm: !1,
            animation: "slide-from-top",
            inputPlaceholder: "Enter Your Transaction Pin",
            inputType: "password"
        },
            function (transaction_pin) {
                if (!1 !== transaction_pin && "" === transaction_pin) {
                    return (swal.showInputError("Transaction Pin is require!"), !1)
                } else {


                    var form = $('.submitRequest').parents('form');
                    var url = form.attr("action");
                    var wallet_id = $('.wallet_id').val();
                    var network = $("#network_id").val();
                    var mode = $("#mode").val();
                    var amount = $("#amount").val();
                    var airtimeDetails = $("#airtimeDetails").val();
                    if (network == "" || mode == "" || amount == "" || airtimeDetails == "") {
                        swal({ title: "Error!!", text: "Please fill the required fields!!", timer: 2e3, showConfirmButton: !1 });
                    } else {
                        const networkJson = JSON.parse(network);
                        network = networkJson.id;
                        $.ajax({
                            type: "POST",
                            url: url,
                            data: {wallet_id: wallet_id, network: network, mode: mode, amount: amount, airtimeDetails: airtimeDetails, transaction_pin: transaction_pin },
                            cache: false,
                            //dataType: "html",
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            beforeSend: function () {
                                $(".submitRequest").attr("disabled", "disabled");
                                $(".submitRequest").html("<i class='fa fa-spinner fa-spin'></i>  Processing...");
                            },
                            success: function (response) {
                                if (!response.success && response.data == null) {
                                    sweetAlert("Oops...", response.message, "error");
                                } else {
                                    sweetAlert("Done", response.message, "success");
                                    window.location.replace('transactions/' + response.data.reference);
                                }

                            },
                            complete: function (data) {
                                $(".submitRequest").removeAttr("disabled");
                                $(".submitRequest").html("Submit");
                            }
                        });

                    }

                    //transaction pin
                }
            });
        //transaction pin

    })

}


//cable tv
function getCablePlans(url) {
    $(document).on('change', '.tv', function () {
        var tv = $(".tv").val();
        if (tv !== "") {
            $.ajax({
                type: "GET",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { type: "cablePlan", tv: tv },
                cache: false,
                dataType: "html",
                beforeSend: function () {
                    $("#plan").LoadingOverlay("show", {
                        background: "rgba(165, 190, 100, 0.5)"
                    });
                },
                success: function (response) {
                    $("#plan").html(response);

                },
                complete: function (data) {
                    $("#plan").LoadingOverlay("hide", true);
                }
            });
        }
    });
}

//verify decoder number
function VerifyCable(url) {
    $(document).on('change', '#decoder_number', function () {
        var cableId = $('#cable_id').val();
        var plan = $('#plan').val();
        var decoder_number = $('#decoder_number').val();
        if (plan == "") {
            swal({ title: "Error!!", text: "Please select a plan", timer: 2e3, showConfirmButton: !1 });
        }
        else if (decoder_number == "") {
            swal({ title: "Error!!", text: "Your decoder number is required", timer: 2e3, showConfirmButton: !1 });
        } else {
            const planJson = JSON.parse(plan);
            var planId = planJson.id;

            $.ajax({
                url: url,
                type: "POST",
                data: {
                    plan_id: planId,
                    cable_id: cableId,
                    decoder_number: decoder_number,
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function () {
                    $.LoadingOverlay("show");
                },
                success: function (response) {
                    if (!response.success && response.data == null) {
                        $("#customer_name-div").attr("hidden", "hidden");
                        $("#customer_name").val("");
                        sweetAlert("Oops...", response.message, "error");
                    } else {
                        $("#customer_name-div").removeAttr("hidden");
                        $("#customer_name").val(response.message);
                    }
                },
                complete: function (data) {
                    $.LoadingOverlay("hide", true);
                },
            });
        }

    });
}

//bill payment
function buyCable() {
    $(document).on('click', '.submitRequest', function () {

        swal({
            title: "Secure Transaction",
            text: "Transaction Pin!!",
            type: "input",
            showCancelButton: !0,
            closeOnConfirm: !1,
            animation: "slide-from-top",
            inputPlaceholder: "Enter Your Transaction Pin",
            inputType: "password"
        },
            function (transaction_pin) {
                if (!1 !== transaction_pin && "" === transaction_pin) {
                    return (swal.showInputError("Transaction Pin is require!"), !1)
                } else {

                    var form = $('.submitRequest').parents('form');
                    var url = form.attr("action");
                    var cableId = $('#cable_id').val();
                    var plan = $('#plan').val();
                    var phone_number = $('#phone_number').val();
                    var decoder_number = $('#decoder_number').val();
                    var customerName = $('#customer_name').val();
                    if (plan == "") {
                        swal({ title: "Error!!", text: "Please select a plan", timer: 2e3, showConfirmButton: !1 });
                    }
                    else if (phone_number == "") {
                        swal({ title: "Error!!", text: "Input your phone number", timer: 2e3, showConfirmButton: !1 });
                    }
                    else if (decoder_number == "") {
                        swal({ title: "Error!!", text: "Your decoder number is required", timer: 2e3, showConfirmButton: !1 });
                    } else {

                        const planJson = JSON.parse(plan);
                        var planId = planJson.id;
                        var planName = planJson.name;
                        var planAmount = planJson.amount;
                        var tvName = planJson.tv_name;

                        if (customerName == "") {
                            swal({
                                title: "Decoder Verification",
                                text: "Unable to verify decoder number do you want to continue?",
                                showCancelButton: !0,
                                closeOnConfirm: !1,
                                showLoaderOnConfirm: !1,
                            }, function () {
                                submitCablePayment(url, tvName, planName, planAmount, decoder_number, phone_number, planId, cableId, transaction_pin, "");
                            });
                        } else {
                            submitCablePayment(url, tvName, planName, planAmount, decoder_number, phone_number, planId, cableId, transaction_pin, customerName);
                        }

                    }

                    //transaction pin
                }
            });
        //transaction pin

    });

}

function submitCablePayment(url, tvName, planName, planAmount, decoder_number, phone_number, planId, cableId, transaction_pin, customerName) {
    var wallet_id = $('.wallet_id').val();
    var message = "You're about to purchase " + tvName + " [ " + planName + " ] for " + planAmount + " Naira on (" + decoder_number + ") " + customerName;
    swal({
        title: "Confirm Transaction",
        text: message,
        showCancelButton: !0,
        closeOnConfirm: !1,
        showLoaderOnConfirm: !0,
    }, function () {
        $.ajax({
            type: "POST",
            url: url,
            data: {
                wallet_id: wallet_id,
                phone_number: phone_number,
                plan_id: planId,
                cable_id: cableId,
                decoder_number: decoder_number,
                transaction_pin: transaction_pin

            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            cache: false,
            beforeSend: function () {
                $(".submitRequest").attr("disabled", "disabled");
                $('.submitRequest').html("<i class='fa fa-spinner fa-spin'></i> processing...");
            },
            success: function (response) {
                if (!response.success && response.data == null) {
                    sweetAlert("Oops...", response.message, "error");
                } else {
                    window.location.replace('transactions/' + response.data.reference);
                }
            },
            complete: function (xhr, textStatus) {
                $(".submitRequest").removeAttr("disabled");
                $(".submitRequest").html("Submit");
            },
        });
    })
}



function VerifyBill(url) {
    $(document).on('change', '#meter_number', function () {
        var disco = $('#disco').val();
        var meter_type = $('#meter_type').val();
        var meter_number = $('#meter_number').val();
        if (disco == "") {
            swal({ title: "Error!!", text: "Please select Disco Type", timer: 2e3, showConfirmButton: !1 });
        }
        else if (meter_type == "") {
            swal({ title: "Error!!", text: "Your meter type is required", timer: 2e3, showConfirmButton: !1 });
        }
        else if (meter_number == "") {
            swal({ title: "Error!!", text: "Your meter number is required", timer: 2e3, showConfirmButton: !1 });
        } else {
            $.ajax({
                url: url,
                type: "POST",
                data: {
                    disco_id: disco,
                    meter_number: meter_number,
                    meter_type: meter_type,
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function () {
                    $.LoadingOverlay("show");
                },
                success: function (response) {
                    if (!response.success && response.data == null) {
                        $("#customer_name-div").attr("hidden", "hidden");
                        $("#customer_name").val("");
                        sweetAlert("Oops...", response.message, "error");
                    } else {
                        $("#customer_name-div").removeAttr("hidden");
                        $("#customer_name").val(response.message);
                    }
                },
                complete: function (data) {
                    $.LoadingOverlay("hide", true);
                },
            });
        }

    });
}

function buyBill() {
    $(document).on('click', '.submitRequest', function () {

        swal({
            title: "Secure Transaction",
            text: "Transaction Pin!!",
            type: "input",
            showCancelButton: !0,
            closeOnConfirm: !1,
            animation: "slide-from-top",
            inputPlaceholder: "Enter Your Transaction Pin",
            inputType: "password"
        },
            function (transaction_pin) {
                if (!1 !== transaction_pin && "" === transaction_pin) {
                    return (swal.showInputError("Transaction Pin is require!"), !1)
                } else {

                    var form = $('.submitRequest').parents('form');
                    var url = form.attr("action");

                    var disco = $('#disco').val();
                    var meter_type = $('#meter_type').val();
                    var meter_number = $('#meter_number').val();
                    var customerName = $('#customer_name').val();
                    var amount = $('#amount').val();
                    var phone_number = $('#phone_number').val();
                    if (disco == "") {
                        swal({ title: "Error!!", text: "Please select Disco Type", timer: 2e3, showConfirmButton: !1 });
                    }
                    else if (meter_type == "") {
                        swal({ title: "Error!!", text: "Your meter type is required", timer: 2e3, showConfirmButton: !1 });
                    }
                    else if (meter_number == "") {
                        swal({ title: "Error!!", text: "Your meter number is required", timer: 2e3, showConfirmButton: !1 });
                    }
                    else if (amount == "") {
                        swal({ title: "Error!!", text: "Amount is required", timer: 2e3, showConfirmButton: !1 });
                    }
                    else if (phone_number == "") {
                        swal({ title: "Error!!", text: "Input your phone number", timer: 2e3, showConfirmButton: !1 });
                    }
                    else {
                        if (customerName == "") {
                            swal({
                                title: "Meter Verification",
                                text: "Unable to verify Meter Number do you want to continue?",
                                showCancelButton: !0,
                                closeOnConfirm: !1,
                                showLoaderOnConfirm: !1,
                            }, function () {
                                //submit meter
                                submitBillPayment(url, phone_number, disco, meter_number, meter_type, amount, transaction_pin, "");
                            });
                        } else {
                            //submit meter
                            submitBillPayment(url, phone_number, disco, meter_number, meter_type, amount, transaction_pin, customerName);
                        }

                    }

                    //transaction pin
                }
            });
        //transaction pin

    });

}

function submitBillPayment(url, phone_number, disco, meter_number, meter_type, amount, transaction_pin, customerName) {
    var wallet_id = $('.wallet_id').val();
    var message = "You're about to purchase " + disco + " [ " + meter_type + " ] for " + amount + " Naira on (" + meter_number + ") " + customerName;
    swal({
        title: "Confirm Transaction",
        text: message,
        showCancelButton: !0,
        closeOnConfirm: !1,
        showLoaderOnConfirm: !0,
    }, function () {
        $.ajax({
            type: "POST",
            url: url,
            data: {
                wallet_id: wallet_id,
                phone_number: phone_number,
                disco_id: disco,
                meter_number: meter_number,
                meter_type: meter_type,
                amount: amount,
                transaction_pin: transaction_pin
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            cache: false,
            beforeSend: function () {
                $(".submitRequest").attr("disabled", "disabled");
                $('.submitRequest').html("<i class='fa fa-spinner fa-spin'></i> processing...");
            },
            success: function (response) {
                if (!response.success && response.data == null) {
                    sweetAlert("Oops...", response.message, "error");
                } else {
                    window.location.replace('transactions/' + response.data.reference);
                }
            },
            complete: function (xhr, textStatus) {
                $(".submitRequest").removeAttr("disabled");
                $(".submitRequest").html("Submit");
            },
        });
    })
}


/****
 *
 *
 * send bulk sms
 *
 */

function sendBulkSms() {
    $(document).on('click', '.submitRequest', function () {

        swal({
            title: "Secure Transaction",
            text: "Transaction Pin!!",
            type: "input",
            showCancelButton: !0,
            closeOnConfirm: !1,
            animation: "slide-from-top",
            inputPlaceholder: "Enter Your Transaction Pin",
            inputType: "password"
        },
            function (transaction_pin) {
                if (!1 !== transaction_pin && "" === transaction_pin) {
                    return (swal.showInputError("Transaction Pin is require!"), !1)
                } else {


                    let form = $('.submitRequest').parents('form');
                    let url = form.attr("action");
                    let wallet_id = $('.wallet_id').val();
                    let sender = $('#sender').val();
                    let receiver = $('#receiver').val();
                    var message = $("#message").val();
                    if (sender != "") {
                        if (receiver != "") {
                            if (message != "") {
                                $.ajax({
                                    type: "POST",
                                    url: url,
                                    data: {wallet_id: wallet_id, sender: sender, receiver: receiver, message: message, transaction_pin: transaction_pin },
                                    cache: false,
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    beforeSend: function () {
                                        $(".submitRequest").attr("disabled", "disabled");
                                        $(".submitRequest").html("<i class='fa fa-spinner fa-spin'></i> Processing...");
                                    },
                                    success: function (response) {
                                        if (!response.success && response.data == null) {
                                            sweetAlert("Oops...", response.message, "error");
                                        } else {
                                            window.location.replace('transactions/' + response.data.reference);
                                        }
                                    },
                                    complete: function (data) {
                                        $(".submitRequest").removeAttr("disabled");
                                        $(".submitRequest").html("Submit");
                                    }
                                });

                            } else {
                                swal({ title: "Error!!", text: "Message field cannot be blank", timer: 2e3, showConfirmButton: !1 });
                            }

                        } else {
                            swal({ title: "Error!!", text: "Please input receiver(s) number", timer: 2e3, showConfirmButton: !1 });
                        }

                    } else {
                        swal({ title: "Error!!", text: "Please input sender name", timer: 2e3, showConfirmButton: !1 });
                    }

                    //transaction pin
                }
            });
        //transaction pin

    });

}


/******
 *
 * exam
 *
 *
 */

function displayExamFields() {
    $(document).on('change', '#exam', function () {
        var exam_option = $("#exam").val();
        if (exam_option == "" || exam_option == null) {
            $("#exam-quantity-div").attr("hidden", "hidden");
            $("#exam-amount-div").attr("hidden", "hidden");
        } else {
            $("#exam-quantity-div").removeAttr("hidden");
            $("#amount").val(0);
        }
    })
}


function displayExamAmount(url) {
    $(document).on('change', '#quantity', function () {
        var exam = $("#exam").val();
        var quantity = $("#quantity").val();
        $("#exam-amount-div").removeAttr("hidden");
        if (exam == "" || quantity == "") {
            $('#amount').val(0);
        } else {
            $.ajax({
                type: "GET",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { quantity: quantity, exam: exam },
                cache: false,
                dataType: 'JSON',
                beforeSend: function () {
                    $("#amount").LoadingOverlay("show", {
                        background: "rgba(165, 190, 100, 0.5)"
                    });
                },
                success: function (response) {
                    $('#amount').val(response);
                },
                complete: function (data) {
                    $("#amount").LoadingOverlay("hide", true);
                }
            });
        }
    })
}



function buyExam() {
    $(document).on('click', '.submitRequest', function () {

        swal({
            title: "Secure Transaction",
            text: "Transaction Pin!!",
            type: "input",
            showCancelButton: !0,
            closeOnConfirm: !1,
            animation: "slide-from-top",
            inputPlaceholder: "Enter Your Transaction Pin",
            inputType: "password"
        },
            function (transaction_pin) {
                if (!1 !== transaction_pin && "" === transaction_pin) {
                    return (swal.showInputError("Transaction Pin is require!"), !1)
                } else {


                    let form = $('.submitRequest').parents('form');
                    let url = form.attr("action");
                    var wallet_id = $('.wallet_id').val();
                    var exam = $('#exam').val();
                    var quantity = $('#quantity').val();
                    if (exam !== "") {
                        if (quantity != "") {
                            $.ajax({
                                type: "POST",
                                url: url,
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                data: {wallet_id: wallet_id, exam_id: exam, quantity: quantity, transaction_pin: transaction_pin },
                                cache: false,
                                //dataType: "html",
                                beforeSend: function () {
                                    $(".submitRequest").attr("disabled", "disabled");
                                    $(".submitRequest").html("<i class='fa fa-spinner fa-spin'></i> Processing...");
                                },
                                success: function (response) {
                                    if (!response.success && response.data == null) {
                                        sweetAlert("Oops...", response.message, "error");
                                    } else {
                                        window.location.replace('transactions/' + response.data.reference);
                                    }
                                },
                                error: function (request, status, error) {
                                    sweetAlert("Oops...", request.responseText, "error");
                                },
                                complete: function (data) {
                                    sweetAlert("Oops...", data, "error");
                                    $(".submitRequest").removeAttr("disabled");
                                    $(".submitRequest").html("Proceed");
                                }
                            });

                        } else {
                            swal({ title: "Error!!", text: "Please select quantity", timer: 2e3, showConfirmButton: !1 });
                        }

                    } else {
                        swal({ title: "Error!!", text: "Please select an exam", timer: 2e3, showConfirmButton: !1 });
                    }

                    //transaction pin
                }
            });
        //transaction pin

    });

}


/*********
 *
 *
 * update password
 *
 *
 */
function updatePassword() {
    $(document).on('click', '.submitRequestPassword', function () {
        var form = $('.submitRequestPassword').parents('form');
        var url = form.attr("action");
        var current_password = $('#current_password').val();
        var password = $('#password').val();
        var password_confirmation = $('#password_confirmation').val();

        if (current_password == "") {
            swal({ title: "Error!!", text: "Current password is required", timer: 2e3, showConfirmButton: !1 });
        }
        else if (password == "") {
            swal({ title: "Error!!", text: "Choose a new password", timer: 2e3, showConfirmButton: !1 });
        }
        else if (password_confirmation == "") {
            swal({ title: "Error!!", text: "Please Confirm your new password", timer: 2e3, showConfirmButton: !1 });
        }
        else if (password != password_confirmation) {
            swal({ title: "Error!!", text: "Password does not match", timer: 2e3, showConfirmButton: !1 });
        } else {
            var message = "you are about to change your Password";
            swal({
                title: "Action Confirmation",
                text: message,
                showCancelButton: !0,
                closeOnConfirm: !1,
                showLoaderOnConfirm: !0,
            }, function () {
                $.ajax({
                    type: "POST",
                    url: url,
                    data: {
                        current_password: current_password,
                        password: password,
                        password_confirmation: password_confirmation
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    cache: false,
                    beforeSend: function () {
                        $(".submitRequest").attr("disabled", "disabled");
                        $('.submitRequest').html("<i class='fa fa-spinner fa-spin'></i> processing...");
                    },
                    success: function (response) {
                        if (!response.success) {
                            sweetAlert("Oops...", response.message, "error");
                        } else {

                            sweetAlert("Yes...", response.message, "success");
                        }
                    },
                    complete: function (xhr, textStatus) {
                        $(".submitRequest").removeAttr("disabled");
                        $(".submitRequest").html("Submit");
                    },
                });
            })
        }

    });

}


function transactionPin(url) {
    $(document).on('click', '.submitRequest', function () {
        var type = $('#type').val();
        var pin = $('#pin').val();
        var current_pin = $('#current_pin').val();
        var pin_confirmation = $('#pin_confirmation').val();

        if ((current_pin == null || current_pin.length < 4 || current_pin.length > 4) && type == "update") {
            swal({ title: "Error!!", text: "Current Pin must be 4 digit number", timer: 2e3, showConfirmButton: !1 });
        }
        else if (pin == "") {
            swal({ title: "Error!!", text: "Pin is required", timer: 2e3, showConfirmButton: !1 });
        }
        else if (pin.length < 4 || pin.length > 4) {
            swal({ title: "Error!!", text: "Pin must be 4 digit", timer: 2e3, showConfirmButton: !1 });
        }
        else if (pin_confirmation == "") {
            swal({ title: "Error!!", text: "Please Confirm your new pin", timer: 2e3, showConfirmButton: !1 });
        }
        else if (pin_confirmation.length < 4 || pin_confirmation.length > 4) {
            swal({ title: "Error!!", text: "Pin Confirmation must be 4 digit number", timer: 2e3, showConfirmButton: !1 });
        }
        else if (pin != pin_confirmation) {
            swal({ title: "Error!!", text: "Pin does not match", timer: 2e3, showConfirmButton: !1 });
        } else {
            var message = "Note You are about to " + type + " transaction pin";
            swal({
                title: "Action Confirmation",
                text: message,
                showCancelButton: !0,
                closeOnConfirm: !1,
                showLoaderOnConfirm: !0,
            }, function () {
                $.ajax({
                    type: "POST",
                    url: url,
                    data: {
                        pin: pin,
                        old_pin: current_pin,
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    cache: false,
                    beforeSend: function () {
                        $(".submitRequest").attr("disabled", "disabled");
                        $('.submitRequest').html("<i class='fa fa-spinner fa-spin'></i> processing...");
                    },
                    success: function (response) {
                        if (!response.success) {
                            sweetAlert("Oops...", response.message, "error");
                        } else {
                            sweetAlert("Yes...", response.data.message, "success");
                            window.location.reload();
                        }
                    },
                    complete: function (xhr, textStatus) {
                        $(".submitRequest").removeAttr("disabled");
                        $(".submitRequest").html("Submit");
                    },
                });
            })
        }

    });

}




function saveProfile() {
    $(document).on('click', '.submitRequest', function () {
        var form = $('.submitRequest').parents('form');
        var url = form.attr("action");
        var email = $('#email').val();
        var username = $('#username').val();
        var firstname = $('#firstname').val();
        var lastname = $('#lastname').val();
        var phone_number = $('#phone_number').val();

        if (username != "" && email != "" && firstname != "" && lastname != "" && phone_number != "") {
            if (phone_number.length == 11) {
                $.ajax({
                    type: "POST",
                    url: url,
                    data: { email: email, username: username, first_name: firstname, last_name: lastname, phone_number: phone_number },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    cache: false,
                    //dataType: "html",
                    beforeSend: function () {
                        $(".submitRequest").attr("disabled", "disabled");
                        $(".submitRequest").html("<i class='fa fa-spinner fa-spin'></i>  Saving...");
                    },
                    success: function (response) {
                        if (!response.success) {
                            sweetAlert("Oops...", response.message, "error");
                        } else {
                            sweetAlert("Yes...", response.message, "success");
                        }
                    },
                    complete: function (data) {
                        $(".submitRequest").removeAttr("disabled");
                        $(".submitRequest").html("Submit");
                    }
                });

            } else {
                swal({ title: "Error!!", text: "Invalid Phone Number", timer: 2e3, showConfirmButton: !1 });
            }
        } else {
            swal({ title: "Error!!", text: "Fields cannot be blank", timer: 2e3, showConfirmButton: !1 });
        }
    })
}


/*******
 *
 *
 *  Upgrade Account
 *
 *
 *
 */
function upgradeAccount(url) {
    $(document).on('click', '.upgradeAccount', function () {
        var package_id = $('#package_id').val();
        if (package_id !== "") {
            $.ajax({
                type: "POST",
                url: url,
                data: { package_id: package_id },
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                //dataType: "html",
                beforeSend: function () {
                    $(".upgradeAccount").attr("disabled", "disabled");
                    $.LoadingOverlay("show");
                    $(".upgradeAccount").html("<i class='fa fa-spinner fa-spin'></i>  Processing...");
                },
                success: function (response) {
                    if (!response.success) {
                        sweetAlert("Oops...", response.message, "error");
                    } else {
                        sweetAlert("Yes...", response.message, "success");
                        window.location.reload();
                    }
                },
                complete: function (data) {
                    $(".upgradeAccount").removeAttr("disabled");
                    $.LoadingOverlay("hide");
                    $(".upgradeAccount").html("Upgrade <i class='fa fa-fighter-jet'></i>");
                }
            });
        } else {
            swal({ title: "Error!!", text: "Please select a package", timer: 2e3, showConfirmButton: !1 });
        }

    });

}


/******
 *
 *
 * pricing check
 *
 *
 */
function levelServicePricing() {
    $(document).on('click', '.submitRequest', function () {
        var form = $('.submitRequest').parents('form');
        var url = form.attr("action");
        let service = $("#service").val();
        let level_id = $('#level_id').val();
        if (service !== "" && level_id !== "") {
            $.ajax({
                url: url,
                type: "POST",
                data: { service: service, level_id: level_id },
                dataType: "html",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function () { $.LoadingOverlay("show"); },
                success: function (data) {
                    $("#message").html(data);
                },
                complete: function (data) {
                    $.LoadingOverlay("hide");
                },
            });
        } else {
            swal({ title: "Error!!", text: "Please fill the required fields", timer: 2e3, showConfirmButton: !1 });

        }
    });
}


/****
 *
 * create virtual account
 *
 */
function createVirtualAccount() {
    $(document).on('click', '.submitVirtualAccountRequest', function () {
        var form = $('.submitVirtualAccountRequest').parents('form');
        var url = form.attr("action");
        var email = $('#email').val();
        if (email != "") {
            $.ajax({
                type: "GET",
                url: url,
                data: { email: email },
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                //dataType: "html",
                beforeSend: function () {
                    $(".submitVirtualAccountRequest").attr("disabled", "disabled");
                    $.LoadingOverlay("show");
                    $(".submitVirtualAccountRequest").html("<i class='fa fa-spinner fa-spin'></i>  Processing...");
                },
                success: function (response) {
                    if (!response.success) {
                        sweetAlert("Oops...", response.message, "error");
                    } else {
                        sweetAlert("Yes...", response.message, "success");
                        window.location.reload();
                    }
                },
                complete: function (data) {
                    $(".submitVirtualAccountRequest").removeAttr("disabled");
                    $.LoadingOverlay("hide");
                    $(".submitVirtualAccountRequest").html("Create Virtaul Account");
                }
            });
        } else {
            swal({ title: "Error!!", text: "Email Cant be Empty", timer: 2e3, showConfirmButton: !1 });
        }

    });

}


/****
 *
 * create support ticket
 *
 */
function createSupportTicket() {
    $(document).on('click', '.submitRequest', function () {
        var form = $('#ismartForm');
        //var formData = new FormData(this);
        var url = form.attr("action");
        var formData = new FormData(form[0]);

        var support_type_id = $('#support_type_id').val();
        var subject = $('#subject').val();
        var message = $('#message').val();
        if (support_type_id != null && subject != "" && message != "") {
            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                contentType: false,
                processData: false,
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                //dataType: "html",
                beforeSend: function () {
                    $(".submitRequest").attr("disabled", "disabled");
                    $.LoadingOverlay("show");
                    $(".submitRequest").html("<i class='fa fa-spinner fa-spin'></i>  Processing...");
                },
                success: function (response) {
                    if (!response.success) {
                        sweetAlert("Oops...", response.message, "error");
                    } else {
                        sweetAlert("Yes...", "Ticket Submited Successfully", "success");
                        $(".createMessage").attr("hidden", "hidden");
                        $(".ticketNumber").html(response.data.message + " your ticket number is " + response.data.ticket_number)
                        $(".ticketNumber").removeAttr("hidden", true);

                    }
                },
                complete: function (data) {
                    $(".submitRequest").removeAttr("disabled");
                    $.LoadingOverlay("hide");
                    $(".submitRequest").html("<span class=\"me-2\"><i class=\"fa fa-paper-plane\"></i></span>Submit");
                }
            });
        } else {
            swal({ title: "Error!!", text: "please fill the require field", timer: 2e3, showConfirmButton: !1 });
        }

    });

}


function submitSupportMessage() {
    $(document).on('click', '.submitRequest', function () {
        var form = $('#ismartForm');

        var url = form.attr("action");
        var formData = new FormData(form[0]);
        var message = $('#message').val();
        if (message != "") {
            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                contentType: false,
                processData: false,
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                //dataType: "html",
                beforeSend: function () {
                    $(".submitRequest").attr("disabled", "disabled");
                    $.LoadingOverlay("show");
                    $(".submitRequest").html("<i class='fa fa-spinner fa-spin'></i>  Processing...");
                },
                success: function (response) {
                    if (!response.success) {
                        sweetAlert("Oops...", response.message, "error");
                    } else {
                        sweetAlert("Yes...", "Message Sent Successfully", "success");
                        window.location.reload();

                    }
                },
                complete: function (data) {
                    $(".submitRequest").removeAttr("disabled");
                    $.LoadingOverlay("hide");
                    $(".submitRequest").html("<span class=\"me-2\"><i class=\"fa fa-paper-plane\"></i></span>Submit");
                }
            });
        } else {
            swal({ title: "Error!!", text: "please fill the require field", timer: 2e3, showConfirmButton: !1 });
        }

    });

}

function viewRecord(url) {
    var filter = $('#filter').val();
    var search = $('#search').val();
    // prev-next or all-links
    $.ajax({
        url: url,
        type: "GET",
        data: { rowcount: $("#rowcount").val(), "pagination_setting": "all-links", key: 2, search: search, filter: filter },
        beforeSend: function () { $.LoadingOverlay("show"); },
        success: function (data) {
            $("#responsecontainer").html(data);
        },
        complete: function (data) {
            $.LoadingOverlay("hide");
        },
    });
}


function filterRecord(url) {
    $(document).on('change', '#filter', function () {
        var filter = $('#filter').val();
        var search = $('#search').val();
        // prev-next or all-links
        $.ajax({
            url: url,
            type: "GET",
            data: { rowcount: $("#rowcount").val(), "pagination_setting": "all-links", key: 2, search: search, filter: filter },
            beforeSend: function () { $.LoadingOverlay("show"); },
            success: function (data) {
                $("#responsecontainer").html(data);
            },
            complete: function (data) {
                $.LoadingOverlay("hide");
            },
        });

    });
}

function searchRecord(url) {
    $(document).on('keyup', '#search', function () {
        var search = $('#search').val();
        var filter = $('#filter').val();
        // prev-next or all-links
        $.ajax({
            url: url,
            type: "GET",
            data: { rowcount: $("#rowcount").val(), "pagination_setting": "all-links", key: 2, search: search, filter: filter },
            beforeSend: function () {
                $("#responsecontainer").LoadingOverlay("show", {
                    background: "rgba(165, 190, 100, 0.5)"
                });
            },
            success: function (data) {
                $("#responsecontainer").html(data);
            },
            complete: function (data) {
                $("#responsecontainer").LoadingOverlay("hide", true);
            },
        });

    });
}





function iSmartSearchTranx(url) {
    $(document).on('click', '#ismart-search-tranx', function (e) {
        e.preventDefault();
        var key = "ismart";

        var phoneNumber = $('#ismart-search-tranx-phoneNumber').val();

        if (phoneNumber !== "") {
            $.ajax({
                url: url,
                type: "GET",
                data: { rowcount: $("#rowcount").val(), "pagination_setting": "all-links", key: key, "phone": phoneNumber },
                beforeSend: function () {
                    $("#ismart-search-tranx").attr("disabled", "disabled");
                    $("#ismart-search-tranx").html("<i class='fa fa-spinner fa-spin'></i>  Searching");
                    //$.LoadingOverlay("show");
                },
                success: function (data) {
                    $("#responsecontainer").html(data);
                },
                complete: function (data) {
                    $("#ismart-search-tranx").removeAttr("disabled");
                    $("#ismart-search-tranx").html("Search");
                    //$.LoadingOverlay("hide");
                },
            });
        } else {
            alert("Phone Number is missing");
        }
    });
}



function submitSalesCalculator() {
    $(document).on('click', '.submitRequest', function (e) {
        e.preventDefault();
        var form = $('#salesCheckForm');
        var url = form.attr("action");
        var formData = new FormData(form[0]);
        var state_date = $('#start-date').val();
        var end_date = $('#end-date').val();
        if (state_date != "" || end_date != "") {
            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                contentType: false,
                processData: false,
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                //dataType: "html",
                beforeSend: function () {
                    $(".submitRequest").attr("disabled", "disabled");
                    $.LoadingOverlay("show");
                    $(".submitRequest").html("<i class='fa fa-spinner fa-spin'></i>  Processing...");
                },
                success: function (response) {
                    $("#SalesResultOutput").html(response);

                },
                complete: function (data) {
                    $(".submitRequest").removeAttr("disabled");
                    $.LoadingOverlay("hide");
                    $(".submitRequest").html("<span class=\"me-2\"><i class=\"fa fa-calculator\"></i></span>Calculate");
                }
            });
        } else {
            swal({ title: "Error!!", text: "please fill the require field", timer: 2e3, showConfirmButton: !1 });
        }

    });

}


function loadSalesAnalysis() {

        var form = $('#salesCheckForm');
        var url = form.attr("action");
        var start_date = $('#start-date').val();
        var end_date = $('#end-date').val();
        if (start_date != "" || end_date != "") {
            $.ajax({
                type: "POST",
                url: url,
                data: {start_date : start_date, end_date:end_date},
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },

                beforeSend: function () {
                    $(".submitRequest").attr("disabled", "disabled");
                    $.LoadingOverlay("show");
                    $(".submitRequest").html("<i class='fa fa-spinner fa-spin'></i>  Processing...");
                },
                success: function (response) {
                    $("#SalesResultOutput").html(response);

                },
                complete: function (data) {
                    $(".submitRequest").removeAttr("disabled");
                    $.LoadingOverlay("hide");
                    $(".submitRequest").html("<span class=\"me-2\"><i class=\"fa fa-calculator\"></i></span>Calculate");
                }
            });
        } else {
            swal({ title: "Error!!", text: "please fill the require field", timer: 2e3, showConfirmButton: !1 });
        }



}

function findUserDetails(formName, hInputName) {
     var form = document.forms[formName];
        var searchVal = form["phone_number"].value;
        if (searchVal == "") {
            swal({ title: "Error!!", text: "This can not be empty", timer: 2e3, showConfirmButton: !1 });
        }
        else {

            $.ajax({
                url: $('#formUrlCheck').val(),
                type: "GET",
                data: {
                    search: searchVal
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function () {
                    $.LoadingOverlay("show");
                },
                success: function (response) {
                    if (!response.success && response.data == null) {
                        $("#account_name-div").attr("hidden", "hidden");
                        $("#account_name").val("");
                        sweetAlert("Oops...", response.message, "error");
                        $('#'+hInputName).remove();
                    } else {
                        $("#account_name-div").append('<input type="hidden" name="'+hInputName+'" id="'+hInputName+'" value="'+response.data.id+'" />');
                        $("#account_name").val(response.data.name);
                        $("#account_name-div").removeAttr("hidden");
                    }
                },
                complete: function (data) {
                    $.LoadingOverlay("hide", true);
                },
            });
        }
}

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
                }else if(this.value === ""){
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




function submitLoginForm() {
    $(document).on('click', '.submitRequest', function(){
        //var form = $('.submitRequest').parents('form');
        var form = $('#login-form');
        var url = form.attr("action");
        var formData = new FormData(form[0]);
        var email = $('#email').val();
        var password = $('#password').val();

        var submitVal = $(".submitRequest").innerText;
        if (email != "" && password != "") {
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
        $(".submitRequest").attr("disabled", "disabled");
        $.LoadingOverlay("show");
        $(".submitRequest").html("<i class='fa fa-spinner fa-spin'></i>  Processing...");
        },
        success: function(response){
            if(!response.success){
                sweetAlert("Oops...", response.message, "error");
            }else{

                sweetAlert("Yes...", "Login Successfully", "success");
                if(response.nextUrl != null){
                    window.location = response.nextUrl;
                }

            }
        },
        error: function (request, status, error) {
            sweetAlert("Oops...", error, "error");
        },
        complete:function(data){
        $(".submitRequest").removeAttr("disabled");
        $.LoadingOverlay("hide");
        $(".submitRequest").html(submitVal);　			}
        });
        } else {
            swal({ title: "Error!!", text: "please fill the require field", timer: 2e3, showConfirmButton: !1 });
        }

    });

}


function generalFormSubmit(formId, fields, submitId = "submitRequest") {
    $(document).on('click', '.'+submitId, function(){

        var form = $('#'+formId);
        var url = form.attr("action");
        var formData = new FormData(form[0]);
        var okay=true;

        $("#"+formId+" :input").each(function(e){
            if(fields == null || !fields.includes(this.name)){
                if(this.type == "checkbox"){
                    if (!$('#'+this.id).is(':checked')){
                    swal({ title: "Error!!", text: "Kindly Check the "+this.name, timer: 2e3, showConfirmButton: !1 });
                    okay = false;
                    return false;
                    }
                }else if(this.value === ""){
                    swal({ title: "Error!!", text: this.name+" can not be empty", timer: 2e3, showConfirmButton: !1 });
                    okay = false;
                    return false;
                }
            }
        });


        if (okay) {
        $.ajax({
        type: "POST",
        url: url,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        //dataType: "html",
        beforeSend: function(){
        $("."+submitId).attr("disabled", "disabled");
        $.LoadingOverlay("show");
        $("."+submitId).html("Processing...");
        },
        success: function(response){
            if(!response.success){
                sweetAlert("Oops...", response.message, "error");
            }else{
                sweetAlert("Yes...", response.message, "success");
                if(response.nextUrl != null){
                    window.location = response.nextUrl;
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
    }


    });

}



