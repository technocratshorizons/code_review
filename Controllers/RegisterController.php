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
use App\Models\Banner;
class RegisterController extends Controller {
	public $success_error_messages;
	
	public function __construct()
    {
		$this->middleware('guest');
		$this->success_error_messages = Lang::get('success_error_messages');
	}
	
	public function index(Request $request) {
	    
		if (!empty($_POST)) {
			if(!empty($request->social_user_type)){
				Session::put('social_user_type', $request->social_user_type);
				return redirect('/glogin');
			}
			
			$data = Input::all();
			$rules = array(
                'name' => 'required',
                'user_type' => 'required',
                'email' => 'unique:users|required',
                'phone' => 'unique:users|required',
                'password' => 'required|min:6|max:15',
			);
			$message = array(
                'phone.unique' => $this->success_error_messages['register_error_phone_unique'],
                'email.unique' => $this->success_error_messages['register_error_email_unique'],
            );
            $validator = Validator::make($data, $rules, $message);
            
            if ($validator->fails()) {
                return redirect()->intended('/register')->withErrors($validator)->withInput($request->input());
            } 
			else 
			{
				$user_insert_data = new User;
                $user_insert_data->name = $request->name;
                $user_insert_data->password = Hash::make($request->password);
                $user_insert_data->email = $request->email;
				$user_insert_data->user_type = $request->user_type;
                $user_insert_data->phone = $request->phone;
                $user_insert_data->created_at = date('Y-m-d H:i:s');
                $user_insert_data->updated_at = date('Y-m-d H:i:s');
                if(  $user_insert_data->save() ){
                    
					/* Email Notification */
					$email_information = array('to_name'=>$user_insert_data->name,'to_email'=>$user_insert_data->email,'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Registration');
					$email_content = array('name'=>ucfirst($user_insert_data->name),'user_type'=>ucfirst($user_insert_data->user_type));
					Mail::send(['html' => 'emails.register_user'], $email_content, function($message) use ($email_information)
					{
						 $message->to($email_information['to_email'], $email_information['to_name'])->subject
							($email_information['subject']);
						 $message->from($email_information['from_email'],$email_information['from_name']);
					});
					/* End Here */
					
                    if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'status' => '1']))
                    {
                        $user = Auth::user();
						
						$notification = array(
                            'message' =>  $this->success_error_messages['register_success'],
                            'alert-type' => 'success'
                        );
						
						if($user->user_type == 'landlord') {
							return redirect()->intended('/dashboard')->with($notification);
						}
						else if($user->user_type == 'tenant') {
							return redirect()->intended('/')->with($notification);
						}
					}
				}
				else{
                    Session::flash('error_flash', 'Something went wrong please try again.');
                    return redirect('/register');
                }
            }
        }
        $banner_info = Banner::where('slug','front_screen')->first();
        return view('register',compact('banner_info'));
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
                return redirect()->intended('forgot_password')->withErrors($validator);
            } 
			else {
                $user_detail = User::where('email', '=', $request->email)->where('status', '=', '1')->get()->toArray();
                if (empty($user_detail[0])) {
                    Session::flash('error_flash', $this->success_error_messages['forgot_password_error']);
                    return redirect('/forgot_password');
                } 
				else {
					
					$encode_id = base64_encode($user_detail[0]['id']);
					$reset_link = config('app.url').'/reset_password/'.$encode_id;
					
					/* Email Notification */
					$email_information = array('to_name'=>$user_detail[0]['name'],'to_email'=>$user_detail[0]['email'],'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Forgot Password Email');
					
					$email_content = array('name'=>ucfirst($user_detail[0]['name']),'reset_link'=>$reset_link);
					Mail::send(['html' => 'emails.forgot_pasword'], $email_content, function($message) use ($email_information)
					{
						 $message->to($email_information['to_email'], $email_information['to_name'])->subject
							($email_information['subject']);
						 $message->from($email_information['from_email'],$email_information['from_name']);
					});
					/* End Here */
					Session::flash('success', $this->success_error_messages['forgot_password_success']);
                    return redirect('/forgot_password');
                }
            }
        }
        $banner_info = Banner::where('slug','front_screen')->first();
        return view('forgot_password',compact('banner_info'));
    }
	
	public function reset_password(Request $request, $token = NULL) {
		
		if (!empty($_POST)) {
			$data = Input::all();
            $rules = array(
                'password' => 'required|min:6|max:15',
                'confirm_password' => 'required|min:6|max:15|same:password',
            );
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                return redirect()->intended('/reset_password/' . $data['id'])->withErrors($validator);
            } 
			else {
				$user_id = base64_decode($data['id']);
                User::where("id", "=", $user_id)->update(['password' => hash::make($data['password'])]);
				
				$user_detail = $user = User::where('id',$user_id)->first();
				
				/* Email Notification */
				$email_information = array('to_name'=>$user_detail->name,'to_email'=>$user_detail->email,'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Reset Password');
				$email_content = array('name'=>ucfirst($user_detail->name));
				Mail::send(['html' => 'emails.reset_password'], $email_content, function($message) use ($email_information)
				{
					 $message->to($email_information['to_email'], $email_information['to_name'])->subject
						($email_information['subject']);
					 $message->from($email_information['from_email'],$email_information['from_name']);
				});
				/* End Here */
				
				if (Auth::attempt(['email' => $user_detail->email, 'password' => $data['password'], 'status' => '1']))
				{
					$user = Auth::user();
					Session::flash('success', $this->success_error_messages['reset_password_success']);
					if($user->user_type == 'landlord') {
						 return redirect('/dashboard');
					}
					else if($user->user_type == 'tenant') {
						 return redirect('/');
					}
					else {
						return redirect('/');
					}
				}
			}
        }
		$banner_info = Banner::where('slug','front_screen')->first();
        return view('reset_password',compact('banner_info'));
    }
	
	
	/* public function confirm(Request $request) {
        $userData = Session::get('userData');
        $randNumber = Session::get('randNumber');
        $userPhone = $userData->phone;
        $user = Auth::user();
        if (isset($user) and ! empty($user)) {
            return redirect()->intended('/dashboard');
        }
        if (!empty($_POST)) {
            $data = Input::all();
            $rules = array(
                'confirm_code' => 'required',
            );
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                return redirect()->intended('/register')->withErrors($validator);
            } 
			else {
                $confirmCode = $request->confirm_code;
                $confirmToken = $request->confirm_token;
                if ($confirmCode == $confirmToken) {
                    if ($userData->save()) {
                        Session::put('userDataEmail', $userData->email);
                        Session::put('userDataPassword', $userData->password);
                        Session::flash('success', 'Congrats your account registered successfully');
                        return redirect()->intended('/congratulations');
                    }
                } else {
                    Session::flash('error_flash', 'Confirmation code does not match. ');
                    return redirect()->intended('/confirm-your-account');
                }
            }
        }
        return view('register_confirm', compact('randNumber', 'userPhone'));
    }
	
	public function congratulations(Request $request) {
        
		$userDataEmail = Session::get('userDataEmail');
        $userDataPassword = Session::get('userDataPassword');
        if (empty($userDataEmail) && empty($userDataPassword)) {
            Session::flash('error_flash', 'You can not access this page directly');
            return redirect('/login');
        }
		return view('register_congratulations', compact('userDataEmail','userDataPassword'));
    } */
	
	
	
	 
}