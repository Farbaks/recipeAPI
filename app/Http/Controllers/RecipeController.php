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

class RecipeController extends Controller
{
    // function to sign up new users
    public function createRecipe(Request $request)
    {   
        $data= Validator::make($request->all(), [
            'name' => 'required|max:255',
            'tags' => 'required|string',
            'description'=> 'required',
            'duration' => 'required',
            'difficulty' => 'required',
            'categoryId' => 'required|numeric',
            'userId' => 'required|numeric',
            // 'picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'ingredients' => 'required',
            'steps' => 'required'
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

        $recipe = new Recipe;
        $recipe->name = $request->name;
        $recipe->tags = $request->tags;
        $recipe->description = $request->description;
        $recipe->duration = $request->duration;
        $recipe->difficulty = $request->difficulty;
        $recipe->categoryId = $request->categoryId;
        $recipe->userId = $request->userId;

        $recipe->save();
        
        // $name = $recipe->id.".jpg";
        // $path = Storage::disk('public')->putFileAs('recipes', $request->file('picture'), $name, 'public');

        // $recipe->update([
        //     'pictureUrl' => Storage::disk('public')->url('recipes/'.$name)
        // ]);
        
        foreach ($request->ingredients as $ingredient) {
            Ingredient::create([
                'name' => $ingredient['name'],
                'quantity' => $ingredient['quantity'],
                'recipeId' => $recipe->id
            ]);
        }

        foreach ($request->steps as $step) {
            Step::create([
                'name' => $step['name'],
                'number' => $step['number'],
                'recipeId' => $recipe->id
            ]);
        }

        $savedRecipe = Recipe::find($recipe->id);
        $savedRecipe->category = Category::find($savedRecipe->categoryId)->only(['id', 'name']);
        $savedRecipe->poster = User::find($savedRecipe->userId)->only(['id', 'name', 'pictureUrl']);
        $savedRecipe->ingredients = Ingredient::where('recipeId', $recipe->id)->get();
        $savedRecipe->steps = Step::where('recipeId', $recipe->id)->get();
        
        return response()->json([
            'status' => 200,
            'message' => 'Recipe  has been created',
            'data' => [
                $savedRecipe
            ]
        ], 200);
    }

    // function to fetch one recipe
    public function getOneRecipe(Request $request, $id)
    {     
        $recipe = Recipe::find($id);
        if($recipe == '') {
            return response()->json([
                'status' => 200,
                'message' => 'Recipe was not found',
                'data' => []
            ], 400);
        }
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
        $recipe->ingredients = Ingredient::where('recipeId', $id)->get();
        $recipe->steps = Step::where('recipeId', $id)->get();

        return response()->json([
            'status' => 200,
            'message' => 'Recipe has been fetched',
            'data' => $recipe
        ], 200);
    }

    // function to fetch recipes by category
    public function getCategoryRecipes(Request $request, $id)
    {   
        $data= Validator::make($request->all(), [
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
        $totalCount = Recipe::where('categoryId', $id)->count();
        $recipes = Recipe::where('categoryId', $id)->orderBy('created_at', 'desc')
                                                    ->offset($request->offset)
                                                    ->limit($request->limit)
                                                    ->get();
        if($totalCount == 0) {
            return response()->json([
                'status' => 200,
                'message' => 'No Recipes were found in this category',
                'data' => []
            ], 400);
        }
        foreach($recipes as $recipe) {
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
            'message' => 'Recipes have been fetched',
            'data' => [
                'offset' => $request->offset,
                'limit' => $request->limit,
                'number of recipes' => $totalCount,
                'recipes' => $recipes
            ]
        ], 200);
    }

    // function to fetch recipes by user
    public function getUserRecipes(Request $request, $id)
    {   
        $data= Validator::make($request->all(), [
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
        $totalCount = Recipe::where('userId', $id)->count();
        $recipes = Recipe::where('userId', $id)->orderBy('created_at', 'desc')
                                                    ->offset($request->offset)
                                                    ->limit($request->limit)
                                                    ->get();
        if($totalCount == 0) {
            return response()->json([
                'status' => 200,
                'message' => 'No Recipes have been created by this user',
                'data' => []
            ], 400);
        }
        foreach($recipes as $recipe) {
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
            'message' => 'Recipes have been fetched',
            'data' => [
                'offset' => $request->offset,
                'limit' => $request->limit,
                'number of recipes' => $totalCount,
                'recipes' => $recipes
            ]
        ], 200);
    }

    // function to fetch recipes by followed users
    public function getFollowingRecipes(Request $request)
    {   
        $data= Validator::make($request->all(), [
            'offset' => 'required|numeric',
            'limit' => 'required|numeric',
            'userId' => 'required|numeric'
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
        $followList = Follow::where('followedBy', $request->userId)->select('userId')->get();

        if($followList->count() == 0){
            return response()->json([
                'status' => 200,
                'message' => 'This user is not following anyone',
                'data'=> []
            ], 200);
        }

        $totalCount = Recipe::whereIn('userId', $followList)->count();
        $recipes = Recipe::whereIn('userId', $followList)->orderBy('created_at', 'desc')
                                                            ->offset($request->offset)
                                                            ->limit($request->limit)
                                                            ->get();
        if($totalCount == 0) {
            return response()->json([
                'status' => 200,
                'message' => 'No Recipes have been created by followed users',
                'data' => []
            ], 400);
        }

        foreach($recipes as $recipe) {
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
            'message' => 'Recipes have been fetched',
            'data' => [
                'offset' => $request->offset,
                'limit' => $request->limit,
                'number of recipes' => $totalCount,
                'recipes' => $recipes
            ]
        ], 200);
    }

    // function to rate a recipe
    public function rateRecipe(Request $request)
    {
        $data= Validator::make($request->all(), [
            'userId' => 'required|numeric',
            'recipeId' => 'required|numeric',
            'rating'=> 'required|numeric|max:5',
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

        if(Rating::where('recipeId', $request->recipeId)->where('userId', $request->userId)->count() != 0) {
            return response()->json([
                'status' => 400,
                'message' => 'Recipe has already been rated by this user',
                'data'=> []
            ], 400);
        }

        Rating::create([
            'rating' => $request->rating,
            'userId' => $request->userId,
            'recipeId' => $request->recipeId
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Recipe has been rated',
            'data'=> []
        ], 200);
    }

    // function to delete recipe
    public function deleteRecipe(Request $request)
    {   
        $data= Validator::make($request->all(), [
            'userId' => 'required|max:255|numeric',
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
        if(Recipe::find($request->recipeId)->delete()) {
            return response()->json([
                'status' => 200,
                'message' => 'Recipe has been deleted',
                'data' => []
            ], 200);
        }

        
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
