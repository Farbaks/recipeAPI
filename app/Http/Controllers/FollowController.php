<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Follow;
use App\Recipe;

class FollowController extends Controller
{
    //function to follow a user
    public function followUser(Request $request)
    {
        $data= Validator::make($request->all(), [
            'userId' => 'required|numeric',
            'followedBy' => 'required|numeric'
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'All fields are required',
                'data'=> []
            ], 400);
        }

        if($request->followedBy != $request->userID){
            return response()->json([
                'status' => 400,
                'message' => 'Not authorized to carry out this action',
                'data'=> []
            ], 400);
        }

        if(User::find($request->userId) == ''){
            return response()->json([
                'status' => 400,
                'message' => 'User does not exist',
                'data'=> []
            ], 400);
        }

        if($request->userId == $request->followedBy){
            return response()->json([
                'status' => 400,
                'message' => 'User cannot be followed',
                'data'=> []
            ], 400);
        }

        if(Follow::where('userId', $request->userId)->where('followedBy', $request->followedBy)->count() == 1){
            return response()->json([
                'status' => 400,
                'message' => 'User is already being followed',
                'data'=> []
            ], 400);
        }

        $follow = Follow::create([
            'userId' => $request->userId,
            'followedBy' => $request->followedBy
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'User has been followed',
            'data'=> []
        ], 200);
    }

    //function to unfollow a user
    public function unfollowUser(Request $request)
    {
        $data= Validator::make($request->all(), [
            'userId' => 'required|numeric',
            'followedBy' => 'required|numeric'
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'All fields are required',
                'data'=> []
            ], 400);
        }

        if($request->followedBy != $request->userID){
            return response()->json([
                'status' => 400,
                'message' => 'Not authorized to carry out this action',
                'data'=> []
            ], 400);
        }

        if(Follow::where('userId', $request->userId)->where('followedBy', $request->followedBy)->count() == 0){
            return response()->json([
                'status' => 400,
                'message' => 'User is not being followed',
                'data'=> []
            ], 400);
        }

        Follow::where('userId', $request->userId)->where('followedBy', $request->followedBy)->delete();

        return response()->json([
            'status' => 200,
            'message' => 'User has been unfollowed',
            'data'=> []
        ], 200);
    }

    //function to fetch list of followers
    public function listFollowers(Request $request, $id)
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
        $totalCount = Follow::where('userId', $request->id)->count();
        $list = Follow::where('userId', $request->id)->join('users', 'follows.followedBy', '=', 'users.id')
                                                    ->select('users.id', 'users.name', 'users.email', 'users.phoneNumber', 'users.pictureUrl', 'users.bio')
                                                    ->offset($request->offset)
                                                    ->limit($request->limit)
                                                    ->get();
        foreach($list as $user){
            $user->followers = Follow::where('userId', $user->id)->count();
            $user->following = Follow::where('followedBy', $user->id)->count();
            $user->recipes = Recipe::where('userId', $user->id)->count();
            $user->followingThisUser = false;
            $user->followedByThisUser = false;
            if(Follow::where('followedBy', $request->userID)->where('userId', $user->id)->count() == 1){
                $user->followingThisUser = true;
            }
            if(Follow::where('followedBy', $user->id)->where('userId', $request->userID)->count() == 1){
                $user->followedByThisUser = true;
            }
        }
        if ($totalCount == 0) {
            return response()->json([
                'status' => 200,
                'message' => 'This user has no follower',
                'data'=> []
            ], 200);
        }

        return response()->json([
            'status' => 200,
            'message' => 'List of followers has been fetched',
            'data'=> [
                'offset' => $request->offset,
                'limit' => $request->limit,
                'number of followers' => $totalCount,
                'followers' => $list
            ]
        ], 200);
    }

    //function to fetch list of users being followed
    public function listFollowing(Request $request, $id)
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
        $totalCount = Follow::where('followedBy', $request->id)->count();
        $list = Follow::where('followedBy', $request->id)->join('users', 'follows.userId', '=', 'users.id')
                                                    ->select('users.id', 'users.name', 'users.email', 'users.phoneNumber', 'users.pictureUrl', 'users.bio')
                                                    ->offset($request->offset)
                                                    ->limit($request->limit)
                                                    ->get();
        foreach($list as $user){
            $user->followers = Follow::where('userId', $user->id)->count();
            $user->following = Follow::where('followedBy', $user->id)->count();
            $user->recipes = Recipe::where('userId', $user->id)->count();
            $user->followingThisUser = false;
            $user->followedByThisUser = false;
            if(Follow::where('followedBy', $request->userID)->where('userId', $user->id)->count() == 1){
                $user->followingThisUser = true;
            }
            if(Follow::where('followedBy', $user->id)->where('userId', $request->userID)->count() == 1){
                $user->followedByThisUser = true;
            }
        }

        if ($totalCount == 0) {
            return response()->json([
                'status' => 200,
                'message' => 'This user is not following anyone',
                'data'=> []
            ], 200);
        }

        return response()->json([
            'status' => 200,
            'message' => 'List of users being followed has been fetched',
            'data'=> [
                'offset' => $request->offset,
                'limit' => $request->limit,
                'number of followed' => $totalCount,
                'following' => $list
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
