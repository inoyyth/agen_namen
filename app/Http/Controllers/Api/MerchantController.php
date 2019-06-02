<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request; 
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\Auth;
use Validator;
use Hash;
use JD\Cloudder\Facades\Cloudder;
use App\Merchant;
use App\User;
use App\Http\Requests\ChangeProfileMerchant;

class MerchantController extends Controller 
{

    const SUCCESS_CODE = 200;
    const LIMIT_DATE_REGISTRATION = 5;

    /** 
     * login api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function login() 
    { 
        if(Auth::attempt(['email' => request('email'), 'password' => request('password'), 'type' => 2])){ 
            $user = Auth::user();
            if($user->otp_verified_at) {
                $success['data'] = ['id'=>$user->id, 'name'=>$user->name, 'email'=>$user->email, 'type'=>2, 'created_at'=>$user->created_at];
                $success['data']['merchant'] = Merchant::select('id','name','address','phone','longitude','latitude','image','description')
                                       ->where('user_id', $user->id)->first();
                $success['data']['token'] =  $user->createToken('MyApp')-> accessToken; 
                return response()->json(['status' => 'success','data' => $success], self::SUCCESS_CODE); 
            }
        }
        
        return response()->json(['error'=>'Unauthorised'], 401);  
    }

    public function changePassword(Request $request) {
        $validator = Validator::make(request()->all(), [
            'password' => 'required|min:6',
            'c_password' => 'required|min:6|same:password'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $user = Auth::user();
            if ($user->otp_verified_at != null) {
                $update = User::where('id', $user->id)
                    ->update(['password' => bcrypt($request->password)]);

                if ($update) {
                    return response()->json(['status'=>'success'], 200);
                }
            }
        } 
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage()], 500); 
        }

        return response()->json(['status'=>'error','message'=>'Gagal update password!'], 401);
    }

    public function changeProfile(ChangeProfileMerchant $request) {
        try{
            $image_url = "";
            $user = Auth::user();
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $img_name = time();
                Cloudder::upload($image, $img_name,['folder'=>'agen_namen/merchant']);
                $image_url = Cloudder::getResult();
            }
            $change_user = [
                'name' => $request->name
            ];
            User::where('id', $user->id)->update($change_user);

            $change_merchant = [
                'name' => $request->name,
                'phone' => $request->phone,
                'description' => $request->description,
                'address' => $request->address,
                'image' => $image_url['public_id'],
                'latitude' => $request->latitude,
                'longitude' => $request->longitude
            ];
            Merchant::where('user_id', $user->id)->update($change_merchant);

         }
         catch(\Exception $e){
            // do task when error
            return response()->json(['error'=>$e->getMessage()], 500); 
         }
        
        return response()->json(['status'=>'success'], self::SUCCESS_CODE); 
    }

    public function getProfile() {
        try{
            $user = Auth::user();
            $merchant = Merchant::where('user_id', $user->id)->first();
            if ($merchant) {
                $image = Cloudder::show($merchant->image,
                            array('width' => 300, 'height' => 300)
                            );
                $data = [
                    'email' => $user->email,
                    'name' => $user->name,
                    'register_date' => $user->created_at,
                    'merchant_name' => $merchant->name,
                    'phone' => $merchant->phone,
                    'address' => $merchant->address,
                    'latitude' => $merchant->latitude,  
                    'longitude' => $merchant->longitude,
                    'description' => $merchant->description,
                    'image' => $image,
                ];

                return response()->json(['status'=>'success','data'=>$data], 201);
            }
        } catch(\Exception $e){
            // do task when error
            return response()->json(['error'=>$e->getMessage()], 500); 
        }

        return response()->json(['status'=>'error','message'=>'Gagal ambil data profile!'], 401);
    }

    public function logout(Request $request) 
    { 
        $request->user()->token()->revoke();
        
        return response()->json([
            'message' => 'Successfully logged out'
        ], 200);
    }
}