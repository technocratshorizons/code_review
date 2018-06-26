<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use Session;
use Lang;
use File;
use App\Models\Testimonial;
use App\Models\Settings;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class IntroductionController extends Controller
{
	public $success_error_messages;

	public function __construct()
    {
		$this->middleware('admin'); 
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    public function index(Request $request) {
    	$Settings = Settings::get();
		return view('adminpanel.introduction',compact('Settings'));
	}

	//Edit Testimonials Function start here
	public function edit(Request $request) {
		$Settings = Settings::where('id',$request->id)->first();
		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
                'introduction' => 'required|max:255',
                'header_logo' => 'image|mimes:jpeg,bmp,png|dimensions:max_height=70,max_width=117',
                'advantages_logo' => 'image|mimes:jpeg,bmp,png|dimensions:max_height=132,max_width=227',
			);

			$message = array(
                'file.dimensions' => 'The Image dimension should correct.'
            );

			$validator = Validator::make($data, $rules, $message);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/introduction/edit/'.$request->id)->withErrors($validator)->withInput($request->input());
			}
			else{
				$introducton_data = array();
                $introducton_data['introduction'] = $request->introduction;
                $introducton_data['updated_at'] = date('Y-m-d H:i:s');
			
				if(!empty($request->header_logo)){
					$header_logo_name =  str_replace('/', '\\',$Settings->header_logo);
					if(file_exists(storage_path("app\public\upload\\").$header_logo_name)){
						unlink(storage_path("app\public\upload\\").$header_logo_name);
					}
					$filename = $request->header_logo->store('settings');
					if(!empty($filename)){
						$introducton_data['header_logo'] = $filename;
					}
                }
                if(!empty($request->advantages_logo)){
					$advantages_logo_name =  str_replace('/', '\\',$Settings->advantages_logo);
					if(file_exists(storage_path("app\public\upload\\").$advantages_logo_name)){
						unlink(storage_path("app\public\upload\\").$advantages_logo_name);
					}
					$filename = $request->advantages_logo->store('settings');
					if(!empty($filename)){
						$introducton_data['advantages_logo'] = $filename;
					}
				}
			
				//Check if values saved in database or not
				if(Settings::where('id',$request->id)->update($introducton_data)){
					Session::flash('success', $this->success_error_messages['introduction_success_update']);
					return redirect('admin/introduction/edit/'.$request->id);
				} else{
					Session::flash('warning', $this->success_error_messages['testimonial_warn_update']);
					return redirect('admin/introduction/edit/'.$request->id);
				}
			}
		}
		return view('adminpanel.introduction_edit', compact('Settings'));
	}

}
