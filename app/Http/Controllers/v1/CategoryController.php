<?php

namespace App\Http\Controllers\v1;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Utils\FuncValidation;
use App\Utils\FuncRandom;
use Illuminate\Support\Facades\Auth;

class CategoryController extends BaseController
{
    use FuncValidation, FuncRandom;

    public function show(Request $request){

        try {

            // set limit and offset
            $offset = $request->has('offset') ? $request->offset : '1';
            $limit = $request->has('limit') ? $request->limit : '10';

            // rumus pagiantion
            $page = ($offset - 1)*$limit;

            // get category where limit and offset
            $categories = DB::table('category')
            ->select('category_name','category_url','active','uuid','created_at','updated_at')
            ->whereNull('deleted_at')
            ->limit($limit)
            ->offset($page)
            ->orderBy('created_at','DESC')->get();

            // count category
            $count = $categories->count();

            // call function allcategory
            $allCategory = $this->allCategory();

            // count all category
            $total = count($allCategory);

            // send response success
            return response()->json([
                'status'        => 200,
                'total'         => $total,
                'count'         => $count,
                'limit'         => $limit,
                'offset'        => $page,
                'body'          => $categories,
            ],200);

        } catch (\Exception $e) {

            // send response error
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);   
        }
    }

    private function allCategory(){
        try {

            $categories = DB::table('category')
            ->select('category_name','category_url','active','uuid','created_at','updated_at')
            ->whereNull('deleted_at')
            ->orderBy('created_at','DESC')->get();

            return $categories;

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function create(Request $request){

        $formParams = $request->all();

        $rules = array(
            'category_name' => 'required|unique:category|max:100',
            'category_url' => 'required|unique:category|max:200',
            'active' => 'required'
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

            DB::table('category')
                ->insert($formParams);
                
            unset($formParams['id']);

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

    private function detail($uuid){
        $category = DB::table('category')
            ->select('id','category_name','category_url','active','uuid','created_at','updated_at')
            ->where('uuid',$uuid)
            ->whereNull('deleted_at')
            ->orderBy('created_at','DESC')->first();

        return $category;
    }

    public function update(Request $request, $uuid){

        $category = $this->detail($uuid);

        if(!isset($category) || empty($category)){
            return response()->json([
                'status' => 400,
                'message' => 'Category not listed'
            ],400);
        }

        $formParams = $request->all();

        $rules = array(
            'category_name' => 'required|max:100|unique:category,category_name,'.$category->id,
            'category_url' => 'required|max:200|unique:category,category_url,'.$category->id,
            'active' => 'required'
        );

        $errors = $this->validation($formParams, $rules);

        if ($errors != null){
            return response()->json([
                'status' => 400,
                'message' => $errors
            ],400);
        }
        
        try{

            DB::table('category')
                ->where('uuid',$uuid)
                ->update($formParams);

            return response()->json([
                'status' => 200,
                'message' => 'Categories successfully updated'
            ],200);

        }catch(\Exception $e){
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);
        }
    }

    public function delete($uuid){

        $category = $this->detail($uuid);

        if(!isset($category) || empty($category)){
            return response()->json([
                'status' => 400,
                'message' => 'Category not listed'
            ],400);
        }

        try{
            $formParams['deleted_at'] = date("Y-m-d H:i:s");
            DB::table('category')
                ->where('uuid',$uuid)
                ->update($formParams);

            return response()->json([
                'status' => 200,
                'message' => 'Category successfully deleted'
            ],200);

        }catch(\Exception $e){
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);
        }
    }
}
