<?php

namespace App\Jobs;

use App\Models\Telecom\VirtualAccount;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\TraitsFolder\MailTrait;

class SendEmail implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, MailTrait;

    public $subject;
    public $content;
    public $users;

    public function __construct($users = null, $subject, $content)
    {
        $this->users   = $users;
        $this->subject = $subject;
        $this->content = $content;
    }

    public function handle()
    {
        $users = $this->users;

        if (!$users) {
            $users = User::whereNotNull('email')->cursor();
        }

        foreach ($users as $user) {

            if (empty($user->email)) {
                continue;
            }

            try {
                $name = $user->profile->username ?? $user->phone_number ?? 'User';

                $this->sendMail(
                    $user->email,
                    $name,
                    $this->subject,
                    $this->content
                );

            } catch (\Throwable $e) {
                \Log::error('Email campaign failed for user ID '.$user->id, [
                    'error' => $e->getMessage()
                ]);

                continue;
            }
        }
    }
}

// class SendEmail implements ShouldQueue
// {
//     use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, MailTrait;

//     public $subject;
//     public $content;
//     public $user;
//     /**
//      * Create a new job instance.
//      *
//      * @return void
//      */
//     public function __construct($user, $subject, $content)
//     {
//         $this->user = $user;
//         $this->subject = $subject;
//         $this->content = $content;
//     }

//     /**
//      * Execute the job.
//      *
//      * @return void
//      */
//     public function handle()
//     {
//         $users = $this->user;
//         if($users == null){
//             $users = User::where("email", "!=", null)->get();
//         }

//         try {
//             foreach($users as $user){
//                 $this->sendMail($user->email, $user->profile->username ?? $user->phone_number, $this->subject, $this->content);
//             }
//         } catch (\Throwable $th) {
//             //throw $th;
//         }

//     }
// }
