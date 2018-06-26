<?php

namespace App\Http\Controllers;

use DB;
use Hash;
use Auth;
use Cookie;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Mail;
use Illuminate\Support\Facades\Input;
use Lang;
use App\User;
use App\Models\Messages;
use App\Models\Inquiries;
use App\Models\Banner;
use App\Models\Properties;
use App\Models\Appointments;

class DashboardController extends Controller {

	public $success_error_messages;
	
	public function __construct()
    {
		$this->success_error_messages = Lang::get('success_error_messages');
	}
	
	public function index(Request $request) {

	    $user = Auth::user();
		
		$property_list = Properties::with('property_images')
            ->where('is_deleted', '=', '0')
			->where('user_id', '=', $user['id'])
			->orderBy('id', 'DESC')
			->paginate(2);
		
		$appointments = Appointments::with(array(
			'property'=>function($query){
				$query->select('id','property_name');
			},
			'tenant_info'=>function($query){
				$query->select('id','name','email');
			}))->orderBy('id', 'DESC')
			->where('landlord_id', $user['id'])->whereDate('date', '>=', date('Y-m-d'))
			->paginate(4);
			
		

		$message_info = Messages::with(array(
			'property'=>function($query){
				$query->select('id','property_name');
			},
			'from_info'=>function($query){
				$query->select('id','name','email','profile_pic');
			},
			'to_info'=>function($query){
				$query->select('id','name','email','profile_pic');
			}))->orderBy('id', 'DESC')
			->where('is_last', '1')->where(function ($query) use ($user) {
				$query->where('from_user', '=', $user['id'])
				->orWhere('to_user', '=', $user['id']);
			})->paginate(2);
		
		
		$message_info = $message_info->toArray();
		if(count($message_info) > 0) {
			$i=0;
			foreach($message_info['data'] as $message) {
				
				$message_info['data'][$i]['reply_count'] = DB::table('messages')->where('from_user','=', $user['id'])->where('property_id','=', $message['property_id'])->count();
				$i++;
			}
		} 
		
		$success_error_messages = $this->success_error_messages;
		
		$bannerDtails = Banner::where('slug','dashboard')->first();
		return view('dashboard', compact('user','bannerDtails','property_list','appointments','success_error_messages','message_info'));
	}
	
	public function support(Request $request) {

	    if(Auth::user())
		{
			$user = Auth::user();
		}
		else
		{
			$user = false;
		}
		// to show banner dynamic
		$bannerDtails = Banner::where('slug','dashboard')->first();
	    return view('dashboard_support',compact('user','bannerDtails'));
	}
	//Add  Support Function start here
	public function support_add(Request $request) {
		if(Auth::user()){
			$user = Auth::user();
		}
		else{
			$user = false;
		}
		//Check if Form submit
		if (!empty($_POST)) {
			$Inquiries = Inquiries::orderBy('name', 'DESC')->get();
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
                'name' => 'required|string|max:255',
                'subject' => 'required',
                'message' => 'required',
				
			);
			$validator = Validator::make($data, $rules);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect('/dashboard/support')->withErrors($validator)->withInput($request->input());
			}
			else{
				if($request->subject){
					$inquiries_insert_data = new Inquiries;
					$inquiries_insert_data->name = $request->name;
					$inquiries_insert_data->email = $user['email'];
					$inquiries_insert_data->subject = $request->subject;
					$inquiries_insert_data->message = $request->message;
					$inquiries_insert_data->created_at = date('Y-m-d H:i:s');
					$inquiries_insert_data->updated_at = date('Y-m-d H:i:s');
					//Check if values saved in database or not
					if($inquiries_insert_data->save())
					{
						
						/* Email Notification to user */
						$user_detail = User::where('id', '=', $user['id'])->get()->toArray();

						$email_information = array('to_name'=>$user_detail[0]['name'],'to_email'=>$user_detail[0]['email'],'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Thanks to Contact us');
						$email_content = array('name'=>ucfirst($user_detail[0]['name']));
						Mail::send(['html' => 'emails.thanks_support'], $email_content, function($message) use ($email_information)
						{
							 $message->to($email_information['to_email'], $email_information['to_name'])->subject
								($email_information['subject']);
							 $message->from($email_information['from_email'],$email_information['from_name']);
						});

						/* Email Notification to admin */
						$user_detail = User::where('user_type', '=', 'admin')->get()->toArray();

						$email_information = array('to_name'=>$user_detail[0]['name'],'to_email'=>$user_detail[0]['email'],'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'New Inquery');
						$email_content = array('name'=>$request->name);
						Mail::send(['html' => 'emails.admin_inquery_notify'], $email_content, function($message) use ($email_information)
						{
							 $message->to($email_information['to_email'], $email_information['to_name'])->subject
								($email_information['subject']);
							 $message->from($email_information['from_email'],$email_information['from_name']);
						});
						
						$notification = array(
							'message' =>  $this->success_error_messages['inquery_submit_success'],
							'alert-type' => 'success'
						);
						return redirect('dashboard')->with($notification);
					} 
					else{
						$notification = array(
							'message' =>  $this->success_error_messages['common_error'],
							'alert-type' => 'error_flash'
						);
					}
					return redirect('dashboard/support')->with($notification);
				}
				
			}
		}
		return view('dashboard_support',compact('user'));
	}
	
	//Add  Support Function start here
	public function faq(Request $request) {
		
		if(Auth::user()){
			$user = Auth::user();
		}
		else{
			$user = false;
		}
		$banner_info = Banner::where('slug','dashboard')->first();
		return view('dashboard_faq',compact('user','banner_info'));
	}

	
}
