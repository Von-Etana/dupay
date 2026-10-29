<?php
// app/Http/Controllers/Admin/SalesController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesController extends Controller
{
    public function calculateSales(Request $request)
    {
        try {
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::today();
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::today()->endOfDay();
            $userId = $request->user_id; // Get user_id from request if present
            
            // If no specific dates provided, handle the range cases
            if (!$request->start_date && !$request->end_date) {
                $range = $request->range ?? 'daily';
                
                switch ($range) {
                    case 'last7days':
                        $startDate = Carbon::now()->subDays(7)->startOfDay();
                        $endDate = Carbon::now()->endOfDay();
                        break;
                    case 'last30days':
                        $startDate = Carbon::now()->subDays(30)->startOfDay();
                        $endDate = Carbon::now()->endOfDay();
                        break;
                    default: // daily
                        $startDate = Carbon::today();
                        $endDate = Carbon::today()->endOfDay();
                        break;
                }
            }

            $salesData = $this->getSalesData($startDate, $endDate, $userId);
            
            return response()->json([
                'success' => true,
                'data' => $salesData,
                'period' => [
                    'start' => $startDate->format('Y-m-d H:i:s'),
                    'end' => $endDate->format('Y-m-d H:i:s')
                ],
                'user_filter' => $userId ? true : false
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating sales: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function getSalesData($startDate, $endDate, $userId = null)
    {
        return [
            // NGN Deposits
            'ngn_deposits' => $this->getNGNDeposits($startDate, $endDate, $userId),
            
            // NGN Withdrawals
            'ngn_withdrawals' => $this->getNGNWithdrawals($startDate, $endDate, $userId),
            
            // Topups Summary
            'topups' => $this->getTopupsSummary($startDate, $endDate, $userId),
            
            // Virtual Card Transactions
            'virtual_card_ngn_withdrawals' => $this->getVirtualCardNGNWithdrawals($startDate, $endDate, $userId),
            'virtual_card_usd_withdrawals' => $this->getVirtualCardUSDWithdrawals($startDate, $endDate, $userId),
            
            // Physical Card Withdrawals
            'physical_card_withdrawals' => $this->getPhysicalCardWithdrawals($startDate, $endDate, $userId),
            
            // Virtual Card Topups
            'virtual_card_topups' => $this->getVirtualCardTopups($startDate, $endDate, $userId),
            
            // Foreign Account Deposits & Withdrawals
            'foreign_accounts' => $this->getForeignAccountsSummary($startDate, $endDate, $userId),
            
            // Currency Conversion
            'currency_conversion' => $this->getCurrencyConversion($startDate, $endDate, $userId),
            
            // Recharge Card Pins
            'recharge_card_pins' => $this->getRechargeCardPins($startDate, $endDate, $userId),
            
            // Charts Data
            'charts_data' => $this->getChartsDataWithForeign($startDate, $endDate, $userId)
        ];
    }
    
    private function getNGNDeposits($startDate, $endDate, $userId = null)
    {
        $query = DB::table('transactions')
            ->where('service_type', 'Wallet Funding')
            ->where('currency', 'NGN')
            ->where('type', 1) // deposit
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->select(
                DB::raw('COUNT(*) as total_count'),
                DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as total_amount'),
                DB::raw('COALESCE(SUM(CAST(charge as DECIMAL(10,2))), 0) as total_charges')
            )
            ->first();
    }

    private function getNGNWithdrawals($startDate, $endDate, $userId = null)
    {
        $query = DB::table('transactions')
            ->where('service_type', 'Fund Withdrawal')
            ->where('currency', 'NGN')
            ->where('type', 0) // withdrawal
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->select(
                DB::raw('COUNT(*) as total_count'),
                DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as total_amount'),
                DB::raw('COALESCE(SUM(CAST(charge as DECIMAL(10,2))), 0) as total_charges')
            )
            ->first();
    }

    private function getTopupsSummary($startDate, $endDate, $userId = null)
    {
        // Airtime Topup
        $airtimeQuery = DB::table('transactions')
            ->where('service_type', 'Airtime Topup')
            ->whereBetween('created_at', [$startDate, $endDate]);
    
        if ($userId) {
            $airtimeQuery->where('user_id', $userId);
        }
    
        $airtime = $airtimeQuery->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as amount'),
                DB::raw('COALESCE(SUM(CAST(charge as DECIMAL(10,2))), 0) as charges')
            )
            ->first();
    
        // Data Topup
        $dataQuery = DB::table('transactions')
            ->where('service_type', 'Data Topup')
            ->whereBetween('created_at', [$startDate, $endDate]);
    
        if ($userId) {
            $dataQuery->where('user_id', $userId);
        }
    
        $data = $dataQuery->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as amount'),
                DB::raw('COALESCE(SUM(CAST(charge as DECIMAL(10,2))), 0) as charges')
            )
            ->first();
    
        // TV/Cable Subscription
        $tvQuery = DB::table('transactions')
            ->where('service_type', 'Cable Subscription')
            ->whereBetween('created_at', [$startDate, $endDate]);
    
        if ($userId) {
            $tvQuery->where('user_id', $userId);
        }
    
        $tv = $tvQuery->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as amount'),
                DB::raw('COALESCE(SUM(CAST(charge as DECIMAL(10,2))), 0) as charges')
            )
            ->first();
    
        // Electricity/Bill Payment
        $electricityQuery = DB::table('transactions')
            ->where('service_type', 'Bill Payment')
            ->whereBetween('created_at', [$startDate, $endDate]);
    
        if ($userId) {
            $electricityQuery->where('user_id', $userId);
        }
    
        $electricity = $electricityQuery->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as amount'),
                DB::raw('COALESCE(SUM(CAST(charge as DECIMAL(10,2))), 0) as charges')
            )
            ->first();
    
        $total_amount = ($airtime->amount ?? 0) + ($data->amount ?? 0) + ($tv->amount ?? 0) + ($electricity->amount ?? 0);
        $total_count = ($airtime->count ?? 0) + ($data->count ?? 0) + ($tv->count ?? 0) + ($electricity->count ?? 0);
        $total_charges = ($airtime->charges ?? 0) + ($data->charges ?? 0) + ($tv->charges ?? 0) + ($electricity->charges ?? 0);
            
        return [
            'airtime' => $airtime,
            'data' => $data,
            'tv' => $tv,
            'electricity' => $electricity,
            'total_amount' => $total_amount,
            'total_count' => $total_count,
            'total_charges' => $total_charges
        ];
    }

    private function getVirtualCardNGNWithdrawals($startDate, $endDate, $userId = null)
    {
        $query = DB::table('transactions')
            ->where('service_type', 'Naira Virtual Card Withdraw')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->select(
                DB::raw('COUNT(*) as total_count'),
                DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as total_amount')
            )
            ->first();
    }

    private function getVirtualCardUSDWithdrawals($startDate, $endDate, $userId = null)
    {
        $query = DB::table('transactions')
            ->where('service_type', 'Dollar Virtual Card Withdraw')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->select(
                DB::raw('COUNT(*) as total_count'),
                DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as total_amount')
            )
            ->first();
    }

    private function getPhysicalCardWithdrawals($startDate, $endDate, $userId = null)
    {
        $query = DB::table('transactions')
            ->where(function($query) {
                $query->where('service_type', 'withdraw')
                      ->orWhere('service_type', 'Naira Physical Card Withdraw');
            })
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->select(
                DB::raw('COUNT(*) as total_count'),
                DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as total_amount'),
                DB::raw('COALESCE(SUM(CAST(charge as DECIMAL(10,2))), 0) as total_charges')
            )
            ->first();
    }

    private function getVirtualCardTopups($startDate, $endDate, $userId = null)
    {
        $query = DB::table('transactions')
            ->where('service_type', 'Dollar Virtual Card Topup')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->select(
                DB::raw('COUNT(*) as total_count'),
                DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as total_amount')
            )
            ->first();
    }

    private function getRechargeCardPins($startDate, $endDate, $userId = null)
    {
        $query = DB::table('transactions')
            ->where('service_type', 'Recharge Card Purchase')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->select(
                DB::raw('COUNT(*) as total_count'),
                DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as total_amount'),
                DB::raw('COALESCE(SUM(CAST(charge as DECIMAL(10,2))), 0) as total_charges')
            )
            ->first();
    }

    private function getForeignAccountsSummary($startDate, $endDate, $userId = null)
    {
        // USD Deposits (Wallet Funding)
        $usdDepositsQuery = DB::table('transactions')
            ->where('service_type', 'Wallet Funding')
            ->where('currency', 'USD')
            ->where('type', 1) // deposit
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($userId) {
            $usdDepositsQuery->where('user_id', $userId);
        }

        $usdDeposits = $usdDepositsQuery->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as amount'),
                DB::raw('COALESCE(SUM(CAST(charge as DECIMAL(10,2))), 0) as charges')
            )
            ->first();

        // USD Withdrawals (Fund Withdrawal)
        $usdWithdrawalsQuery = DB::table('transactions')
            ->where('service_type', 'Fund Withdrawal')
            ->where('currency', 'USD')
            ->where('type', 0) // withdrawal
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($userId) {
            $usdWithdrawalsQuery->where('user_id', $userId);
        }

        $usdWithdrawals = $usdWithdrawalsQuery->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as amount'),
                DB::raw('COALESCE(SUM(CAST(charge as DECIMAL(10,2))), 0) as charges')
            )
            ->first();

        // EUR Deposits (Wallet Funding)
        $eurDepositsQuery = DB::table('transactions')
            ->where('service_type', 'Wallet Funding')
            ->where('currency', 'EUR')
            ->where('type', 1) // deposit
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($userId) {
            $eurDepositsQuery->where('user_id', $userId);
        }

        $eurDeposits = $eurDepositsQuery->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as amount'),
                DB::raw('COALESCE(SUM(CAST(charge as DECIMAL(10,2))), 0) as charges')
            )
            ->first();

        // EUR Withdrawals (Fund Withdrawal)
        $eurWithdrawalsQuery = DB::table('transactions')
            ->where('service_type', 'Fund Withdrawal')
            ->where('currency', 'EUR')
            ->where('type', 0) // withdrawal
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($userId) {
            $eurWithdrawalsQuery->where('user_id', $userId);
        }

        $eurWithdrawals = $eurWithdrawalsQuery->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as amount'),
                DB::raw('COALESCE(SUM(CAST(charge as DECIMAL(10,2))), 0) as charges')
            )
            ->first();

        return [
            'usd' => [
                'deposits' => $usdDeposits,
                'withdrawals' => $usdWithdrawals,
                'net_flow' => ($usdDeposits->amount ?? 0) - ($usdWithdrawals->amount ?? 0)
            ],
            'eur' => [
                'deposits' => $eurDeposits,
                'withdrawals' => $eurWithdrawals,
                'net_flow' => ($eurDeposits->amount ?? 0) - ($eurWithdrawals->amount ?? 0)
            ]
        ];
    }

    private function getCurrencyConversion($startDate, $endDate, $userId = null)
    {
        // Get all currency conversion transactions within the date range
        $conversionsQuery = DB::table('transactions')
            ->where('service_type', 'Currency Conversion')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($userId) {
            $conversionsQuery->where('user_id', $userId);
        }

        $conversions = $conversionsQuery->select(
                'reference',
                'currency',
                'type',
                'amount',
                'charge',
                'description',
                'created_at',
                'user_id'
            )
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by reference to pair debit and credit transactions
        $groupedConversions = $conversions->groupBy('reference');
        
        $conversionSummary = [
            'total_conversions' => 0,
            'total_volume' => 0,
            'total_charges' => 0,
            'conversion_pairs' => [],
            'breakdown_by_from_currency' => [],
            'breakdown_by_to_currency' => [],
            'conversion_matrix' => []
        ];

        foreach ($groupedConversions as $reference => $transactions) {
            if ($transactions->count() == 2) {
                $debit = $transactions->where('type', 0)->first(); // From currency
                $credit = $transactions->where('type', 1)->first(); // To currency
                
                if ($debit && $credit) {
                    $conversionSummary['total_conversions']++;
                    $conversionSummary['total_volume'] += floatval($debit->amount);
                    $conversionSummary['total_charges'] += floatval($debit->charge ?? 0);
                    
                    $fromCurrency = $debit->currency;
                    $toCurrency = $credit->currency;
                    $fromAmount = floatval($debit->amount);
                    $toAmount = floatval($credit->amount);
                    $charge = floatval($debit->charge ?? 0);
                    
                    // Calculate exchange rate
                    $exchangeRate = $toAmount > 0 ? $fromAmount / $toAmount : 0;
                    
                    $conversionPair = [
                        'reference' => $reference,
                        'from_currency' => $fromCurrency,
                        'to_currency' => $toCurrency,
                        'from_amount' => $fromAmount,
                        'to_amount' => $toAmount,
                        'charge' => $charge,
                        'exchange_rate' => $exchangeRate,
                        'description' => $debit->description,
                        'date' => $debit->created_at
                    ];
                    
                    $conversionSummary['conversion_pairs'][] = $conversionPair;
                    
                    // Build breakdown by from currency
                    if (!isset($conversionSummary['breakdown_by_from_currency'][$fromCurrency])) {
                        $conversionSummary['breakdown_by_from_currency'][$fromCurrency] = [
                            'total_volume' => 0,
                            'total_charges' => 0,
                            'count' => 0,
                            'to_currencies' => []
                        ];
                    }
                    $conversionSummary['breakdown_by_from_currency'][$fromCurrency]['total_volume'] += $fromAmount;
                    $conversionSummary['breakdown_by_from_currency'][$fromCurrency]['total_charges'] += $charge;
                    $conversionSummary['breakdown_by_from_currency'][$fromCurrency]['count']++;
                    
                    // Track to currencies for this from currency
                    if (!isset($conversionSummary['breakdown_by_from_currency'][$fromCurrency]['to_currencies'][$toCurrency])) {
                        $conversionSummary['breakdown_by_from_currency'][$fromCurrency]['to_currencies'][$toCurrency] = [
                            'volume' => 0,
                            'count' => 0
                        ];
                    }
                    $conversionSummary['breakdown_by_from_currency'][$fromCurrency]['to_currencies'][$toCurrency]['volume'] += $fromAmount;
                    $conversionSummary['breakdown_by_from_currency'][$fromCurrency]['to_currencies'][$toCurrency]['count']++;
                    
                    // Build breakdown by to currency
                    if (!isset($conversionSummary['breakdown_by_to_currency'][$toCurrency])) {
                        $conversionSummary['breakdown_by_to_currency'][$toCurrency] = [
                            'total_volume' => 0,
                            'count' => 0,
                            'from_currencies' => []
                        ];
                    }
                    $conversionSummary['breakdown_by_to_currency'][$toCurrency]['total_volume'] += $toAmount;
                    $conversionSummary['breakdown_by_to_currency'][$toCurrency]['count']++;
                    
                    // Track from currencies for this to currency
                    if (!isset($conversionSummary['breakdown_by_to_currency'][$toCurrency]['from_currencies'][$fromCurrency])) {
                        $conversionSummary['breakdown_by_to_currency'][$toCurrency]['from_currencies'][$fromCurrency] = [
                            'volume' => 0,
                            'count' => 0
                        ];
                    }
                    $conversionSummary['breakdown_by_to_currency'][$toCurrency]['from_currencies'][$fromCurrency]['volume'] += $toAmount;
                    $conversionSummary['breakdown_by_to_currency'][$toCurrency]['from_currencies'][$fromCurrency]['count']++;
                    
                    // Build conversion matrix (from -> to)
                    if (!isset($conversionSummary['conversion_matrix'][$fromCurrency])) {
                        $conversionSummary['conversion_matrix'][$fromCurrency] = [];
                    }
                    if (!isset($conversionSummary['conversion_matrix'][$fromCurrency][$toCurrency])) {
                        $conversionSummary['conversion_matrix'][$fromCurrency][$toCurrency] = [
                            'volume' => 0,
                            'count' => 0,
                            'average_rate' => 0
                        ];
                    }
                    $conversionSummary['conversion_matrix'][$fromCurrency][$toCurrency]['volume'] += $fromAmount;
                    $conversionSummary['conversion_matrix'][$fromCurrency][$toCurrency]['count']++;
                    // Store rates for average calculation
                    if (!isset($conversionSummary['conversion_matrix'][$fromCurrency][$toCurrency]['rates'])) {
                        $conversionSummary['conversion_matrix'][$fromCurrency][$toCurrency]['rates'] = [];
                    }
                    $conversionSummary['conversion_matrix'][$fromCurrency][$toCurrency]['rates'][] = $exchangeRate;
                }
            }
        }

        // Calculate average exchange rates for each currency pair
        foreach ($conversionSummary['conversion_matrix'] as $fromCurrency => $toCurrencies) {
            foreach ($toCurrencies as $toCurrency => $data) {
                if (!empty($data['rates'])) {
                    $conversionSummary['conversion_matrix'][$fromCurrency][$toCurrency]['average_rate'] = 
                        array_sum($data['rates']) / count($data['rates']);
                }
                // Remove rates array as we don't need it in the response
                unset($conversionSummary['conversion_matrix'][$fromCurrency][$toCurrency]['rates']);
            }
        }

        return $conversionSummary;
    }

    private function getChartsDataWithForeign($startDate, $endDate, $userId = null)
    {
        $daysDiff = $startDate->diffInDays($endDate);
        
        $chartsData = [
            'labels' => [],
            'inflow' => [],
            'outflow' => [],
            'conversion' => [],
            'airtime_networks' => [],
            'data_networks' => [],
            'conversion_breakdown' => [],
            'usd_flow' => [
                'inflow' => [],
                'outflow' => []
            ],
            'eur_flow' => [
                'inflow' => [],
                'outflow' => []
            ]
        ];

        if ($daysDiff <= 1) {
            return $this->getHourlyChartDataWithForeign($startDate, $endDate, $chartsData, $userId);
        } elseif ($daysDiff <= 30) {
            return $this->getDailyChartDataWithForeign($startDate, $endDate, $chartsData, $userId);
        } else {
            return $this->getMonthlyChartDataWithForeign($startDate, $endDate, $chartsData, $userId);
        }
    }

    private function getHourlyChartDataWithForeign($startDate, $endDate, $data, $userId = null)
    {
        for ($i = 0; $i < 24; $i++) {
            $hourStart = $startDate->copy()->addHours($i);
            $hourEnd = $hourStart->copy()->addHour();
            
            // Inflow (Deposits)
            $inflowQuery = DB::table('transactions')
                ->where('service_type', 'Wallet Funding')
                ->whereBetween('created_at', [$hourStart, $hourEnd]);

            if ($userId) {
                $inflowQuery->where('user_id', $userId);
            }

            $inflow = $inflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            // Outflow (Withdrawals + Topups + Payments)
            $outflowQuery = DB::table('transactions')
                ->where(function($query) {
                    $query->where('service_type', 'Fund Withdrawal')
                          ->orWhere('service_type', 'Airtime Topup')
                          ->orWhere('service_type', 'Data Topup')
                          ->orWhere('service_type', 'Cable Subscription')
                          ->orWhere('service_type', 'Bill Payment');
                })
                ->whereBetween('created_at', [$hourStart, $hourEnd]);

            if ($userId) {
                $outflowQuery->where('user_id', $userId);
            }

            $outflow = $outflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            // Currency Conversion
            $conversionQuery = DB::table('transactions')
                ->where('service_type', 'Currency Conversion')
                ->whereBetween('created_at', [$hourStart, $hourEnd]);

            if ($userId) {
                $conversionQuery->where('user_id', $userId);
            }

            $conversion = $conversionQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            $data['labels'][] = $hourStart->format('H:00');
            $data['inflow'][] = floatval($inflow);
            $data['outflow'][] = floatval($outflow);
            $data['conversion'][] = floatval($conversion);
            
            // USD Flow
            $usdInflowQuery = DB::table('transactions')
                ->where('service_type', 'Wallet Funding')
                ->where('currency', 'USD')
                ->where('type', 1) // deposit
                ->whereBetween('created_at', [$hourStart, $hourEnd]);

            if ($userId) {
                $usdInflowQuery->where('user_id', $userId);
            }

            $usdInflow = $usdInflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            $usdOutflowQuery = DB::table('transactions')
                ->where(function($query) {
                    $query->where('service_type', 'Fund Withdrawal')
                          ->orWhere('service_type', 'Currency Conversion');
                })
                ->where('currency', 'USD')
                ->where('type', 0) // withdrawal or debit
                ->whereBetween('created_at', [$hourStart, $hourEnd]);

            if ($userId) {
                $usdOutflowQuery->where('user_id', $userId);
            }

            $usdOutflow = $usdOutflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            // EUR Flow
            $eurInflowQuery = DB::table('transactions')
                ->where('service_type', 'Wallet Funding')
                ->where('currency', 'EUR')
                ->where('type', 1) // deposit
                ->whereBetween('created_at', [$hourStart, $hourEnd]);

            if ($userId) {
                $eurInflowQuery->where('user_id', $userId);
            }

            $eurInflow = $eurInflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            $eurOutflowQuery = DB::table('transactions')
                ->where(function($query) {
                    $query->where('service_type', 'Fund Withdrawal')
                          ->orWhere('service_type', 'Currency Conversion');
                })
                ->where('currency', 'EUR')
                ->where('type', 0) // withdrawal or debit
                ->whereBetween('created_at', [$hourStart, $hourEnd]);

            if ($userId) {
                $eurOutflowQuery->where('user_id', $userId);
            }

            $eurOutflow = $eurOutflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            $data['usd_flow']['inflow'][] = floatval($usdInflow);
            $data['usd_flow']['outflow'][] = floatval($usdOutflow);
            $data['eur_flow']['inflow'][] = floatval($eurInflow);
            $data['eur_flow']['outflow'][] = floatval($eurOutflow);
            
            // Airtime by Network
            $airtimeNetworksQuery = DB::table('transactions')
                ->where('service_type', 'Airtime Topup')
                ->whereBetween('created_at', [$hourStart, $hourEnd]);

            if ($userId) {
                $airtimeNetworksQuery->where('user_id', $userId);
            }

            $airtimeNetworks = $airtimeNetworksQuery->select('service', DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as amount'))
                ->groupBy('service')
                ->get();
                
            // Data by Network
            $dataNetworksQuery = DB::table('transactions')
                ->where('service_type', 'Data Topup')
                ->whereBetween('created_at', [$hourStart, $hourEnd]);

            if ($userId) {
                $dataNetworksQuery->where('user_id', $userId);
            }

            $dataNetworks = $dataNetworksQuery->select('service', DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as amount'))
                ->groupBy('service')
                ->get();
            
            // Initialize networks for this hour
            foreach ($airtimeNetworks as $network) {
                $networkName = $network->service ?: 'Unknown';
                if (!isset($data['airtime_networks'][$networkName])) {
                    $data['airtime_networks'][$networkName] = array_fill(0, 24, 0);
                }
                $data['airtime_networks'][$networkName][$i] = floatval($network->amount);
            }
            
            foreach ($dataNetworks as $network) {
                $networkName = $network->service ?: 'Unknown';
                if (!isset($data['data_networks'][$networkName])) {
                    $data['data_networks'][$networkName] = array_fill(0, 24, 0);
                }
                $data['data_networks'][$networkName][$i] = floatval($network->amount);
            }
        }

        $this->fillMissingNetworkData($data, 24);
        return $data;
    }

    private function getDailyChartDataWithForeign($startDate, $endDate, $data, $userId = null)
    {
        $currentDate = $startDate->copy();
        $index = 0;
        
        while ($currentDate <= $endDate) {
            $dayEnd = $currentDate->copy()->endOfDay();
            
            // Inflow (Deposits)
            $inflowQuery = DB::table('transactions')
                ->where('service_type', 'Wallet Funding')
                ->whereBetween('created_at', [$currentDate, $dayEnd]);

            if ($userId) {
                $inflowQuery->where('user_id', $userId);
            }

            $inflow = $inflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            // Outflow (Withdrawals + Topups + Payments)
            $outflowQuery = DB::table('transactions')
                ->where(function($query) {
                    $query->where('service_type', 'Fund Withdrawal')
                          ->orWhere('service_type', 'Airtime Topup')
                          ->orWhere('service_type', 'Data Topup')
                          ->orWhere('service_type', 'Cable Subscription')
                          ->orWhere('service_type', 'Bill Payment');
                })
                ->whereBetween('created_at', [$currentDate, $dayEnd]);

            if ($userId) {
                $outflowQuery->where('user_id', $userId);
            }

            $outflow = $outflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            // Currency Conversion
            $conversionQuery = DB::table('transactions')
                ->where('service_type', 'Currency Conversion')
                ->whereBetween('created_at', [$currentDate, $dayEnd]);

            if ($userId) {
                $conversionQuery->where('user_id', $userId);
            }

            $conversion = $conversionQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            $data['labels'][] = $currentDate->format('M j');
            $data['inflow'][] = floatval($inflow);
            $data['outflow'][] = floatval($outflow);
            $data['conversion'][] = floatval($conversion);
            
            // USD Flow
            $usdInflowQuery = DB::table('transactions')
                ->where('service_type', 'Wallet Funding')
                ->where('currency', 'USD')
                ->where('type', 1) // deposit
                ->whereBetween('created_at', [$currentDate, $dayEnd]);

            if ($userId) {
                $usdInflowQuery->where('user_id', $userId);
            }

            $usdInflow = $usdInflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            $usdOutflowQuery = DB::table('transactions')
                ->where(function($query) {
                    $query->where('service_type', 'Fund Withdrawal')
                          ->orWhere('service_type', 'Currency Conversion');
                })
                ->where('currency', 'USD')
                ->where('type', 0) // withdrawal or debit
                ->whereBetween('created_at', [$currentDate, $dayEnd]);

            if ($userId) {
                $usdOutflowQuery->where('user_id', $userId);
            }

            $usdOutflow = $usdOutflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            // EUR Flow
            $eurInflowQuery = DB::table('transactions')
                ->where('service_type', 'Wallet Funding')
                ->where('currency', 'EUR')
                ->where('type', 1) // deposit
                ->whereBetween('created_at', [$currentDate, $dayEnd]);

            if ($userId) {
                $eurInflowQuery->where('user_id', $userId);
            }

            $eurInflow = $eurInflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            $eurOutflowQuery = DB::table('transactions')
                ->where(function($query) {
                    $query->where('service_type', 'Fund Withdrawal')
                          ->orWhere('service_type', 'Currency Conversion');
                })
                ->where('currency', 'EUR')
                ->where('type', 0) // withdrawal or debit
                ->whereBetween('created_at', [$currentDate, $dayEnd]);

            if ($userId) {
                $eurOutflowQuery->where('user_id', $userId);
            }

            $eurOutflow = $eurOutflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            $data['usd_flow']['inflow'][] = floatval($usdInflow);
            $data['usd_flow']['outflow'][] = floatval($usdOutflow);
            $data['eur_flow']['inflow'][] = floatval($eurInflow);
            $data['eur_flow']['outflow'][] = floatval($eurOutflow);
            
            // Airtime by Network
            $airtimeNetworksQuery = DB::table('transactions')
                ->where('service_type', 'Airtime Topup')
                ->whereBetween('created_at', [$currentDate, $dayEnd]);

            if ($userId) {
                $airtimeNetworksQuery->where('user_id', $userId);
            }

            $airtimeNetworks = $airtimeNetworksQuery->select('service', DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as amount'))
                ->groupBy('service')
                ->get();
                
            // Data by Network
            $dataNetworksQuery = DB::table('transactions')
                ->where('service_type', 'Data Topup')
                ->whereBetween('created_at', [$currentDate, $dayEnd]);

            if ($userId) {
                $dataNetworksQuery->where('user_id', $userId);
            }

            $dataNetworks = $dataNetworksQuery->select('service', DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as amount'))
                ->groupBy('service')
                ->get();
            
            // Process networks
            foreach ($airtimeNetworks as $network) {
                $networkName = $network->service ?: 'Unknown';
                if (!isset($data['airtime_networks'][$networkName])) {
                    $data['airtime_networks'][$networkName] = [];
                }
                // Fill any missing previous indexes with zeros
                while (count($data['airtime_networks'][$networkName]) < $index) {
                    $data['airtime_networks'][$networkName][] = 0;
                }
                $data['airtime_networks'][$networkName][$index] = floatval($network->amount);
            }
            
            foreach ($dataNetworks as $network) {
                $networkName = $network->service ?: 'Unknown';
                if (!isset($data['data_networks'][$networkName])) {
                    $data['data_networks'][$networkName] = [];
                }
                while (count($data['data_networks'][$networkName]) < $index) {
                    $data['data_networks'][$networkName][] = 0;
                }
                $data['data_networks'][$networkName][$index] = floatval($network->amount);
            }
            
            $currentDate->addDay();
            $index++;
        }

        $this->fillMissingNetworkData($data, $index);
        return $data;
    }

    private function getMonthlyChartDataWithForeign($startDate, $endDate, $data, $userId = null)
    {
        $currentDate = $startDate->copy();
        $index = 0;
        
        while ($currentDate <= $endDate) {
            $monthEnd = $currentDate->copy()->endOfMonth();
            if ($monthEnd > $endDate) {
                $monthEnd = $endDate;
            }
            
            // Inflow (Deposits)
            $inflowQuery = DB::table('transactions')
                ->where('service_type', 'Wallet Funding')
                ->whereBetween('created_at', [$currentDate, $monthEnd]);

            if ($userId) {
                $inflowQuery->where('user_id', $userId);
            }

            $inflow = $inflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            // Outflow (Withdrawals + Topups + Payments)
            $outflowQuery = DB::table('transactions')
                ->where(function($query) {
                    $query->where('service_type', 'Fund Withdrawal')
                          ->orWhere('service_type', 'Airtime Topup')
                          ->orWhere('service_type', 'Data Topup')
                          ->orWhere('service_type', 'Cable Subscription')
                          ->orWhere('service_type', 'Bill Payment');
                })
                ->whereBetween('created_at', [$currentDate, $monthEnd]);

            if ($userId) {
                $outflowQuery->where('user_id', $userId);
            }

            $outflow = $outflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            // Currency Conversion
            $conversionQuery = DB::table('transactions')
                ->where('service_type', 'Currency Conversion')
                ->whereBetween('created_at', [$currentDate, $monthEnd]);

            if ($userId) {
                $conversionQuery->where('user_id', $userId);
            }

            $conversion = $conversionQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            $data['labels'][] = $currentDate->format('M Y');
            $data['inflow'][] = floatval($inflow);
            $data['outflow'][] = floatval($outflow);
            $data['conversion'][] = floatval($conversion);
            
            // USD Flow
            $usdInflowQuery = DB::table('transactions')
                ->where('service_type', 'Wallet Funding')
                ->where('currency', 'USD')
                ->where('type', 1) // deposit
                ->whereBetween('created_at', [$currentDate, $monthEnd]);

            if ($userId) {
                $usdInflowQuery->where('user_id', $userId);
            }

            $usdInflow = $usdInflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            $usdOutflowQuery = DB::table('transactions')
                ->where(function($query) {
                    $query->where('service_type', 'Fund Withdrawal')
                          ->orWhere('service_type', 'Currency Conversion');
                })
                ->where('currency', 'USD')
                ->where('type', 0) // withdrawal or debit
                ->whereBetween('created_at', [$currentDate, $monthEnd]);

            if ($userId) {
                $usdOutflowQuery->where('user_id', $userId);
            }

            $usdOutflow = $usdOutflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            // EUR Flow
            $eurInflowQuery = DB::table('transactions')
                ->where('service_type', 'Wallet Funding')
                ->where('currency', 'EUR')
                ->where('type', 1) // deposit
                ->whereBetween('created_at', [$currentDate, $monthEnd]);

            if ($userId) {
                $eurInflowQuery->where('user_id', $userId);
            }

            $eurInflow = $eurInflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            $eurOutflowQuery = DB::table('transactions')
                ->where(function($query) {
                    $query->where('service_type', 'Fund Withdrawal')
                          ->orWhere('service_type', 'Currency Conversion');
                })
                ->where('currency', 'EUR')
                ->where('type', 0) // withdrawal or debit
                ->whereBetween('created_at', [$currentDate, $monthEnd]);

            if ($userId) {
                $eurOutflowQuery->where('user_id', $userId);
            }

            $eurOutflow = $eurOutflowQuery->sum(DB::raw('COALESCE(CAST(amount as DECIMAL(10,2)), 0)'));
                
            $data['usd_flow']['inflow'][] = floatval($usdInflow);
            $data['usd_flow']['outflow'][] = floatval($usdOutflow);
            $data['eur_flow']['inflow'][] = floatval($eurInflow);
            $data['eur_flow']['outflow'][] = floatval($eurOutflow);
            
            // Airtime by Network
            $airtimeNetworksQuery = DB::table('transactions')
                ->where('service_type', 'Airtime Topup')
                ->whereBetween('created_at', [$currentDate, $monthEnd]);

            if ($userId) {
                $airtimeNetworksQuery->where('user_id', $userId);
            }

            $airtimeNetworks = $airtimeNetworksQuery->select('service', DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as amount'))
                ->groupBy('service')
                ->get();
                
            // Data by Network
            $dataNetworksQuery = DB::table('transactions')
                ->where('service_type', 'Data Topup')
                ->whereBetween('created_at', [$currentDate, $monthEnd]);

            if ($userId) {
                $dataNetworksQuery->where('user_id', $userId);
            }

            $dataNetworks = $dataNetworksQuery->select('service', DB::raw('COALESCE(SUM(CAST(amount as DECIMAL(10,2))), 0) as amount'))
                ->groupBy('service')
                ->get();
            
            // Process networks
            foreach ($airtimeNetworks as $network) {
                $networkName = $network->service ?: 'Unknown';
                if (!isset($data['airtime_networks'][$networkName])) {
                    $data['airtime_networks'][$networkName] = [];
                }
                while (count($data['airtime_networks'][$networkName]) < $index) {
                    $data['airtime_networks'][$networkName][] = 0;
                }
                $data['airtime_networks'][$networkName][$index] = floatval($network->amount);
            }
            
            foreach ($dataNetworks as $network) {
                $networkName = $network->service ?: 'Unknown';
                if (!isset($data['data_networks'][$networkName])) {
                    $data['data_networks'][$networkName] = [];
                }
                while (count($data['data_networks'][$networkName]) < $index) {
                    $data['data_networks'][$networkName][] = 0;
                }
                $data['data_networks'][$networkName][$index] = floatval($network->amount);
            }
            
            $currentDate->addMonth()->startOfMonth();
            $index++;
        }

        $this->fillMissingNetworkData($data, $index);
        return $data;
    }

    private function fillMissingNetworkData(&$data, $totalPoints)
    {
        // Fill airtime networks
        if (isset($data['airtime_networks'])) {
            foreach ($data['airtime_networks'] as $network => &$values) {
                while (count($values) < $totalPoints) {
                    $values[] = 0;
                }
            }
        }
        
        // Fill data networks
        if (isset($data['data_networks'])) {
            foreach ($data['data_networks'] as $network => &$values) {
                while (count($values) < $totalPoints) {
                    $values[] = 0;
                }
            }
        }
    }
}