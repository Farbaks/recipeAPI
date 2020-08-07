<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('users/signup', 'UserController@signup');
Route::post('users/signin', 'UserController@signin');



Route::middleware('user-auth')->group(function () {
    // User api
    Route::get('users/', 'UserController@getAllUsers');
    Route::get('users/{id}', 'UserController@getOneUser');
    Route::post('users/', 'UserController@updateUser');
    Route::post('users/profilepicture', 'UserController@updateUserProfilePicture');
    Route::post('users/removeprofilepicture', 'UserController@deleteUserProfilePicture');
    Route::post('users/reset', 'UserController@'); //Not done
    Route::post('users/notification', 'UserController@'); //Not done
    Route::post('users/signout', 'UserController@signout'); 
    Route::delete('users/', 'UserController@'); //Not done

    // follow api
    Route::post('users/follow', 'FollowController@followUser');
    Route::post('users/unfollow', 'FollowController@unfollowUser');
    Route::post('users/following/{id}', 'FollowController@listFollowing');
    Route::post('users/followers/{id}', 'FollowController@listFollowers');

    // Category api
    Route::post('category/', 'CategoryController@createCategory');
    Route::post('category/update', 'CategoryController@updateCategory');
    Route::get('category', 'CategoryController@getAllCategories'); 
    Route::delete('category', 'CategoryController@'); //Not done

    // Recipe api
    Route::post('recipe/createrecipe', 'RecipeController@createRecipe');
    Route::get('recipe/{id}', 'RecipeController@getOneRecipe');
    Route::post('recipe/category/{id}', 'RecipeController@getCategoryRecipes');
    Route::post('recipe/user/{id}', 'RecipeController@getUserRecipes');
    Route::post('recipe/following', 'RecipeController@getFollowingRecipes');
    Route::post('recipe/remove', 'RecipeController@deleteRecipe');
    Route::post('recipe/rate', 'RecipeController@rateRecipe');

    // save api
    Route::post('favorite/recipe', 'FavoriteController@favoriteRecipe');
    Route::post('favorite/comment', 'FavoriteController@favoriteComment'); 
    Route::post('favorite/recipe/remove', 'FavoriteController@removeFavoriteRecipe');
    Route::post('favorite/comment/remove', 'FavoriteController@removeFavoriteComment');
    Route::post('favorite/recipe/list', 'FavoriteController@FavoriteList'); 

    // Comment api
    Route::post('comment/recipe', 'CommentController@commentRecipe'); 
    Route::post('comment/reply', 'CommentController@commentReply'); 
    Route::post('comment/list', 'CommentController@commentList');
    Route::post('comment/remove', 'CommentController@deleteComment'); 

    // Search api
    Route::post('search/recipe', 'SearchController@searchRecipe'); 
    Route::post('search/user', 'SearchController@searchUser'); 
});



