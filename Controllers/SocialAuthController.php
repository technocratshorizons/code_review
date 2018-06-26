<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Socialite;
use App\User;
use Session;
use Lang;
use Hash;
use Mail;
use Auth;

class SocialAuthController extends Controller
{   
    public $success_error_messages;
    public function redirect()
    {
        return Socialite::driver('facebook')->redirect();   
    }   

    public function __construct()
    {
        $this->middleware('guest');
        $this->success_error_messages = Lang::get('success_error_messages');
    }
    

    public function callback()
    {
        $user = $service->createOrGetUser(Socialite::driver('facebook')->user());

        auth()->login($user);

        return redirect()->to('/register');
    }
    
    // for user login from google
    public function googleLogin(Request $request)  {
        $google_redirect_url = route('glogin');
        $gClient = new \Google_Client();
        $gClient->setApplicationName(config('services.google.app_name'));
        $gClient->setClientId(config('services.google.client_id'));
        $gClient->setClientSecret(config('services.google.client_secret'));
        $gClient->setRedirectUri($google_redirect_url);
        $gClient->setDeveloperKey(config('services.google.api_key'));
        $gClient->setScopes(array(
            'https://www.googleapis.com/auth/plus.me',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
        ));
        $google_oauthV2 = new \Google_Service_Oauth2($gClient);
        if ($request->get('code')){
            $gClient->authenticate($request->get('code'));
            $request->session()->put('token', $gClient->getAccessToken());
        }
        if ($request->session()->get('token'))
        {
            $gClient->setAccessToken($request->session()->get('token'));
        }
        if ($gClient->getAccessToken())
        {
            //For logged in user, get details from google using access token
                $guser = $google_oauthV2->userinfo->get();  
                $social_user_type = $request->session()->get('social_user_type');
                $request->session()->put('name', $guser['name']);
                
                if ($user = User::where('email',$guser['email'])->first())
                {   
                    if(empty($user->google_id)){
                        User::where('email',$guser['email'])->update(['google_id' => $guser['id']]);
                    }

                    //Check if user register with different user type
                    if($user->user_type != $social_user_type){

                        if($social_user_type == 'tenant'){

                            Session::flash('error_flash', $this->success_error_messages['uesr_type_tenant_error_gmail']);


                        }else if( $social_user_type == 'landlord' ){

                            Session::flash('error_flash', $this->success_error_messages['uesr_type_landlord_error_gmail']);

                        }
                      
                        return redirect('/register');
                    }
                    //END

                    if(Auth::loginUsingId($user->id)){
                        $user = Auth::user();
                       // Session::flash('success', $this->success_error_messages['register_success']);
                        if($user->user_type == 'landlord') {
                             return redirect('/dashboard');
                        }
                        else if($user->user_type == 'tenant') {
                             return redirect('/');
                        }
                    }

                } else{
                    //register your user with response data
                    $six_digit_random_number = mt_rand(100000, 999999);
                   
                    $userData = new User;
                    $userData->name = $guser['name'];
                    $userData->email = $guser['email'];
                    $userData->google_id = @$guser['id'];
                    $userData->user_type = @$social_user_type;
                    $userData->password =  Hash::make($six_digit_random_number);
                    $userData->created_at = date('Y-m-d H:i:s');
                    $userData->updated_at = date('Y-m-d H:i:s');
                    
                    if(  $userData->save() ){
						$userdata = User::find($userData->id);
						$lang = Lang::get('register');
                        
                        $emailcontent = array (
							'subject' => $lang['subject'],
							'emailmessage' => $lang['emailmessage'],
                        );

                        Mail::send('emails.registeremail', $emailcontent, function($message){
                           $message->to($userdata->email,'Account register')->subject('Contact using Our Contact Form');
                        });

                        //END SEND MAIL
                        if (Auth::attempt(['email' => $userData->email, 'password' => $six_digit_random_number]))
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
                }               
                         
    } else
        {
            //For Guest user, get google login url
            $authUrl = $gClient->createAuthUrl();
            return redirect()->to($authUrl);
        }
    }
}
