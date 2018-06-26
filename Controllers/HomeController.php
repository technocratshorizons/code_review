<?php

namespace App\Http\Controllers;

use DB;
use Hash;
use Auth;
use Cookie;
use Session;
use Redirect;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Models\Banner;
use App\Models\Testimonial;
use App\Models\Cities;
use App\Models\Properties;
use App\Models\Advantages;
use App\Models\Advantages_features;
use App\Models\Settings;
use Mail;
use Illuminate\Support\Facades\Input;

class HomeController extends Controller {

	public function __construct()
    {
		//$this->middleware('guest');
		
    }
	
    //Login function START
    public function index(Request $request) {
    	if(Auth::user()){
    		$user = Auth::user();
    	}else{
    		$user = false;
    	}

        if(isset($_POST) && !empty($_POST)){
            if(!empty($request->price_filter)){
                $price_filter = explode(';',$request->price_filter);
                session(['Selected_Min_Price' => $price_filter[0]]);
                session(['Selected_Max_price' => $price_filter[1]]);
            }
            $sendData = array();
            session(['city' => $request->city]);
            session(['bedrooms' => $request->bedrooms]);
            session(['bathroom' => $request->bathroom]);
            return redirect('property/search');
        }
		
		$testimonial_data = Testimonial::orderby('display_order','asc')->get();
        $bannerDtails = Banner::where('slug','home_screen_banner')->first();
        $Cities = Cities::orderBy('city_name', 'ASC')->where('status','1')->get();
        $Max_price = Properties::where('is_deleted', '0')->max('monthly_rent');
        $Min_price = Properties::where('is_deleted', '0')->min('monthly_rent');

        $User_advantages = Advantages::with(['advantages_feature'])->get();
        // $Advantages_features = Advantages_features::get();
        $Settings = Settings::get();
        
          $stats = Cities::with(['city_states'])->get();
        //   echo '<pre>',print_r($stats),'</pre>';die;
       	return view('home',compact('user','testimonial_data','bannerDtails','Cities','Max_price','Min_price','Cities','User_advantages','Settings','stats'));
    }
}
