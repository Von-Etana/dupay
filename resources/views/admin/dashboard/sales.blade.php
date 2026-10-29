@extends('admin.layouts.app')

@section('style')
    <!-- Keep all your existing styles -->
    <link href="{{ asset('assets/admin/src/assets/css/dark/dashboard/dash_2.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/admin/src/assets/css/dark/users/user-profile.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/admin/src/assets/css/dark/components/modal.css') }}" rel="stylesheet" type="text/css">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/src/vanillaSelectBox/vanillaSelectBox.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/dark/vanillaSelectBox/custom-vanillaSelectBox.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/dt-global_style-2.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/custom_dt_custom-2.css') }}">
    <link href="{{ asset('assets/admin/src/plugins/src/flatpickr/flatpickr.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/admin/src/assets/css/dark/scrollspyNav.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/admin/src/plugins/css/dark/flatpickr/custom-flatpickr.css') }}" rel="stylesheet" type="text/css">
    
    <!-- Additional Custom Styles for Sales Dashboard -->
    <style>
        .sales-dashboard {
            background: #f8f9fa;
        }
        .stat-card {
            background: #0D1320;
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #007bff;
            overflow: hidden;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        .stat-card.primary { border-left-color: #007bff; }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.danger { border-left-color: #dc3545; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.info { border-left-color: #17a2b8; }
        .stat-card.dark { border-left-color: #343a40; }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-meta {
            font-size: 0.8rem;
            color: #868e96;
        }
        
        .chart-container {
            background: #0D1320;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }
        
        .table-custom {
            background: #0D1320;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .table-custom thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
            padding: 15px 20px;
        }
        .table-custom tbody td {
            padding: 12px 20px;
            border-color: #f8f9fa;
            vertical-align: middle;
        }
        .table-custom tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .period-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-group-custom .btn {
            border-radius: 25px;
            margin: 0 5px;
            padding: 8px 20px;
            font-weight: 600;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .btn-group-custom .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .network-badge {
            background: #e9ecef;
            color: #495057;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin: 2px;
        }
        
        .loader-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 300px;
            flex-direction: column;
        }
        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .chart-legend {
            display: flex;
            align-items: center;
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            margin-right: 5px;
        }
    </style>
@endsection

@section('title', "Sales Dashboard")

@section('content')
<div class="layout-px-spacing sales-dashboard">
    <div class="middle-content container-xxl p-0">

        <!-- BREADCRUMB -->
        <div class="page-meta">
            <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Sales Analytics</li>
                </ol>
            </nav>
        </div>
        <!-- /BREADCRUMB -->

        @can('view_sales_stats')
        <!-- Quick Date Range Buttons -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Quick Date Range</h5>
                            <div class="btn-group btn-group-custom" role="group">
                                <button type="button" class="btn btn-outline-success" onclick="loadSalesAnalysis('daily')">
                                    <i class="fa fa-calendar-day"></i> Today
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="loadSalesAnalysis('last7days')">
                                    <i class="fa fa-calendar-week"></i> Last 7 Days
                                </button>
                                <button type="button" class="btn btn-outline-primary" onclick="loadSalesAnalysis('last30days')">
                                    <i class="fa fa-calendar-alt"></i> Last 30 Days
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Range Selector -->
        <div class="row mb-4">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-calculator text-primary"></i> Custom Date Range Analysis</h5>
                        <form id="salesCheckForm" method="POST" action="{{ route('admin.salesCalculate') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-5 mb-3">
                                    <label class="form-label fw-bold">Start Date & Time</label>
                                    <input name="start_date" class="form-control flatpickr flatpickr-input datepicker-default start_date" type="text" placeholder="Select Start Date.." readonly="readonly">
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label class="form-label fw-bold">End Date & Time</label>
                                    <input name="end_date" class="form-control flatpickr flatpickr-input datepicker-default end_date" type="text" placeholder="Select End Date.." readonly="readonly">
                                </div>
                                <div class="col-md-2 mb-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100 submitRequest" id="calculate">
                                        <span class="btn-label">
                                            <i class="fa fa-chart-line"></i>
                                        </span>
                                        Generate Report
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Output -->
        <div id="SalesResultOutput">
            <div class="loader-container">
                <div class="loader-spinner"></div>
                <p class="mt-3 text-muted">Loading sales analytics data...</p>
            </div>
        </div>
        @endcan

    </div>
</div>
@endsection

@can('view_sales_stats')
@section('scripts')
    <script src="{{ asset('assets/admin/src/plugins/src/apex/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/admin/src/plugins/src/flatpickr/flatpickr.js') }}"></script>

    <script>
    // Chart instances
    let inflowOutflowChart = null;
    let airtimeChart = null;
    let dataChart = null;
    let conversionChart = null;
    let conversionFlowChart = null;
    let usdFlowChart = null;
    let eurFlowChart = null;

    // Initialize date pickers
    var f2 = flatpickr(document.getElementsByClassName('datepicker-default'), {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        time_24hr: true
    });

    // Load default sales analysis
    loadSalesAnalysis();

    function loadSalesAnalysis(range = 'daily') {
        console.log('Loading sales analysis for range:', range);
        showLoader();
        
        $.ajax({
            url: "{{ route('admin.salesCalculate') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                range: range
            },
            success: function(response) {
                console.log('AJAX Success Response:', response);
                hideLoader();
                if (response.success) {
                    $('#SalesResultOutput').html(generateSalesHTML(response.data, response.period));
                    initializeCharts(response.data.charts_data, response.data.currency_conversion);
                } else {
                    showError('Error Loading Data', response.message || 'Unknown error occurred');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr, status, error);
                hideLoader();
                showError('Request Failed', `Status: ${status}, Error: ${error}`);
            }
        });
    }

    function submitSalesCalculator() {
        $('#salesCheckForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted');
            showLoader();
            
            const formData = new FormData(this);
            
            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Form AJAX Success:', response);
                    hideLoader();
                    if (response.success) {
                        $('#SalesResultOutput').html(generateSalesHTML(response.data, response.period));
                        initializeCharts(response.data.charts_data, response.data.currency_conversion);
                    } else {
                        showError('Calculation Error', response.message || 'Unknown error occurred');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Form AJAX Error:', xhr, status, error);
                    hideLoader();
                    showError('Submission Failed', `Status: ${status}, Error: ${error}`);
                }
            });
        });
    }

    function generateSalesHTML(data, period) {
        console.log('Generating HTML with data:', data);
        
        if (!data) {
            return `
                <div class="empty-state">
                    <i class="fa fa-chart-bar"></i>
                    <h4>No Data Available</h4>
                    <p>No sales data found for the selected period.</p>
                </div>
            `;
        }

        return `
            <div class="row layout-top-spacing">
                <!-- Period Info -->
                <div class="col-12 mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="period-badge">
                            <i class="fa fa-calendar"></i> 
                            ${new Date(period.start).toLocaleDateString()} ${new Date(period.start).toLocaleTimeString()} 
                            - 
                            ${new Date(period.end).toLocaleDateString()} ${new Date(period.end).toLocaleTimeString()}
                        </div>
                    </div>
                </div>

                <!-- Main Financial Cards -->
                ${generateFinancialCards(data)}

                <!-- Foreign Accounts Summary -->
                ${generateForeignAccountsHTML(data.foreign_accounts)}

                <!-- Topups Summary -->
                <div class="col-12 layout-spacing">
                    <div class="table-custom">
                        <div class="widget widget-table-three">
                            <div class="widget-heading">
                                <h5 class="p-4 mb-0"><i class="fa fa-mobile-alt text-primary"></i> Topups & Services Summary</h5>
                            </div>
                            <div class="widget-content p-0">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Service Type</th>
                                                <th>Transactions</th>
                                                <th>Amount</th>
                                                <th>Charges</th>
                                                <th>Percentage</th>
                                                <th>Avg. Transaction</th>
                                                <th>Avg. Charge</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${generateTopupRow('Airtime', data.topups?.airtime, data.topups?.total_amount)}
                                            ${generateTopupRow('Data', data.topups?.data, data.topups?.total_amount)}
                                            ${generateTopupRow('TV Subscription', data.topups?.tv, data.topups?.total_amount)}
                                            ${generateTopupRow('Electricity', data.topups?.electricity, data.topups?.total_amount)}
                                            <tr class="table-primary fw-bold">
                                                <td>TOTAL</td>
                                                <td>${data.topups?.total_count || 0}</td>
                                                <td>₦ ${formatNumber(data.topups?.total_amount || 0)}</td>
                                                <td>₦ ${formatNumber(data.topups?.total_charges || 0)}</td>
                                                <td>100%</td>
                                                <td>₦ ${formatNumber((data.topups?.total_amount || 0) / (data.topups?.total_count || 1))}</td>
                                                <td>₦ ${formatNumber((data.topups?.total_charges || 0) / (data.topups?.total_count || 1))}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="col-12 layout-spacing">
                    <div class="chart-container">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0"><i class="fa fa-chart-line text-primary"></i> Cash Flow Analysis</h5>
                            <div class="chart-legend">
                                <span class="legend-item"><span class="legend-color" style="background:#00E396"></span> Inflow</span>
                                <span class="legend-item ms-3"><span class="legend-color" style="background:#FF4560"></span> Outflow</span>
                            </div>
                        </div>
                        <div id="inflowOutflowChart" style="height: 400px;"></div>
                    </div>
                </div>

                <!-- Foreign Account Flow Charts -->
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="chart-container">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0"><i class="fa fa-dollar-sign text-success"></i> USD Account Flow</h5>
                            <div class="chart-legend">
                                <span class="legend-item"><span class="legend-color" style="background:#00E396"></span> Inflow</span>
                                <span class="legend-item ms-3"><span class="legend-color" style="background:#FF4560"></span> Outflow</span>
                            </div>
                        </div>
                        <div id="usdFlowChart" style="height: 350px;"></div>
                    </div>
                </div>

                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="chart-container">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0"><i class="fa fa-euro-sign text-warning"></i> EUR Account Flow</h5>
                            <div class="chart-legend">
                                <span class="legend-item"><span class="legend-color" style="background:#00E396"></span> Inflow</span>
                                <span class="legend-item ms-3"><span class="legend-color" style="background:#FF4560"></span> Outflow</span>
                            </div>
                        </div>
                        <div id="eurFlowChart" style="height: 350px;"></div>
                    </div>
                </div>

                <!-- Network Charts -->
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="chart-container">
                        <h5 class="mb-4"><i class="fa fa-signal text-success"></i> Airtime Topup by Network</h5>
                        <div id="airtimeChart" style="height: 350px;"></div>
                    </div>
                </div>

                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="chart-container">
                        <h5 class="mb-4"><i class="fa fa-wifi text-info"></i> Data Topup by Network</h5>
                        <div id="dataChart" style="height: 350px;"></div>
                    </div>
                </div>

                <!-- Currency Conversion Section -->
                ${generateCurrencyConversionHTML(data.currency_conversion)}

                <!-- Additional Cards -->
                ${generateAdditionalCards(data)}
            </div>
        `;
    }

    function generateFinancialCards(data) {
        return `
            <!-- NGN Transactions -->
            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card success">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-success">₦ ${formatNumber(data.ngn_deposits?.total_amount || 0)}</h6>
                                <p class="stat-label">NGN Deposits</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${data.ngn_deposits?.total_count || 0} TXNs</span>
                                    <span class="badge bg-light text-dark">₦ ${formatNumber(data.ngn_deposits?.total_charges || 0)} Fees</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card danger">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-danger">₦ ${formatNumber(data.ngn_withdrawals?.total_amount || 0)}</h6>
                                <p class="stat-label">NGN Withdrawals</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${data.ngn_withdrawals?.total_count || 0} TXNs</span>
                                    <span class="badge bg-light text-dark">₦ ${formatNumber(data.ngn_withdrawals?.total_charges || 0)} Fees</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Virtual Cards -->
            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card info">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-info">₦ ${formatNumber(data.virtual_card_ngn_withdrawals?.total_amount || 0)}</h6>
                                <p class="stat-label">Virtual Card NGN</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${data.virtual_card_ngn_withdrawals?.total_count || 0} TXNs</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card warning">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-warning">$ ${formatNumber(data.virtual_card_usd_withdrawals?.total_amount || 0)}</h6>
                                <p class="stat-label">Virtual Card USD</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${data.virtual_card_usd_withdrawals?.total_count || 0} TXNs</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function generateForeignAccountsHTML(foreignData) {
        if (!foreignData || (!foreignData.usd.deposits?.amount && !foreignData.eur.deposits?.amount)) {
            return `
                <div class="col-12 layout-spacing">
                    <div class="table-custom">
                        <div class="widget widget-table-three">
                            <div class="widget-heading">
                                <h5 class="p-4 mb-0"><i class="fa fa-globe text-info"></i> Foreign Accounts Summary</h5>
                            </div>
                            <div class="widget-content p-0">
                                <div class="text-center py-4">
                                    <p class="text-muted">No foreign account activity for the selected period.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        return `
            <div class="col-12 layout-spacing">
                <div class="table-custom">
                    <div class="widget widget-table-three">
                        <div class="widget-heading">
                            <h5 class="p-4 mb-0"><i class="fa fa-globe text-info"></i> Foreign Accounts Summary</h5>
                        </div>
                        <div class="widget-content p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Currency</th>
                                            <th>Type</th>
                                            <th>Transactions</th>
                                            <th>Amount</th>
                                            <th>Charges</th>
                                            <th>Net Flow</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${generateForeignAccountRow('USD', 'Deposits', foreignData.usd.deposits)}
                                        ${generateForeignAccountRow('USD', 'Withdrawals', foreignData.usd.withdrawals)}
                                        <tr class="table-info">
                                            <td><strong>USD</strong></td>
                                            <td><strong>Net Flow</strong></td>
                                            <td></td>
                                            <td><strong>$${formatNumber(foreignData.usd.net_flow)}</strong></td>
                                            <td></td>
                                            <td>
                                                <span class="badge ${foreignData.usd.net_flow >= 0 ? 'bg-success' : 'bg-danger'}">
                                                    ${foreignData.usd.net_flow >= 0 ? 'Positive' : 'Negative'}
                                                </span>
                                            </td>
                                        </tr>
                                        ${generateForeignAccountRow('EUR', 'Deposits', foreignData.eur.deposits)}
                                        ${generateForeignAccountRow('EUR', 'Withdrawals', foreignData.eur.withdrawals)}
                                        <tr class="table-warning">
                                            <td><strong>EUR</strong></td>
                                            <td><strong>Net Flow</strong></td>
                                            <td></td>
                                            <td><strong>€${formatNumber(foreignData.eur.net_flow)}</strong></td>
                                            <td></td>
                                            <td>
                                                <span class="badge ${foreignData.eur.net_flow >= 0 ? 'bg-success' : 'bg-danger'}">
                                                    ${foreignData.eur.net_flow >= 0 ? 'Positive' : 'Negative'}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Foreign Account Cards -->
            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card primary">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-primary">$${formatNumber(foreignData.usd.deposits?.amount || 0)}</h6>
                                <p class="stat-label">USD Deposits</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${foreignData.usd.deposits?.count || 0} TXNs</span>
                                    <span class="badge bg-light text-dark">$${formatNumber(foreignData.usd.deposits?.charges || 0)} Fees</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card danger">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-danger">$${formatNumber(foreignData.usd.withdrawals?.amount || 0)}</h6>
                                <p class="stat-label">USD Withdrawals</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${foreignData.usd.withdrawals?.count || 0} TXNs</span>
                                    <span class="badge bg-light text-dark">$${formatNumber(foreignData.usd.withdrawals?.charges || 0)} Fees</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card warning">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-warning">€${formatNumber(foreignData.eur.deposits?.amount || 0)}</h6>
                                <p class="stat-label">EUR Deposits</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${foreignData.eur.deposits?.count || 0} TXNs</span>
                                    <span class="badge bg-light text-dark">€${formatNumber(foreignData.eur.deposits?.charges || 0)} Fees</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card dark">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-dark">€${formatNumber(foreignData.eur.withdrawals?.amount || 0)}</h6>
                                <p class="stat-label">EUR Withdrawals</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${foreignData.eur.withdrawals?.count || 0} TXNs</span>
                                    <span class="badge bg-light text-dark">€${formatNumber(foreignData.eur.withdrawals?.charges || 0)} Fees</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function generateForeignAccountRow(currency, type, data) {
        if (!data || !data.amount) {
            return `
                <tr>
                    <td>${currency}</td>
                    <td>${type}</td>
                    <td>0</td>
                    <td>${currency === 'USD' ? '$' : '€'}0.00</td>
                    <td>${currency === 'USD' ? '$' : '€'}0.00</td>
                    <td></td>
                </tr>
            `;
        }

        const symbol = currency === 'USD' ? '$' : '€';
        
        return `
            <tr>
                <td>${currency}</td>
                <td>${type}</td>
                <td>${data.count || 0}</td>
                <td>${symbol}${formatNumber(data.amount)}</td>
                <td>${symbol}${formatNumber(data.charges || 0)}</td>
                <td></td>
            </tr>
        `;
    }

    function generateTopupRow(serviceName, serviceData, totalAmount) {
        if (!serviceData || !serviceData.amount) {
            return `
                <tr>
                    <td>${serviceName}</td>
                    <td>0</td>
                    <td>₦ 0.00</td>
                    <td>₦ 0.00</td>
                    <td>0%</td>
                    <td>₦ 0.00</td>
                    <td>₦ 0.00</td>
                </tr>
            `;
        }
    
        const percentage = totalAmount ? ((serviceData.amount / totalAmount) * 100).toFixed(1) : '0';
        const avgTransaction = serviceData.count ? (serviceData.amount / serviceData.count) : 0;
        const avgCharge = serviceData.count ? ((serviceData.charges || 0) / serviceData.count) : 0;
        
        return `
            <tr>
                <td>${serviceName}</td>
                <td>${serviceData.count || 0}</td>
                <td>₦ ${formatNumber(serviceData.amount)}</td>
                <td>₦ ${formatNumber(serviceData.charges || 0)}</td>
                <td>${percentage}%</td>
                <td>₦ ${formatNumber(avgTransaction)}</td>
                <td>₦ ${formatNumber(avgCharge)}</td>
            </tr>
        `;
    }

    function generateCurrencyConversionHTML(conversionData) {
        if (!conversionData || conversionData.total_conversions === 0) {
            return `
                <div class="col-12 layout-spacing">
                    <div class="table-custom">
                        <div class="widget widget-table-three">
                            <div class="widget-heading">
                                <h5 class="p-4 mb-0"><i class="fa fa-exchange-alt text-warning"></i> Currency Conversion Summary</h5>
                            </div>
                            <div class="widget-content p-0">
                                <div class="text-center py-4">
                                    <p class="text-muted">No currency conversion data available for the selected period.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        return `
            <div class="col-12 layout-spacing">
                <div class="table-custom">
                    <div class="widget widget-table-three">
                        <div class="widget-heading">
                            <h5 class="p-4 mb-0"><i class="fa fa-exchange-alt text-warning"></i> Currency Conversion Summary</h5>
                        </div>
                        <div class="widget-content p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Metric</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Total Conversions</td>
                                            <td><strong>${conversionData.total_conversions}</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Total Conversion Volume</td>
                                            <td><strong>${formatNumber(conversionData.total_volume)}</strong> (across all currencies)</td>
                                        </tr>
                                        <tr>
                                            <td>Total Charges</td>
                                            <td><strong>${formatNumber(conversionData.total_charges)}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Currency Conversion Matrix -->
            <div class="col-12 layout-spacing">
                <div class="chart-container">
                    <h5 class="mb-4"><i class="fa fa-project-diagram text-info"></i> Currency Conversion Flow</h5>
                    <div id="conversionFlowChart" style="height: 500px;"></div>
                </div>
            </div>

            <!-- Conversion Matrix Table -->
            <div class="col-12 layout-spacing">
                <div class="table-custom">
                    <div class="widget widget-table-three">
                        <div class="widget-heading">
                            <h5 class="p-4 mb-0"><i class="fa fa-table text-primary"></i> Currency Conversion Matrix</h5>
                        </div>
                        <div class="widget-content p-0">
                            <div class="table-responsive">
                                ${generateConversionMatrixTable(conversionData.conversion_matrix)}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Breakdown by Source Currency -->
            ${generateSourceCurrencyBreakdown(conversionData.breakdown_by_from_currency)}

            <!-- Recent Conversion Pairs -->
            <div class="col-12 layout-spacing">
                <div class="table-custom">
                    <div class="widget widget-table-three">
                        <div class="widget-heading">
                            <h5 class="p-4 mb-0"><i class="fa fa-list text-primary"></i> Recent Conversion Pairs</h5>
                        </div>
                        <div class="widget-content p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>From → To</th>
                                            <th>Amount</th>
                                            <th>Rate</th>
                                            <th>Charge</th>
                                            <th>Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${generateConversionPairsRows(conversionData.conversion_pairs)}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function generateConversionMatrixTable(conversionMatrix) {
        if (!conversionMatrix || Object.keys(conversionMatrix).length === 0) {
            return '<div class="text-center py-4"><p class="text-muted">No conversion matrix data available</p></div>';
        }

        // Get all unique currencies
        const allCurrencies = new Set();
        const fromCurrencies = Object.keys(conversionMatrix);
        
        fromCurrencies.forEach(from => {
            allCurrencies.add(from);
            Object.keys(conversionMatrix[from]).forEach(to => {
                allCurrencies.add(to);
            });
        });

        const currencies = Array.from(allCurrencies).sort();
        
        let tableHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>From → To</th>
                        ${currencies.map(currency => `<th class="text-center">${currency}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
        `;

        currencies.forEach(fromCurrency => {
            tableHTML += `<tr>`;
            tableHTML += `<td><strong>${fromCurrency}</strong></td>`;
            
            currencies.forEach(toCurrency => {
                if (fromCurrency === toCurrency) {
                    tableHTML += `<td class="text-center bg-light">-</td>`;
                } else if (conversionMatrix[fromCurrency] && conversionMatrix[fromCurrency][toCurrency]) {
                    const data = conversionMatrix[fromCurrency][toCurrency];
                    const volume = formatNumber(data.volume);
                    const count = data.count;
                    const avgRate = data.average_rate ? data.average_rate.toFixed(4) : 'N/A';
                    
                    tableHTML += `
                        <td class="text-center bg-success-light">
                            <div class="fw-bold">${volume} ${fromCurrency}</div>
                            <small class="text-muted">${count} conversions</small>
                            <div class="small">Rate: ${avgRate}</div>
                        </td>
                    `;
                } else {
                    tableHTML += `<td class="text-center bg-light">-</td>`;
                }
            });
            
            tableHTML += `</tr>`;
        });

        tableHTML += `</tbody></table>`;
        return tableHTML;
    }

    function generateSourceCurrencyBreakdown(breakdownData) {
        if (!breakdownData || Object.keys(breakdownData).length === 0) {
            return '';
        }

        let html = '';
        
        // Generate cards for each source currency
        for (const [currency, data] of Object.entries(breakdownData)) {
            const currencyClass = getCurrencyColorClass(currency);
            const currencySymbol = getCurrencySymbol(currency);
            
            html += `
                <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="table-custom">
                        <div class="widget widget-table-three">
                            <div class="widget-heading">
                                <h5 class="p-4 mb-0 ${currencyClass}">
                                    <i class="fa fa-arrow-up ${currencyClass}"></i> ${currency} to Other Currencies
                                </h5>
                            </div>
                            <div class="widget-content p-0">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>To Currency</th>
                                                <th>Volume</th>
                                                <th>Conversions</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${generateSourceCurrencyRows(currency, data)}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        return html;
    }

    function generateSourceCurrencyRows(currency, data) {
        if (!data.to_currencies || Object.keys(data.to_currencies).length === 0) {
            return '<tr><td colspan="4" class="text-center">No conversion data</td></tr>';
        }

        let rows = '';
        const totalVolume = data.total_volume;
        
        for (const [toCurrency, conversionData] of Object.entries(data.to_currencies)) {
            const percentage = totalVolume > 0 ? ((conversionData.volume / totalVolume) * 100).toFixed(1) : '0';
            const toCurrencyClass = getCurrencyColorClass(toCurrency);
            
            rows += `
                <tr>
                    <td>
                        <span class="${toCurrencyClass} fw-bold">${toCurrency}</span>
                    </td>
                    <td>
                        <strong>${formatNumber(conversionData.volume)} ${currency}</strong>
                    </td>
                    <td>${conversionData.count}</td>
                    <td>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar ${getCurrencyColorClass(currency)}" 
                                 style="width: ${percentage}%"></div>
                        </div>
                        <small>${percentage}%</small>
                    </td>
                </tr>
            `;
        }

        // Add total row
        rows += `
            <tr class="table-primary fw-bold">
                <td>TOTAL</td>
                <td>${formatNumber(totalVolume)} ${currency}</td>
                <td>${data.count}</td>
                <td>100%</td>
            </tr>
        `;
        
        return rows;
    }

    function generateConversionPairsRows(conversionPairs) {
        if (!conversionPairs || conversionPairs.length === 0) {
            return '<tr><td colspan="6" class="text-center">No conversion pairs available</td></tr>';
        }

        // Show only recent 10 conversions
        const recentPairs = conversionPairs.slice(0, 10);
        
        let rows = '';
        recentPairs.forEach(pair => {
            const date = new Date(pair.date).toLocaleDateString();
            const fromTo = `
                <span class="${getCurrencyColorClass(pair.from_currency)}">${pair.from_currency}</span> 
                → 
                <span class="${getCurrencyColorClass(pair.to_currency)}">${pair.to_currency}</span>
            `;
            const amount = `
                ${formatNumber(pair.from_amount)} ${pair.from_currency} 
                → 
                ${formatNumber(pair.to_amount)} ${pair.to_currency}
            `;
            const rate = pair.exchange_rate > 0 ? `1 ${pair.to_currency} = ${formatNumber(pair.exchange_rate)} ${pair.from_currency}` : 'N/A';
            
            rows += `
                <tr>
                    <td>${date}</td>
                    <td>${fromTo}</td>
                    <td>${amount}</td>
                    <td>${rate}</td>
                    <td>${formatNumber(pair.charge)} ${pair.from_currency}</td>
                    <td><small class="text-muted">${pair.reference}</small></td>
                </tr>
            `;
        });
        
        if (conversionPairs.length > 10) {
            rows += `
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        Showing 10 of ${conversionPairs.length} conversions
                    </td>
                </tr>
            `;
        }
        
        return rows;
    }

    function getCurrencyColorClass(currency) {
        const colors = {
            'NGN': 'text-success',
            'USD': 'text-primary', 
            'EUR': 'text-warning',
            'GBP': 'text-danger'
        };
        return colors[currency] || 'text-secondary';
    }

    function getCurrencySymbol(currency) {
        const symbols = {
            'NGN': '₦',
            'USD': '$',
            'EUR': '€',
            'GBP': '£'
        };
        return symbols[currency] || currency;
    }

    function generateAdditionalCards(data) {
        let html = '';
        
        // Currency Conversion
        if (data.currency_conversion && data.currency_conversion.total_volume > 0) {
            html += `
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12 layout-spacing">
                    <div class="widget widget-card-four stat-card primary">
                        <div class="widget-content">
                            <div class="w-content">
                                <div class="w-info text-center p-4">
                                    <h6 class="stat-value text-primary">${formatNumber(data.currency_conversion.total_volume || 0)}</h6>
                                    <p class="stat-label">Currency Conversion</p>
                                    <div class="stat-meta">
                                        <span class="badge bg-light text-dark">${data.currency_conversion.total_conversions || 0} TXNs</span>
                                        <span class="badge bg-light text-dark">${formatNumber(data.currency_conversion.total_charges || 0)} Fees</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Physical Card Withdrawals
        if (data.physical_card_withdrawals && data.physical_card_withdrawals.total_amount > 0) {
            html += `
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12 layout-spacing">
                    <div class="widget widget-card-four stat-card dark">
                        <div class="widget-content">
                            <div class="w-content">
                                <div class="w-info text-center p-4">
                                    <h6 class="stat-value text-dark">₦ ${formatNumber(data.physical_card_withdrawals.total_amount || 0)}</h6>
                                    <p class="stat-label">Physical Card</p>
                                    <div class="stat-meta">
                                        <span class="badge bg-light text-dark">${data.physical_card_withdrawals.total_count || 0} TXNs</span>
                                        <span class="badge bg-light text-dark">₦ ${formatNumber(data.physical_card_withdrawals.total_charges || 0)} Fees</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Recharge Card Pins
        if (data.recharge_card_pins && data.recharge_card_pins.total_amount > 0) {
            html += `
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12 layout-spacing">
                    <div class="widget widget-card-four stat-card warning">
                        <div class="widget-content">
                            <div class="w-content">
                                <div class="w-info text-center p-4">
                                    <h6 class="stat-value text-warning">₦ ${formatNumber(data.recharge_card_pins.total_amount || 0)}</h6>
                                    <p class="stat-label">Recharge Cards</p>
                                    <div class="stat-meta">
                                        <span class="badge bg-light text-dark">${data.recharge_card_pins.total_count || 0} TXNs</span>
                                        <span class="badge bg-light text-dark">₦ ${formatNumber(data.recharge_card_pins.total_charges || 0)} Fees</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        return html;
    }

    function formatNumber(num) {
        if (num === null || num === undefined) return '0.00';
        const number = parseFloat(num);
        if (isNaN(number)) return '0.00';
        return number.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function showLoader() {
        $('#SalesResultOutput').html(`
            <div class="loader-container">
                <div class="loader-spinner"></div>
                <p class="mt-3 text-muted">Loading sales analytics data...</p>
            </div>
        `);
    }

    function hideLoader() {
        // Loader will be replaced by content
    }

    function showError(title, message) {
        $('#SalesResultOutput').html(`
            <div class="alert alert-danger">
                <h5>${title}</h5>
                <p>${message}</p>
            </div>
        `);
    }

    function initializeCharts(chartData, conversionData) {
        console.log('Initializing charts with data:', chartData);
        
        if (!chartData) {
            console.log('No chart data available');
            return;
        }

        // Destroy existing chart instances
        if (inflowOutflowChart) inflowOutflowChart.destroy();
        if (airtimeChart) airtimeChart.destroy();
        if (dataChart) dataChart.destroy();
        if (conversionChart) conversionChart.destroy();
        if (conversionFlowChart) conversionFlowChart.destroy();
        if (usdFlowChart) usdFlowChart.destroy();
        if (eurFlowChart) eurFlowChart.destroy();

        // Inflow vs Outflow Chart
        if (chartData.labels && chartData.inflow && chartData.outflow) {
            initializeInflowOutflowChart(chartData);
        } else {
            document.getElementById('inflowOutflowChart').innerHTML = '<div class="empty-state"><p>No chart data available</p></div>';
        }

        // USD Flow Chart
        if (chartData.usd_flow && chartData.labels) {
            initializeUSDChart(chartData);
        } else {
            document.getElementById('usdFlowChart').innerHTML = '<div class="empty-state"><p>No USD flow data available</p></div>';
        }

        // EUR Flow Chart
        if (chartData.eur_flow && chartData.labels) {
            initializeEURChart(chartData);
        } else {
            document.getElementById('eurFlowChart').innerHTML = '<div class="empty-state"><p>No EUR flow data available</p></div>';
        }

        // Airtime by Network Chart
        if (chartData.airtime_networks && Object.keys(chartData.airtime_networks).length > 0) {
            initializeAirtimeChart(chartData);
        } else {
            document.getElementById('airtimeChart').innerHTML = '<div class="empty-state"><p>No airtime data available</p></div>';
        }

        // Data by Network Chart
        if (chartData.data_networks && Object.keys(chartData.data_networks).length > 0) {
            initializeDataChart(chartData);
        } else {
            document.getElementById('dataChart').innerHTML = '<div class="empty-state"><p>No data topup available</p></div>';
        }

        // Currency Conversion Flow Chart
        if (conversionData && conversionData.conversion_matrix && Object.keys(conversionData.conversion_matrix).length > 0) {
            initializeConversionFlowChart(conversionData.conversion_matrix);
        } else {
            const flowChartElement = document.getElementById('conversionFlowChart');
            if (flowChartElement) {
                flowChartElement.innerHTML = '<div class="empty-state"><p>No conversion flow data available</p></div>';
            }
        }
    }

    function initializeInflowOutflowChart(chartData) {
        const chartElement = document.getElementById("inflowOutflowChart");
        if (!chartElement) return;

        const options = {
            series: [
                {
                    name: 'Inflow',
                    data: chartData.inflow
                },
                {
                    name: 'Outflow',
                    data: chartData.outflow
                }
            ],
            chart: {
                background: 'transparent',
                foreColor: '#8593AB',
                type: 'line',
                height: 400,
                zoom: {
                    enabled: true
                },
                toolbar: {
                    show: true
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            title: {
                text: 'Cash Flow Analysis',
                align: 'left',
                style: {
                    fontSize: '16px',
                    fontWeight: 'bold'
                }
            },
            grid: {
                borderColor: '#e7e7e7',
                row: {
                    colors: ['#f3f3f3', 'transparent'],
                    opacity: 0.5
                },
            },
            markers: {
                size: 5,
            },
            xaxis: {
                categories: chartData.labels,
            },
            yaxis: {
                title: {
                    text: 'Amount (₦)'
                },
                labels: {
                    formatter: function(value) {
                        return '₦' + value.toLocaleString();
                    }
                }
            },
            colors: ['#00E396', '#FF4560'],
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                floating: true,
                offsetY: -25,
                offsetX: -5
            }
        };

        inflowOutflowChart = new ApexCharts(chartElement, options);
        inflowOutflowChart.render();
    }

    function initializeUSDChart(chartData) {
        const chartElement = document.getElementById("usdFlowChart");
        if (!chartElement) return;

        if (!chartData.usd_flow || !chartData.labels) {
            chartElement.innerHTML = '<div class="empty-state"><p>No USD flow data available</p></div>';
            return;
        }

        const options = {
            series: [
                {
                    name: 'USD Inflow',
                    data: chartData.usd_flow.inflow
                },
                {
                    name: 'USD Outflow',
                    data: chartData.usd_flow.outflow
                }
            ],
            chart: {
                background: 'transparent',
                foreColor: '#8593AB',
                type: 'line',
                height: 350,
                zoom: {
                    enabled: true
                },
                toolbar: {
                    show: true
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            title: {
                text: 'USD Account Flow',
                align: 'left',
                style: {
                    fontSize: '16px',
                    fontWeight: 'bold'
                }
            },
            grid: {
                borderColor: '#e7e7e7',
                row: {
                    colors: ['#f3f3f3', 'transparent'],
                    opacity: 0.5
                },
            },
            markers: {
                size: 5,
            },
            xaxis: {
                categories: chartData.labels,
            },
            yaxis: {
                title: {
                    text: 'Amount ($)'
                },
                labels: {
                    formatter: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            },
            colors: ['#00E396', '#FF4560'],
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                floating: true,
                offsetY: -25,
                offsetX: -5
            }
        };

        usdFlowChart = new ApexCharts(chartElement, options);
        usdFlowChart.render();
    }

    function initializeEURChart(chartData) {
        const chartElement = document.getElementById("eurFlowChart");
        if (!chartElement) return;

        if (!chartData.eur_flow || !chartData.labels) {
            chartElement.innerHTML = '<div class="empty-state"><p>No EUR flow data available</p></div>';
            return;
        }

        const options = {
            series: [
                {
                    name: 'EUR Inflow',
                    data: chartData.eur_flow.inflow
                },
                {
                    name: 'EUR Outflow',
                    data: chartData.eur_flow.outflow
                }
            ],
            chart: {
                background: 'transparent',
                foreColor: '#8593AB',
                type: 'line',
                height: 350,
                zoom: {
                    enabled: true
                },
                toolbar: {
                    show: true
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            title: {
                text: 'EUR Account Flow',
                align: 'left',
                style: {
                    fontSize: '16px',
                    fontWeight: 'bold'
                }
            },
            grid: {
                borderColor: '#e7e7e7',
                row: {
                    colors: ['#f3f3f3', 'transparent'],
                    opacity: 0.5
                },
            },
            markers: {
                size: 5,
            },
            xaxis: {
                categories: chartData.labels,
            },
            yaxis: {
                title: {
                    text: 'Amount (€)'
                },
                labels: {
                    formatter: function(value) {
                        return '€' + value.toLocaleString();
                    }
                }
            },
            colors: ['#FEB019', '#FF4560'],
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                floating: true,
                offsetY: -25,
                offsetX: -5
            }
        };

        eurFlowChart = new ApexCharts(chartElement, options);
        eurFlowChart.render();
    }

    function initializeAirtimeChart(chartData) {
        const chartElement = document.getElementById("airtimeChart");
        if (!chartElement) return;

        const series = [];
        const colors = ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'];

        for (const [network, data] of Object.entries(chartData.airtime_networks)) {
            series.push({
                name: network || 'Unknown',
                data: data
            });
        }

        const options = {
            series: series,
            chart: {
                background: 'transparent',
                foreColor: '#8593AB',
                type: 'bar',
                height: 350,
                stacked: true,
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: chartData.labels,
            },
            yaxis: {
                title: {
                    text: 'Amount (₦)'
                },
                labels: {
                    formatter: function(value) {
                        return '₦' + value.toLocaleString();
                    }
                }
            },
            fill: {
                opacity: 1
            },
            colors: colors,
            legend: {
                position: 'top'
            }
        };

        airtimeChart = new ApexCharts(chartElement, options);
        airtimeChart.render();
    }

    function initializeDataChart(chartData) {
        const chartElement = document.getElementById("dataChart");
        if (!chartElement) return;

        const series = [];
        const colors = ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'];

        for (const [network, data] of Object.entries(chartData.data_networks)) {
            series.push({
                name: network || 'Unknown',
                data: data
            });
        }

        const options = {
            series: series,
            chart: {
                background: 'transparent',
                foreColor: '#8593AB',
                type: 'bar',
                height: 350,
                stacked: true,
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: chartData.labels,
            },
            yaxis: {
                title: {
                    text: 'Amount (₦)'
                },
                labels: {
                    formatter: function(value) {
                        return '₦' + value.toLocaleString();
                    }
                }
            },
            fill: {
                opacity: 1
            },
            colors: colors,
            legend: {
                position: 'top'
            }
        };

        dataChart = new ApexCharts(chartElement, options);
        dataChart.render();
    }

    function initializeConversionFlowChart(conversionMatrix) {
        const chartElement = document.getElementById("conversionFlowChart");
        if (!chartElement) return;

        if (!conversionMatrix || Object.keys(conversionMatrix).length === 0) {
            chartElement.innerHTML = '<div class="empty-state"><p>No conversion flow data available</p></div>';
            return;
        }

        // Prepare data for the chart
        const seriesData = [];
        const categories = [];

        Object.keys(conversionMatrix).forEach(from => {
            Object.keys(conversionMatrix[from]).forEach(to => {
                const data = conversionMatrix[from][to];
                categories.push(`${from} → ${to}`);
                seriesData.push({
                    x: `${from} → ${to}`,
                    y: data.volume,
                    count: data.count,
                    rate: data.average_rate
                });
            });
        });

        // Sort by volume descending
        seriesData.sort((a, b) => b.y - a.y);

        const options = {
            series: [{
                name: 'Conversion Volume',
                data: seriesData.map(item => item.y)
            }],
            chart: {
                background: 'transparent',
                foreColor: '#8593AB',
                type: 'bar',
                height: 500,
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: true,
                    distributed: true,
                    dataLabels: {
                        position: 'bottom'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                textAnchor: 'start',
                style: {
                    colors: ['#fff']
                },
                formatter: function(val, opt) {
                    const item = seriesData[opt.dataPointIndex];
                    return `${formatNumber(val)} ${item.x.split(' → ')[0]}`;
                },
                offsetX: 0,
                dropShadow: {
                    enabled: true
                }
            },
            colors: ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0', '#546E7A', '#26a69a', '#D10CE8'],
            xaxis: {
                categories: seriesData.map(item => item.x),
                labels: {
                    formatter: function(val) {
                        return formatNumber(val);
                    }
                },
                title: {
                    text: 'Conversion Volume'
                }
            },
            yaxis: {
                labels: {
                    maxWidth: 200
                }
            },
            title: {
                text: 'Currency Conversion Volumes by Pair',
                align: 'center'
            },
            tooltip: {
                y: {
                    formatter: function(val, { series, seriesIndex, dataPointIndex, w }) {
                        const item = seriesData[dataPointIndex];
                        return `
                            <div>
                                <strong>${formatNumber(val)} ${item.x.split(' → ')[0]}</strong><br/>
                                Conversions: ${item.count}<br/>
                                Avg Rate: ${item.rate ? item.rate.toFixed(4) : 'N/A'}
                            </div>
                        `;
                    }
                }
            }
        };

        conversionFlowChart = new ApexCharts(chartElement, options);
        conversionFlowChart.render();
    }

    function exportToExcel() {
        alert('Export functionality would be implemented here');
        // This would typically make an AJAX call to generate and download an Excel file
    }

    $(document).ready(function() {
        console.log('Document ready, initializing sales calculator');
        submitSalesCalculator();
    });
</script>
@endsection
@endcan