<?php

namespace App\TraitsFolder;


use App\Models\BasicSetting;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

trait MailTrait
{
    public function sendMail($email,$name,$subject,$text, $template = "sendmail"){

        $mail_val = [
            'email' => $email,
            'name' => $name,
            'g_email' => config('mail.from.address'),
            'g_title' => getSiteSettings("title"),
            'subject' => $subject,
        ];

        $body = str_replace("{{name}}",$name,$text);
        $body = str_replace("{{message}}",$text,$body);
        $code = str_replace("{{message}}",$text,$body);

        Mail::send('emails.'.$template, ['body'=> $body, 'name'=>$name, 'code' =>$code], function ($m) use ($mail_val) {
            $m->from($mail_val['g_email'], $mail_val['g_title']);
            $m->to($mail_val['email'], $mail_val['name'])->subject($mail_val['subject']);
        });


    }


    public function sendCMail($email,$name,$subject,$text){

        $mail_val = [
            'email' => $email,
            'name' => $name,
            'g_email' => config('mail.from.address'),
            'g_title' => getSiteSettings("title"),
            'subject' => $subject,
        ];

        $body = str_replace("{{name}}",$name,$text);
        $body = str_replace("{{message}}",$text,$body);

        Mail::send('emails.newverifymail', ['name'=>$name, 'code' => $text], function ($m) use ($mail_val) {
            $m->from($mail_val['g_email'], $mail_val['g_title']);
            $m->to($mail_val['email'], $mail_val['name'])->subject($mail_val['subject']);
        });

    }


    public function sendPasswordReset($email,$name,$subject,$text){

        $mail_val = [
            'email' => $email,
            'name' => $name,
            'g_email' => config('mail.from.address'),
            'g_title' => getSiteSettings("title"),
            'subject' => $subject,
        ];

        $body = str_replace("{{name}}",$name,$text);
        $body = str_replace("{{message}}",$text,$body);

        Mail::send('emails.newpasswordemail', ['name'=>$name, 'password' => $text], function ($m) use ($mail_val) {
            $m->from($mail_val['g_email'], $mail_val['g_title']);
            $m->to($mail_val['email'], $mail_val['name'])->subject($mail_val['subject']);
        });

    }


    public function sendWelcomeMail($email,$user){

        $mail_val = [
            'email' => $email,
            'g_email' => config('mail.from.address'),
            'g_title' => getSiteSettings("title"),
            'subject' => "Welcome to ".getSiteSettings("title"),
        ];

        Mail::send('emails.welcome', ['user'=>$user], function ($m) use ($mail_val) {
            $m->from($mail_val['g_email'], $mail_val['g_title']);
            $m->to($mail_val['email'])->subject($mail_val['subject']);
        });

    }
    public function sendSms($to,$text){
        $basic = BasicSetting::first();
        $appi = $basic->smsapi;
        $text = urlencode($text);
        $appi = str_replace("{{number}}",$to,$appi);
        $appi = str_replace("{{message}}",$text,$appi);
        $result = file_get_contents($appi);
    }

    public function sendOTP($phone, $code){
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://app.smartsmssolutions.com/io/api/client/v1/smsotp/send/',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => [
        "token" => "7K1JIKmnTLGJm2YcVk0IU8ZNyXsa12AsPMsWneRrpqGHTfKEwX",
        "phone" => $phone,
        "otp" => $code,
        "sender" => "OTP NG",
        "template_code" => "7153792424",
        "ref_id" => "bizapp",
        ],
            )
        );

            $response_ = curl_exec($curl);

            curl_close($curl);
            $json = json_decode($response_);

            //dd($json);
        } catch (\Throwable $th) {
            //throw $th;
        }

    }



    public function callMeForOTP($phone, $code){
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://app.smartsmssolutions.com/io/api/client/v1/voiceotp/send/',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => [
        "token" => "7K1JIKmnTLGJm2YcVk0IU8ZNyXsa12AsPMsWneRrpqGHTfKEwX",
        "phone" => $phone,
        "otp" => $code,
        "class" => "AEYBPV3VKA",
        "ref_id" => "bizapp",
        ],
            )
        );

            $response_ = curl_exec($curl);

            curl_close($curl);
            $json = json_decode($response_);

            //dd($json);
        } catch (\Throwable $th) {
            //throw $th;
        }

    }

    public function sendContact($email,$name,$subject,$text,$phone)
    {

        $mail_val = [
            'email' => $email,
            'name' => $name,
            'g_email' => config('mail.from.address'),
            'g_title' => getSiteSettings("title"),
            'subject' => 'Contact Message - '.$subject,
        ];

        $body = str_replace("Hi",'Hi. I\'m',$text);
        $body = str_replace("{{name}}",$name." - ".$phone,$body);
        $body = str_replace("{{message}}",$text,$body);

        Mail::send('emails.sendemail', ['body'=>$body, 'name'=>$name], function ($m) use ($mail_val) {
            $m->from($mail_val['email'], $mail_val['name']);
            $m->to($mail_val['g_email'], $mail_val['g_title'])->subject($mail_val['subject']);
        });
    }
    public function userPasswordReset($email)
    {

        $user = User::whereEmail($email)->first();
        $mail_val = [
            'email' => $email,
            'name' => $user->name,
            'g_email' => config('mail.from.address'),
            'g_title' => getSiteSettings("title"),
            'subject' => 'Password Reset Request',
        ];


        $reset = DB::table('password_resets')->whereEmail($email)->count();
        $token = Str::random(40);
        $bToken = bcrypt($token);
        if ($reset == 0){
            DB::table('password_resets')->insert(
                ['email' => $email, 'token' => $bToken]
            );
            $url = route('password.reset',$token);
            Mail::send('emails.reset-email', ['name' => $user->profile->username,'link'=>$url], function ($m) use ($mail_val) {
                $m->from($mail_val['g_email'], $mail_val['g_title']);
                $m->to($mail_val['email'], $mail_val['name'])->subject($mail_val['subject']);
            });
        }else{
            $user = User::whereEmail($email)->first();
            DB::table('password_resets')
                ->where('email', $email)
                ->update(['email' => $email, 'token' => $bToken]);
            $url = route('password.reset',$token);
            Mail::send('emails.reset-email', ['name' => $user->name,'link'=>$url,], function ($m) use ($mail_val) {
                $m->from($mail_val['g_email'], $mail_val['g_title']);
                $m->to($mail_val['email'], $mail_val['name'])->subject($mail_val['subject']);
            });
        }
    }

}
