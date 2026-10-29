<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records the rate the way the admin actually quoted it, alongside the
 * multiplier the conversion code uses.
 *
 * `rate` must stay a multiplier (amount_in_from x rate = amount_in_to), but for
 * NGN-USD that is 0.00061728 — unreadable, and impossible to type correctly.
 * Deriving the human figure back from it loses precision: 1 / 0.00061728 is
 * 1620.0103, not 1620. Storing what was typed keeps the display exact.
 *
 * Nothing reads these columns for conversion maths, so this is additive and
 * safe: existing rows simply have them null until next edited.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            if (!Schema::hasColumn('exchange_rates', 'quoted_rate')) {
                $table->decimal('quoted_rate', 24, 8)->nullable()->after('rate');
            }
            if (!Schema::hasColumn('exchange_rates', 'quote_inverted')) {
                $table->boolean('quote_inverted')->default(false)->after('quoted_rate');
            }
        });
    }

    public function down()
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            foreach (['quoted_rate', 'quote_inverted'] as $col) {
                if (Schema::hasColumn('exchange_rates', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
