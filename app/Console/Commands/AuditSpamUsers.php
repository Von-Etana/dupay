<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AuditSpamUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:audit-spam-users 
                            {--block : Flag and block detected spam accounts} 
                            {--limit=100 : Maximum records to process per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit database for historical spam accounts, disposable emails, and unverified users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting historical spam account audit...');

        $blockedDomains = config('disposable_email.domains', []);
        $domainLookup = array_fill_keys(array_map('strtolower', $blockedDomains), true);

        $shouldBlock = $this->option('block');
        $batchLimit = (int) $this->option('limit');

        $spamCount = 0;
        $unverifiedCount = 0;
        $flaggedUsers = [];

        User::chunk(500, function ($users) use (&$spamCount, &$unverifiedCount, &$flaggedUsers, $domainLookup, $shouldBlock, $batchLimit) {
            foreach ($users as $user) {
                $email = strtolower(trim((string) $user->email));
                $parts = explode('@', $email);
                $domain = $parts[1] ?? '';

                $isDisposable = isset($domainLookup[$domain]);
                $isUnverified = ((string) $user->email_verify !== '1' || (string) $user->account_activation !== '1');

                if ($isDisposable) {
                    $spamCount++;
                    if (count($flaggedUsers) < $batchLimit) {
                        $flaggedUsers[] = [
                            'id' => $user->id,
                            'email' => $user->email,
                            'domain' => $domain,
                            'verified' => $user->email_verify ? 'Yes' : 'No',
                            'status' => $user->blocked_status ? 'Blocked' : 'Active',
                            'created_at' => optional($user->created_at)->format('Y-m-d H:i') ?? 'N/A',
                        ];
                    }

                    if ($shouldBlock && !$user->blocked_status) {
                        $user->update(['blocked_status' => '1']);
                        \Log::warning('Historical spam user blocked by audit command', ['user_id' => $user->id, 'email' => $user->email]);
                    }
                } elseif ($isUnverified) {
                    $unverifiedCount++;
                }
            }
        });

        $this->newLine();
        $this->info("=== Audit Results ===");
        $this->line("Disposable / spam email accounts identified: <fg=red>{$spamCount}</>");
        $this->line("Unverified / incomplete accounts: <fg=yellow>{$unverifiedCount}</>");

        if (!empty($flaggedUsers)) {
            $this->newLine();
            $this->info("Sample Flagged Accounts (up to {$batchLimit}):");
            $this->table(['User ID', 'Email', 'Domain', 'Verified', 'Status', 'Registered'], $flaggedUsers);
        }

        if ($shouldBlock) {
            $this->warn("All identified disposable email accounts have been set to blocked_status = 1.");
        } else if ($spamCount > 0) {
            $this->comment("Run with --block to automatically set blocked_status = 1 for all identified disposable email accounts.");
        }

        return 0;
    }
}
