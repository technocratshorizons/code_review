<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use Session;
use Lang;
use File;
use App\User;
use App\Models\Utilities;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class LandlordController extends Controller
{
	public $success_error_messages;

	public function __construct()
    {
		$this->middleware('admin'); 
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    public function index(Request $request) {
		$Landlord = User::where('user_type','landlord')->get();
		
		return view('adminpanel.landlord',compact('Landlord'));
	}

	public function activate_deactivate (Request $request){
		$User = User::where('id',$request->id)->first();
		// Check if form has data
		if (!empty($User)) {
			if($User->is_blocked == '1'){
				$update_data['is_blocked'] = '0';
				$update_data['updated_at'] = date('Y-m-d H:i:s');
				$message = $this->success_error_messages['landlord_activate_success_active'];
			} else{
				$update_data['is_blocked'] = '1';
				$update_data['updated_at'] = date('Y-m-d H:i:s');
				$message = $this->success_error_messages['landlord_deactivat_success_active'];
			}
			//Update the record from database
			User::where('id',$request->id)->update($update_data);
			Session::flash('success', $message);
			return redirect('admin/landlord');
		}
	}

}
