<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * KYC verification table upgrades.
 *
 * 1. `method`       — BVN or NIN. The user picks this at submission, but the
 *                     admin can change it before we send the record to
 *                     SafeHaven (identityType on the subaccount call).
 * 2. `nin`          — a separate column so a user who holds BOTH a BVN and a
 *                     NIN can have both numbers on file. Previously every
 *                     number was crammed into the `bvn` column, so switching
 *                     the method sent the wrong number to SafeHaven.
 * 3. `submitted_at` — set every time a user submits or re-submits. Ordering by
 *                     created_at was burying re-submissions, because
 *                     updateOrCreate() reuses the original row and never moves
 *                     created_at forward.
 * 4. `reviewed_at` / `reviewed_by` — audit trail for who approved or rejected.
 *
 * Every step is guarded, so this migration is safe to run on a database where
 * some of these columns were already added by hand.
 */
return new class extends Migration
{
    public function up()
    {
        $table = 'wallet_verificaitons'; // note: existing (misspelled) table name

        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($table) {
            if (! Schema::hasColumn($table, 'method')) {
                $t->string('method', 10)->nullable()->default('BVN');
            }

            if (! Schema::hasColumn($table, 'nin')) {
                $t->string('nin')->nullable();
            }

            if (! Schema::hasColumn($table, 'submitted_at')) {
                $t->timestamp('submitted_at')->nullable();
            }

            if (! Schema::hasColumn($table, 'reviewed_at')) {
                $t->timestamp('reviewed_at')->nullable();
            }

            if (! Schema::hasColumn($table, 'reviewed_by')) {
                $t->unsignedBigInteger('reviewed_by')->nullable();
            }
        });

        // Normalise whatever is already in `method`: "bvn", "Bvn", " nin " -> "BVN" / "NIN".
        DB::table($table)->whereNotNull('method')->update([
            'method' => DB::raw("UPPER(TRIM(method))"),
        ]);

        DB::table($table)
            ->where(function ($q) {
                $q->whereNull('method')->orWhereNotIn('method', ['BVN', 'NIN']);
            })
            ->update(['method' => 'BVN']);

        // Backfill submitted_at so existing rows sort sensibly straight away.
        DB::table($table)->whereNull('submitted_at')->update([
            'submitted_at' => DB::raw('COALESCE(updated_at, created_at)'),
        ]);

        // Index the column we now sort the admin list by.
        try {
            Schema::table($table, function (Blueprint $t) {
                $t->index('submitted_at', 'wallet_verificaitons_submitted_at_index');
            });
        } catch (\Throwable $e) {
            // Index already exists — nothing to do.
        }
    }

    public function down()
    {
        $table = 'wallet_verificaitons';

        if (! Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex('wallet_verificaitons_submitted_at_index');
            });
        } catch (\Throwable $e) {
            //
        }

        Schema::table($table, function (Blueprint $t) use ($table) {
            foreach (['submitted_at', 'reviewed_at', 'reviewed_by', 'nin'] as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $t->dropColumn($column);
                }
            }
        });

        // `method` is deliberately left in place: it existed before this
        // migration on most installs and dropping it would lose data.
    }
};
