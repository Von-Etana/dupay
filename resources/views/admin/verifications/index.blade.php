@extends('admin.layouts.app')

@section('style')
<link rel="stylesheet" href="{{ asset('assets/admin/src/plugins/src/table/datatable/datatables-2.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/dt-global_style-2.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/custom_dt_custom-2.css') }}">
@endsection

@section('title', $page_title)

@section('content')
<div class="layout-px-spacing">
    <div class="container-xxl p-0">

        <!-- FILTERS -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label>Status</label>
                        <select id="statusFilter" class="form-select">
                            <option value="">All</option>
                            <option value="0">Pending</option>
                            <option value="1">Verified</option>
                            <option value="2">Rejected</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label>Method</label>
                        <select id="methodFilter" class="form-select">
                            <option value="">All</option>
                            <option value="BVN">BVN</option>
                            <option value="NIN">NIN</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Date Range</label>
                        <input type="text" id="dateFilter" class="form-control" placeholder="YYYY-MM-DD to YYYY-MM-DD">
                    </div>

                    <div class="col-md-2">
                        <label>Sort</label>
                        <select id="sortFilter" class="form-select">
                            <option value="newest">Newest first</option>
                            <option value="pending_first">Pending first</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button id="resetFilters" class="btn btn-secondary w-100">Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="verificationTable">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Method</th>
                                <th>ID Number</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- VIEW MODAL -->
<div class="modal fade" id="viewModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Verification Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewContent"></div>
        </div>
    </div>
</div>

<!-- VERIFY MODAL — pick BVN or NIN before sending to SafeHaven -->
<div class="modal fade" id="verifyModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Approve verification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <p class="mb-3">
                    Confirm which document to send to SafeHaven for
                    <strong id="verifyUserName">this user</strong>.
                </p>

                <label class="form-label">Identity type</label>
                <div class="btn-group w-100 mb-3" role="group">
                    <input type="radio" class="btn-check" name="verifyMethod" id="methodBVN" value="BVN" autocomplete="off">
                    <label class="btn btn-outline-dark" for="methodBVN">BVN</label>

                    <input type="radio" class="btn-check" name="verifyMethod" id="methodNIN" value="NIN" autocomplete="off">
                    <label class="btn btn-outline-primary" for="methodNIN">NIN</label>
                </div>

                <label class="form-label" for="verifyIdNumber">
                    <span id="idNumberLabel">BVN</span> number
                </label>
                <input type="text" id="verifyIdNumber" class="form-control" maxlength="11" inputmode="numeric"
                       placeholder="11 digits">
                <small class="text-muted d-block mt-1">
                    Sent to SafeHaven as <code>identityNumber</code>. Edit it if the user filed both a BVN and a NIN.
                </small>

                <div class="mt-3 small text-muted">
                    Date of birth on file: <strong id="verifyDob">—</strong>
                </div>

                <hr class="my-3">

                <p class="small text-muted mb-2">
                    SafeHaven checks the phone and date of birth against the
                    number's <strong>registered</strong> records — not the app
                    profile. If approval fails with
                    &ldquo;Date of Birth or Phone Number does not match&rdquo;,
                    enter the values registered to the BVN/NIN below. Leave blank
                    to use the phone and DOB already on the record.
                </p>

                <label class="form-label" for="verifyBvnPhone">Registered phone <span class="text-muted">(optional)</span></label>
                <input type="text" id="verifyBvnPhone" class="form-control" inputmode="numeric"
                       placeholder="Phone registered to the BVN/NIN">

                <label class="form-label mt-2">Registered date of birth <span class="text-muted">(optional)</span></label>
                <div class="row g-2">
                    <div class="col-4">
                        <select id="verifyDobDay" class="form-select">
                            <option value="">Day</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <select id="verifyDobMonth" class="form-select">
                            <option value="">Month</option>
                            <option value="01">Jan</option>
                            <option value="02">Feb</option>
                            <option value="03">Mar</option>
                            <option value="04">Apr</option>
                            <option value="05">May</option>
                            <option value="06">Jun</option>
                            <option value="07">Jul</option>
                            <option value="08">Aug</option>
                            <option value="09">Sep</option>
                            <option value="10">Oct</option>
                            <option value="11">Nov</option>
                            <option value="12">Dec</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <select id="verifyDobYear" class="form-select">
                            <option value="">Year</option>
                        </select>
                    </div>
                </div>
                <!-- Assembled YYYY-MM-DD value the backend reads -->
                <input type="hidden" id="verifyBvnDob">

                <div id="verifyError" class="alert alert-danger mt-3 d-none"></div>

                <input type="hidden" id="verifyId">
                <input type="hidden" id="storedBvn">
                <input type="hidden" id="storedNin">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button id="submitVerify" class="btn btn-success">Verify &amp; create account</button>
            </div>
        </div>
    </div>
</div>

<!-- REJECT MODAL -->
<div class="modal fade" id="rejectModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Reject Verification</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea id="rejectReason" class="form-control" placeholder="Enter reason"></textarea>
                <input type="hidden" id="rejectId">
            </div>
            <div class="modal-footer">
                <button id="submitReject" class="btn btn-danger">Submit</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/admin/js/general.js') }}"></script>

<script>
let table;

$(document).ready(function () {

    table = $('#verificationTable').DataTable({
        processing: true,
        // Keep the server's order. VerificationController::fetch() sorts by
        // submitted_at DESC so the newest submission is row one. Without
        // order: [] DataTables re-sorts A-Z on the first column and buries it.
        order: [],
        ajax: {
            url: "{{ route('admin.verifications.fetch') }}",
            data: function (d) {
                d.status = $('#statusFilter').val();
                d.method = $('#methodFilter').val();
                d.date   = $('#dateFilter').val();
                d.sort   = $('#sortFilter').val();
            }
        },
        columns: [
            { data: 'user' },
            { data: 'method' },
            { data: 'id_number' },
            { data: 'name' },
            { data: 'phone' },
            { data: 'status' },
            { data: 'date' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    // FILTERS
    $('#statusFilter, #methodFilter, #dateFilter, #sortFilter').on('change', function () {
        table.ajax.reload();
    });

    $('#resetFilters').click(function () {
        $('#statusFilter').val('');
        $('#methodFilter').val('');
        $('#dateFilter').val('');
        $('#sortFilter').val('newest');
        table.ajax.reload();
    });

    // Fill DOB day (1-31) and year (this year down to 1920) dropdowns.
    (function fillDobSelects() {
        let $day = $('#verifyDobDay');
        for (let d = 1; d <= 31; d++) {
            let v = d < 10 ? '0' + d : '' + d;
            $day.append(`<option value="${v}">${d}</option>`);
        }
        let $year = $('#verifyDobYear');
        let now = new Date().getFullYear();
        for (let y = now; y >= 1920; y--) {
            $year.append(`<option value="${y}">${y}</option>`);
        }
    })();

    // Whenever any of the three change, rebuild the hidden YYYY-MM-DD value.
    $('#verifyDobDay, #verifyDobMonth, #verifyDobYear').on('change', function () {
        let y = $('#verifyDobYear').val();
        let m = $('#verifyDobMonth').val();
        let d = $('#verifyDobDay').val();
        $('#verifyBvnDob').val((y && m && d) ? `${y}-${m}-${d}` : '');
    });
});


// ================= VIEW =================
$(document).on('click', '.viewBtn', function () {
    let id = $(this).data('id');

    $.get('{{ url("adminoffice/verifications") }}/' + id)
    .done(function (res) {

        let statusText = {
            0: 'Pending',
            1: 'Verified',
            2: 'Rejected'
        };

        let idImage   = res.id_image   ? `/storage/${res.id_image}`   : '/default.png';
        let userPhoto = res.user_photo ? `/storage/${res.user_photo}` : '/default.png';

        let html = `
            <div class="row">
                <div class="col-md-6">
                    <p><b>Name:</b> ${res.first_name ?? ''} ${res.last_name ?? ''}</p>
                    <p><b>Other Names:</b> ${res.other_names ?? '-'}</p>
                    <p><b>Method:</b> ${res.method ?? 'BVN'}</p>
                    <p><b>ID Number:</b> ${res.identity_number ?? '-'}</p>
                    <p><b>BVN on file:</b> ${res.bvn ?? '-'}</p>
                    <p><b>NIN on file:</b> ${res.nin ?? '-'}</p>
                    <p><b>Phone:</b> ${res.mobile_no ?? '-'}</p>
                    <p><b>DOB:</b> ${res.date_of_birth ?? '-'}</p>
                    <p><b>Address:</b> ${res.address ?? '-'}</p>
                    <p><b>State:</b> ${res.state ?? '-'}</p>
                    <p><b>City:</b> ${res.city ?? '-'}</p>
                    <p><b>Status:</b> ${statusText[res.status] ?? 'Unknown'}</p>
                </div>
                <div class="col-md-6">
                    <p><b>ID Image:</b></p>
                    <img src="${idImage}" class="img-fluid mb-2"/>

                    <p><b>User Photo:</b></p>
                    <img src="${userPhoto}" class="img-fluid"/>
                </div>
            </div>
        `;

        $('#viewContent').html(html);
        $('#viewModal').modal('show');

    })
    .fail(function () {
        alert('Failed to load details');
    });
});


// ================= VERIFY =================
// Open the modal pre-filled with what the user submitted, then let the admin
// change it. Nothing is sent to SafeHaven until "Verify & create account".
$(document).on('click', '.verifyBtn', function () {
    let $btn = $(this);

    let method = ($btn.data('method') || 'BVN').toString().toUpperCase();
    let bvn = ($btn.data('bvn') || '').toString();
    let nin = ($btn.data('nin') || '').toString();

    $('#verifyId').val($btn.data('id'));
    $('#storedBvn').val(bvn);
    $('#storedNin').val(nin);
    $('#verifyUserName').text($btn.data('name') || 'this user');
    $('#verifyDob').text($btn.data('dob') || '—');
    $('#verifyBvnPhone').val('');
    $('#verifyBvnDob').val('');
    $('#verifyDobDay, #verifyDobMonth, #verifyDobYear').val('');
    $('#verifyError').addClass('d-none').text('');

    $(`#method${method}`).prop('checked', true);
    applyMethodToForm(method);

    $('#verifyModal').modal('show');
});

function applyMethodToForm(method) {
    let bvn = $('#storedBvn').val();
    let nin = $('#storedNin').val();

    $('#idNumberLabel').text(method);

    // Show the number that belongs to the selected method. Fall back to the
    // other one, since older records stored every number in the bvn column.
    let value = method === 'NIN' ? (nin || bvn) : (bvn || nin);
    $('#verifyIdNumber').val(value);
}

$(document).on('change', 'input[name="verifyMethod"]', function () {
    applyMethodToForm($(this).val());
});

$('#submitVerify').click(function () {

    let $btn = $(this);
    let id = $('#verifyId').val();
    let method = $('input[name="verifyMethod"]:checked').val();
    let number = ($('#verifyIdNumber').val() || '').trim();

    let $err = $('#verifyError').addClass('d-none').text('');

    if (!method) {
        $err.removeClass('d-none').text('Select BVN or NIN.');
        return;
    }

    if (!/^\d{11}$/.test(number)) {
        $err.removeClass('d-none').text('The ' + method + ' must be exactly 11 digits.');
        return;
    }

    let bvnPhone = ($('#verifyBvnPhone').val() || '').trim();
    let bvnDob   = ($('#verifyBvnDob').val() || '').trim();

    if (!confirm('Send this KYC to SafeHaven as ' + method + ' ' + number + '?')) return;

    $btn.prop('disabled', true).text('Sending...');

    $.post('{{ url("adminoffice/verifications") }}/' + id + '/verify', {
        _token: '{{ csrf_token() }}',
        method: method,
        identity_number: number,
        // Optional overrides — only sent when the admin filled them.
        // Blank values are sent as empty and the controller falls back to
        // the account phone / record DOB.
        bvn_phone: bvnPhone,
        bvn_dob: bvnDob
    })
    .done(function (res) {
        alert(res.message);
        $('#verifyModal').modal('hide');
        table.ajax.reload(null, false);
    })
    .fail(function (xhr) {
        let msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Verification failed';
        $err.removeClass('d-none').text(msg);
    })
    .always(function () {
        $btn.prop('disabled', false).text('Verify & create account');
    });
});


// ================= REJECT =================
$(document).on('click', '.rejectBtn', function () {
    $('#rejectId').val($(this).data('id'));
    $('#rejectReason').val('');
    $('#rejectModal').modal('show');
});

$('#submitReject').click(function () {

    let id = $('#rejectId').val();
    let reason = $('#rejectReason').val();

    if (!reason.trim()) {
        alert('Please enter rejection reason');
        return;
    }

    $.post('{{ url("adminoffice/verifications") }}/' + id + '/reject', {
        _token: '{{ csrf_token() }}',
        reason: reason
    })
    .done(function (res) {
        alert(res.message);
        $('#rejectModal').modal('hide');
        table.ajax.reload(null, false);
    })
    .fail(function () {
        alert('Something went wrong');
    });
});
</script>
@endsection