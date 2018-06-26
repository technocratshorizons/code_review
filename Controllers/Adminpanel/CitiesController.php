<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use Session;
use Lang;
use File;
use App\Models\Ammenties;
use App\Models\Cities;
use App\Models\Stats;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class CitiesController extends Controller
{
	public $success_error_messages;

	public function __construct()
    {
		$this->middleware('admin'); 
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    public function index(Request $request) {
    	$Cities = Cities::orderBy('city_name', 'ASC')->get();
		return view('adminpanel.cities_index',compact('Cities'));
	}

	//Add  city Function start here
	public function add(Request $request) {

		//Check if Form submit
		if (!empty($_POST)) {
			$Cities = Cities::orderBy('id', 'DESC')->get();
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
                'city_name' => 'unique:cities|required',
            	'file' => 'required|image|mimes:jpeg,bmp,png',
            	'state_code' => 'required',
			);

			$message = array(
                
            );

			$validator = Validator::make($data, $rules,$message);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/cities/add')->withErrors($validator)->withInput($request->input());
			}
			else{
				$filename = $request->file->store('cities_images');
				$city_insert = new Cities;
                $city_insert->city_name = $request->city_name;
                $city_insert->state_code = $request->state_code;
				$city_insert->image = $filename;
				$city_insert->created_at = date('Y-m-d H:i:s');
				$city_insert->updated_at = date('Y-m-d H:i:s');

				//Check if values saved in database or not
				if($city_insert->save()){
					$insert_id = $city_insert->id;
					Session::flash('success', $this->success_error_messages['city_add_success']);
					return redirect('admin/cities/add');
				}
			}
		}
		return view('adminpanel.cities_add');
	}

	//active deactive status Function start here
	public function status(Request $request) {
		$cities = Cities::where('id',$request->id)->first();
		// Check if form has data
		if (!empty($cities)) {
			if($cities->status == '1'){
				$update_data['status'] = '0';
				$update_data['updated_at'] = date('Y-m-d H:i:s');
				$message = $this->success_error_messages['city_success_deactive'];
			} else{
				$update_data['status'] = '1';
				$update_data['updated_at'] = date('Y-m-d H:i:s');
				$message = $this->success_error_messages['city_success_active'];
			}
			//Update the record from database
			Cities::where('id',$request->id)->update($update_data);
			Session::flash('success', $message);
			return redirect('admin/cities');
		}
	}

	//Edit  cities Function start here
	public function edit(Request $request) {
		$city_data = Cities::where('id',$request->id)->first();
		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
				'city_name' => 'required|unique:cities,city_name,'.$city_data->id,
				'file' => 'image|mimes:jpeg,bmp,png',
            	'state_code' => 'required',
			);

			$message = array(
                'city_name' => $this->success_error_messages['name_error_unique'],
            );

			$validator = Validator::make($data, $rules, $message);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/cities/edit/'.$request->id)->withErrors($validator)->withInput($request->input());
			}
			else{
				$cities_edit = array();
				$cities_edit['city_name'] = $request->city_name;
				$cities_edit['state_code'] = $request->state_code;
				

				if(!empty($request->file)){
					$image_name =  str_replace('/', '\\',$city_data->image);
					if(file_exists(storage_path("app\public\upload\\").$image_name)){
						unlink(storage_path("app\public\upload\\").$image_name);
					}
					$filename = $request->file->store('cities_images');
					if(!empty($filename)){
						$cities_edit['image'] = $filename;
					}
				}
				//Check if values saved in database or not
				if(Cities::where('id',$request->id)->update($cities_edit)){
					$cities_edit['updated_at'] = date('Y-m-d H:i:s');
					Cities::where('id',$request->id)->update($cities_edit);
					
					Session::flash('success', $this->success_error_messages['city_success_update']);
					return redirect('admin/cities/edit/'.$request->id);
				} else{
					Session::flash('warning', $this->success_error_messages['city_warn_update']);
					return redirect('admin/cities/edit/'.$request->id);
				}
			}
		}
		return view('adminpanel.cities_edit', compact('city_data'));
	}

	public function city_stats(Request $request){

		$stats = Stats::where('city_id',$request->id)->get();
		$city_id = $request->id;
		// echo '<pre>',print_r($view_features),'</pre>';die;
		return view('adminpanel.city_stats_index', compact('stats','city_id'));
	}
	

	public function stats_add(Request $request, $city_id) {
		
		$city_id = $city_id;
		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();
			//Define the rules for validation
			$rules = array(
				'studio' => 'required',
				'bed_1' => 'required',
				'bed_2' => 'required',
				'bed_3' => 'required',
				'bed_4_more' => 'required',
			);

			$validator = Validator::make($data, $rules);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect('/admin/cities/stats_add/'.$city_id)->withErrors($validator)->withInput($request->input());
			}
			else 
			{
				
				$Stats_insert = new Stats;
				$Stats_insert->city_id = $city_id;
				$Stats_insert->studio = str_replace(',','',$request->studio);
				$Stats_insert->bed_1 = str_replace(',','',$request->bed_1);
				$Stats_insert->bed_2 = str_replace(',','',$request->bed_2);
				$Stats_insert->bed_3 = str_replace(',','',$request->bed_3);
				$Stats_insert->bed_4_more = str_replace(',','',$request->bed_4_more);

				$Stats_insert->created_at = date('Y-m-d H:i:s');
				$Stats_insert->modified_at = date('Y-m-d H:i:s');
		
				//Check if values saved in database or not
				if($Stats_insert->save()){
					Session::flash('success', $this->success_error_messages['stat_add_success']);
					return redirect('admin/cities/stats_add/'.$city_id);
				}
			}
		}
		return view('adminpanel.city_stats_add', compact('city_id'));
	}

	

	public function stat_edit(Request $request) {
		
		$Stats = Stats::where('id',$request->id)->first();
		$city_id = $Stats->city_id;
		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();
			//Define the rules for validation
			$rules = array(
                'studio' => 'required',
				'bed_1' => 'required',
				'bed_2' => 'required',
				'bed_3' => 'required',
				'bed_4_more' => 'required',
			);

			$validator = Validator::make($data, $rules);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/cities/stats_edit/'.$request->id)->withErrors($validator)->withInput($request->input());
			}
			else{
				$stat_edit = array();
				$stat_edit['studio'] = str_replace(',','',$request->studio);
				$stat_edit['bed_1'] = str_replace(',','',$request->bed_1);
				$stat_edit['bed_2'] = str_replace(',','',$request->bed_2);
				$stat_edit['bed_3'] = str_replace(',','',$request->bed_3);
				$stat_edit['bed_4_more'] = str_replace(',','',$request->bed_4_more);

				
				//Check if values saved in database or not
				if(Stats::where('id',$request->id)->update($stat_edit)){
					Session::flash('success', $this->success_error_messages['stat_update_success']);
					return redirect('admin/cities/stats_edit/'.$request->id);
				} else{
					Session::flash('warning', $this->success_error_messages['stat_warn_update']);
					return redirect('admin/cities/stats_edit/'.$request->id);
				}
			}
		}
		// echo '<pre>',print_r($view_features),'</pre>';die;
		return view('adminpanel.city_stats_edit', compact('Stats','city_id'));
	}

	

	//Delete Testimonials Function start here
	public function stats_delete(Request $request) {
		$Stats = Stats::where('id',$request->id)->first();
		// Check if form has data
		if (!empty($Stats)) {
			
			//Delete the record from database
			Stats::where('id',$request->id)->delete();
			Session::flash('success', $this->success_error_messages['stats_success_delete']);
					return redirect('admin/cities/city_stats/'.$Stats->city_id);
		}
	}

}
