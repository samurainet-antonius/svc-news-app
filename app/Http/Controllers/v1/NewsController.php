<?php
namespace App\Http\Controllers\v1;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Utils\FuncValidation;
use App\Utils\FuncRandom;
use App\Jobs\FormulaViews;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class NewsController extends BaseController
{
    use FuncValidation, FuncRandom;

    public function show(Request $request){

        try {

            // set limit and offset
            $offset = $request->has('offset') ? $request->offset : '1';
            $limit = $request->has('limit') ? $request->limit : '10';

            // rumus pagination
            $page = ($offset - 1)*$limit;

            // get news where limit and offset
            $news = DB::table('news')
            ->join('category','news.category_id','category.id')
            ->leftJoin('users','news.users_id','users.id')
            ->select('news_title','news_url','category_name','category_url','news_content','news_image','users.name as author','news.active','news.views','news.share','news.uuid','news.created_at','news.updated_at')
            ->whereNull('news.deleted_at')
            ->limit($limit)
            ->offset($page)
            ->orderBy('news.created_at','DESC')->get();

            // count news
            $count = $news->count();

            // call function allnews
            $allNews = $this->allNews();

            // count all news
            $total = count($allNews);

            // send response success
            return response()->json([
                'status'        => 200,
                'total'         => $total,
                'count'         => $count,
                'limit'         => $limit,
                'offset'        => $page,
                'body'          => $news,
            ],200);

        } catch (\Exception $e) {

            // send response error
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);   
        }
    }

    private function allNews(){
        try {

            $news = DB::table('news')
            ->join('category','news.category_id','category.id')
            ->leftJoin('users','news.users_id','users.id')
            ->select('news_title','news_url','category_name','category_url','news_content','news_image','users.name as author','news.active','news.views','news.share','news.uuid','news.created_at','news.updated_at')
            ->whereNull('news.deleted_at')
            ->orderBy('news.created_at','DESC')->get();

            return $news;

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    private function allNewsAuthor($users_id){
        try {

            $news = DB::table('news')
            ->join('category','news.category_id','category.id')
            ->leftJoin('users','news.users_id','users.id')
            ->select('news_title','news_url','category_name','category_url','news_content','news_image','users.name as author','news.active','news.views','news.share','news.uuid','news.created_at','news.updated_at')
            ->where('users_id',$users_id)
            ->whereNull('news.deleted_at')
            ->orderBy('news.created_at','DESC')->get();

            return $news;

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    private function allNewsActive($column=null,$values=null){
        try {

            $news = DB::table('news')
            ->join('category','news.category_id','category.id')
            ->leftJoin('users','news.users_id','users.id')
            ->select('news_title','news_url','category_name','category_url','news_content','news_image','users.name as author','news.active','news.views','news.share','news.uuid','news.created_at','news.updated_at')
            ->where('news.active',1)
            ->where(function($q) use($column,$values){
                if($column != null && $values != null){
                    $q->where($column,$values);
                }
            })
            ->whereNull('news.deleted_at')
            ->orderBy('news.created_at','DESC')->get();

            return $news;

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function create(Request $request){

        $auth = Auth::user();

        $formParams = $request->all();

        $rules = array(
            'news_title' => 'required|unique:news|max:100',
            'news_url' => 'required|unique:news|max:200',
            'news_content' => 'required',
            'news_image' => 'required|image',
            'active' => 'required'
        );

        $errors = $this->validation($formParams, $rules);

        if ($errors != null){
            return response()->json([
                'status' => 400,
                'message' => $errors
            ],400);
        }


        $keyData = "assets/img/news";

        $file = $request->file('news_image');
        $filename = time().'_'.$file->getClientOriginalName();
        

        DB::beginTransaction();

        try{

            $category = $this->category($request->category);

            unset($formParams['category']);

            $formParams['id'] = $this->uuid_short();
            $formParams['uuid'] = $this->uuid();
            $formParams['users_id'] = $auth->id;
            $formParams['category_id'] = $category->id;

            $file->move($keyData,$filename);

            $formParams['news_image'] = $keyData."/".$filename;

            DB::table('news')
                ->insert($formParams);

            DB::commit();

            $formParams['category'] = $category->uuid;
            unset($formParams['category_id']);
            unset($formParams['id']);
            unset($formParams['users_id']);

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
        $news = DB::table('news')
            ->join('category','news.category_id','category.id')
            ->leftJoin('users','news.users_id','users.id')
            ->select('news.id','news_title','news_url','category_name','category_url','news_content','news_image','users.name as author','news.active','views','share','news.uuid','news.created_at','news.updated_at')
            ->where('news.uuid',$uuid)
            ->whereNull('news.deleted_at')
            ->orderBy('created_at','DESC')->first();

        return $news;
    }

    public function update(Request $request, $uuid){

        $news = $this->detail($uuid);

        if(!isset($news) || empty($news)){
            return response()->json([
                'status' => 400,
                'message' => 'News not listed'
            ],400);
        }

        $formParams = $request->all();

        $rules = array(
            'news_title' => 'required|max:100|unique:news,news_title,'.$news->id,
            'news_url' => 'required|max:200|unique:news,news_url,'.$news->id,
            'news_content' => 'required',
            'news_image' => 'image',
            'active' => 'required'
        );

        $errors = $this->validation($formParams, $rules);

        if ($errors != null){
            return response()->json([
                'status' => 400,
                'message' => $errors
            ],400);
        }


        $keyData = "assets/img/news";


        if($request->hasFile('news_image')){
            unlink($news->news_image);
            $file = $request->file('news_image');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move($keyData,$filename);
            $formParams['news_image'] = $keyData."/".$filename;
        }
        

        DB::beginTransaction();

        try{
            $category = $this->category($request->category);

            unset($formParams['category']);
            $formParams['category_id'] = $category->id;

            DB::table('news')
                ->where('uuid',$uuid)
                ->update($formParams);

            DB::commit();

            $formParams['category'] = $category->uuid;
            unset($formParams['category_id']);
            unset($formParams['id']);

            return response()->json([
                'status' => 200,
                'message' => 'News successfully updated'
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

        $news = $this->detail($uuid);

        if(!isset($news) || empty($news)){
            return response()->json([
                'status' => 400,
                'message' => 'News not listed'
            ],400);
        }

        try{
            $formParams['deleted_at'] = date("Y-m-d H:i:s");
            DB::table('news')
                ->where('uuid',$uuid)
                ->update($formParams);

            return response()->json([
                'status' => 200,
                'message' => 'News successfully deleted'
            ],200);

        }catch(\Exception $e){
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);
        }
    }

    public function achieve(Request $request){

        $active = $request->has('active') ? $request->active : '1';
        $role = $request->has('role') ? $request->role : 'admin';

        try {

            $news = DB::table('news')
            ->join('category','news.category_id','category.id')
            ->leftJoin('users','news.users_id','users.id')
            ->select('news_title','news_url','category_name','category_url','news_content','news_image','users.name as author','news.active','news.uuid','news.created_at','news.updated_at')
            ->where('news.active',$active)
            ->where('users.role',$role)
            ->whereNull('news.deleted_at')
            ->orderBy('news.created_at','DESC')->get();

            return response()->json([
                'status'        => 200,
                'total'         => $news->count(),
            ],200);

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    // author

    public function newsAuthor(Request $request){

        $auth = Auth::user();

        try {

            // set limit and offset
            $offset = $request->has('offset') ? $request->offset : '1';
            $limit = $request->has('limit') ? $request->limit : '10';

            // rumus pagination
            $page = ($offset - 1)*$limit;

            // get news where limit and offset
            $news = DB::table('news')
            ->join('category','news.category_id','category.id')
            ->leftJoin('users','news.users_id','users.id')
            ->select('news_title','news_url','category_name','category_url','news_content','news_image','users.name as author','news.active','news.views','news.share','news.uuid','news.created_at','news.updated_at')
            ->where('users_id',$auth->id)
            ->whereNull('news.deleted_at')
            ->limit($limit)
            ->offset($page)
            ->orderBy('news.created_at','DESC')->get();

            // count news
            $count = $news->count();

            // call function allnews
            $allNews = $this->allNewsAuthor($auth->id);

            // count all news
            $total = count($allNews);

            // send response success
            return response()->json([
                'status'        => 200,
                'total'         => $total,
                'count'         => $count,
                'limit'         => $limit,
                'offset'        => $page,
                'body'          => $news,
            ],200);

        } catch (\Exception $e) {

            // send response error
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);   
        }
    }


    // reader

    private function category($uuid){
        $category = DB::table('category')
            ->select('id','category_name','category_url','active','uuid','created_at','updated_at')
            ->where('uuid',$uuid)
            ->whereNull('deleted_at')
            ->orderBy('created_at','DESC')->first();

        return $category;
    }

    public function activeNews(Request $request){

        try {

            // set limit and offset
            $offset = $request->has('offset') ? $request->offset : '1';
            $limit = $request->has('limit') ? $request->limit : '10';

            // rumus pagination
            $page = ($offset - 1)*$limit;

            // get news where limit and offset
            $news = DB::table('news')
            ->join('category','news.category_id','category.id')
            ->leftJoin('users','news.users_id','users.id')
            ->select('news_title','news_url','category_name','category_url','news_content','news_image','users.name as author','news.active','news.views','news.share','news.uuid','news.created_at','news.updated_at')
            ->where('news.active',1)
            ->whereNull('news.deleted_at')
            ->limit($limit)
            ->offset($page)
            ->orderBy('news.created_at','DESC')
            ->get();

            // count news
            $count = $news->count();

            // call function allnews
            $allNews = $this->allNewsActive();

            // count all news
            $total = count($allNews);

            // send response success
            return response()->json([
                'status'        => 200,
                'total'         => $total,
                'count'         => $count,
                'limit'         => $limit,
                'offset'        => $page,
                'body'          => $news,
            ],200);

        } catch (\Exception $e) {

            // send response error
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);   
        }
    }

    public function newsView(Request $request){

        try {

            // set limit and offset
            $offset = $request->has('offset') ? $request->offset : '1';
            $limit = $request->has('limit') ? $request->limit : '10';

            // sorting views
            $views = ($request->has('views')) ? $request->views : 'DESC';

            // rumus pagination
            $page = ($offset - 1)*$limit;

            // get news where limit and offset
            $news = DB::table('news')
            ->join('category','news.category_id','category.id')
            ->leftJoin('users','news.users_id','users.id')
            ->select('news_title','news_url','category_name','category_url','news_content','news_image','users.name as author','news.active','news.views','news.share','news.uuid','news.created_at','news.updated_at')
            ->where('news.active',1)
            ->whereNull('news.deleted_at')
            ->limit($limit)
            ->offset($page)
            ->orderBy('news.views',$views)
            ->get();

            // count news
            $count = $news->count();

            // call function allnews
            $allNews = $this->allNewsActive();

            // count all news
            $total = count($allNews);

            // send response success
            return response()->json([
                'status'        => 200,
                'total'         => $total,
                'count'         => $count,
                'limit'         => $limit,
                'offset'        => $page,
                'body'          => $news,
            ],200);

        } catch (\Exception $e) {

            // send response error
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);   
        }
    }

    public function newsCategory(Request $request){

        $uuid = ($request->has('category')) ? $request->category : null;

        $category = $this->category($uuid);

        try {

            // set limit and offset
            $offset = $request->has('offset') ? $request->offset : '1';
            $limit = $request->has('limit') ? $request->limit : '10';

            // rumus pagination
            $page = ($offset - 1)*$limit;

            // get news where limit and offset
            $news = DB::table('news')
            ->join('category','news.category_id','category.id')
            ->leftJoin('users','news.users_id','users.id')
            ->select('news_title','news_url','category_name','category_url','news_content','news_image','users.name as author','news.active','news.views','news.share','news.uuid','news.created_at','news.updated_at')
            ->where([
                ['news.active',1],
                ['category_id',$category->id]
            ])
            ->whereNull('news.deleted_at')
            ->limit($limit)
            ->offset($page)
            ->orderBy('news.created_at','DESC')
            ->get();

            // count news
            $count = $news->count();

            // call function allnews
            $allNews = $this->allNewsActive('category_id',$category->id);

            // count all news
            $total = count($allNews);

            // send response success
            return response()->json([
                'status'        => 200,
                'total'         => $total,
                'count'         => $count,
                'limit'         => $limit,
                'offset'        => $page,
                'body'          => $news,
            ],200);

        } catch (\Exception $e) {

            // send response error
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);   
        }
    }

    public function newsDetail($uuid){

        try{

            $post = $this->detail($uuid);
            $auth = Auth::user();

            $post->views_id = $this->uuid_short();
            $post->views_uuid = $this->uuid();

            $date = Carbon::now()->addSeconds(1);
            Queue::later($date, new FormulaViews($post,$auth));

            $news = DB::table('news')
                ->join('category','news.category_id','category.id')
                ->leftJoin('users','news.users_id','users.id')
                ->select('news_title','news_url','category_name','category_url','news_content','news_image','users.name as author','news.active','news.views','news.share','news.uuid','news.created_at','news.updated_at')
                ->where('news.uuid',$uuid)
                ->whereNull('news.deleted_at')
                ->orderBy('created_at','DESC')->first();

                return response()->json([
                    'status'        => 200,
                    'body'          => array($news),
                ],200);

        } catch (\Exception $e) {

            // send response error
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);   
        }
    }

    public function share($uuid){

        DB::beginTransaction();

        try{

            $news = $this->detail($uuid);

            $count['share'] = $news->share+1;

            DB::table('news')
                ->where('uuid',$uuid)
                ->update($count);
            
            DB::commit();

            return response()->json([
                'status'        => 200,
                'message'          => 'news successfully shared.',
            ],200);

        } catch (\Exception $e) {
            DB::rollback();

            // send response error
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);   
        }
    }
}