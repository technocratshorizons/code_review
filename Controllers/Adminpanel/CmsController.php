<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use Session;
use Lang;
use File;
use App\Models\Cms;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class CmsController extends Controller
{
	public $success_error_messages;

	public function __construct()
    {
		$this->middleware('admin'); 
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    // public function index(Request $request) {
    // 	$testimonials = Testimonial::orderBy('display_order', 'DESC')->get();
	// 	return view('adminpanel.testimonial',compact('testimonials'));
    // }
    
    public function privacy_policy(Request $request) {
    	$Cms = Cms::orderBy('id', 'DESC')->where('slug','privacy_page')->get();

        // echo '<pre>',print_r($User_advantages),'</pre>';die;
        return view('adminpanel.privacy_policy',compact('Cms'));
    }

    public function edit_cms(Request $request) {
    	$Cms = Cms::orderBy('id', 'DESC')->where('slug',$request->id)->get()->first();
		if (!empty($_POST)) {
			$data = Input::all();
			
			//Define the rules for validation
			$rules = array(
                'content' => 'required|string',
			);

			$validator = Validator::make($data, $rules);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect('/admin/page/'.$request->id)->withErrors($validator)->withInput($request->input());
			}
			else 
			{
				$update_data['content'] = base64_encode($request->content);
				DB::table('cms')->where('id',$request->page_id)->update($update_data);

				Session::flash('success', $Cms->page_name.' has been updated successfully.');
				return redirect('/admin/page/'.$request->id);
			}
		}
        // echo '<pre>',print_r($User_advantages),'</pre>';die;
        return view('adminpanel.page_cms',compact('Cms'));
    }

}
