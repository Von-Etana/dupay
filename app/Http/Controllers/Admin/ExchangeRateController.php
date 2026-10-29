<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Session;
use App\TraitsFolder\MailTrait;

class ExchangeRateController extends Controller
{
    use MailTrait;
    
    public function index()
    {
        $data["page_title"] = "Exchange Rate";
        $data["page_sub_title"] = "View All Exchange Rate";
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
        
        $rates = ExchangeRate::latest()->paginate(20);

        return view('admin.exchange.index', compact('rates') + $data);
    }
    
    public function edit($id)
    {
        $data["page_title"] = "Exchange Rate";
        $data["page_sub_title"] = "View Exchange Rate";
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
        
        $rateData = ExchangeRate::findOrFail($id);

        return view('admin.exchange.edit', compact('rateData') + $data);
    }
    
    public function update(Request $request, $id)
    {
        try {
            $rateData = ExchangeRate::findOrFail($id);
    
            $validated = $request->validate([
                'currency'       => 'required',
                'rate'              => 'required',
                'fee'      => 'required',
                'cap'      => 'nullable|numeric|min:0',
            ]);

            $validated['cap'] = $request->input('cap', 0);

            // ── Accept the rate the way a human quotes it ────────────────────
            // `rate` is stored as a MULTIPLIER: amount_in_from x rate =
            // amount_in_to. But nobody quotes NGN-USD as 0.00061728 — they say
            // "N1,620 to the dollar". Typing 1620 used to be stored verbatim,
            // which meant N1 = $1,620, and convertCurrency() then refused the
            // pair, silently taking ALL conversion offline until someone read
            // the logs. That happened twice.
            //
            // A pair and its reverse can never both exceed 1 (that would let a
            // round trip create money), so an entry that breaks the invariant
            // is unambiguously the reciprocal of what was meant. Store the
            // reciprocal and tell the admin exactly what was saved.
            $newRate = (float) str_replace(',', '', (string) $validated['rate']);

            if ($newRate <= 0) {
                return $this->rateError('Rate must be greater than zero.');
            }

            [$fromCur, $toCur] = array_pad(explode('-', $rateData->currency), 2, null);
            $interpreted   = null;
            $quotedRate    = $newRate;   // what the admin typed, before any flip
            $quoteInverted = false;

            if ($fromCur && $toCur) {
                $reverse     = ExchangeRate::where('currency', "{$toCur}-{$fromCur}")->first();
                $reverseRate = $reverse ? (float) $reverse->rate : 0.0;

                if ($reverseRate > 0 && ($newRate * $reverseRate) > 1.0001) {

                    $flipped = 1 / $newRate;

                    if (($flipped * $reverseRate) <= 1.0001) {
                        // Inverting fixes it — the admin quoted it the human way.
                        $interpreted = "Read as {$this->trimNum($newRate)} {$fromCur} = 1 {$toCur}, "
                            . "stored as {$this->trimNum($flipped)}.";
                        $quotedRate    = $newRate;   // exactly what was typed
                        $quoteInverted = true;
                        $newRate       = $flipped;
                    } else {
                        // Inverting does NOT fix it, so the OTHER row is the
                        // broken one. Refusing here would trap the admin.
                        return $this->rateError(
                            "Can't save this: {$reverse->currency} is currently {$this->trimNum($reverseRate)}, "
                            . "which is itself inverted. Fix {$reverse->currency} first "
                            . "(it should be about {$this->trimNum(1 / $reverseRate)}), then set {$rateData->currency}."
                        );
                    }
                }
            }
            // `rate` stays the multiplier every other part of the system reads.
            // `quoted_rate` remembers the human figure so the admin screens can
            // show "1,620 NGN = 1 USD" exactly, instead of deriving 1620.0103
            // back out of a value rounded to 8 decimals.
            $validated['rate']           = $newRate;
            $validated['quoted_rate']    = $quotedRate;
            $validated['quote_inverted'] = $quoteInverted;

            $rateData->update($validated);
    
            session()->flash('message', 'Exchange Rate updated successfully.'
                . ($interpreted ? ' ' . $interpreted : ''));
            session()->flash('type', 'success');
            session()->flash('title', 'Success');
    
            return redirect()->back();
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = implode(', ', $e->validator->errors()->all());
    
            session()->flash('message', 'Validation failed: ' . $errors);
            session()->flash('type', 'error');
            session()->flash('title', 'Validation Error');
    
            return redirect()->back();
        } catch (\Exception $e) {
            session()->flash('message', 'Something went wrong while updating the Exchange Rate: ' . $e->getMessage());
            session()->flash('type', 'error');
            session()->flash('title', 'Error');
    
            return redirect()->back();
        }
    }
    
    /** Render a rate without trailing zeros: 0.00061728, 1400, 0.82. */
    private function trimNum(float $n): string
    {
        return rtrim(rtrim(number_format($n, 8, '.', ''), '0'), '.');
    }

    /** Flash a rate validation failure and bounce back to the form. */
    private function rateError(string $message)
    {
        session()->flash('message', $message);
        session()->flash('type', 'error');
        session()->flash('title', 'Invalid exchange rate');

        return redirect()->back()->withInput();
    }

    public function destroy($id)
    {
        $accountData = ExchangeRate::findOrFail($id);
        $accountData->delete();
        
        session()->flash('message', 'Exchange Rate deleted successfully.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return redirect()->back();
    }
}