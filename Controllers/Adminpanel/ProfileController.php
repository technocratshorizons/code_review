<?php

namespace App\Http\Controllers\Adminpanel;

use DB;
use Hash;
use Auth;
use Cookie;
use Session;
use Lang;
use Mail;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use Illuminate\Validation\Rule;
use App\User;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
        $this->success_error_messages = Lang::get('success_error_messages');
    }
    
	public function index(Request $request) {
        
        $user = Auth::user();

        if (!empty($_POST)) {
			$data = Input::all();
			
            $rules = array(
                'name'      => 'required',
                'email'     => 'required|email|unique:users,email,'.$user->id,
                'phone'     => 'required',
                'address'   => 'required',
                'country'   => 'required',
                'state'     => 'required',
                'city'      => 'required',
			);

			$message = array(
                'email' => $this->success_error_messages['register_error_email_unique'],
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

            if ($validator->fails()) {
                return redirect()->intended('admin/profile')->withErrors($validator)->withInput($request->input());
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
                    
                //if new password field is empty 
                if ($request->new_password == '' || $request->confirm_password == ''){   
                        unset($request->new_password);
                        unset($request->confirm_password);
                } else{
                    $user_update_data['password'] = Hash::make($request->new_password);
                }

                if(User::where('id',$request->user_id)->update($user_update_data)){

                    Session::flash('success', $this->success_error_messages['profile_update']);
					
                } else{
                    Session::flash('success', $this->success_error_messages['common_error']);
                }

                return redirect('admin/profile');
                
                } else{
                    Session::flash('success', $this->success_error_messages['common_error']);
                    return redirect('admin/profile');
                }  
            }
        }
        return view('adminpanel.profile', compact('user'));
    }

}





