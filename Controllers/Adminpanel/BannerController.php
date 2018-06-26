<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use Session;
use Lang;
use File;
use App\Models\Banner;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class BannerController extends Controller
{
	public $success_error_messages;

	public function __construct()
    {
		$this->middleware('admin'); 
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    public function index(Request $request) {
    	$banner = Banner::orderBy('name', 'DESC')->get();
		return view('adminpanel.banner',compact('banner'));
    }
    
	
	//Edit  Utilities Function start here
	public function edit(Request $request) {
		$banner_data = Banner::where('id',$request->id)->first();
		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();
            if($request->slug == 'home_screen_banner'){
                
                //Define the rules for home
                $rules = array(
                    'name' => 'required|string||max:255|unique:ammenties,name,'.$banner_data->id,
                    'file' => 'image|mimes:jpeg,bmp,png|dimensions:min_height=649,min_width=1366,max_height=650,max_width=1368',
                );
                $message = array(
                    'file.dimensions' => 'The Image dimension should 1366 X 650.'
                );

            } else {
                //Define the rules for dashboard-login-register
                $rules = array(
                    'name' => 'required|string||max:255|unique:ammenties,name,'.$banner_data->id,
                    'file' => 'image|mimes:jpeg,bmp,png|dimensions:min_height=360,min_width=1366,max_height=360,max_width=1368',
                );
                $message = array(
                    'file.dimensions' => 'The Image dimension should 1366 X 360.'
                );
            }
			
			$validator = Validator::make($data, $rules, $message);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/banner/edit/'.$request->id)->withErrors($validator)->withInput($request->input());
			}
			else{
				$banner_update_data = array();
				$banner_update_data['name'] = $request->name;

				if(!empty($request->file)){
                    $image_name =  str_replace('/', '\\',$banner_data->image);
                    if(!empty($image_name)){
                        if(file_exists(storage_path("app\public\upload\\").$image_name)){
                            unlink(storage_path("app\public\upload\\").$image_name);
                        }
                        
                    }
                    $filename = $request->file->store('banner_images');
                    if(!empty($filename)){
                        $banner_update_data['image'] = $filename;
                    }
					
				}
			
				//Check if values saved in database or not
				if(Banner::where('id',$request->id)->update($banner_update_data)){
					Session::flash('success', $this->success_error_messages['banner_success_update']);
					return redirect('admin/banner/edit/'.$request->id);
				} else{
					Session::flash('warning', $this->success_error_messages['banner_warn_update']);
					return redirect('admin/banner/edit/'.$request->id);
				}
			}
		}
		return view('adminpanel.banner_edit', compact('banner_data'));
	}

}
