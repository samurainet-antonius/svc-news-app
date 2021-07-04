<?php

namespace App\Http\Controllers\v1;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Utils\FuncValidation;
use App\Utils\FuncRandom;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UsersController extends BaseController
{
    use FuncValidation, FuncRandom;

    // admin

    public function show(Request $request){

        try {

            // set limit and offset
            $offset = $request->has('offset') ? $request->offset : '1';
            $limit = $request->has('limit') ? $request->limit : '10';
            $role = $request->has('role') ? $request->role : 'reader' ;

            // rumus pagiantion
            $page = ($offset - 1)*$limit;

            // get users where limit and offset
            $users = DB::table('users')
            ->select('username','email','name','photo','active','uuid','created_at','updated_at')
            ->where('role',$role)
            ->whereNull('deleted_at')
            ->limit($limit)
            ->offset($page)
            ->orderBy('created_at','DESC')->get();

            // count user
            $count = $users->count();

            // call function alluser
            $allUser = $this->allUser($role);

            // count all Users
            $total = count($allUser);

            // send response success
            return response()->json([
                'status'        => 200,
                'total'         => $total,
                'count'         => $count,
                'limit'         => $limit,
                'offset'        => $page,
                'body'          => $users,
            ],200);

        } catch (\Exception $e) {

            // send response error
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);   
        }
    }

    private function allUser($role = null){
        try {

            $user = DB::table('users')
            ->select('username','email','name','photo','active','uuid','created_at','updated_at');
            if($role != null){
                $user->where('role',$role);
            }
            $users = $user->whereNull('deleted_at')
            ->orderBy('created_at','DESC')->get();

            return $users;

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function create(Request $request){

        $formParams = $request->all();

        $rules = array(
            'username' => 'required|unique:users|max:100',
            'email' => 'required|email|unique:users|max:200',
            'name' => 'required',
            'password' => 'required',
            'role' => 'required',
            'photo' => 'required|image',
            'active' => 'required'
        );

        $errors = $this->validation($formParams, $rules);

        if ($errors != null){
            return response()->json([
                'status' => 400,
                'message' => $errors
            ],400);
        }


        $keyData = "assets/img/users";

        $file = $request->file('photo');
        $filename = time().'_'.$file->getClientOriginalName();
        

        DB::beginTransaction();

        try{

            $formParams['id'] = $this->uuid_short();
            $formParams['uuid'] = $this->uuid();

            $file->move($keyData,$filename);

            $formParams['photo'] = $keyData."/".$filename;

            $formParams['password'] = Hash::make($request->password,[
                'memory' => 1024,
                'time' => 2,
                'threads' => 2,
            ]);

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

            unlink($keyData."/".$filename);

            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);
        }
    }

    private function detail($uuid){
        $user = DB::table('users')
            ->select('id','username','email','name','photo','active','uuid','created_at','updated_at')
            ->where('uuid',$uuid)
            ->whereNull('deleted_at')
            ->orderBy('created_at','DESC')->first();

        return $user;
    }

    public function update(Request $request,$uuid){

        $user = $this->detail($uuid);

        if(!isset($user) || empty($user)){
            return response()->json([
                'status' => 400,
                'message' => 'User not listed.'
            ],400);
        }
        

        $formParams = $request->all();

        $rules = array(
            'username' => 'required|max:100|unique:users,username,'.$user->id,
            'email' => 'required|email|max:200|unique:users,email,'.$user->id,
            'name' => 'required',
            'role' => 'required',
            'photo' => 'image',
            'active' => 'required'
        );

        $errors = $this->validation($formParams, $rules);

        if ($errors != null){
            return response()->json([
                'status' => 400,
                'message' => $errors
            ],400);
        }


        $keyData = "assets/img/users";

        if($request->hasFile('photo')){
            unlink($user->photo);
            $file = $request->file('photo');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move($keyData,$filename);
            $formParams['photo'] = $keyData."/".$filename;
        }
        

        DB::beginTransaction();

        try{

            if($request->has('password') && $request->password != null && $request->password != ''){
                $formParams['password'] = Hash::make($request->password,[
                    'memory' => 1024,
                    'time' => 2,
                    'threads' => 2,
                ]);
            }else{
                unset($formParams['password']);
            }

            DB::table('users')
                ->where('uuid',$uuid)
                ->update($formParams);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'User successfully updated.'
            ],200);

        }catch(\Exception $e){
            DB::rollback();

            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);
        }
    }

    public function delete($uuid){

        $user = $this->detail($uuid);

        if(!isset($user) || empty($user)){
            return response()->json([
                'status' => 400,
                'message' => 'User not listed'
            ],400);
        }

        try{
            $formParams['deleted_at'] = date("Y-m-d H:i:s");
            DB::table('users')
                ->where('uuid',$uuid)
                ->update($formParams);

            return response()->json([
                'status' => 200,
                'message' => 'User successfully deleted.'
            ],200);

        }catch(\Exception $e){
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);
        }
    }

    public function achieve(Request $request){

        $role = $request->has('role') ? $request->role : 'reader';
        $active = $request->has('active') ? $request->active : '1';

        try {

            $users = DB::table('users')
            ->select('username','email','name','photo','active','uuid','created_at','updated_at')
            ->where('role',$role)
            ->where('active',$active)
            ->whereNull('deleted_at')
            ->orderBy('created_at','DESC')->get();

            return response()->json([
                'status'        => 200,
                'total'         => $users->count(),
            ],200);

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    // admin author reader

    public function profile(){

        $auth = Auth::user();
        $user = $this->detail($auth->uuid);

        if(!isset($user) || empty($user)){
            return response()->json([
                'status' => 400,
                'message' => 'User not found.'
            ],400);
        }

        unset($user->id);
        unset($user->created_at);
        unset($user->updated_at);
        unset($user->uuid);

        return response()->json([
            'status'        => 200,
            'body'         => array($user),
        ],200);
    }

    public function updateProfile(Request $request){

        $auth = Auth::user();

        $user = $this->detail($auth->uuid);

        if(!isset($user) || empty($user)){
            return response()->json([
                'status' => 400,
                'message' => 'User not found.'
            ],400);
        }
        

        $formParams = $request->all();

        $rules = array(
            'email' => 'required|email|max:200|unique:users,email,'.$user->id,
            'name' => 'required',
            'gender' => 'required',
            'address' => 'required',
            'nohp' => 'required',
            'photo' => 'image',
        );

        $errors = $this->validation($formParams, $rules);

        if ($errors != null){
            return response()->json([
                'status' => 400,
                'message' => $errors
            ],400);
        }


        $keyData = "assets/img/users";

        if($request->hasFile('photo')){
            unlink($user->photo);
            $file = $request->file('photo');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move($keyData,$filename);
            $formParams['photo'] = $keyData."/".$filename;
        }
        

        DB::beginTransaction();

        try{

            DB::table('users')
                ->where('uuid',$user->uuid)
                ->update($formParams);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Profile successfully updated.'
            ],200);

        }catch(\Exception $e){
            DB::rollback();

            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);
        }
    }

    public function updatePassword(Request $request){

        $auth = Auth::user();

        $user = $this->detail($auth->uuid);

        if(!isset($user) || empty($user)){
            return response()->json([
                'status' => 400,
                'message' => 'User not found.'
            ],400);
        }
        
        $formParams = $request->all();

        $rules = array(
            'old_password' => 'required',
            'new_password' => 'required',
        );

        $errors = $this->validation($formParams, $rules);

        if ($errors != null){
            return response()->json([
                'status' => 400,
                'message' => $errors
            ],400);
        }

        $check = Hash::check($request->old_password, $auth->password);

        if(!$check){
            return response()->json([
                'status' => 400,
                'message' => 'old password does not match.'
            ],400);
        }

        DB::beginTransaction();

        try{

            $data['password'] = Hash::make($request->new_password,[
                'memory' => 1024,
                'time' => 2,
                'threads' => 2,
            ]);

            DB::table('users')
                ->where('uuid',$user->uuid)
                ->update($data);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Password changed successfully'
            ],200);

        }catch(\Exception $e){
            DB::rollback();

            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);
        }
    }
}