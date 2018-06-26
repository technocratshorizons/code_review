<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use App\User;
use App\Savingdiary;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class DashboardController extends Controller
{
	public function __construct()
    {
		 $this->middleware('admin'); 
    }
	
    public function index(Request $request) {
		$bannerDtails = Banner::where('slug','dashboard')->first();
		return view('adminpanel.dashboard');
	}
}
