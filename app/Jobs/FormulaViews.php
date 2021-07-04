<?php

namespace App\Jobs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FormulaViews extends Job
{
    private $data;
    private $auth;

    public function __construct($formParams,$user)
    {
        $this->data = $formParams;
        $this->auth = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $data = $this->data;
        $auth = $this->auth;

        DB::beginTransaction();
        try{

            $views = DB::table('views')
                        ->where([
                            ['users_id',$auth->id],
                            ['news_id',$data->id]
                        ])->first();

            if(!isset($views) || empty($views)){

                $count['views'] = $data->views+1;

                DB::table('news')
                    ->where('uuid',$data->uuid)
                    ->update($count);

                $formViews = array(
                                'users_id' => $auth->id,
                                'news_id' => $data->id,
                                'id' => $data->views_id,
                                'uuid' => $data->views_uuid,
                            );

                DB::table('views')
                    ->insert($formViews);
            }
            
            DB::commit();

            return true;

        }catch(\Exception $e){
            DB::rollback();
            return false;
        }
    }
}
