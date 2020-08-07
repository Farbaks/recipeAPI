<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Recipe;
use App\Category;
use App\Ingredient;
use App\Step;
use App\Follow;
use App\Favorite;
use App\Comment;
use App\Rating;
use App\User;

class FavoriteController extends Controller
{
    //
    // Function to favorite a recipe
    public function favoriteRecipe(Request $request) 
    {
        $data= Validator::make($request->all(), [
            'userId' => 'required|numeric',
            'recipeId' => 'required|numeric'
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'All fields are required',
                'data'=> []
            ], 400);
        }

        if($request->userId != $request->userID){
            return response()->json([
                'status' => 400,
                'message' => 'Not authorized to carry out this action',
                'data'=> []
            ], 400);
        }

        if(Recipe::find($request->recipeId) == '') {
            return response()->json([
                'status' => 400,
                'message' => 'Recipe does not exist',
                'data'=> []
            ], 400);
        }
        $checkfavorite = Favorite::where('recipeId', $request->recipeId)->where('userId', $request->userId)->count();
        if($checkfavorite != 0) {
            return response()->json([
                'status' => 400,
                'message' => 'Recipe has already been saved',
                'data'=> []
            ], 400);
        }
        Favorite::create([
            'recipeId' => $request->recipeId,
            'userId' => $request->userId
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Recipe has been saved',
            'data'=> []
        ], 200);
    }

    // Function to favorite a comment
    public function favoriteComment(Request $request) 
    {
        $data= Validator::make($request->all(), [
            'userId' => 'required|numeric',
            'commentId' => 'required|numeric'
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'All fields are required',
                'data'=> []
            ], 400);
        }

        if($request->userId != $request->userID){
            return response()->json([
                'status' => 400,
                'message' => 'Not authorized to carry out this action',
                'data'=> []
            ], 400);
        }

        if(Comment::find($request->commentId) == '') {
            return response()->json([
                'status' => 400,
                'message' => 'Comment does not exist',
                'data'=> []
            ], 400);
        }
        $checkfavorite = Favorite::where('commentId', $request->commentId)->where('userId', $request->userId)->count();
        if($checkfavorite != 0) {
            return response()->json([
                'status' => 400,
                'message' => 'Comment has already been saved',
                'data'=> []
            ], 400);
        }
        Favorite::create([
            'commentId' => $request->commentId,
            'userId' => $request->userId
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Comment has been saved',
            'data'=> []
        ], 200);
    }

    // Function to favorite a recipe
    public function removeFavoriteRecipe(Request $request) 
    {
        $data= Validator::make($request->all(), [
            'userId' => 'required|numeric',
            'recipeId' => 'required|numeric'
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'All fields are required',
                'data'=> []
            ], 400);
        }

        if($request->userId != $request->userID){
            return response()->json([
                'status' => 400,
                'message' => 'Not authorized to carry out this action',
                'data'=> []
            ], 400);
        }

        if(Recipe::find($request->recipeId) == '') {
            return response()->json([
                'status' => 400,
                'message' => 'Recipe does not exist',
                'data'=> []
            ], 400);
        }
        $checkfavorite = Favorite::where('recipeId', $request->recipeId)->where('userId', $request->userId)->get();
        if($checkfavorite->count() == 0) {
            return response()->json([
                'status' => 400,
                'message' => 'Recipe has not been saved',
                'data'=> []
            ], 400);
        }

        Favorite::where('recipeId', $request->recipeId)->where('userId', $request->userId)->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Recipe has been unsaved',
            'data'=> []
        ], 200);
    }

    // Function to favorite a recipe
    public function removeFavoriteComment(Request $request) 
    {
        $data= Validator::make($request->all(), [
            'userId' => 'required|numeric',
            'commentId' => 'required|numeric'
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'All fields are required',
                'data'=> []
            ], 400);
        }

        if($request->userId != $request->userID){
            return response()->json([
                'status' => 400,
                'message' => 'Not authorized to carry out this action',
                'data'=> []
            ], 400);
        }

        if(Comment::find($request->commentId) == '') {
            return response()->json([
                'status' => 400,
                'message' => 'Comment does not exist',
                'data'=> []
            ], 400);
        }
        $checkfavorite = Favorite::where('commentId', $request->commentId)->where('userId', $request->userId)->get();
        if($checkfavorite->count() == 0) {
            return response()->json([
                'status' => 400,
                'message' => 'Comment has not been saved',
                'data'=> []
            ], 400);
        }

        Favorite::where('commentId', $request->commentId)->where('userId', $request->userId)->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Comment has been unsaved',
            'data'=> []
        ], 200);
    }

    // Function to favorite a recipe
    public function FavoriteList(Request $request) 
    {
        $data= Validator::make($request->all(), [
            'userId' => 'required|numeric',
            'offset' => 'required|numeric',
            'limit' => 'required|numeric'
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'All fields are required',
                'data'=> []
            ], 400);
        }

        $totalCount = Favorite::where('userId', $request->userId)->where('commentId', null)->count();
        $list = Favorite::where('favorites.userId', $request->userId)->where('commentId', null)->join('recipes', 'favorites.recipeId', '=', 'recipes.id')
                                                                                        ->select('recipes.*')                 
                                                                                        ->orderBy('created_at', 'desc')
                                                                                        ->offset($request->offset)
                                                                                        ->limit($request->limit)
                                                                                        ->get();
        foreach($list as $recipe) {
            $recipe->RatingCount = Rating::where('recipeId', $recipe->id)->count();
            $recipe->averageRating = round(Rating::where('recipeId', $recipe->id)->get('rating')->average('rating'), 1);
            $recipe->commentCount = Comment::where('recipeId', $recipe->id)->where('parentId', null)->count();
            $recipe->saveCount = Favorite::where('recipeId', $recipe->id)->where('commentId', null)->count();
            $recipe->savedByThisUser = false;
            if(Favorite::where('recipeId', $recipe->id)->where('userId', $request->userID)->where('commentId', null)->count() == 1){
                $recipe->savedByThisUser = true;
            }
            $recipe->category = Category::find($recipe->categoryId)->only(['id', 'name']);
            $recipe->poster = $this->getPoster($request, $recipe->userId);
            $recipe->ingredients = Ingredient::where('recipeId', $recipe->id)->get();
            $recipe->steps = Step::where('recipeId', $recipe->id)->get();
        }

        return response()->json([
            'status' => 200,
            'message' => 'Saved recipes have been fetched',
            'data'=> [
                'offset' => $request->offset,
                'limit' => $request->limit,
                'number of saved recipes' => $totalCount,
                'saved recipes' => $list
            ]
        ], 200);
    }

    // function to fetch one user
    public function getPoster($request, $id)
    {   
        $user = User::find($id);
        if($user == ''){
            return response()->json([
                'status' => 400,
                'message' => 'This user does not exist',
                'data' => []
            ], 400);
        }
        $user->followers = Follow::where('userId', $user->id)->count();
        $user->following = Follow::where('followedBy', $user->id)->count();
        $user->recipes = Recipe::where('userId', $user->id)->count();
        $user->saveCount = Favorite::where('userId', $user->id)->where('commentId', null)->count();
        $user->followingThisUser = false;
        $user->followedByThisUser =false;
        if(Follow::where('followedBy', $request->userID)->where('userId', $user->id)->count() == 1){
            $user->followingThisUser = true;
        }
        if(Follow::where('followedBy', $user->id)->where('userId', $request->userID)->count() == 1){
            $user->followedByThisUser = true;
        }
        return $user;
    }
}
