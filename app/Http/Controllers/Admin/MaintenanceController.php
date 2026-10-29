<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Transaction;
use App\TraitsFolder\MailTrait;

class MaintenanceController extends Controller
{
    use MailTrait;
    
    public function index()
    {
        $data["page_title"] = "Monthly Maintenance";
        $data["page_sub_title"] = "Maintenance debit";
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
        
        return view('admin.maintenance.index', $data);
    }
    
    public function debitMaintenance(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);
    
        $amount = $request->amount;
        $alert  = $request->has('alert');
        $now    = Carbon::now();
        $totalDebited = 0;
        $processedCount = 0;
        $failedCount = 0;
    
        DB::beginTransaction();
    
        try {
            // Use chunking for better memory management with large user base
            User::chunk(100, function ($users) use ($amount, $alert, $now, &$totalDebited, &$processedCount, &$failedCount) {
                foreach ($users as $user) {
                    try {
                        $init_balance  = (float) $user->balance;
                        
                        // Check if user has sufficient balance
                        if ($init_balance < $amount) {
                            $failedCount++;
                            \Log::warning("User #{$user->id} has insufficient balance for maintenance fee");
                            continue; // Skip this user but continue with others
                        }
                        
                        $final_balance = $init_balance - $amount;
    
                        // Use update instead of save for better performance
                        User::where('id', $user->id)->update(['balance' => $final_balance]);
    
                        $totalDebited += $amount;
                        $processedCount++;
    
                        if ($alert) {
                            $trx = $this->generateUniqueReference();
                            
                            // Check if wallet_id is valid - you might want to fetch this dynamically
                            $wallet_id = 1; // Consider making this configurable
    
                            $user->transaction()->create([
                                'wallet_id'        => $wallet_id,
                                'user_level_id'    => $user->user_level_id,
                                'service'          => 'System',
                                'service_type'     => 'Maintenance Fee',
                                'description'      => "Monthly maintenance fee of ₦" . number_format($amount, 2),
                                'amount'           => $amount,
                                'phone_number'     => $user->phone_number,
                                'type'             => '1', // debit
                                'initial_balance'  => $init_balance,
                                'final_balance'    => $final_balance,
                                'reference'        => $trx,
                                'status'           => 'success',
                                'created_at'       => $now,
                            ]);
    
                            $this->sendMaintenanceEmail($user, $amount, $final_balance, $formattedDate = $now->format('d-M-Y h:i A'));
                        }
                    } catch (\Exception $e) {
                        $failedCount++;
                        \Log::error("Failed to process maintenance fee for user #{$user->id}: " . $e->getMessage());
                        // Continue with next user
                    }
                }
            });
    
            DB::commit();
    
            $message = "Monthly maintenance fee of ₦" . number_format($amount, 2) . 
                       " successfully debited from {$processedCount} user accounts.";
            
            if ($failedCount > 0) {
                $message .= " Failed to process {$failedCount} accounts due to insufficient balance or errors.";
            }
    
            Session::flash('message', $message);
            Session::flash('type', $failedCount > 0 ? 'warning' : 'success');
            Session::flash('title', $failedCount > 0 ? 'Partial Success' : 'Success');
    
            return redirect()->back();
    
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Maintenance fee debit failed: ' . $e->getMessage());
            
            Session::flash('message', 'An error occurred while processing maintenance fees. Please try again.');
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
    
            return redirect()->back();
        }
    }
    
    /**
     * Generate a unique transaction reference
     */
    private function generateUniqueReference()
    {
        do {
            $reference = strtoupper(Str::random(20));
        } while (DB::table('transactions')->where('reference', $reference)->exists());
        
        return $reference;
    }
    
    /**
     * Send maintenance fee email notification
     */
    private function sendMaintenanceEmail($user, $amount, $final_balance, $formattedDate)
    {
        // Only send if email notifications are enabled and user has email
        if (getSiteSettings("email_notify") != "Yes" || empty($user->email)) {
            return;
        }
    
        // Check if user has profile relationship
        if (!$user->relationLoaded('profile')) {
            $user->load('profile');
        }
    
        $maskedAccount     = $this->maskAccountNumber($user->account_number);
        $formattedAmount   = number_format($amount, 2);
        $formattedBalance  = number_format($final_balance, 2);
        $accountName       = $user->profile->first_name ?? '' . ' ' . $user->profile->last_name ?? '';
        
        if (empty(trim($accountName))) {
            $accountName = $user->email;
        }
    
        $message = $this->buildEmailTemplate($accountName, $formattedAmount, $formattedBalance, $formattedDate, $maskedAccount);
    
        $this->sendMail(
            $user->email,
            $accountName,
            "Dupay Monthly Maintenance Notification",
            $message
        );
    }
    
    /**
     * Mask account number for security
     */
    private function maskAccountNumber($accountNumber)
    {
        if (empty($accountNumber)) {
            return 'XXXXXXX';
        }
        
        return substr($accountNumber, 0, 1) . 'XX..' . substr($accountNumber, -2);
    }
    
    /**
     * Build email HTML template
     */
    private function buildEmailTemplate($accountName, $formattedAmount, $formattedBalance, $formattedDate, $maskedAccount)
    {
        return "
            <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
            <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Account Name</td>
                    <td style='padding:8px; border:1px solid #ddd;'><strong>{$accountName}</strong></td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Account Number</td>
                    <td style='padding:8px; border:1px solid #ddd;'><strong>{$maskedAccount}</strong></td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                    <td style='padding:8px; border:1px solid #ddd;'>DEBIT ALERT</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Transaction Amount</td>
                    <td style='padding:8px; border:1px solid #ddd;'>₦{$formattedAmount}</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Transaction Currency</td>
                    <td style='padding:8px; border:1px solid #ddd;'>NGN</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Transaction Narration</td>
                    <td style='padding:8px; border:1px solid #ddd;'>Monthly Maintenance Fee</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Date and Time</td>
                    <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Available Balance</td>
                    <td style='padding:8px; border:1px solid #ddd;'>₦{$formattedBalance}</td>
                </tr>
            </table>
            <br>
            <p>Thank you for using Dupay.</p>
        ";
    }
    
    // public function debitMaintenance(Request $request)
    // {
    //     $request->validate([
    //         'amount' => 'required|numeric|min:0.01',
    //     ]);
    
    //     $amount = $request->amount;
    //     $alert  = $request->has('alert');
    //     $now    = Carbon::now();
    //     $totalDebited = 0;
    
    //     DB::beginTransaction();
    
    //     try {
    //         $users = User::all();
    
    //         foreach ($users as $user) {
    //             $init_balance  = $user->balance;
    //             $final_balance = $init_balance - $amount;
    
    //             $user->balance = $final_balance;
    //             $user->save();
    
    //             $totalDebited += $amount;
    
    //             if ($alert) {
    //                 $trx = strtoupper(Str::random(20));
    
    //                 $user->transaction()->create([
    //                     'wallet_id'        => '1',
    //                     'user_level_id'    => $user->user_level_id ?? null,
    //                     'service'          => 'System',
    //                     'service_type'     => 'Maintenance Fee',
    //                     'description'      => "Monthly maintenance fee of ₦" . number_format($amount, 2),
    //                     'amount'           => $amount,
    //                     'phone_number'     => $user->phone_number ?? null,
    //                     'type'             => '1', // debit
    //                     'initial_balance'  => $init_balance,
    //                     'final_balance'    => $final_balance,
    //                     'reference'        => $trx,
    //                     'status'           => 'success',
    //                     'created_at'       => $now,
    //                 ]);
    
    //                 if (getSiteSettings("email_notify") == "Yes" && !empty($user->email)) {
    //                     $maskedAccount     = substr($user->account_number ?? 'XXXXXXX', 0, 1) . 'XX..' . substr($user->account_number ?? 'XXXXXXX', -2);
    //                     $formattedDate     = now()->format('d-M-Y h:i A');
    //                     $formattedAmount   = number_format($amount, 2);
    //                     $formattedBalance  = number_format($final_balance, 2);
    //                     $accountName       = $user->profile->first_name.' '.$user->profile->last_name;
    
    //                     $message = "
    //                         <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
    //                         <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
    //                             <tr>
    //                                 <td style='padding:8px; border:1px solid #ddd;'>Account Name</td>
    //                                 <td style='padding:8px; border:1px solid #ddd;'><strong>{$accountName}</strong></td>
    //                             </tr>
    //                             <tr>
    //                                 <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
    //                                 <td style='padding:8px; border:1px solid #ddd;'>DEBIT ALERT</td>
    //                             </tr>
    //                             <tr>
    //                                 <td style='padding:8px; border:1px solid #ddd;'>Transaction Amount</td>
    //                                 <td style='padding:8px; border:1px solid #ddd;'>₦{$formattedAmount}</td>
    //                             </tr>
    //                             <tr>
    //                                 <td style='padding:8px; border:1px solid #ddd;'>Transaction Currency</td>
    //                                 <td style='padding:8px; border:1px solid #ddd;'>NGN</td>
    //                             </tr>
    //                             <tr>
    //                                 <td style='padding:8px; border:1px solid #ddd;'>Transaction Narration</td>
    //                                 <td style='padding:8px; border:1px solid #ddd;'>Monthly Maintenance Fee</td>
    //                             </tr>
    //                             <tr>
    //                                 <td style='padding:8px; border:1px solid #ddd;'>Date and Time</td>
    //                                 <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
    //                             </tr>
    //                             <tr>
    //                                 <td style='padding:8px; border:1px solid #ddd;'>Available Balance</td>
    //                                 <td style='padding:8px; border:1px solid #ddd;'>₦{$formattedBalance}</td>
    //                             </tr>
    //                         </table>
    //                         <br>
    //                         <p>Thank you for using Dupay.</p>
    //                     ";
    
    //                     $this->sendMail(
    //                         $user->email,
    //                         $user->profile->first_name,
    //                         "Dupay Monthly Maintenance Notification",
    //                         $message
    //                     );
    //                 }
    //             }
    //         }
    
    //         DB::commit();
    
    //         Session::flash('message', 'Monthly maintenance fee successfully debited from all user accounts.');
    //         Session::flash('type', 'success');
    //         Session::flash('title', 'Success');
    
    //         return redirect()->back();
    
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    
    //         Session::flash('message', 'An error occurred: ' . $e->getMessage());
    //         Session::flash('type', 'error');
    //         Session::flash('title', 'Error');
    
    //         return redirect()->back();
    //     }
    // }

}
