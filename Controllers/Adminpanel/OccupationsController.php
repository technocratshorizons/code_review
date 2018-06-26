<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use Session;
use Lang;
use File;
use App\Models\Occupations;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class OccupationsController extends Controller
{
	public $success_error_messages;

	public function __construct()
    {
		$this->middleware('admin'); 
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    public function index(Request $request) {
    	$occupations = occupations::orderBy('name', 'DESC')->get();
		return view('adminpanel.occupations',compact('occupations'));
	}

	//Add  occupations  Function start here
	public function add(Request $request) {

		//Check if Form submit
		if (!empty($_POST)) {
			$occupations = Occupations::orderBy('name', 'DESC')->get();
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
				'name' => 'unique:occupations|required|max:255',
				
			);
			$validator = Validator::make($data, $rules);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/occupations/add')->withErrors($validator)->withInput($request->input());
			}
			else{
				
				$occupations_insert = new Occupations;
				$occupations_insert->name = $request->name;
                $occupations_insert->created_at = date('Y-m-d H:i:s');
                $occupations_insert->updated_at = date('Y-m-d H:i:s');

				//Check if values saved in database or not
				if($occupations_insert->save()){
					$insert_id = $occupations_insert->id;
					Session::flash('success', $this->success_error_messages['occupations_success']);
					return redirect('admin/occupations/add');
				}
			}
		}
		return view('adminpanel.occupations_add');
	}

	//Edit Occupations Function start here
	public function edit(Request $request) {
		$occupations_data = Occupations::where('id',$request->id)->first();
		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
				'name' => 'required|max:255|unique:occupations,name,'.$occupations_data->id,
			);

			$message = array(
                'name' => $this->success_error_messages['name_error_unique'],
            );

			$validator = Validator::make($data, $rules, $message);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/occupations/edit/'.$request->id)->withErrors($validator)->withInput($request->input());
			}
			else{
				$Occupations_insert = array();
                $Occupations_insert['name'] = $request->name;
                
				//Check if values saved in database or not
				if(Occupations::where('id',$request->id)->update($Occupations_insert)){
                    $Occupations_insert['updated_at'] = date('Y-m-d H:i:s');
                    Occupations::where('id',$request->id)->update($Occupations_insert);

					Session::flash('success', $this->success_error_messages['occupations_success_update']);
					return redirect('admin/occupations/edit/'.$request->id);
				} else{
					Session::flash('warning', $this->success_error_messages['common_warn_update']);
					return redirect('admin/occupations/edit/'.$request->id);
				}
			}
		}
		return view('adminpanel.occupations_edit', compact('occupations_data'));
	}


	//Delete  occupations Function start here
	public function status(Request $request) {
		$occupations = Occupations::where('id',$request->id)->first();
		// Check if form has data
		if (!empty($occupations)) {
			if($occupations->status == '1'){
                $update_data['status'] = '0';
                $update_data['updated_at'] = date('Y-m-d H:i:s');
				$message = $this->success_error_messages['occupations_success_deactive'];
			} else{
                $update_data['status'] = '1';
                $update_data['updated_at'] = date('Y-m-d H:i:s');
				$message = $this->success_error_messages['occupations_success_active'];
			}
			//Update the record from database
			Occupations::where('id',$request->id)->update($update_data);
			Session::flash('success', $message);
			return redirect('admin/occupations');
		}
	}

}
