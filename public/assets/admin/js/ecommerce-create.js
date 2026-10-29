/**
 * ===================================
 *    Product Description Editor
 * ===================================
*/
var quill = new Quill('#product-description', {
    modules: {
        toolbar: [
        [{ header: [1, 2, false] }],
        ['bold', 'italic', 'underline'],
        ['image', 'code-block']
        ]
    },
    placeholder: 'Write product description...',
    theme: 'snow'  // or 'bubble'
});


quill.on('text-change', function(delta, source) {
    var justHtml = quill.root.innerHTML;
    $('#description').val(justHtml);
 });


/**
 * ====================
 *      File Pond
 * ====================
*/

// We want to preview images, so we register
// the Image Preview plugin, We also register
// exif orientation (to correct mobile image
// orientation) and size validation, to prevent
// large files from being added
FilePond.registerPlugin(
    FilePondPluginImagePreview,
    FilePondPluginImageExifOrientation,
    FilePondPluginFileValidateSize,
    // FilePondPluginImageEdit
);

// // Select the file input and use
// // create() to turn it into a pond
 var ecommerce = FilePond.create(document.querySelector('.file-upload-multiple'));

//  ecommerce.setOptions({
//     instantUpload: false,
//     server: {
//     //   process:(fieldName, file, metadata, load, error, progress, abort, transfer, options) => {
//     //       this.upload('filearray', file, load, error, progress)
//     //     },
//         revert: (filename, load) => {
//             removeFile(filename);
//             this.removeUpload('filearray', filename, load);
//         },
//     },

//   });

//   function removeFile(file){
//     alert("am called");
//   }

 $(document).on('click', '.submitproduct', function(e){
    e.preventDefault();
    var el=$(this);
    var form = $(el).closest("form");
    var url = form.attr("action");
    var formData = new FormData(form[0]);
    $.each(ecommerce.getFiles(), function (i, file) { //filesObj is the instance of FilePond.create()
        formData.append('images[]', file.file);
      });

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
        beforeSend: function () {

            $.LoadingOverlay("show", {
                background: "rgba(165, 190, 100, 0.5)"
            });
        },
        success: function (data) {
            if (data.success) {
                toastr.success(data.message);
                window.location.href = data.nextUrl;
            } else {
                toastr.error(data.message);
            }
        },
        error: function (xhr) {
            $('#validation-errors').html('');
            $.each(xhr.responseJSON.errors, function(key,value) {
                toastr.error(value);
          });
         },
        complete:function(data){
            $.LoadingOverlay("hide", true);
         }
        });
});


/**
 * =====================
 *      Product Tags
 * =====================
*/
// The DOM element you wish to replace with Tagify
var input = document.querySelector('.product-tags');

// initialize Tagify on the above input node reference
new Tagify(input)
