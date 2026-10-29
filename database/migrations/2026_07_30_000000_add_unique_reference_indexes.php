<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Idempotency guard for wallet funding — payments.reference.
 *
 * This is the gateway reference the Paystack / Flutterwave / Monnify / SafeHaven
 * funding webhooks check, and the key that stops the "funded X, spent 2X" bug.
 *
 * This migration is SAFE TO RE-RUN: it only creates the index if it is missing,
 * so a half-applied earlier attempt (index created but migration not recorded)
 * no longer causes "Duplicate key name" errors.
 *
 * transactions.paymentReference is intentionally NOT indexed: it is reused across
 * separate deposits by the same virtual account (e.g. Flutterwave), so it is not
 * a per-payment idempotency key. transactions.reference is free-text and also not
 * indexed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->indexExists('payments', 'payments_reference_unique')) {
            $this->guardNoDuplicates('payments', 'reference');
            Schema::table('payments', function (Blueprint $table) {
                $table->unique('reference', 'payments_reference_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('payments', 'payments_reference_unique')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropUnique('payments_reference_unique');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $rows = DB::select(
            "SELECT 1 FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1",
            [DB::getDatabaseName(), $table, $index]
        );
        return ! empty($rows);
    }

    private function guardNoDuplicates(string $table, string $column): void
    {
        $dupes = DB::table($table)
            ->select($column)
            ->whereNotNull($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->limit(5)
            ->pluck($column)
            ->all();

        if (! empty($dupes)) {
            throw new \RuntimeException(
                "Cannot add unique index on {$table}.{$column}: duplicate values exist "
                . "(e.g. " . implode(', ', $dupes) . "). De-duplicate first, e.g.:\n"
                . "  SELECT {$column}, COUNT(*) c FROM {$table} "
                . "WHERE {$column} IS NOT NULL GROUP BY {$column} HAVING c > 1;"
            );
        }
    }
};
