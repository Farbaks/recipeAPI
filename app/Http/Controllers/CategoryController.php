<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Category;

class CategoryController extends Controller
{
    //
    // function to create a category
    public function createCategory(Request $request)
    {   
        $data= Validator::make($request->all(), [
            'name' => 'required|max:255',
            'picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'All fields are required',
                'data'=> []
            ], 400);
        }

        $category = Category::where('name', $request->name)->count();
        if($category != 0) {
            return response()->json([
                'status' => 400,
                'message' => 'Category already exists',
                'data'=> []
            ], 400);
        }

        $category = Category::create([
            'name' => $request->name,
            'pictureUrl'=> ' '
        ]);

        $name = $category->id.".jpg";
        $path = Storage::disk('public')->putFileAs('categories', $request->file('picture'), $name, 'public');

        $category->update([
            'pictureUrl' => Storage::disk('public')->url('categories/'.$name)
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Category  has been created',
            'data' => [
                $category
            ]
        ], 200);
    }

    //  Function to update a category
    public function updateCategory(Request $request)
    {   
        $data= Validator::make($request->all(), [
            'categoryId' => 'required|numeric',
            'name' => 'required|max:255',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'All fields are required',
                'data'=> []
            ], 400);
        }
        $category = Category::find($request->categoryId);
        if($category == '') {
            return response()->json([
                'status' => 400,
                'message' => 'Category not found',
                'data'=> []
            ], 400);
        }
        
        if($request->file('picture')) {
            $name = $request->categoryId.".jpg";
            $path = Storage::disk('public')->putFileAs('categories', $request->file('picture'), $name, 'public');

            $category->update([
                'name' => $request->name,
                'pictureUrl' => Storage::disk('public')->url('categories/'.$name)
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Category  has been updated',
                'data' => [
                    $category
                ]
            ], 200);
        }

        $category->update([
            'name' => $request->name
        ]);
        return response()->json([
            'status' => 200,
            'message' => 'Category  has been updated',
            'data' => [
                $category
            ]
        ], 200);
        
    }

    // function to fetch all categories
    public function getAllCategories(Request $request)
    {   
        $categories = Category::all();

        return response()->json([
            'status' => 200,
            'message' => 'User accounts have been fetched',
            'data' => $categories
        ], 200);
    }
    
}
