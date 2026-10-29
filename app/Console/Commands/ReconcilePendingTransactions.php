<?php

namespace App\Console\Commands;

use App\Models\Telecom\ApiConnection;
use App\Models\Telecom\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * tx:reconcile
 *
 * Walks the transactions table for rows stuck in 'pending' status that are old
 * enough to be safely requeried, asks the provider (VTpass/etc) via the
 * verifyProviderTransaction() helper what actually happened, and resolves them:
 *
 *   - provider says SUCCESS  -> flip the row to 'success'
 *   - provider says FAILED   -> mark 'unsuccessful' AND issue a refund row
 *   - pending / unknown      -> leave alone, try again on the next run
 *
 * Schedule it every 5 minutes (see app/Console/Kernel.php), and run it once
 * manually with --minutes=0 to backfill historically stuck rows.
 */
class ReconcilePendingTransactions extends Command
{
    protected $signature = 'tx:reconcile
                            {--minutes=5 : Minimum age of a pending row before it gets reconciled}
                            {--max=200 : Maximum number of rows to process this run}';

    protected $description = 'Requery the provider for pending transactions and resolve them.';

    public function handle()
    {
        $cutoff = Carbon::now()->subMinutes((int) $this->option('minutes'));

        $rows = Transaction::where('status', 'pending')
            ->where('type', '0') // debits only — never touch credit / refund rows
            ->whereNotNull('api_connection_id')
            ->where('created_at', '<=', $cutoff)
            ->orderBy('created_at', 'asc')
            ->limit((int) $this->option('max'))
            ->get();

        $this->info("Found {$rows->count()} pending transaction(s) to reconcile.");

        $stats = ['success' => 0, 'failed' => 0, 'pending' => 0, 'unknown' => 0, 'skipped' => 0];

        foreach ($rows as $tx) {
            $apiConnection = ApiConnection::find($tx->api_connection_id);
            if (!$apiConnection) {
                $stats['skipped']++;
                continue;
            }

            $verify = verifyProviderTransaction($apiConnection, $tx->reference);
            $this->line("  TX#{$tx->id} {$tx->reference} -> {$verify['status']}");

            if ($verify['status'] === 'success') {
                $tx->update([
                    'status'  => 'success',
                    'message' => 'Reconciled (provider confirmed success)',
                ]);
                $stats['success']++;
                continue;
            }

            if ($verify['status'] === 'failed') {
                // Only refund if we have not already created a refund row for this Trx
                $alreadyRefunded = Transaction::where('reference_ref', $tx->reference)
                    ->where('status', 'refund')
                    ->exists();

                $tx->update([
                    'status'  => 'unsuccessful',
                    'message' => 'Reconciled (provider confirmed failed)',
                ]);

                if (!$alreadyRefunded) {
                    $user = $tx->user;
                    if (!$user) {
                        $stats['failed']++;
                        continue;
                    }

                    $balanceAction = userBalanceAction($user, $tx->amount, $tx->wallet_id ?? 1, "+");
                    if (!empty($balanceAction["success"])) {
                        $user->transaction()->create([
                            'wallet_id'         => $tx->wallet_id,
                            'user_level_id'     => $tx->user_level_id,
                            'service'           => $tx->service,
                            'api_connection_id' => $tx->api_connection_id,
                            'service_type_id'   => $tx->service_type_id,
                            'service_plan_id'   => $tx->service_plan_id,
                            'service_type'      => $tx->service_type,
                            'description'       => $tx->description . ' Refund (reconciled)',
                            'amount'            => $tx->amount,
                            'phone_number'      => $tx->phone_number,
                            'type'              => "1",
                            'initial_balance'   => $balanceAction["init_balance"],
                            'final_balance'     => $balanceAction["final_balance"],
                            'reference'         => strtoupper(Str::random(16)),
                            'reference_ref'     => $tx->reference,
                            'status'            => "refund",
                        ]);
                    }
                }
                $stats['failed']++;
                continue;
            }

            // pending / unknown -> leave the row alone and retry next run
            $stats[$verify['status']]++;
        }

        $this->info(sprintf(
            'Done. success=%d, failed=%d, pending=%d, unknown=%d, skipped=%d',
            $stats['success'], $stats['failed'], $stats['pending'], $stats['unknown'], $stats['skipped']
        ));

        return 0;
    }
}
