<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use Session;
use Lang;
use File;
use App\Models\Parking_types;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class ParkingController extends Controller
{
	public $success_error_messages;

	public function __construct()
    {
		$this->middleware('admin'); 
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    public function index(Request $request) {
    	$parking = Parking_types::orderBy('id', 'DESC')->get();
		return view('adminpanel.parking_types',compact('parking'));
	}

	//Add  parking Function start here
	public function add(Request $request) {

		//Check if Form submit
		if (!empty($_POST)) {
			$Parking_types = Parking_types::orderBy('name', 'DESC')->get();
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
				'name' => 'unique:parking_types|required|string|max:255',
				
			);
			$validator = Validator::make($data, $rules);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/parking/add')->withErrors($validator)->withInput($request->input());
			}
			else{
				
				$parking_insert = new Parking_types;
				$parking_insert->name = $request->name;
				$parking_insert->created = date('Y-m-d H:i:s');

				//Check if values saved in database or not
				if($parking_insert->save()){
					$insert_id = $parking_insert->id;
					Session::flash('success', $this->success_error_messages['parking_add_success']);
					return redirect('admin/parking/add');
				}
			}
		}
		return view('adminpanel.parking_type_add');
	}

	//Edit  parking Function start here
	public function edit(Request $request) {
		$Parking_types = Parking_types::where('id',$request->id)->first();
		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
				'name' => 'required|string||max:255|unique:parking_types,name,'.$Parking_types->id,
			);

			$message = array(
                'name' => $this->success_error_messages['name_error_unique'],
            );

			$validator = Validator::make($data, $rules, $message);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/parking/edit/'.$request->id)->withErrors($validator)->withInput($request->input());
			}
			else{
				$parking_update = array();
				$parking_update['name'] = $request->name;
				//Check if values saved in database or not
				if(Parking_types::where('id',$request->id)->update($parking_update)){
					Session::flash('success', $this->success_error_messages['parking_update_success']);
					return redirect('admin/parking/edit/'.$request->id);
				} else{
					Session::flash('warning', $this->success_error_messages['parking_warn_update']);
					return redirect('admin/parking/edit/'.$request->id);
				}
			}
		}
		return view('adminpanel.parking_type_edit', compact('Parking_types'));
	}


	//status parking Function start here
	public function status(Request $request) {
		$Parking_types = Parking_types::where('id',$request->id)->first();
		// Check if form has data
		if (!empty($Parking_types)) {
			if($Parking_types->status == '1'){
				$update_data['status'] = '0';
				$message = $this->success_error_messages['parking_success_deactive'];
			} else{
				$update_data['status'] = '1';
				$message = $this->success_error_messages['parking_success_active'];
			}
			//Update the record from database
			Parking_types::where('id',$request->id)->update($update_data);
			Session::flash('success', $message);
			return redirect('admin/parking');
		}
	}

}
