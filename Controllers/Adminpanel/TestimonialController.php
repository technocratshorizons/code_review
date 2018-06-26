<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use Session;
use Lang;
use File;
use App\Models\Testimonial;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class TestimonialController extends Controller
{
	public $success_error_messages;

	public function __construct()
    {
		$this->middleware('admin'); 
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    public function index(Request $request) {
    	$testimonials = Testimonial::orderBy('display_order', 'DESC')->get();
		return view('adminpanel.testimonial',compact('testimonials'));
	}

	//Add Testimonials Function start here
	public function add(Request $request) {

		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();
			
			//Define the rules for validation
			$rules = array(
                'name' => 'required|string|max:255',
            	'file' => 'required|image|mimes:jpeg,bmp,png|dimensions:max_height=500,max_width=500',
            	'description' => 'required|string',
			);

			$message = array(
                'file.dimensions' => 'The Image dimension should be less than 500 X 500.'
            );

			$validator = Validator::make($data, $rules,$message);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect('/admin/testimonials/add')->withErrors($validator)->withInput($request->input());
			}
			else 
			{
				
				$filename = $request->file->store('testimonials');
				$testimonail_insert_data = new Testimonial;
				$testimonail_insert_data->name = $request->name;
				$testimonail_insert_data->description = htmlentities($request->description);
				$testimonail_insert_data->image = $filename;
				$testimonail_insert_data->created = date('Y-m-d H:i:s');
		
				//Check if values saved in database or not
				if($testimonail_insert_data->save()){
					$insert_id = $testimonail_insert_data->id;
					$update_testi = array();
					$update_testi['display_order'] = $insert_id;
					Testimonial::where('id',$insert_id)->update($update_testi);

					Session::flash('success', $this->success_error_messages['testimonial_success']);
					return redirect('admin/testimonials/add');
				}
			}
		}
		return view('adminpanel.testimonial_add');
	}

	//Edit Testimonials Function start here
	public function edit(Request $request) {
		$testimonial = Testimonial::where('id',$request->id)->first();
		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
                'name' => 'required|string|max:255',
            	'file' => 'image|mimes:jpeg,bmp,png|dimensions:max_height=500,max_width=500',
            	'description' => 'required|string',
			);

			$message = array(
                'file.dimensions' => 'The Image dimension should be less than 500 X 500.'
            );

			$validator = Validator::make($data, $rules, $message);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/testimonials/edit/'.$request->id)->withErrors($validator)->withInput($request->input());
			}
			else{
				$testimonail_insert_data = array();
				$testimonail_insert_data['name'] = $request->name;
				$testimonail_insert_data['description'] = htmlentities($request->description);

				if(!empty($request->file)){
					$image_name =  str_replace('/', '\\',$testimonial->image);
					if(file_exists(storage_path("app\public\upload\\").$image_name)){
						unlink(storage_path("app\public\upload\\").$image_name);
					}
					$filename = $request->file->store('testimonials');
					if(!empty($filename)){
						$testimonail_insert_data['image'] = $filename;
					}
				}
			
				//Check if values saved in database or not
				if(Testimonial::where('id',$request->id)->update($testimonail_insert_data)){
					Session::flash('success', $this->success_error_messages['testimonial_success_update']);
					return redirect('admin/testimonials/edit/'.$request->id);
				} else{
					Session::flash('warning', $this->success_error_messages['testimonial_warn_update']);
					return redirect('admin/testimonials/edit/'.$request->id);
				}
			}
		}
		return view('adminpanel.testimonial_edit', compact('testimonial'));
	}


	//Delete Testimonials Function start here
	public function delete(Request $request) {
		$testimonial = Testimonial::where('id',$request->id)->first();
		// Check if form has data
		if (!empty($testimonial)) {
			$image_name =  str_replace('/', '\\',$testimonial->image);
			//check if file exist on selected location
			if(file_exists(storage_path("app\public\upload\\").$image_name)){
				unlink(storage_path("app\public\upload\\").$image_name);
			}
			//Delete the record from database
			Testimonial::where('id',$request->id)->delete();
			Session::flash('success', $this->success_error_messages['testimonial_success_delete']);
					return redirect('admin/testimonials');
		}
	}


	//Order  Testimonials Function start here
	public function order(Request $request) {
		//Check if Form submit
		if (!empty($_POST)) {
			$data = json_decode($request->sorted_value);
			if(!empty($data)){
				foreach ($data as $key => $item) {
					$update_testi = array();
					$update_testi['display_order'] = $item->index;
					Testimonial::where('id',$item->id)->update($update_testi);
				}
				Session::flash('success', $this->success_error_messages['testimonial_success_order']);
				return redirect('admin/testimonials/order');
			}
		}
		$testimonials = Testimonial::orderBy('display_order', 'asc')->get();
		return view('adminpanel.testimonial_order',compact('testimonials'));
	}
}
