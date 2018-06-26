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
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\User;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
        $this->success_error_messages = Lang::get('success_error_messages');
    }
    
	public function index(Request $request) {
       
		return view('adminpanel.login');
    }

    //Forget password function START
    public function forgot_password(Request $request) {
       
        if (!empty($_POST)) {
           
            $data = Input::all();
            $rules = array(
                'email' => 'required|email',
            );
			$validator = Validator::make($data, $rules);
            
			if ($validator->fails()) 
			{
                return redirect()->intended('admin/forgot_password')->withErrors($validator);
            } 
			else {
                $user_detail = User::where('email', '=', $request->email)->where('status', '=', '1')->where('user_type', '=', 'admin')->get()->toArray();
                
                if (empty($user_detail[0])) {
                    Session::flash('error_flash', $this->success_error_messages['forgot_password_error']);
                    return redirect('admin/forgot_password');
                } 
				else {

                    $password = str_random(12);
					$new_password =  Hash::make($password);
                    
                    
					/* Email Notification */
					$email_information = array('to_name'=>$user_detail[0]['name'],'to_email'=>$user_detail[0]['email'],'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Forgot Password Email');
					
					$email_content = array('name'=>ucfirst($user_detail[0]['name']),'new_password'=>$password);
                    
                    Mail::send(['html' => 'emails.admin_forgot_password'], $email_content, function($message) use ($email_information)
					{
						 $message->to($email_information['to_email'], $email_information['to_name'])->subject
							($email_information['subject']);
						 $message->from($email_information['from_email'],$email_information['from_name']);
                        
                    });
                    $user_update_data = array(
                        'password'      => $new_password,
                        'updated_at'=> date('Y-m-d H:i:s'), 
                    );
                    User::where('id',$user_detail[0]['id'])->update($user_update_data);
					/* End Here */
					Session::flash('success', $this->success_error_messages['forgot_password_success']);
                    return redirect('admin/forgot_password');
                }
            }
        }
        return view('adminpanel.forgot_password');
    }

    
}





