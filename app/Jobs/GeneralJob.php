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
use iSmartMonnify;
use iSmartPaystack;

class GeneralJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, MailTrait;

    public $data;
    public $type;
    public $user;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $data, $type)
    {
        $this->user = $user;
        $this->data = $data;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->type == "createVirtualAccount"){
            
            //(new iSmartMonnify())->generateVirtualAccount($this->user->id);
            
            (new iSmartPaystack())->createDedicatedVirtualAccount($this->user);
        }

        if($this->type == "deleteAllVirtualAccount"){
            $virtualAccount = VirtualAccount::all();
            if(count($virtualAccount) > 0){
                foreach ($virtualAccount as $account) {
                    $reference = $account->account_reference;
                   $del =  $account->delete();
                   if($del){
                    try {
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                   }

                }
            }
        }


        if($this->type == "sendMail"){

            if($this->user == "all"){
                $user = User::where("email_verify", "1")->get();
            }elseif($this->user == "email"){
                $user = User::where("email", "1")->first();
            }

            if(count($user) > 0){
                foreach ($user as $item) {
                    //send welcome mail
            try {
                $this->sendWelcomeMail($item->email,$item);
            } catch (\Throwable $th) {
                //throw $th;
            }
                }
            }
        }
    }
}
