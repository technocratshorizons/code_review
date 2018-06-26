<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use Session;
use Lang;
use File;
use App\Models\Ammenties;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class AmmentiesController extends Controller
{
	public $success_error_messages;

	public function __construct()
    {
		$this->middleware('admin'); 
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    public function index(Request $request) {
    	$Ammenties = Ammenties::orderBy('name', 'DESC')->get();
		return view('adminpanel.ammenties',compact('Ammenties'));
	}

	//Add  Ammenties Function start here
	public function add(Request $request) {

		//Check if Form submit
		if (!empty($_POST)) {
			$Ammenties = Ammenties::orderBy('name', 'DESC')->get();
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
				
                'name' => 'unique:ammenties|required|string|max:255',
            	'file' => 'required|image|mimes:jpeg,bmp,png|dimensions:max_height=200,max_width=200',
            	'ammenties_type' => 'required|string',
			);

			$message = array(
                'file.dimensions' => 'The Image dimension should be less than 200 X 200.'
            );

			$validator = Validator::make($data, $rules,$message);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/ammenties/add')->withErrors($validator)->withInput($request->input());
			}
			else{
				$filename = $request->file->store('ammenties_icons');
				$ammenties_data_insert = new Ammenties;
				$ammenties_data_insert->name = $request->name;
				$ammenties_data_insert->ammenties_type = $request->ammenties_type;
				$ammenties_data_insert->image = $filename;
				$ammenties_data_insert->created = date('Y-m-d H:i:s');

				//Check if values saved in database or not
				if($ammenties_data_insert->save()){
					$insert_id = $ammenties_data_insert->id;
					$update_testi = array();
					$update_testi['display_order'] = $insert_id;
					// Utilities::where('id',$insert_id)->update($update_testi);

					Session::flash('success', $this->success_error_messages['ammenties_success']);
					return redirect('admin/ammenties/add');
				}
			}
		}
		return view('adminpanel.ammenties_add');
	}

	//Edit  Utilities Function start here
	public function edit(Request $request) {
		$ammenties_data = Ammenties::where('id',$request->id)->first();
		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
				'name' => 'required|string|max:255|unique:ammenties,name,'.$ammenties_data->id,
				'ammenties_type' => 'required',
				'file' => 'image|mimes:jpeg,bmp,png|dimensions:max_height=200,max_width=200',
			);

			$message = array(
                'file.dimensions' => 'The Image dimension should be less than 200 X 200.'
            );

			$validator = Validator::make($data, $rules, $message);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/ammenties/edit/'.$request->id)->withErrors($validator)->withInput($request->input());
			}
			else{
				$utilities_insert_data = array();
				$utilities_insert_data['name'] = $request->name;
				$utilities_insert_data['ammenties_type'] = $request->ammenties_type;

				if(!empty($request->file)){
					$image_name =  str_replace('/', '\\',$ammenties_data->image);
					if(file_exists(storage_path("app\public\upload\\").$image_name)){
						unlink(storage_path("app\public\upload\\").$image_name);
					}
					$filename = $request->file->store('ammenties_icons');
					if(!empty($filename)){
						$utilities_insert_data['image'] = $filename;
					}
				}
			
				//Check if values saved in database or not
				if(Ammenties::where('id',$request->id)->update($utilities_insert_data)){
					Session::flash('success', $this->success_error_messages['ammenties_success_update']);
					return redirect('admin/ammenties/edit/'.$request->id);
				} else{
					Session::flash('warning', $this->success_error_messages['ammenties_warn_update']);
					return redirect('admin/ammenties/edit/'.$request->id);
				}
			}
		}
		return view('adminpanel.ammenties_edit', compact('ammenties_data'));
	}


	//Delete  Utilities Function start here
	public function status(Request $request) {
		$Ammenties = Ammenties::where('id',$request->id)->first();
		// Check if form has data
		if (!empty($Ammenties)) {
			if($Ammenties->status == '1'){
				$update_data['status'] = '0';
				$message = $this->success_error_messages['ammenties_success_deactive'];
			} else{
				$update_data['status'] = '1';
				$message = $this->success_error_messages['ammenties_success_active'];
			}
			//Update the record from database
			Ammenties::where('id',$request->id)->update($update_data);
			Session::flash('success', $message);
			return redirect('admin/ammenties');
		}
	}

}
