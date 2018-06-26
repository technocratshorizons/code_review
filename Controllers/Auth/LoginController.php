<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Models\Banner;
use Lang;
use Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    /* protected $redirectTo = '/home'; */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
	 
	 public function logout(Request $request){
		
		$user = Auth::user(); 
		Auth::logout();
        
		$this->success_error_messages = Lang::get('success_error_messages');
		$notification = array(
			'message' =>  $this->success_error_messages['logout_success'],
			'alert-type' => 'success'
		);
		
		if($user->user_type === 'admin') 
			return redirect('/admin');
		else if($user->user_type == "landlord")	
			return redirect('/')->with($notification);
		else if($user->user_type == "tenant")	
			return redirect('/')->with($notification);
		
	}
	
	protected function authenticated(Request $request, $user)
    {   
        $this->success_error_messages = Lang::get('success_error_messages');
        
        if($user->is_blocked === '1') {
            Auth::logout();
            $notification = array(
                'message' =>  $this->success_error_messages['check_authentication_blocked_account'],
                'alert-type' => 'error'
            );
            return redirect('/')->with($notification);
        }

		$notification = array(
			'message' =>  $this->success_error_messages['login_success'],
			'alert-type' => 'success'
		);
        if($user->user_type === 'admin') 
			return redirect('/admin/dashboard');
		else if($user->user_type == "landlord") 
			return redirect('/dashboard')->with($notification);
		else if($user->user_type == "tenant")
			return redirect('/')->with($notification);
    }
	
    public function __construct()
    {
        
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {   
        $banner_info = Banner::where('slug','front_screen')->first();
        return view('auth.login', compact('banner_info'));
    }
    
}
