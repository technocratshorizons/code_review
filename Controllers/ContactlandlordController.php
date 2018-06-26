<?php

namespace App\Http\Controllers;

use DB;
use Hash;
use Auth;
use Cookie;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Address;
use Mail;
use App\Mortgage;
use App\Savingdiary;
use App\Category;
use App\Advice;
use App\Test;
use App\Answer;
use App\TestDetail;
use Illuminate\Support\Facades\Input;

class ContactlandlordController extends Controller {

	public function __construct()
    {
		$this->middleware('guest');
    }
	
	public function index(Request $request) {
	    return view('contact_landlord');
    }
	
}
