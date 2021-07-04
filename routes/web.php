<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// action
$action = array(
    'get' => '/',
    'post' => '/create',
    'put' => '/update',
    'delete' => '/delete',
    'achieve' => '/achieve',
    'register' => '/register',
    'verify' => '/verify/{token}',
    'login' => '/login',
    'refresh' => '/refresh',
    'logout' => '/logout'
);

// version 1
$router->group(['prefix' => 'public/api/v1'], function() use($router,$action){

    $router->group(['prefix' => 'master','middleware' => 'auth'], function() use($router,$action){

        // category
        $router->group(['prefix' => 'category'], function() use($router,$action){
            $router->get($action['get'],'v1\CategoryController@show');
            $router->post($action['post'],'v1\CategoryController@create');
            $router->put($action['put']."/{uuid}",'v1\CategoryController@update');
            $router->delete($action['delete']."/{uuid}",'v1\CategoryController@delete');
        });

        // news
        $router->group(['prefix' => 'news'], function() use($router,$action){
            $router->get($action['get'],'v1\NewsController@show');
            $router->post($action['post'],'v1\NewsController@create');
            $router->put($action['put']."/{uuid}",'v1\NewsController@update');
            $router->delete($action['delete']."/{uuid}",'v1\NewsController@delete');
        });

        // users
        $router->group(['prefix' => 'users'], function() use($router,$action){
            $router->get($action['get'],'v1\UsersController@show');
            $router->post($action['post'],'v1\UsersController@create');
            $router->put($action['put']."/{uuid}",'v1\UsersController@update');
            $router->delete($action['delete']."/{uuid}",'v1\UsersController@delete');
        });

    });

    $router->group(['prefix' => 'report','middleware' => 'auth'],function() use($router,$action){

        // users
        $router->group(['prefix' => 'users'], function() use($router,$action){
            $router->get($action['achieve'],'v1\UsersController@achieve');
        });

        // news
        $router->group(['prefix' => 'news'], function() use($router,$action){
            $router->get($action['achieve'],'v1\NewsController@achieve');
        });
    });


    $router->group(['prefix' => 'auth'],function() use($router,$action){

        // reader
        $router->group(['prefix' => 'reader'], function() use($router,$action){
            $router->post($action['register'],'v1\AuthController@registerReader');
        });

        // author
        $router->group(['prefix' => 'author'], function() use($router,$action){
            $router->post($action['register'],'v1\AuthController@registerAuthor');
        });

        // verify
        $router->get($action['verify'],'v1\AuthController@verifyEmail');

        // login
        $router->post($action['login'],'v1\AuthController@login');


        $router->group(['middleware' => 'auth'], function () use ($router,$action) {
            // refresh
            $router->get($action['refresh'],'v1\AuthController@refresh');

            // logout
            $router->get($action['logout'],'v1\AuthController@logout');
        });
    });

    // user
    $router->group(['prefix' => 'users','middleware' => 'auth'], function() use($router,$action){
        $router->get($action['get']."profile",'v1\UsersController@profile');
        $router->put($action['put']."/profile",'v1\UsersController@updateProfile');
        $router->put($action['put']."/password",'v1\UsersController@updatePassword');
    });


    // news
    $router->group(['prefix' => 'news','middleware' => 'auth'], function() use($router,$action){
        $router->get($action['get'],'v1\NewsController@activeNews');
        $router->get($action['get']."{uuid}",'v1\NewsController@newsDetail');
        $router->get($action['get'].'sorting/views','v1\NewsController@newsView');
        $router->get($action['get'].'sorting/category','v1\NewsController@newsCategory');
        $router->get($action['get']."share/{uuid}",'v1\NewsController@share');
        $router->get($action['get']."{uuid}/comments",'v1\CommentsController@show');
        $router->post($action['get']."{uuid}/comments",'v1\CommentsController@create');
    });

    // author
    $router->group(['prefix' => 'author','middleware' => 'auth'], function() use($router,$action){
        
        // news
        $router->group(['prefix' => 'news'], function() use($router,$action){
            $router->get($action['get'],'v1\NewsController@newsAuthor');
            $router->post($action['post'],'v1\NewsController@create');
            $router->put($action['put']."/{uuid}",'v1\NewsController@update');
            $router->delete($action['delete']."/{uuid}",'v1\NewsController@delete');
        });

    });
});

