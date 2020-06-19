<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\User;

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

        $email = User::where('email', $request->email)->value('email');

        if($email != "") {
            return response()->json([
                'status' => 400,
                'message' => 'Email account already exists',
                'data' => []
            ], 400);
        }
        
        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phoneNumber = $request->phoneNumber;
        $user->pictureUrl = $request->pictureUrl;
        $user->bio = $request->bio;
        $user->password = Hash::make($request->password);

        $user->save();
        $user->apiToken = $this->generateAPI($user->id);
        return response()->json([
            'status' => 200,
            'message' => 'User account has been created',
            'data' => $user
        ], 200);
    }

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

        // Generate api_token
        $user->apiToken = $this->generateAPI($user->id);
        return response()->json([
            'status' => 200,
            'message' => 'Login succesful',
            'data' => $user
        ], 200);


    }

    public function generateAPI($id) 
    {
        $token = Str::random(60);

        User::where('id', $id)
        ->update([
            'apiToken' => Hash::make($token)
            ]);

        return $token;
    }
}
