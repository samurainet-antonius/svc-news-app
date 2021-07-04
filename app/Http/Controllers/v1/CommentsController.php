<?php
namespace App\Http\Controllers\v1;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Utils\FuncValidation;
use App\Utils\FuncRandom;
use Illuminate\Support\Facades\Auth;

class CommentsController extends BaseController
{
    use FuncValidation, FuncRandom;

    public function show(Request $request,$uuid){

        try {

            // set limit and offset
            $offset = $request->has('offset') ? $request->offset : '1';
            $limit = $request->has('limit') ? $request->limit : '10';

            // rumus pagination
            $page = ($offset - 1)*$limit;

            // get comment where limit and offset
            $comment = DB::table('comment')
            ->join('news','comment.news_id','news.id')
            ->join('users','comment.users_id','users.id')
            ->select('comment.comment','users.name','comment.uuid','comment.created_at','comment.updated_at')
            ->where('news.uuid',$uuid)
            ->whereNull('comment.deleted_at')
            ->limit($limit)
            ->offset($page)
            ->orderBy('comment.created_at','DESC')->get();

            // count comment
            $count = $comment->count();

            // call function allComment
            $allComment = $this->allComment($uuid);

            // count all comment
            $total = $allComment->count();

            // send response success
            return response()->json([
                'status'        => 200,
                'total'         => $total,
                'count'         => $count,
                'limit'         => $limit,
                'offset'        => $page,
                'body'          => $comment,
            ],200);

        } catch (\Exception $e) {

            // send response error
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ],500);   
        }
    }

    private function allComment($uuid){
        try {

            $news = DB::table('comment')
            ->join('news','comment.news_id','news.id')
            ->join('users','comment.users_id','users.id')
            ->select('comment.comment','users.name','comment.uuid','comment.created_at','comment.updated_at')
            ->where('news.uuid',$uuid)
            ->whereNull('comment.deleted_at')
            ->orderBy('comment.created_at','DESC')->get();

            return $news;

        } catch (\Exception $e) {
            return $e->getMessage();
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

    public function create(Request $request, $uuid){

        $news = $this->detail($uuid);

        if(!isset($news) || empty($news)){
            return response()->json([
                'status' => 400,
                'message' => 'News not listed'
            ],400);
        }

        $auth = Auth::user();

        $formParams = $request->all();

        $rules = array(
            'comment' => 'required',
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
            $formParams['users_id'] = $auth->id;
            $formParams['news_id'] = $news->id;

            DB::table('comment')
                ->insert($formParams);

            DB::commit();

            unset($formParams['id']);
            unset($formParams['news_id']);
            unset($formParams['users_id']);

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
}