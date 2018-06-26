<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use Session;
use Lang;
use File;
use App\Models\Advantages;
use App\Models\Advantages_features;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class AdvantagesController extends Controller
{
	public $success_error_messages;

	public function __construct()
    {
		$this->middleware('admin'); 
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    public function index(Request $request) {
		$Advantages = Advantages::get();
		// Advantages_features::get();
		return view('adminpanel.advantages',compact('Advantages'));
	}

	//Edit  Advantages Function start here
	public function edit(Request $request) {
		$advantages_data = Advantages::where('id',$request->id)->first();
		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
				'name' 			=> 'required|max:30',
				'description' 	=> 'required|max:500',
			);

			$message = array(
                'name' => $this->success_error_messages['name_error_unique'],
            );

			$validator = Validator::make($data, $rules, $message);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/advantages/edit/'.$request->id)->withErrors($validator)->withInput($request->input());
			}
			else{
				$advantages_data = array();
				$advantages_data['name'] 			= $request->name;
				$advantages_data['description'] 	= $request->description;
				$advantages_data['updated_at'] 		= date('Y-m-d H:i:s');
				//Check if values saved in database or not
				if(Advantages::where('id',$request->id)->update($advantages_data)){
					Session::flash('success', $this->success_error_messages['advantages_success_update']);
					return redirect('admin/advantages/edit/'.$request->id);
				} else{
					Session::flash('warning', $this->success_error_messages['advantages_warn_update']);
					return redirect('admin/advantages/edit/'.$request->id);
				}
			}
		}
		return view('adminpanel.advantages_edit', compact('advantages_data'));
	}

	//view_features  Advantages Function start here
	public function view_features(Request $request) {

		$view_features = Advantages_features::where('advantage_id',$request->id)->get();
		$advantage_id = $request->id;
		$advantages = Advantages::where('id',$request->id)->first();
		// echo '<pre>',print_r($view_features),'</pre>';die;
		return view('adminpanel.view_features', compact('view_features','advantage_id','advantages'));
	}

	public function feature_edit(Request $request) {
		
		$view_features = Advantages_features::where('id',$request->id)->first();
		$advantage_id = $view_features->advantage_id;
		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();
			//Define the rules for validation
			$rules = array(
                'feature_name' => 'required|max:30',
            	'file' => 'image|mimes:jpeg,bmp,png|dimensions:max_height=36,max_width=36',
			);

			$message = array(
                'file.dimensions' => 'The Image dimension should be less than 36 X 36.'
            );

			$validator = Validator::make($data, $rules, $message);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/advantages/feature_edit/'.$request->id)->withErrors($validator)->withInput($request->input());
			}
			else{
				$feature_edit_data = array();
				$feature_edit_data['feature_name'] = $request->feature_name;

				if(!empty($request->file)){
					$image_name =  str_replace('/', '\\',$view_features->feature_image);
					if(file_exists(storage_path("app\public\upload\\").$image_name)){
						unlink(storage_path("app\public\upload\\").$image_name);
					}
					$filename = $request->file->store('user_advantages');
					if(!empty($filename)){
						$feature_edit_data['feature_image'] = $filename;
					}
				}
			
				//Check if values saved in database or not
				if(Advantages_features::where('id',$request->id)->update($feature_edit_data)){
					Session::flash('success', $this->success_error_messages['feature_success_update']);
					return redirect('admin/advantages/feature_edit/'.$request->id);
				} else{
					Session::flash('warning', $this->success_error_messages['feature_warn_update']);
					return redirect('admin/advantages/feature_edit/'.$request->id);
				}
			}
		}
		// echo '<pre>',print_r($view_features),'</pre>';die;
		return view('adminpanel.view_features_edit', compact('view_features','advantage_id'));
	}

	public function feature_add(Request $request, $advantage_id) {
		
		$advantage_id = $advantage_id;
		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();
			
			//Define the rules for validation
			$rules = array(
                'feature_name' => 'required|max:30',
            	'file' => 'required|image|mimes:jpeg,bmp,png|dimensions:max_height=36,max_width=36',
            	
			);

			$message = array(
                'file.dimensions' => 'The Image dimension should be less than 36 X 36.'
            );

			$validator = Validator::make($data, $rules,$message);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect('/admin/advantages/feature_add/'.$advantage_id)->withErrors($validator)->withInput($request->input());
			}
			else 
			{
				
				$filename = $request->file->store('user_advantages');
				$feature_add_data = new Advantages_features;
				$feature_add_data->advantage_id = $advantage_id;
				$feature_add_data->feature_name = $request->feature_name;
				$feature_add_data->feature_image = $filename;
				$feature_add_data->created_at = date('Y-m-d H:i:s');
				$feature_add_data->updated_at = date('Y-m-d H:i:s');
		
				//Check if values saved in database or not
				if($feature_add_data->save()){
					Session::flash('success', $this->success_error_messages['feature_add_success']);
					return redirect('admin/advantages/view_features/'.$advantage_id);
				}
			}
		}
		return view('adminpanel.feature_add' , compact('advantage_id'));
	}

	//Delete Testimonials Function start here
	public function feature_delete(Request $request) {
		$Advantages_features = Advantages_features::where('id',$request->id)->first();
		// Check if form has data
		if (!empty($Advantages_features)) {
			$image_name =  str_replace('/', '\\',$Advantages_features->feature_image);
			//check if file exist on selected location
			if(file_exists(storage_path("app\public\upload\\").$image_name)){
				unlink(storage_path("app\public\upload\\").$image_name);
			}
			//Delete the record from database
			Advantages_features::where('id',$request->id)->delete();
			Session::flash('success', $this->success_error_messages['feature_success_delete']);
					return redirect('admin/advantages/view_features/'.$Advantages_features->advantage_id);
		}
	}

	
}
