<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Recipe;
use App\Category;
use App\Ingredient;
use App\Step;
use App\Follow;
use App\Favorite;
use App\Comment;
use App\Rating;
use App\User;

class SearchController extends Controller
{
    // function to search for a recipe
    public function searchRecipe(Request $request) 
    {
        $data= Validator::make($request->all(), [
            'search' => 'required|string',
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
        $totalCount = Recipe::where('name', 'like',"%$request->search%")
                            ->orWhere('tags', 'like',"%$request->search%")
                            ->orWhere('description', 'like',"%$request->search%")->count();
        $list = Recipe::where('name', 'like',"%$request->search%")
                        ->orWhere('tags', 'like',"%$request->search%")
                        ->orWhere('description', 'like',"%$request->search%")
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
            'message' => 'Recipes for query have been fetched',
            'data'=> [
                'offset' => $request->offset,
                'limit' => $request->limit,
                'number of recipes' => $totalCount,
                'recipes' => $list
            ]
        ], 200);
    }

    // function to search for a user
    public function searchUser(Request $request) 
    {
        $data= Validator::make($request->all(), [
            'search' => 'required|string',
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
        $totalCount = User::where('name', 'like',"%$request->search%")
                            ->orWhere('email', 'like',"%$request->search%")
                            ->orWhere('bio', 'like',"%$request->search%")->count();
        $users = User::where('name', 'like',"%$request->search%")
                        ->orWhere('email', 'like',"%$request->search%")
                        ->orWhere('bio', 'like',"%$request->search%")
                        ->offset($request->offset)
                        ->limit($request->limit)
                        ->get();
        foreach($users as $user){
            $user->followers = Follow::where('userId', $user->id)->count();
            $user->following = Follow::where('followedBy', $user->id)->count();
            $user->recipes = Recipe::where('userId', $user->id)->count();
            $user->saveCount = Favorite::where('userId', $user->id)->where('commentId', null)->count();
            $user->followingThisUser = false;
            $user->followedByThisUser = false;
            if(Follow::where('followedBy', $request->userID)->where('userId', $user->id)->count() == 1){
                $user->followingThisUser = true;
            }
            if(Follow::where('followedBy', $user->id)->where('userId', $request->userID)->count() == 1){
                $user->followedByThisUser = true;
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'Recipes for query have been fetched',
            'data'=> [
                'offset' => $request->offset,
                'limit' => $request->limit,
                'number of users' => $totalCount,
                'users' => $users
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
