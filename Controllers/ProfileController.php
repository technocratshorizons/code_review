<?php

namespace App\Http\Controllers;

use DB;
use Hash;
use Auth;
use Cookie;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Mail;
use Illuminate\Support\Facades\Input;
use Lang;
use App\User;
use Illuminate\Validation\Rule;
use App\Models\Banner;

class ProfileController extends Controller {

	public $success_error_messages;
	
	public function __construct()
    {
		$this->success_error_messages = Lang::get('success_error_messages');
	}
	
	public function index(Request $request) {
        $user = Auth::user();
        
		if (!empty($_POST)) {
			$data = Input::all();
			
            $rules = array(
                'name' => 'required',
                'email' => 'required|email|unique:users,email,'.$user->id,
                'phone' => 'required',
                'address' => 'required',
                'country' => 'required',
                'state' => 'required',
                'city' => 'required',
                'file' => 'image|mimes:jpeg,png|max:1024'
			);

			$message = array(
                'email.unique' => $this->success_error_messages['register_error_email_unique'],
            );
            $validator = Validator::make($data, $rules, $message);
            // user set new password
            if ($request->new_password != '' || $request->confirm_password != '')
                {
                    $rules = array(
                        'new_password' => 'required|confirmed||min:6|max:15',
                    );
                    $message = array(
                        'new_password' => $this->success_error_messages['password_not_match'],
                    );
            }

            if($_FILES){

            }

            if ($validator->fails()) {
                return redirect()->intended('/profile')->withErrors($validator)->withInput($request->input());
            } 
			else 
			{ 
                
                if($request->user_id){
                    $user_update_data = array(
                        'name'      => $request->name,
                        'email'     => $request->email,
                        'phone'     => $request->phone,
                        'address'   => $request->address,
                        'country'   => $request->country,
                        'state'     => $request->state,
                        'city'      => $request->city,
                        'updated_at'=> date('Y-m-d H:i:s'),
                        
                    );
                    
                if($request->file){
                    $image_name =  str_replace('/', '\\',$user->profile_pic);
                    if(isset($image_name) && !empty($image_name)){
                        if(file_exists(storage_path("app\public\upload\\").$image_name)){
                            unlink(storage_path("app\public\upload\\").$image_name);
                        }
                    }
                    
                    $user_update_data['profile_pic'] = $request->file->store('user_profile');
                } 
                if($request->about){
                    $user_update_data['about'] = $request->about;
                }  
                if($request->landlord_type){
                    $user_update_data['landlord_type'] = $request->landlord_type;
                }    
                //if new password field is empty 
                if ($request->new_password == '' || $request->confirm_password == ''){   
                        unset($request->new_password);
                        unset($request->confirm_password);
                } else{
                    $user_update_data['password'] = Hash::make($request->new_password);
                }

                if(User::where('id',$request->user_id)->update($user_update_data)){
                    $notification = array(
                        'message' =>  $this->success_error_messages['profile_update'],
                        'alert-type' => 'success'
                    );
                } else{
                    $notification = array(
                        'message' =>  $this->success_error_messages['common_error'],
                        'alert-type' => 'error_flash'
                    );
                }
                return redirect()->intended('/profile')->with($notification);
                
                } else{
                    $notification = array(
                        'message' =>  $this->success_error_messages['common_error'],
                        'alert-type' => 'error_flash'
                    );
                    return redirect()->intended('/profile')->with($notification);
                }
                
            }
        }
        $bannerDtails = Banner::where('slug','dashboard')->first();
        return view('profile', compact('user','bannerDtails'));
    }
}
