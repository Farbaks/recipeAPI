<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Recipe;
use App\Favorite;
use App\Follow;
use App\User;
use App\Comment;

class CommentController extends Controller
{
    //
    // Function to comment on a recipe
    public function commentRecipe(Request $request) 
    {
        $data= Validator::make($request->all(), [
            'userId' => 'required|numeric',
            'recipeId' => 'required|numeric',
            'comment' => 'required|string'
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
        Comment::create([
            'recipeId' => $request->recipeId,
            'userId' => $request->userId,
            'comment' => $request->comment
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Comment has been saved',
            'data'=> []
        ], 200);
    }

    // Function to comment on a reply
    public function commentReply(Request $request) 
    {
        $data= Validator::make($request->all(), [
            'userId' => 'required|numeric',
            'parentId' => 'required|numeric',
            'recipeId' => 'required|numeric',
            'comment' => 'required|string'
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

        if(Comment::find($request->parentId) == '') {
            return response()->json([
                'status' => 400,
                'message' => 'Reply does not exist',
                'data'=> []
            ], 400);
        }
        Comment::create([
            'comment' => $request->comment,
            'recipeId' => $request->recipeId,
            'parentId' => $request->parentId,
            'userId' => $request->userId,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Comment has been saved',
            'data'=> []
        ], 200);
    }

    // Function to list all comments under a recipe or reply
    public function commentList(Request $request) 
    {
        $data= Validator::make($request->all(), [
            'parentId' => 'nullable',
            'recipeId' => 'required|numeric',
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

        if(Recipe::find($request->recipeId) == '') {
            return response()->json([
                'status' => 400,
                'message' => 'Recipe does not exist',
                'data'=> []
            ], 400);
        }

        if($request->parentId != null && Comment::find($request->parentId) == '') {
            return response()->json([
                'status' => 400,
                'message' => 'Reply does not exist',
                'data'=> []
            ], 400);
        }
        $totalCount = Comment::where('parentId', $request->parentId)->where('recipeId', $request->recipeId)->count();
        $list = Comment::where('parentId', $request->parentId)->where('recipeId', $request->recipeId)->orderBy('created_at', 'desc')
                                                                                                        ->offset($request->offset)
                                                                                                        ->limit($request->limit)
                                                                                                        ->get();
        foreach($list as $comment) {
            $comment->commentCount =  Comment::where('parentId', $comment->id)->where('recipeId', $comment->recipeId)->count();
            $comment->saveCount = Favorite::where('commentId', $comment->id)->where('recipeId', null)->count();
            $comment->savedByThisUser = false;
            if(Favorite::where('commentId', $comment->id)->where('userId', $request->userID)->where('recipeId', null)->count() == 1){
                $comment->savedByThisUser = true;
            }
            $comment->poster = $this->getPoster($request, $comment->userId);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Comments have been fetched',
            'data'=> [
                'offset' => $request->offset,
                'limit' => $request->limit,
                'number of comments' => $totalCount,
                'comments' => $list
            ]
        ], 200);
    }

    // Function to delete a comment on a recipe
    public function deleteComment(Request $request) 
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
        $comment = Comment::find($request->commentId);

        if($comment == '') {
            return response()->json([
                'status' => 400,
                'message' => 'Comment does not exist',
                'data'=> []
            ], 400);
        }

        if($comment->userId != $request->userId) {
            return response()->json([
                'status' => 400,
                'message' => 'Not authorized to carry out this action',
                'data'=> []
            ], 400);
        }

        $comment->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Comment has been deleted',
            'data'=> []
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
