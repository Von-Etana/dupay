<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkActionRequest;
use App\Models\Payment;
use App\Models\Fintech\WithdrawalLog;
use App\Models\Profile;
use App\Models\Telecom\DataType;
use App\Models\Telecom\Network;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Telecom\ApiConnection;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use FirebaseUserUpdate;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view_transaction',     ['only' => ['index', 'show', 'ajax']]);
        $this->middleware('can:create_transaction',   ['only' => ['create', 'store']]);
        $this->middleware('can:edit_transaction',     ['only' => ['edit', 'update']]);
        $this->middleware('can:delete_transaction',   ['only' => ['destroy', 'bulk_delete']]);
    }


    public function index($username = null, $wallet_id = null)
    {
        $data["page_title"] = "Transactions";
        switch ($wallet_id) {
            case '1':
                $subTitle = "Main Wallet Transactions";
                break;
            case '2':
                $subTitle = "Bonus Transactions";
                break;
            case '3':
                $subTitle = "Commission Transactions";
                break;
            default:
                $subTitle = "All Transactions";
                break;
        }
        $data["page_sub_title"] = $subTitle;
        $data["breadcrumb"] = [
            [
                "title" => "Home",
                "url" => route('admin.index')
            ],
            [
                "title" => $data["page_sub_title"],
                "url" => null
            ],
        ];

          //add activity
          $ac['activity'] = "Visited Transaction -- $subTitle page";
          $ac['actions'] =  "visit";
          $ac['module'] = "Transaction";
          $ac['user_type'] = "2";
          addSiteActitvity(auth()->guard('admin')->user(), $ac);

        $data["ajaxUrl"] = route('admin.showTransactionJs', [$username, $wallet_id]);
        $model_name = app("App\Models\\Transaction");
        $data["column"] = $model_name->column();
        return view('admin.transaction.index', $data);
    }
    
    public function transactionView($reference)
    {
            $data["page_title"] = "Transaction Details";
    
            $transaction = Transaction::where('reference', $reference)->firstOrFail();
    
            switch ($transaction->wallet_id) {
                case '1':
                    $subTitle = "Main Wallet Transaction";
                    break;
                case '2':
                    $subTitle = "Bonus Wallet Transaction";
                    break;
                case '3':
                    $subTitle = "Commission Wallet Transaction";
                    break;
                default:
                    $subTitle = "Transaction Details";
                    break;
            }
    
            $data["page_sub_title"] = $subTitle;
    
            // Breadcrumb
            $data["breadcrumb"] = [
                ["title" => "Home", "url" => route('admin.index')],
                ["title" => "Transactions", "url" => route('admin.index')],
                ["title" => $subTitle, "url" => null],
            ];
    
            // Pass transaction to the view
            $data["transaction"] = $transaction;
    
            return view('admin.transaction.view', $data);
    }

    public function downloadPDF($reference)
    {
        $transaction = Transaction::where('reference', $reference)
            ->orWhere('id', $reference)
            ->first();

        if (!$transaction) {
            return redirect()->back()->with('error', 'Transaction not found');
        }

        $data['title'] = "Transaction Receipt";
        $data['transaction'] = $transaction;
        $data['data'] = $transaction;

        $pdf = Pdf::loadView('pdf.transaction_receipt', $data);
        return $pdf->download("Receipt_{$reference}.pdf");
    }


    public function ajax(Request $request, $username = null, $wallet_id = null)
    {
        $model = Transaction::query()->orderBy("id", "desc");
    
        if ($username != null) {
            $user = User::whereHas('profile', function (Builder $query) use ($username) {
                $query->where('username', $username)->orWhere("ref_id", $username);
            })->first();
    
            if (!$user) {
                return response()->json(['data' => []]);
            }
            $model->where('user_id',  $user->id);
        }
        
        if ($wallet_id != null) {
            $model->where('wallet_id',  $wallet_id);
        }
        
        // Get model instance to call methods
        $transactionModel = new Transaction();
        
        // Apply search filter
        if ($request->has('search') && $request->search != '') {
            $model = $transactionModel->searchMethod($model, $request->search);
        }
        
        // Apply type filter
        if ($request->has('filter') && $request->filter != '' && $request->filter != 'all') {
            $model = $transactionModel->filterMethod($model, $request->filter);
        }
        
        // Apply currency filter
        if ($request->has('currency') && $request->currency != '' && $request->currency != 'all') {
            $model = $transactionModel->currencyFilter($model, $request->currency);
        }
        
        // Apply date range filter
        if ($request->has('start_date') || $request->has('end_date')) {
            $startDate = $request->start_date ?? '';
            $endDate = $request->end_date ?? '';
            $model = $transactionModel->dateRangeFilter($model, $startDate, $endDate);
        }
        
        $pageType = "transaction";
        $dataTble = DataTables::eloquent($model);
        $model_name = app("App\Models\\Transaction");
        $columns = $model_name->column();
        
        foreach ($columns as $key => $column) {
            $dataTble->editColumn($column->column_name, function ($item) use ($column, $pageType) {
                return view('admin.form._columnView', compact('item', 'column', 'pageType'));
            });
        }
        
        $dataTble->addColumn('action', function ($item) {
            return view('admin.transaction._action', compact('item'));
        });
        
        $dataTble->addColumn('bulk_checkbox', function ($item) {
            return view('admin.layouts._bulk_checkbox', compact('item'));
        });
    
        return $dataTble->toJson();
    }

    public function calculateSales(Request $request)
    {
        // Validation
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Safe float conversion helper
        $toSafeFloat = function ($value): float {
            if ($value === null || $value === '') return 0.0;
            if (is_float($value) || is_int($value)) return (float) $value;
            
            $stringValue = trim((string) $value);
            if ($stringValue === '') return 0.0;
            
            $cleaned = preg_replace('/[^\d\.\-]/', '', $stringValue);
            if ($cleaned === '' || $cleaned === '-' || $cleaned === '.') return 0.0;
            
            if (substr_count($cleaned, '.') > 1) {
                $parts = explode('.', $cleaned);
                $cleaned = $parts[0] . '.' . implode('', array_slice($parts, 1));
            }
            
            return is_numeric($cleaned) ? (float) $cleaned : 0.0;
        };

        // Determine date range
        $startDate = $request->start_date 
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::today()->startOfDay();
            
        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::today()->endOfDay();

        // Validate date range
        if ($startDate->gt($endDate)) {
            return response()->json([
                'success' => false,
                'html' => "<div class='alert alert-danger mt-4 text-center'>Invalid date range</div>"
            ]);
        }

        // Check if there are transactions
        $hasTransactions = DB::table('transactions')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'success')
            ->exists();

        if (!$hasTransactions) {
            $startFormatted = $startDate->format('M d, Y');
            $endFormatted = $endDate->format('M d, Y');
            return response()->json([
                'success' => false,
                'html' => "<div class='alert alert-danger mt-4 text-center'>No records found from $startFormatted to $endFormatted</div>"
            ]);
        }

        // ========= CHART DATA =========
        $dateFormat = "%d %b";
        $isHourly = false;
        
        // If it's today only, show hourly data
        if ($startDate->isToday() && $endDate->isToday()) {
            $dateFormat = "%d %b %H:00";
            $isHourly = true;
        }

        $metricsQuery = DB::table('transactions')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '$dateFormat') AS date"),
                DB::raw("COALESCE(SUM(
                    CASE 
                        WHEN service_type = 'Wallet Funding' AND 
                             amount IS NOT NULL AND 
                             amount != '' AND
                             amount REGEXP '^-?[0-9]*[.]?[0-9]+$'
                        THEN CAST(amount AS DECIMAL(15,2))
                        ELSE 0 
                    END
                ), 0) AS deposit_total"),
                DB::raw("COALESCE(SUM(
                    CASE 
                        WHEN service_type = 'Fund Withdrawal' AND 
                             amount IS NOT NULL AND 
                             amount != '' AND
                             amount REGEXP '^-?[0-9]*[.]?[0-9]+$'
                        THEN CAST(amount AS DECIMAL(15,2))
                        ELSE 0 
                    END
                ), 0) AS withdrawal_total"),
                DB::raw("COALESCE(SUM(
                    CASE 
                        WHEN type = '0' AND 
                             charge IS NOT NULL AND 
                             charge != '' AND
                             charge REGEXP '^-?[0-9]*[.]?[0-9]+$'
                        THEN CAST(charge AS DECIMAL(15,4))
                        ELSE 0 
                    END
                ), 0) AS profit_total")
            )
            ->where('status', 'success')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'asc');

        $metricsRecords = $metricsQuery->get();

        // Transform data
        $metricsRecords->transform(function ($item) use ($toSafeFloat) {
            $item->deposit_total = $toSafeFloat($item->deposit_total);
            $item->withdrawal_total = $toSafeFloat($item->withdrawal_total);
            $item->profit_total = $toSafeFloat($item->profit_total);
            return $item;
        });

        $metricsByDate = $metricsRecords->keyBy('date');

        // Build complete date range for chart
        $categories = [];
        $depositData = [];
        $withdrawalData = [];
        $profitData = [];
        $currentDate = $startDate->copy();

        if ($isHourly) {
            // Hourly view for single day
            for ($hour = 0; $hour < 24; $hour++) {
                $labelTime = $currentDate->copy()->setTime($hour, 0, 0);
                $dateString = $labelTime->format('d M H:00');
                $categories[] = $dateString;

                $metrics = $metricsByDate[$dateString] ?? null;
                $depositData[] = $metrics ? $metrics->deposit_total : 0.0;
                $withdrawalData[] = $metrics ? $metrics->withdrawal_total : 0.0;
                $profitData[] = $metrics ? $metrics->profit_total : 0.0;
            }
        } else {
            // Daily view for multiple days
            while ($currentDate <= $endDate) {
                $dateString = $currentDate->format('d M');
                $categories[] = $dateString;

                $metrics = $metricsByDate[$dateString] ?? null;
                $depositData[] = $metrics ? $metrics->deposit_total : 0.0;
                $withdrawalData[] = $metrics ? $metrics->withdrawal_total : 0.0;
                $profitData[] = $metrics ? $metrics->profit_total : 0.0;

                $currentDate->addDay();
            }
        }

        // ========= TOTALS CALCULATION =========
        $totalsQuery = DB::table('transactions')
            ->select(
                DB::raw("COALESCE(SUM(
                    CASE 
                        WHEN service_type = 'Wallet Funding' AND 
                             amount IS NOT NULL AND 
                             amount != '' AND
                             amount REGEXP '^-?[0-9]*[.]?[0-9]+$'
                        THEN CAST(amount AS DECIMAL(15,2))
                        ELSE 0 
                    END
                ), 0) AS total_deposit"),
                DB::raw("COALESCE(SUM(
                    CASE 
                        WHEN service_type = 'Fund Withdrawal' AND 
                             amount IS NOT NULL AND 
                             amount != '' AND
                             amount REGEXP '^-?[0-9]*[.]?[0-9]+$'
                        THEN CAST(amount AS DECIMAL(15,2))
                        ELSE 0 
                    END
                ), 0) AS total_withdrawal"),
                DB::raw("COALESCE(SUM(
                    CASE 
                        WHEN type = '0' AND 
                             charge IS NOT NULL AND 
                             charge != '' AND
                             charge REGEXP '^-?[0-9]*[.]?[0-9]+$'
                        THEN CAST(charge AS DECIMAL(15,4))
                        ELSE 0 
                    END
                ), 0) AS total_profit")
            )
            ->where('status', 'success')
            ->whereBetween('created_at', [$startDate, $endDate]);

        $totals = $totalsQuery->first();
        $totalDeposit = $toSafeFloat($totals->total_deposit);
        $totalWithdrawal = $toSafeFloat($totals->total_withdrawal);
        $totalProfit = $toSafeFloat($totals->total_profit);
        $totalIncome = $totalDeposit;
        $totalExpenses = $totalWithdrawal;

        // Calculate progress bar percentages
        $maxValue = max($totalIncome, $totalProfit, $totalExpenses, 1);
        $incomePercent = ($totalIncome / $maxValue) * 100;
        $profitPercent = ($totalProfit / $maxValue) * 100;
        $expensesPercent = ($totalExpenses / $maxValue) * 100;

        // ========= SERVICE SUMMARY =========
        $serviceSummary = DB::table('transactions')
            ->select(
                'service_type',
                DB::raw("COALESCE(SUM(
                    CASE 
                        WHEN amount IS NOT NULL AND 
                             amount != '' AND
                             amount REGEXP '^-?[0-9]*[.]?[0-9]+$'
                        THEN CAST(amount AS DECIMAL(15,2))
                        ELSE 0 
                    END
                ), 0) AS total_sales"),
                DB::raw("COALESCE(SUM(
                    CASE 
                        WHEN type = '0' AND 
                             charge IS NOT NULL AND 
                             charge != '' AND
                             charge REGEXP '^-?[0-9]*[.]?[0-9]+$'
                        THEN CAST(charge AS DECIMAL(15,4))
                        ELSE 0 
                    END
                ), 0) AS total_profit")
            )
            ->where('status', 'success')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('service_type')
            ->get()
            ->transform(function ($item) use ($toSafeFloat) {
                $item->total_sales = $toSafeFloat($item->total_sales);
                $item->total_profit = $toSafeFloat($item->total_profit);
                return $item;
            })
            ->keyBy('service_type');

        // Define all service types
        $serviceTypes = [
            "Data Topup", "Airtime Topup", "Airtime Cash", "Cable Subscription",
            "Bill Payment", "Result Checker", "Bulk SMS", "Fund Transfer",
            "Fund Withdrawal", "Wallet Funding"
        ];

        // ========= NETWORK SUMMARY =========
        $networkSummary = DB::table('transactions')
            ->join('networks', 'transactions.network_id', '=', 'networks.id')
            ->select(
                'networks.name as network_name',
                'transactions.service_type',
                DB::raw("COALESCE(SUM(
                    CASE 
                        WHEN amount IS NOT NULL AND 
                             amount != '' AND
                             amount REGEXP '^-?[0-9]*[.]?[0-9]+$'
                        THEN CAST(amount AS DECIMAL(15,2))
                        ELSE 0 
                    END
                ), 0) AS total_sales"),
                DB::raw("COALESCE(SUM(
                    CASE 
                        WHEN type = '0' AND 
                             charge IS NOT NULL AND 
                             charge != '' AND
                             charge REGEXP '^-?[0-9]*[.]?[0-9]+$'
                        THEN CAST(charge AS DECIMAL(15,4))
                        ELSE 0 
                    END
                ), 0) AS total_profit")
            )
            ->where('transactions.status', 'success')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->whereIn('transactions.service_type', ['Data Topup', 'Airtime Topup', 'Airtime Cash'])
            ->groupBy('networks.name', 'transactions.service_type')
            ->get()
            ->transform(function ($item) use ($toSafeFloat) {
                $item->total_sales = $toSafeFloat($item->total_sales);
                $item->total_profit = $toSafeFloat($item->total_profit);
                return $item;
            });

        // Get networks
        $networks = DB::table('networks')->get();

        // Prepare data for view
        $data = [
            'series' => [
                ['name' => 'Deposit', 'data' => $depositData],
                ['name' => 'Withdrawal', 'data' => $withdrawalData],
                ['name' => 'Profit', 'data' => $profitData],
            ],
            'xaxis' => $categories,
            'totalDeposit' => number_format($totalDeposit, 2),
            'totalWithdrawal' => number_format($totalWithdrawal, 2),
            'totalProfit' => number_format($totalProfit, 2),
            'totalIncome' => number_format($totalIncome, 2),
            'totalExpenses' => number_format($totalExpenses, 2),
            'incomePercent' => $incomePercent,
            'profitPercent' => $profitPercent,
            'expensesPercent' => $expensesPercent,
            'serviceTypes' => $serviceTypes,
            'serviceSummary' => $serviceSummary,
            'networkSummary' => $networkSummary,
            'networks' => $networks,
            'dateRange' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ]
        ];

        $html = view('admin.sales-report', $data)->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'chartData' => [
                'series' => $data['series'],
                'xaxis' => $data['xaxis'],
                'subtitle' => '₦' . $data['totalDeposit']
            ]
        ]);
    }


//     public function calculateSales(Request $request)
// {
//     // Validate request
//     $request->validate([
//         'range' => 'in:daily,last7days,last30days,custom',
//         'start_date' => 'nullable|date',
//         'end_date' => 'nullable|date|after_or_equal:start_date',
//         'username' => 'nullable|string'
//     ]);

//     // Initialize variables as floats
//     $totalDeposit = 0.0;
//     $totalTransaction = 0.0;
//     $totalProfit = 0.0;
//     $categories = [];
//     $depositData = [];
//     $expenseData = [];

//     // Date Range Calculation
//     $range = $request->range;
//     $startDate = null;
//     $endDate = null;
//     $dateFormat = "%d %b"; // Default daily format

//     if ($range == 'daily' && !$request->start_date) {
//         $startDate = Carbon::today();
//         $endDate = Carbon::today()->endOfDay();
//         $dateFormat = "%d %b %H:00"; // Hourly format
//     } elseif ($range == 'last7days') {
//         $startDate = Carbon::today()->subDays(6)->startOfDay();
//         $endDate = Carbon::now()->endOfDay();
//     } elseif ($range == 'last30days') {
//         $startDate = Carbon::today()->subDays(29)->startOfDay();
//         $endDate = Carbon::now()->endOfDay();
//     } else {
//         $startDate = Carbon::parse($request->start_date)->startOfDay();
//         $endDate = $request->end_date 
//             ? Carbon::parse($request->end_date)->endOfDay()
//             : Carbon::parse($request->start_date)->endOfDay();
//     }

//     // Validate date range
//     if ($startDate->gt($endDate)) {
//         return "<div class='alert alert-danger mt-4 text-center'>Invalid date range</div>";
//     }

//     // User Filter
//     $userId = null;
//     if ($request->username) {
//         $profile = DB::table('profiles')
//             ->where("username", $request->username)
//             ->orWhere("ref_id", $request->username)
//             ->first();
//         $userId = $profile ? $profile->user_id : null;
//     }

//     // Enhanced helper function to safely convert any value to float
//     function toSafeFloat($value): float {
//         if (is_null($value) || $value === '') {
//             return 0.0;
//         }
        
//         // If it's already a float or int, return it
//         if (is_float($value) || is_int($value)) {
//             return (float) $value;
//         }
        
//         // Convert to string and clean
//         $stringValue = (string) $value;
        
//         // Remove all non-numeric characters except decimal point and minus sign
//         $cleaned = preg_replace('/[^\d.-]/', '', $stringValue);
        
//         // Handle cases where cleaning results in empty string or just a minus sign
//         if ($cleaned === '' || $cleaned === '-' || $cleaned === '.') {
//             return 0.0;
//         }
        
//         // Ensure proper decimal format (only one decimal point)
//         if (substr_count($cleaned, '.') > 1) {
//             $parts = explode('.', $cleaned);
//             $cleaned = $parts[0] . '.' . implode('', array_slice($parts, 1));
//         }
        
//         $result = (float) $cleaned;
        
//         // Final validation
//         return is_finite($result) ? $result : 0.0;
//     }

//     // Check if we have data using DB query
//     $hasTransactions = DB::table('transactions')
//         ->when($userId, function ($query) use ($userId) {
//             return $query->where('user_id', $userId);
//         })
//         ->whereBetween('created_at', [$startDate, $endDate])
//         ->where('status', 'success')
//         ->exists();

//     if (!$hasTransactions) {
//         $startFormatted = $startDate->format('Y-m-d');
//         $endFormatted = $endDate->format('Y-m-d');
//         return "<div class='alert alert-danger mt-4 text-center'>No records found from $startFormatted to $endFormatted</div>";
//     }

//     // Get deposit records - Using more robust casting
//     $depositQuery = DB::table('transactions')
//         ->select(
//             DB::raw("DATE_FORMAT(created_at, '$dateFormat') AS date"),
//             DB::raw('COALESCE(SUM(NULLIF(TRIM(amount), "")), 0) AS total') // Handle empty strings
//         )
//         ->where('service_type', 'Wallet Funding')
//         ->whereBetween('created_at', [$startDate, $endDate])
//         ->where('status', 'success')
//         ->groupBy('date')
//         ->orderBy('date', 'asc');

//     if ($userId) {
//         $depositQuery->where('user_id', $userId);
//     }
    
//     $depositRecords = $depositQuery->get()->map(function ($item) use (&$toSafeFloat) {
//         return (object) [
//             'date' => $item->date,
//             'total' => toSafeFloat($item->total)
//         ];
//     })->keyBy('date');

//     // Get profit records
//     $profitQuery = DB::table('transactions')
//         ->select(
//             DB::raw("DATE_FORMAT(created_at, '$dateFormat') AS date"),
//             DB::raw('COALESCE(SUM(NULLIF(TRIM(charge), "")), 0) AS total')
//         )
//         ->where('type', "0")
//         ->where('status', 'success')
//         ->whereBetween('created_at', [$startDate, $endDate])
//         ->groupBy('date')
//         ->orderBy('date', 'asc');

//     if ($userId) {
//         $profitQuery->where('user_id', $userId);
//     }
    
//     $profitRecords = $profitQuery->get()->map(function ($item) use (&$toSafeFloat) {
//         return (object) [
//             'date' => $item->date,
//             'total' => toSafeFloat($item->total)
//         ];
//     })->keyBy('date');

//     // Get expense records
//     $expenseQuery = DB::table('transactions')
//         ->select(
//             DB::raw("DATE_FORMAT(created_at, '$dateFormat') AS date"),
//             DB::raw('COALESCE(SUM(NULLIF(TRIM(amount), "")), 0) AS total')
//         )
//         ->where('service_type', 'Fund Withdrawal')
//         ->where('status', 'success')
//         ->whereBetween('created_at', [$startDate, $endDate])
//         ->groupBy('date')
//         ->orderBy('date', 'asc');

//     if ($userId) {
//         $expenseQuery->where('user_id', $userId);
//     }
    
//     $expenseRecords = $expenseQuery->get()->map(function ($item) use (&$toSafeFloat) {
//         return (object) [
//             'date' => $item->date,
//             'total' => toSafeFloat($item->total)
//         ];
//     })->keyBy('date');

//     // Build chart data
//     $currentDate = $startDate->copy();
//     $isHourly = strpos($dateFormat, '%H') !== false;

//     while ($currentDate <= $endDate) {
//         if ($isHourly) {
//             // Hourly data for single day
//             for ($hour = 0; $hour < 24; $hour++) {
//                 $dateString = $currentDate->copy()->addHours($hour)->format('d M H:00');
//                 $categories[] = $dateString;

//                 // Use safe float conversion
//                 $depositVal = isset($depositRecords[$dateString]) 
//                     ? toSafeFloat($depositRecords[$dateString]->total)
//                     : 0.0;
                
//                 $expenseVal = isset($expenseRecords[$dateString]) 
//                     ? toSafeFloat($expenseRecords[$dateString]->total)
//                     : 0.0;
                
//                 $profitVal = isset($profitRecords[$dateString]) 
//                     ? toSafeFloat($profitRecords[$dateString]->total)
//                     : 0.0;

//                 $depositData[] = $depositVal;
//                 $expenseData[] = $expenseVal;
                
//                 // Accumulate with float precision
//                 $totalDeposit += $depositVal;
//                 $totalTransaction += $expenseVal;
//                 $totalProfit += $profitVal;
//             }
//             $currentDate->addDay();
//         } else {
//             // Daily data
//             $dateString = $currentDate->format('d M');
//             $categories[] = $dateString;

//             // Use safe float conversion
//             $depositVal = isset($depositRecords[$dateString]) 
//                 ? toSafeFloat($depositRecords[$dateString]->total)
//                 : 0.0;
            
//             $expenseVal = isset($expenseRecords[$dateString]) 
//                 ? toSafeFloat($expenseRecords[$dateString]->total)
//                 : 0.0;
            
//             $profitVal = isset($profitRecords[$dateString]) 
//                 ? toSafeFloat($profitRecords[$dateString]->total)
//                 : 0.0;

//             $depositData[] = $depositVal;
//             $expenseData[] = $expenseVal;
            
//             $totalDeposit += $depositVal;
//             $totalTransaction += $expenseVal;
//             $totalProfit += $profitVal;

//             $currentDate->addDay();
//         }
//     }

//     // Get transactions with pagination using DB query
//     $transactionQuery = DB::table('transactions')
//         ->select('*')
//         ->whereBetween('created_at', [$startDate, $endDate])
//         ->where('status', 'success')
//         ->orderBy('created_at', 'desc');

//     if ($userId) {
//         $transactionQuery->where('user_id', $userId);
//     }

//     // Convert amount fields to float for the paginated results
//     $transactions = $transactionQuery->paginate(50);
    
//     // Transform the transaction data to ensure numeric values
//     $transactions->getCollection()->transform(function ($transaction) use (&$toSafeFloat) {
//         // Convert all numeric fields to float using safe conversion
//         $transaction->amount = toSafeFloat($transaction->amount);
//         $transaction->charge = toSafeFloat($transaction->charge);
//         $transaction->initial_balance = toSafeFloat($transaction->initial_balance);
//         $transaction->final_balance = toSafeFloat($transaction->final_balance);
        
//         return $transaction;
//     });

//     // Debug: Check if there are still non-numeric values
//     $nonNumericFound = false;
//     $transactions->getCollection()->each(function ($transaction) use (&$nonNumericFound) {
//         $fields = ['amount', 'charge', 'initial_balance', 'final_balance'];
//         foreach ($fields as $field) {
//             if (!is_numeric($transaction->$field)) {
//                 $nonNumericFound = true;
//                 \Log::warning("Non-numeric value found in $field: " . $transaction->$field);
//             }
//         }
//     });

//     if ($nonNumericFound) {
//         \Log::warning("Non-numeric values detected in transaction data after conversion");
//     }

//     // Activity Log
//     $userDescription = $userId ? "user $userId" : "all users";
//     $ac = [
//         'activity' => "Calculate sales transaction of " . $userDescription,
//         'actions' => "visit",
//         'module' => "Transaction",
//         'user_type' => "2",
//     ];
    
//     if (auth()->guard('admin')->check()) {
//         addSiteActitvity(auth()->guard('admin')->user(), $ac);
//     }

//     // Prepare data for view - ensure all values are properly formatted
//     $data = [
//         'series' => [
//             ['name' => 'Deposit', 'data' => $depositData],
//             ['name' => 'Transaction', 'data' => $expenseData],
//         ],
//         'xaxis' => $categories,
//         'totalDeposit' => number_format($totalDeposit, 2),
//         'totalTransaction' => number_format($totalTransaction, 2),
//         'totalProfit' => number_format($totalProfit, 2),
//         'item' => $transactions,
//         'service_type' => [
//             "Data Topup", "Airtime Topup", "Airtime Cash", "Cable Subscription",
//             "Bill Payment", "Result Checker", "Bulk SMS", "Fund Transfer",
//             "Fund Withdrawal", "Wallet Funding"
//         ],
//         'networkService' => ["Data Topup", "Airtime Topup", "Airtime Cash"],
//         'network' => DB::table('networks')->get(),
//         'dataType' => DB::table('data_types')->get(),
//     ];

//     return view("admin.dashboard.sales_item", $data)->render();
// }




    public function view($reference)
    {
        $data["data"] = Transaction::where("reference", $reference)->firstOrFail();
        $data["isPayment"] = false;
        $data["page_title"] = "Transaction";
        $data["page_sub_title"] = "Transaction Detail";
        if ($data["data"]->payment != null) {
            $data["isPayment"] = true;
        }
         //add activity
         $ac['activity'] = "Visited Transaction -- Details  page";
         $ac['actions'] =  "visit";
         $ac['module'] = "Transaction";
         $ac['user_type'] = "2";
         addSiteActitvity(auth()->guard('admin')->user(), $ac);
        return view("admin.transaction.details", $data);
    }


    public function editTransactionStatus(Request $request, $type, $reference)
    {
        if ($type == "withdrawal") {
            $item = WithdrawalLog::where("referrence", $reference)->first();
        } else if ($type == "all") {
            $item = Transaction::where("reference", $reference)->first();
        }


        if (!$item) {
            session()->flash('message', "Transaction does not exists.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->back();
        }

        $data = request()->except('_method', '_token');
        $item->fill($data)->save();
        $user = User::find($item->user_id);
        $description = $item->description;


        if ($type != "all") {
            return redirect()->back();
        }

        if($request->status != "pending" && $request->status != "success" && ($item->service_type != "Airtime Cash" && $item->service_type != "Commission Redeem" && $item->service_type != "Wallet Funding")){
            $user = User::find($item->user_id);
             $amount = $item->amount;

           $walletActionRes =  userBalanceAction($user,$amount, $item->wallet_id);
           $init_balance = $walletActionRes["init_balance"];
           $final_balance = $walletActionRes["final_balance"];

            $description = $item->description." Refund";
            $trx = strtoupper(Str::random(16));
            $ul['service'] = $item->service;
            $ul['service_type'] = $item->service_type;
            $ul['description'] = $description;
            $ul['amount'] = str_replace("₦","",$amount);
            $ul['phone_number'] = $item->phone_number;
            $ul['type'] = "1";
            $ul['initial_balance'] = $init_balance;
            $ul['final_balance'] = $final_balance;
            $ul['reference'] = $trx;
            $ul['status'] = "refund";
            $transaction =  $user->transaction()->create($ul);
  }else if($request->status == "success" && ($item->service_type == "Airtime Cash" || $item->service_type == "Commission Redeem")){

    $amount = $item->amount;

   $walletActionRes =  userBalanceAction($user, $amount, ($item->wallet_id ?? "1"));
   $init_balance = $walletActionRes["init_balance"];
   $final_balance = $walletActionRes["final_balance"];
    $description = $item->description." Payment";
    $trx = strtoupper(Str::random(16));
    $ul['service'] = $item->service;
    $ul['service_type'] = $item->service_type;
    $ul['description'] = $description;
    $ul['amount'] = str_replace("₦","",$amount);
    $ul['phone_number'] = $item->phone_number;
    $ul['type'] = "1";
    $ul['initial_balance'] = $init_balance;
    $ul['final_balance'] = $final_balance;
    $ul['reference'] = $trx;
    $ul['status'] = "success";
    $transaction =  $user->transaction()->create($ul);
}
else if($request->status == "success" && $item->service_type == "Wallet Funding"){
$trans = $user->payment()->where("reference", $item->reference)->where("status", "pending")->first();
if($trans){
    $charge = getSiteSettings("manual_deposit_charge");
    $amount = $trans->amount - $charge;
    $walletActionRes =  userBalanceAction($user, $amount, ($item->wallet_id ?? "1"));
    $init_balance = $walletActionRes["init_balance"];
    $final_balance = $walletActionRes["final_balance"];
    $trans->status = "success";
    $trans->save();
    $item->status = "success";
    $item->save();
}
}

try {
    (new FirebaseUserUpdate)->addUserNotification($user,
    [
        "message" => $description,
        "type" => "transaction",
        "read" => 0,
        "data" => [
            "service" => 'Transaction Update',
            "id" => $item->id,
            "reference_id" => $trx,
            "status" => $item->success,
            'date' => Carbon::now()
        ]
    ]
    );
    if (getSiteSettings("email_notify") == "Yes") {
        $text = $description . " Reference ID Is : #$trx";
        $this->sendMail($user->email, $user->profile->first_name, 'Transaction Update', $text);
    }
    if (getSiteSettings("phone_notify") == "Yes") {
        $text = $description . " Reference ID Is : #$trx";
        $this->sendSms($user->phone_number, $text);
    }
} catch (\Throwable $th) {
    //throw $th;

}

 //add activity
 $ac['activity'] = "Update Transaction status";
 $ac['actions'] =  "update";
 $ac['module'] = "Transaction";
 $ac['user_type'] = "2";
 $ac["extra"] = json_encode(["id" =>  $item->id, "request" => $request]);
 addSiteActitvity(auth()->guard('admin')->user(), $ac);

        (new FirebaseUserUpdate)->updateUser($user);
        session()->flash('message', "Transaction Updated Successfully.");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
        return redirect()->back();

    }
    
    
     public function editBulkTransactionStatus(Request $request)
    {
    
        if (!request("status")) {
            return response(['success' => false, 'message' => "Status is required "]);
        }
        $ids = explode(",", $request->data_id);
        foreach ($ids as $id) {
        $item = Transaction::where("id", $id)->first();

        if (!$item) {
            return response(['success' => false, 'message' => "Transaction does not exists ".request("data_id")]);
        }

        $data = request()->except('_method', '_token','data_id');
        $item->fill($data)->save();
        $user = User::find($item->user_id);
        $description = $item->description;


        if($request->status != "pending" && $request->status != "success" && ($item->service_type != "Airtime Cash" && $item->service_type != "Commission Redeem" && $item->service_type != "Wallet Funding")){
            $user = User::find($item->user_id);
             $amount = $item->amount;

           $walletActionRes =  userBalanceAction($user,$amount, $item->wallet_id);
           $init_balance = $walletActionRes["init_balance"];
           $final_balance = $walletActionRes["final_balance"];

            $description = $item->description." Refund";
            $trx = strtoupper(Str::random(16));
            $ul['service'] = $item->service;
            $ul['service_type'] = $item->service_type;
            $ul['description'] = $description;
            $ul['amount'] = str_replace("₦","",$amount);
            $ul['phone_number'] = $item->phone_number;
            $ul['type'] = "1";
            $ul['initial_balance'] = $init_balance;
            $ul['final_balance'] = $final_balance;
            $ul['reference'] = $trx;
            $ul['status'] = "refund";
            $transaction =  $user->transaction()->create($ul);
  }else if($request->status == "success" && ($item->service_type == "Airtime Cash" || $item->service_type == "Commission Redeem")){

    $amount = $item->amount;

   $walletActionRes =  userBalanceAction($user, $amount, ($item->wallet_id ?? "1"));
   $init_balance = $walletActionRes["init_balance"];
   $final_balance = $walletActionRes["final_balance"];
    $description = $item->description." Payment";
    $trx = strtoupper(Str::random(16));
    $ul['service'] = $item->service;
    $ul['service_type'] = $item->service_type;
    $ul['description'] = $description;
    $ul['amount'] = str_replace("₦","",$amount);
    $ul['phone_number'] = $item->phone_number;
    $ul['type'] = "1";
    $ul['initial_balance'] = $init_balance;
    $ul['final_balance'] = $final_balance;
    $ul['reference'] = $trx;
    $ul['status'] = "success";
    $transaction =  $user->transaction()->create($ul);
}
else if($request->status == "success" && $item->service_type == "Wallet Funding"){
$trans = $user->payment()->where("reference", $item->reference)->where("status", "pending")->first();
if($trans){
    $charge = getSiteSettings("manual_deposit_charge");
    $amount = $trans->amount - $charge;
    $walletActionRes =  userBalanceAction($user, $amount, ($item->wallet_id ?? "1"));
    $init_balance = $walletActionRes["init_balance"];
    $final_balance = $walletActionRes["final_balance"];
    $trans->status = "success";
    $trans->save();
    $item->status = "success";
    $item->save();
}
}

try {
    (new FirebaseUserUpdate)->addUserNotification($user,
    [
        "message" => $description,
        "type" => "transaction",
        "read" => 0,
        "data" => [
            "service" => 'Transaction Update',
            "id" => $item->id,
            "reference_id" => $trx,
            "status" => $item->success,
            'date' => Carbon::now()
        ]
    ]
    );
    if (getSiteSettings("email_notify") == "Yes") {
        $text = $description . " Reference ID Is : #$trx";
        $this->sendMail($user->email, $user->profile->first_name, 'Transaction Update', $text, "transaction", $item);
    }
    if (getSiteSettings("phone_notify") == "Yes") {
        $text = $description . " Reference ID Is : #$trx";
        $this->sendSms($user->phone_number, $text);
    }
} catch (\Throwable $th) {
    //throw $th;

}

 //add activity
 $ac['activity'] = "Update Transaction status";
 $ac['actions'] =  "update";
 $ac['module'] = "Transaction";
 $ac['user_type'] = "2";
 $ac["extra"] = json_encode(["id" =>  $item->id, "request" => $request]);
 addSiteActitvity(auth()->guard('admin')->user(), $ac);
 (new FirebaseUserUpdate)->updateUser($user);
}

    

        return response(['success' => true, 'message' => "Transaction Updated Successfully."]);

    }
    
    
    
    public function verifyTransactionFromApi(Request $request)
    {
    
        if (!request("api_connection_id")) {
            return response(['success' => false, 'message' => "Kindly Select Verification API Connection"]);
        }
        $ids = explode(",", $request->data_id);
        foreach ($ids as $id) {
        $item = Transaction::where("id", $id)->first();

        if (!$item) {
            return response(['success' => false, 'message' => "Transaction does not exists ".request("data_id")]);
        }

        $apiConnection = ApiConnection::where("id", $request->api_connection_id)->first();
        if(!$apiConnection){
            return response(['success' => false, 'message' => "Api Connection not found"]);
        }

        $status = $item->status;
        $sucess = false;

        try {
            $finalStatus = "success";
            $json = null;
            $apiSuccessField = $apiConnection->success_field;
            $apiSuccessResponse = $apiConnection->success_response;
            $endpoint = $apiConnection->url;
    
             //header
             $Myheader = getHeaderApiConnection($apiConnection);
             $header = $Myheader["header"];
             $token = $Myheader["token"];
    
             if ($apiConnection->method == "0") {
                //IF GET METHOD
                $endpoint = str_replace("{{phoneNumber}}",$item->phone_number,$endpoint);
                $endpoint = str_replace("{{token}}",$token,$endpoint);
                $endpoint = str_replace("{{referenceNumber}}",$item->reference,$endpoint);
                $endpoint = str_replace("{{current_date}}",date("Ymdhis"),$endpoint);
                $json = getHttp($endpoint, $header);
             }else {
                //APi Connection POST Method
            //payload
            $apiPayLoad = json_decode($apiConnection->payload, true);
                $payload = array();
            if($apiPayLoad == null ||  !is_array($apiPayLoad)){
                return response(['success' => false, 'message' => "Service Connection playload not active contact admin"]);
                }else{
                    //payload here 
                    foreach ($apiPayLoad as $key => $value) {
                        $value = str_replace("{{phoneNumber}}",$item->phone_number,$value);
                        $value = str_replace("{{token}}",$token,$value);
                        $value = str_replace("{{referenceNumber}}",$item->reference,$value);
                        $value = str_replace("{{current_date}}",date("Ymdhis"),$value);
                        $payload[$key] = $value;
            }
            }
                //$json = postHttp($payload, $endpoint, $header);
                if($apiConnection->method == "1"){
                    $json = postHttp($payload, $endpoint, $header);
                }else if($apiConnection->method == "2"){
                    $json = postHttpParameter($endpoint, $payload,  $header);
                }else{
                    $json = httpPostFileContent($payload, $endpoint,  $header);
                }
    
            }
            
            
        //return response(['success' => false, 'message' => "Check Console for Updat.", $json]);
    
            if ($json != null) {
                $reponseVal = $json;
                foreach(explode('->', $apiSuccessField) as $itemS) {
                $reponseVal = $reponseVal->$itemS;
                }
        if ($reponseVal == $apiSuccessResponse) {
            $sucess = true;
            $item->status = "success";
            $item->save();
        } else {
            $sucess = false;
        }
    }
        } catch (\Throwable $th) {
            //throw $th;
        }

        $user = User::find($item->user_id);
        $description = $item->description;


        if(!$sucess && $item->status != "success" && ($item->service_type != "Airtime Cash" && $item->service_type != "Commission Redeem" && $item->service_type != "Wallet Funding")){
            
            if(request("auto_refund") == "Yes"){
                //if auto refund user when unsuccessfuk
            $item->status = "unsuccessful";
            $item->save();
            $user = User::find($item->user_id);
            $amount = $item->amount;

          $walletActionRes =  userBalanceAction($user,$amount, $item->wallet_id);
          $init_balance = $walletActionRes["init_balance"];
          $final_balance = $walletActionRes["final_balance"];

           $descriptionR = $item->description." Refund";
           $trx = strtoupper(Str::random(16));
           $ul['service'] = $item->service;
           $ul['service_type'] = $item->service_type;
           $ul['description'] = $descriptionR;
           $ul['amount'] = str_replace("₦","",$amount);
           $ul['phone_number'] = $item->phone_number;
           $ul['type'] = "1";
           $ul['initial_balance'] = $init_balance;
           $ul['final_balance'] = $final_balance;
           $ul['reference'] = $trx;
           $ul['status'] = "refund";
           $transaction =  $user->transaction()->create($ul);

           try {
            (new FirebaseUserUpdate)->addUserNotification($user,
            [
                "message" => $descriptionR,
                "type" => "transaction",
                "read" => 0,
                "data" => [
                    "service" => 'Transaction Refund',
                    "id" => $transaction->id,
                    "reference_id" => $trx,
                    "status" => $transaction->success,
                    'date' => Carbon::now()
                ]
            ]
            );
            if (getSiteSettings("email_notify") == "Yes") {
                $text = $descriptionR . " Reference ID Is : #$trx";
                $this->sendMail($user->email, $user->profile->first_name, 'Refund Transaction', $text, "transaction", $item);
            }
            if (getSiteSettings("phone_notify") == "Yes") {
                $text = $descriptionR . " Reference ID Is : #$trx";
                $this->sendSms($user->phone_number, $text);
            }
        } catch (\Throwable $th) {
            //throw $th;
        
        }

            }else if(request("auto_update") == "Yes"){
            $item->status = "unsuccessful";
            $item->save();
            }
            
  }
if($status != $item->status){
try {
    (new FirebaseUserUpdate)->addUserNotification($user,
    [
        "message" => $description,
        "type" => "transaction",
        "read" => 0,
        "data" => [
            "service" => 'Transaction Update',
            "id" => $item->id,
            "reference_id" => $trx,
            "status" => $item->success,
            'date' => Carbon::now()
        ]
    ]
    );
    if (getSiteSettings("email_notify") == "Yes") {
        $text = $description . " Reference ID Is : #$trx";
        $this->sendMail($user->email, $user->profile->first_name, 'Transaction Update', $text, "transaction", $item);
    }
    if (getSiteSettings("phone_notify") == "Yes") {
        $text = $description . " Reference ID Is : #$trx";
        $this->sendSms($user->phone_number, $text);
    }
} catch (\Throwable $th) {
    //throw $th;

}

}

 //add activity
 $ac['activity'] = "Update Transaction status from api";
 $ac['actions'] =  "update";
 $ac['module'] = "Transaction";
 $ac['user_type'] = "2";
 $ac["extra"] = json_encode(["id" =>  $item->id, "request" => $request]);
 addSiteActitvity(auth()->guard('admin')->user(), $ac);
 (new FirebaseUserUpdate)->updateUser($user);
}
return response(['success' => true, 'message' => "Transaction Checked & Updated Successfully."]);

 }

    
    

    public function destroy($reference)
    {
        $item = Transaction::where("reference", $reference)->firstOrFail();
        if (!$item) {
            session()->flash('message', "Transaction does not exists.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return back();
        }

        $del =   $item->delete();
        if ($del) {
            try {
                $item->transfer()->delete();
                $item->withdraw()->delete();
                $item->payment()->delete();
            } catch (\Throwable $th) {
                //throw $th;
            }
        }

         //add activity
        $ac['activity'] = "Deleted Transaction";
        $ac['actions'] =  "delete";
        $ac['module'] = "Transaction";
        $ac['user_type'] = "2";
        $ac["extra"] = json_encode(["id" =>  $item->id]);
        addSiteActitvity(auth()->guard('admin')->user(), $ac);

        // AJAX (DataTables row delete) expects JSON; browser form posts get the
        // usual flash + redirect.
        if (request()->expectsJson()) {
            return response()->json([
                'status'  => true,
                'message' => "Transaction Deleted Successfully.",
            ]);
        }

        session()->flash('message', "Transaction Deleted Successfully.");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
        return back();
    }


    public function bulk_delete(BulkActionRequest $request)
    {
        foreach ($request['ids'] as $id) {

            $item = Transaction::find($id);
            $item->delete();
        }

        session()->flash('message', "Bulk Deleted Successfully");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
        return back();
    }
}