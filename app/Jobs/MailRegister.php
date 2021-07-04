<?php

namespace App\Jobs;
use Illuminate\Support\Facades\Mail;

class MailRegister extends Job
{
    private $data;

    public function __construct($formParams)
    {
        $this->data = $formParams;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $data = $this->data;

        $html = "<h1>Selamat Datang, ".$data['name']."!</h1>
        <p>Terima Kasih telah mendaftar sebagai ".$data['role']." pada News App</p>
        <p>Silahkan lakukan aktivasi akun anda, dengan mengklik link dibawah ini.</p>
        <a href='".$data['url_verify']."/".$data['token']."'>Activate account</a>
        <p>Terima Kasih.</p>";

        try{

            Mail::send([],[], function($message) use($data,$html) {
                $message->to($data['email'], $data['name'])->subject('Register');
                $message->setBody($html,'text/html');
            });
            
            return true;

        }catch(\Exception $e){

            return false;
        }
    }
}
