<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request; 
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\Auth;
use App\Category;

class CategoryController extends Controller 
{

    const SUCCESS_CODE = 200;

    public function index() {
        try{
            $category = Category::select('id','name','description')->where('status', 1)->get();
            if ($category) {

                return response()->json(['status'=>'success','data'=>$category], 201);
            }
        } catch(\Exception $e){
            // do task when error
            return response()->json(['error'=>$e->getMessage()], 500); 
        }

        return response()->json(['status'=>'error','message'=>'No categories exists!'], 401);
    }
}