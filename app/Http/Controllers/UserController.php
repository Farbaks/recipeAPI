<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\User;
use App\Follow;
use App\Recipe;
use App\Favorite;

class UserController extends Controller
{
    // function to sign up new users
    public function signup(Request $request)
    {   
        $data= Validator::make($request->all(), [
            'name' => 'required|max:255',
            'email' => 'required',
            'phoneNumber'=> 'required',
            'bio' => 'required',
            'password' => 'required',
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'All fields are required',
                'data'=> []
            ], 400);
        }

        $checkUser = User::where('email', $request->email)->first();

        if($checkUser != "") {
            return response()->json([
                'status' => 400,
                'message' => 'Email account already exists',
                'data'=> []
            ], 400);
        }

        $checkUser = User::where('phoneNumber', $request->phoneNumber)->first();

        if($checkUser != "") {
            return response()->json([
                'status' => 400,
                'message' => 'Phone number already exists',
                'data'=> []
            ], 400);
        }
        
        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phoneNumber = $request->phoneNumber;
        $user->bio = $request->bio;
        $user->password = Hash::make($request->password);

        $user->save();

        $user->followers = Follow::where('userId', $user->id)->count();
        $user->following = Follow::where('followedBy', $user->id)->count();
        $user->recipes = Recipe::where('userId', $user->id)->count();
        return response()->json([
            'status' => 200,
            'message' => 'User account has been created',
            'apiToken' => $this->generateAPI($user->id),
            'data' => [
                $user
            ]
        ], 200);
    }

    // Function to sign in users
    public function signin(Request $request)
    {   
        // Validate request
        $data= Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'All fields are required',
                'data'=> []
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        
        // Check if user email exists
        if($user == "") {
            return response()->json([
                'status' => 400,
                'message' => 'Email account does not exist',
                'data' => []
            ], 400);
        }

        // Check if password is correct
        if (!Hash::check($request->password, $user->password)) {
            // The passwords don't match...
            return response()->json([
                'status' => 400,
                'message' => 'Email or password incorrect',
                'data' => []
            ], 400);
        }
        $user->followers = Follow::where('userId', $user->id)->count();
        $user->following = Follow::where('followedBy', $user->id)->count();
        $user->recipes = Recipe::where('userId', $user->id)->count();
        // Generate api_token
        return response()->json([
            'status' => 200,
            'message' => 'Login succesful',
            'apiToken' => $this->generateAPI($user->id),
            'data' => [
                $user
            ]
        ], 200);


    }

    // Function to sign out users
    public function signout(Request $request)
    {   
        $user = User::find($request->userID)->update([
            'apiToken' => NULL
            ]);

        return response()->json([
            'status' => 200,
            'message' => 'Logout succesful',
            'data' => []
        ], 200);


    }

    // function to fetch all users
    public function getAllUsers(Request $request)
    {   
        $users = User::all();
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
            'message' => 'User accounts have been fetched',
            'data' => $users
        ], 200);
    }

    // function to fetch one user
    public function getOneUser(Request $request, $id)
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
        return response()->json([
            'status' => 200,
            'message' => 'User accounts have been fetched',
            'data' => $user
        ], 200);
    }
    
    // function to update user details
    public function updateUser(Request $request)
    {
        $data= Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required|max:255',
            'email' => 'required',
            'phoneNumber'=> 'required',
            'bio' => 'required',
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'All fields are required',
                'data'=> []
            ], 400);
        }

        if($request->id != $request->userID){
            return response()->json([
                'status' => 400,
                'message' => 'Not authorized to carry out this action',
                'data'=> []
            ], 400);
        }

        $checkUser = User::where('email', $request->email)->first();

        if($checkUser != "" && $checkUser->id != $request->id) {
            return response()->json([
                'status' => 400,
                'message' => 'Email account already exists',
                'data'=> []
            ], 400);
        }

        $checkUser = User::where('phoneNumber', $request->phoneNumber)->first();

        if($checkUser != "" && $checkUser->id != $request->id) {
            return response()->json([
                'status' => 400,
                'message' => 'Phone number already exists',
                'data'=> []
            ], 400);
        }

        User::find($request->id)->update([
            'name' => $request->name,
            'email' => $request->email,
            'phoneNumber'=> $request->phoneNumber,
            'bio' => $request->bio,
            'pictureUrl' => $request->pictureUrl
        ]);
        $user = User::find($request->id);
        $user->followers = Follow::where('userId', $user->id)->count();
        $user->following = Follow::where('followedBy', $user->id)->count();
        $user->recipes = Recipe::where('userId', $user->id)->count();
        $user->saveCount = Favorite::where('userId', $user->id)->where('commentId', null)->count();
        return response()->json([
            'status' => 200,
            'message' => 'User account has been updated',
            'data' => [
                $user
            ]
        ], 200);
    }

    // function to update user profile picture
    public function updateUserProfilePicture(Request $request)
    {
        $data= Validator::make($request->all(), [
            'id' => 'required',
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'All fields are required',
                'data'=> []
            ], 400);
        }

        if($request->id != $request->userID){
            return response()->json([
                'status' => 400,
                'message' => 'Not authorized to carry out this action',
                'data'=> []
            ], 400);
        }

        $name = $request->id.".jpg";
        $path = Storage::disk('public')->putFileAs('avatars', $request->file('avatar'), $name, 'public');

        User::find($request->id)->update([
            'pictureUrl' => Storage::disk('public')->url('avatars/'.$name)
        ]);
        $user = User::find($request->id);
        $user->followers = Follow::where('userId', $user->id)->count();
        $user->following = Follow::where('followedBy', $user->id)->count();
        $user->recipes = Recipe::where('userId', $user->id)->count();
        $user->saveCount = Favorite::where('userId', $user->id)->where('commentId', null)->count();
        return response()->json([
            'status' => 200,
            'message' => 'User account has been updated',
            'data' => [
                // User::find($request->id)->only('id', 'name', 'email', 'phoneNumber','bio', 'pictureUrl'),
                $user
            ]
        ], 200);
    }

    // function to delete user profile picture
    public function deleteUserProfilePicture(Request $request)
    {
        $data= Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'All fields are required',
                'data'=> []
            ], 400);
        }

        if($request->id != $request->userID){
            return response()->json([
                'status' => 400,
                'message' => 'Not authorized to carry out this action',
                'data'=> []
            ], 400);
        }

        User::find($request->id)->update([
            'pictureUrl' => null
        ]);
        $user = User::find($request->id);
        $user->followers = Follow::where('userId', $user->id)->count();
        $user->following = Follow::where('followedBy', $user->id)->count();
        $user->recipes = Recipe::where('userId', $user->id)->count();

        return response()->json([
            'status' => 200,
            'message' => 'User account has been updated',
            'data' => [
                $user
            ]
        ], 200);
    }

    // Function to generate API token
    public function generateAPI($id) 
    {
        $token = Str::random(60);

        User::find($id)
        ->update([
            'apiToken' => $token
            ]);

        $note = [
            'apiToken' => $token,
            'id'=> $id
        ];
        $encrypted = Crypt::encrypt($note);
        return $encrypted;
    }
}
