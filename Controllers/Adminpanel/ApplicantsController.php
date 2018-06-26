<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use Session;
use Lang;
use File;
use App\Models\Rental_forms;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class ApplicantsController extends Controller
{
	public $success_error_messages;

	public function __construct()
    {
		$this->middleware('admin'); 
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    public function index(Request $request) {
        $applicants = Rental_forms::with(array(
            'property'=>function($query) {
                $query->select('id','property_name');
            },
            'tenant_info'=>function($query){
                $query->select('id','name','email');
            },))->get();
    	
		return view('adminpanel.rental_applicants',compact('applicants'));
    }
    
   
}
