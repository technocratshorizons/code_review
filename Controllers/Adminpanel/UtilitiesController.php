<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use Session;
use Lang;
use File;
use App\Models\Utilities;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class UtilitiesController extends Controller
{
	public $success_error_messages;

	public function __construct()
    {
		$this->middleware('admin'); 
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    public function index(Request $request) {
    	$Utilities = Utilities::orderBy('name', 'DESC')->get();
		return view('adminpanel.utilities',compact('Utilities'));
	}

	//Add  Utilities Function start here
	public function add(Request $request) {

		//Check if Form submit
		if (!empty($_POST)) {
			$Utilities = Utilities::orderBy('name', 'DESC')->get();
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
				'name' => 'unique:utilities|required|string|max:255',
				
			);
			$validator = Validator::make($data, $rules);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/utilities/add')->withErrors($validator)->withInput($request->input());
			}
			else{
				
				$utilities_type_insert_data = new Utilities;
				$utilities_type_insert_data->name = $request->name;
				$utilities_type_insert_data->created = date('Y-m-d H:i:s');
				// $utilities_type_insert_data->updated = date('Y-m-d H:i:s');
				//Check if values saved in database or not
				if($utilities_type_insert_data->save()){
					$insert_id = $utilities_type_insert_data->id;
					Session::flash('success', $this->success_error_messages['utilities__success']);
					return redirect('admin/utilities/add');
				}
			}
		}
		return view('adminpanel.utilities_add');
	}

	//Edit  Utilities Function start here
	public function edit(Request $request) {
		$utilities_data = Utilities::where('id',$request->id)->first();
		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
				'name' => 'required|string|max:255|unique:utilities,name,'.$utilities_data->id,
			);

			$message = array(
                'name' => $this->success_error_messages['name_error_unique'],
            );

			$validator = Validator::make($data, $rules, $message);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/utilities/edit/'.$request->id)->withErrors($validator)->withInput($request->input());
			}
			else{
				$utilities_insert_data = array();
				$utilities_insert_data['name'] = $request->name;
				//Check if values saved in database or not
				if(Utilities::where('id',$request->id)->update($utilities_insert_data)){
					Session::flash('success', $this->success_error_messages['utilities_success_update']);
					return redirect('admin/utilities/edit/'.$request->id);
				} else{
					Session::flash('warning', $this->success_error_messages['utilities_warn_update']);
					return redirect('admin/utilities/edit/'.$request->id);
				}
			}
		}
		return view('adminpanel.utilities_edit', compact('utilities_data'));
	}


	//Delete  Utilities Function start here
	public function status(Request $request) {
		$utilities = Utilities::where('id',$request->id)->first();
		// Check if form has data
		if (!empty($utilities)) {
			if($utilities->status == '1'){
				$update_data['status'] = '0';
				$message = $this->success_error_messages['utilities_success_deactive'];
			} else{
				$update_data['status'] = '1';
				$message = $this->success_error_messages['utilities_success_active'];
			}
			//Update the record from database
			Utilities::where('id',$request->id)->update($update_data);
			Session::flash('success', $message);
			return redirect('admin/utilities');
		}
	}

}
