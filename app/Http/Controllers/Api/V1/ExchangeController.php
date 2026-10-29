<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ExchangeRate;

class ExchangeController extends Controller
{
    public function convert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_currency' => 'required|string|max:3',
            'to_currency'   => 'required|string|max:3|different:from_currency',
            'amount'        => 'required|numeric|min:0.01',
            'apply_fee'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $fromCurrency = strtoupper($request->from_currency);
        $toCurrency   = strtoupper($request->to_currency);
        $amount       = $request->amount;
        $applyFee     = $request->apply_fee ?? true;

        $result = convertCurrency($fromCurrency, $toCurrency, $amount, $applyFee);

        if (!$result['status']) {
            return response()->json([
                'status'  => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success'  => true,
            'message' => 'Conversion successful.',
            'data'    => $result,
        ]);
    }
    
    public function rates()
    {
        $rates = ExchangeRate::all();
        return response()->json([
            'success'  => true,
            'message' => 'Conversion successful.',
            'data'    => $rates,
        ]);
    }
}
