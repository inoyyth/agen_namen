<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request; 
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Product;

class ProductController extends Controller 
{

    const SUCCESS_CODE = 200;

    public function index(Request $request) {
        $validator = Validator::make(request()->all(), [
            'per_page' => 'required|numeric',
            'page' => 'required|numeric'
        ]);

        $page = $request->page - 1;
        $keyword = $request->keyword ? $request->keyword : '';

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        try{
            $data = Product::select('id','merchant_id','category_id','name','description','price','image')
                        ->with(['Merchant' => function($q) {
                            $q->select('id','name','address');
                        }])
                        ->with(['Category' => function($q) {
                            $q->select('id','name');
                        }])
                        ->where('status', 1)
                        ->Where('name', 'like', '%' . $keyword . '%')
                        ->paginate($request->per_page)
                        ->toArray();
            if ($data) {

                return response()->json([
                    'status'=>'success',
                    'data'=>$data
                ], 201);
            }
        } catch(\Exception $e){
            // do task when error
            return response()->json(['error'=>$e->getMessage()], 500); 
        }

        return response()->json(['status'=>'error','message'=>'No Product exists!'], 401);
    }
}