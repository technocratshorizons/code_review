<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use Session;
use Lang;
use File;
use App\User;
use Mail;
use App\Models\Inquiries;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class InquiriesController extends Controller
{
	public $success_error_messages;

	public function __construct()
    {
		$this->middleware('admin'); 
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    public function index(Request $request) {
    	$Inquiries = Inquiries::orderBy('inquery_id', 'DESC')->get();
		return view('adminpanel.inquiries',compact('Inquiries'));
	}

	public function reply(Request $request) {
		
		if (!empty($_POST)) {
			$Inquiries = Inquiries::where('inquery_id',$request->inquery_id)->first();
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
                'email' => 'required',
                'reply_message' => 'required',
				
			);
			$validator = Validator::make($data, $rules);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/inquiries')->withErrors($validator)->withInput($request->input());
			}
			else{
				if($request->email){
					$update_inquery_data = array(
						'admin_reply' => $request->reply_message,
						'status' => '1',
						'updated_at' => date('Y-m-d H:i:s'),
					);
				//Check if values saved in database or not
				if(Inquiries::where('inquery_id',$request->inquery_id)->update($update_inquery_data)){
					
					/* Email Notification to user */
					

					$email_information = array('to_name'=>$request->user_name,'to_email'=>$request->email,'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Inquery Reply');
					$email_content = array('name'=>ucfirst($request->user_name),'reply_message'=>$request->reply_message,'user_question'=>$request->user_question);
                    Mail::send(['html' => 'emails.admin_inquery_reply'], $email_content, function($message) use ($email_information)
					{
						 $message->to($email_information['to_email'], $email_information['to_name'])->subject
							($email_information['subject']);
						 $message->from($email_information['from_email'],$email_information['from_name']);
					});
					Session::flash('success', $this->success_error_messages['inquery_submit_success']);
		
				} else{
					Session::flash('success', $this->success_error_messages['common_error']);
				}
				
				return redirect('admin/inquiries');
				}
				
			}
		}
		return view('adminpanel.inquiries',compact('Inquiries'));
	}
}
