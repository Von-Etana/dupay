<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;
    protected $fillable = [
        'currency',
        'rate',
        'fee',
        'cap',
        'quoted_rate',
        'quote_inverted',
    ];

    /**
     * The rate written the way people actually quote it.
     *
     * `rate` is stored as a multiplier, which for NGN-USD is 0.00061728 — a
     * number nobody recognises. This renders the same rate as
     * "1,620 NGN = 1 USD", so the admin list is readable and the reciprocal
     * stays an implementation detail.
     */
    public function getHumanQuoteAttribute(): string
    {
        $rate = (float) $this->rate;
        if ($rate <= 0) {
            return '—';
        }

        [$from, $to] = array_pad(explode('-', (string) $this->currency), 2, null);
        if (!$from || !$to) {
            return (string) $this->rate;
        }

        // Prefer the figure the admin actually typed — deriving it back from
        // the stored multiplier is lossy (1 / 0.00061728 = 1620.0103, not 1620).
        if ($this->quoted_rate !== null && (float) $this->quoted_rate > 0) {
            $quoted = (float) $this->quoted_rate;

            if ($this->quote_inverted) {
                return $this->trim($quoted) . ' ' . $from . ' = 1 ' . $to;
            }

            // Quote it forwards when the typed figure is one a person would
            // actually say — 0.82 for "1 USD = 0.82 EUR" is natural, whereas a
            // sub-cent value like 0.00061728 is a raw multiplier (an old row
            // re-saved unchanged), and rendering that forwards would read as
            // "1 NGN = 0.00061728 USD". Same 0.01 threshold convertCurrency
            // uses to distinguish sub-cent values.
            if ($quoted >= 0.01) {
                return '1 ' . $from . ' = ' . $this->trim($quoted) . ' ' . $to;
            }
        }

        // Fallback for rows not yet re-saved: quote whichever direction gives
        // the number greater than 1, which is how the pair is spoken aloud.
        if ($rate >= 1) {
            return '1 ' . $from . ' = ' . $this->trim($rate) . ' ' . $to;
        }

        return $this->trim(round(1 / $rate, 2)) . ' ' . $from . ' = 1 ' . $to;
    }

    private function trim(float $n): string
    {
        return rtrim(rtrim(number_format($n, 8, '.', ''), '0'), '.');
    }
}