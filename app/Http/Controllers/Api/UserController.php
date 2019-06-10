<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request; 
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\Auth; 
use Carbon\Carbon;
use Validator;
use Hash;
use JD\Cloudder\Facades\Cloudder;
use App\UserDetails;
use App\User;
use App\Http\Requests\UserRegister;
use App\Http\Requests\ChangeProfile;

class UserController extends Controller 
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
        try{
            if(Auth::attempt(['email' => request('email'), 'password' => request('password'), 'type' => 1])){ 
                $user = Auth::user();
                if($user->otp_verified_at) {
                    $success['data'] = $user;
                    $success['data']['token'] =  $user->createToken('MyApp')-> accessToken; 
                    return response()->json(['status' => 'success','data' => $success], self::SUCCESS_CODE); 
                }
            }
        }
        catch(\Exception $e){
           // do task when error
           return response()->json(['error'=>$e->getMessage()], 500); 
        }
        
        return response()->json(['error'=>'Unauthorised'], 401); 
        
    }

    /** 
     * Register api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function register(UserRegister $request) 
    { 
        try{
            $input = $request->all(); 
            $input['password'] = bcrypt($input['password']);
            $input['otp_code'] = 123456;
            $user = User::create($input);
            $input['user_id'] = $user->id;
            $user_detail = UserDetails::create($input);
            //sendMail
            // $this->__sendUserActivationMail($user);
         }
         catch(\Exception $e){
            // do task when error
            return response()->json(['error'=>$e->getMessage()], 500); 
         }
        
        return response()->json(['status'=>'success', 'data' => ['email' => $user->email]], 201); 
    }

    public function userActivation(Request $request) {
        $validator = Validator::make(request()->all(), [
            'email' => 'required|email',
            'otp_code' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $today = Carbon::now();
            $is_user = User::where('otp_code', $request->otp_code)
                        ->where('email', $request->email)
                        ->where('otp_verified_at', NULL)->first();
            if($is_user) {
                $activate_user = User::where('id', $is_user->id)
                                 ->update(['otp_verified_at' => $today->format('Y-m-d h:i:s')]);
                if ($activate_user) {
                    return response()->json(['status'=>'success'], self::SUCCESS_CODE);
                }
                
            }
        } catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage()], 500);
        }

        return response()->json(['status'=>'error','message'=>'Gagal mengaktifkan akun atau anda sudah melakukan aktifasi sebelumnya'], 401);
    }

    /** 
     * logout api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function logout(Request $request) 
    { 
        $request->user()->token()->revoke();
        
        return response()->json([
            'message' => 'Successfully logged out'
        ], 200);
    }

    public function forgotPassword(Request $request) {
        $validator = Validator::make(request()->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['status'=>'error','message'=>'Email tidak terdaftar'], 401);
        }

        if ($user->email_verified_at == null) {
            return response()->json(['status'=>'error','message'=>'Anda belum mekakukan aktivasi akun'], 401);
        }

        $sendMail = $this->__sendForgotPasswordMail($user);

        return response()->json(['status'=>'success'], self::SUCCESS_CODE);
        
    }

    public function checkForgotPassword(Request $request) {
        $key =  Crypt::decrypt($request->key);
        $key_array = explode('|', $key);
        $today = Carbon::now();
        $submit_date = Carbon::parse($key_array[1]);

        if ($today > $submit_date) {
            return response()->json(['error'=>'Link sudah kadaluarsa'], 401);
        }
        
        try {
            $is_user_exist = User::where('id', $key_array[0])->first();
            if (!$is_user_exist) {
                return response()->json(['error'=>'User tidak valid'], 401);
            }
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage()], 500); 
        }

        return response()->json(['status'=>'success', 'data' => $is_user_exist], 201);

    }

    public function changePassword(Request $request) {
        $validator = Validator::make(request()->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
            'c_password' => 'required|min:6|same:password'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $user =  User::where('email', $request->email)->first();
            if ($user != null && $user->otp_verified_at != null) {
                $update = User::where('email', $request->email)
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

    public function changeProfile(ChangeProfile $request) {
        try{
            $image_url = "";
            $user = Auth::user();
            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                $img_name = time();
                Cloudder::upload($image, $img_name,['folder'=>'agen_namen/user']);
                $image_url = Cloudder::getResult();
            }
            $change_user = [
                'name' => $request->name
            ];
            User::where('id', $user->id)->update($change_user);

            $change_detail_user = [
                'birth_date' => $request->birth_date,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'address' => $request->address,
                'profile_image' => $image_url['public_id']
            ];
            UserDetails::where('user_id', $user->id)->update($change_detail_user);

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
            $user_detail = UserDetails::where('user_id', $user->id)->first();
            if ($user_detail) {
                $image = Cloudder::show($user_detail->profile_image,
                            array('width' => 300, 'height' => 300)
                            );
                $data = [
                    'email' => $user->email,
                    'name' => $user->name,
                    'register_date' => $user->created_at,
                    'birth_date' => $user_detail->birth_date,
                    'phone' => $user_detail->phone,
                    'gender' => $user_detail->gender == 0 ? 'female' : 'male',
                    'address' => $user_detail->address,
                    'profile_image' => $image,
                ];

                return response()->json(['status'=>'success','data'=>$data], 201);
            }
        } catch(\Exception $e){
            // do task when error
            return response()->json(['error'=>$e->getMessage()], 500); 
        }

        return response()->json(['status'=>'error','message'=>'Gagal ambil data profile!'], 401);
    }

    public function changeProfilePassword(Request $request) {
        $validator = Validator::make(request()->all(), [
            'password' => 'required|min:6', 
            'c_password' => 'required|min:6|same:password',
            'old_password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $user = Auth::user();
        $hash = Hash::check($request->old_password, Auth::user()->password, []);
        if ($hash) {
            try {
                $update = User::where('id', $user->id)
                    ->update(['password' => bcrypt($request->password)]);

                if ($update) {
                    return response()->json(['status'=>'success'], 200);
                }
            } 
            catch(\Exception $e){
                return response()->json(['error'=>$e->getMessage()], 500); 
            }
        }

        return response()->json(['status'=>'error','message'=>'Gagal update password!'], 401);
    }

    private function __sendUserActivationMail($user)
    {
        $obj = new \stdClass();
        $obj->receiver = $user->name;
        $obj->encrypt_param = Crypt::encrypt($user->id);
        $obj->expired = $this->__getExpiredActivation($user->created_at);
 
        Mail::to($user->email)->send(new UserRegisterActivationMail($obj));
    }

    private function __getExpiredActivation($date) {
        $register_date = Carbon::parse($date);
        $expired_date = $register_date->addDays(self::LIMIT_DATE_REGISTRATION)->format('d F Y h:i:s');

        return $expired_date;
    }

    private function __sendForgotPasswordMail($user)
    {
        $date = Carbon::now();
        $limit_date = $date->addDays(self::LIMIT_DATE_REGISTRATION);
        $obj = new \stdClass();
        $obj->receiver = $user->name;
        $obj->encrypt_param = Crypt::encrypt($user->id . '|' . $limit_date->toDateTimeString());
        $obj->expired = $limit_date->format('d F Y h:i:s');
 
        Mail::to($user->email)->send(new ForgotPasswordMail($obj));
    }

    private function __getAreaDetail($id) {
        $users_district = Areas::where('id', $id)->first();
        $users_city = Areas::where('code', substr($users_district->code,0,5))->first();
        $users_province = Areas::where('code', substr($users_district->code,0,2))->first();

        return [
            'province_id' => $users_province->id,
            'province_name' => $users_province->name,
            'city_id' => $users_city->id,
            'city_name' => $users_city->name,
            'district_id' => $users_district->id,
            'district_name' => $users_district->name
        ];
    }
}