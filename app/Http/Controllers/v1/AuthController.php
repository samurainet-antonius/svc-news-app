<?php
namespace App\Http\Controllers\v1;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Utils\FuncValidation;
use App\Utils\FuncRandom;
use Illuminate\Support\Facades\Hash;
use App\Jobs\MailRegister;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AuthController extends BaseController
{
    use FuncValidation, FuncRandom;

    public function registerAuthor(Request $request){

        $formParams = $request->all();

        $rules = array(
            'username' => 'required|unique:users|max:100',
            'email' => 'required|email|unique:users|max:200',
            'name' => 'required',
            'experience' => 'required',
            'url_verify' => 'required',
        );

        $errors = $this->validation($formParams, $rules);

        if ($errors != null){
            return response()->json([
                'status' => 400,
                'message' => $errors
            ],400);
        }

        DB::beginTransaction();

        try{

            $formParams['id'] = $this->uuid_short();
            $formParams['uuid'] = $this->uuid();
            $formParams['role'] = 'author';
            $formParams['token'] = $this->uuid();

            $formParams['password'] = Hash::make($request->password,[
                'memory' => 1024,
                'time' => 2,
                'threads' => 2,
            ]);

            $date = Carbon::now()->addSeconds(1);
            Queue::later($date, new MailRegister($formParams));

            unset($formParams['url_verify']);

            DB::table('users')
                ->insert($formParams);

            DB::commit();

            unset($formParams['id']);
            unset($formParams['password']);

            return response()->json([
                'status' => 201,
                'body' => $formParams
            ],201);

        }catch(\Exception $e){
            DB::rollback();

            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);
        }
    }

    public function registerReader(Request $request){

        $formParams = $request->all();

        $rules = array(
            'username' => 'required|unique:users|max:100',
            'email' => 'required|email|unique:users|max:200',
            'name' => 'required',
            'url_verify' => 'required',
        );

        $errors = $this->validation($formParams, $rules);

        if ($errors != null){
            return response()->json([
                'status' => 400,
                'message' => $errors
            ],400);
        }

        DB::beginTransaction();

        try{

            $formParams['id'] = $this->uuid_short();
            $formParams['uuid'] = $this->uuid();
            $formParams['role'] = 'reader';
            $formParams['token'] = $this->uuid();

            $formParams['password'] = Hash::make($request->password,[
                'memory' => 1024,
                'time' => 2,
                'threads' => 2,
            ]);

            $date = Carbon::now()->addSeconds(1);
            Queue::later($date, new MailRegister($formParams));

            unset($formParams['url_verify']);

            DB::table('users')
                ->insert($formParams);

            DB::commit();

            unset($formParams['id']);
            unset($formParams['password']);

            return response()->json([
                'status' => 201,
                'body' => $formParams
            ],201);

        }catch(\Exception $e){
            DB::rollback();

            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);
        }
    }

    public function verifyEmail($token){

        $user = DB::table('users')
                ->where('token',$token)
                ->whereNull('deleted_at')
                ->first();

        if(!isset($user) || empty($user)){
            return response()->json([
                'status' => 400,
                'message' => 'User not listed or token is expired.'
            ],400);
        }

        $formParams['token'] = $this->uuid();
        $formParams['active'] = 1;

        DB::beginTransaction();

        try{

            DB::table('users')
                ->where('uuid',$user->uuid)
                ->update($formParams);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'account verified successfully.'
            ],200);

        }catch(\Exception $e){
            DB::rollback();

            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);
        }


        
    }

    public function login(Request $request){

        $formParams = $request->all();

        $rules = array(
            'username' => 'required',
            'password' => 'required',
        );

        $errors = $this->validation($formParams, $rules);

        if ($errors != null){
            return response()->json([
                'status' => 400,
                'message' => $errors
            ],400);
        }

        $user = DB::table('users')
                    ->where([
                        ['username',$request->username],
                        ['active',1],
                    ])->whereNull('deleted_at')
                    ->first();

        if(!isset($user) || empty($user)){
            return response()->json([
                'status' => 400,
                'message' => 'User not listed.'
            ],400);
        }

        
        $credentials = $request->only(['username', 'password']);

        if (! $token = Auth::attempt($credentials)) {
            return response()->json([
                'status' => 400,
                'message' => 'email or password is wrong.'
            ],400);
        }

        return $this->respondWithToken($token);
    }

    public function refresh() {
        try {
            return $this->respondWithToken(auth()->refresh());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized.'
            ],401);
        }
    }

    public function logout() {

        try {

            auth()->logout();
            return response()->json([
                'status' => 200,
                'message' => 'User loged out.'
            ],200);
            
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    protected function respondWithToken($token){

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60
        ], 200);
    }

}