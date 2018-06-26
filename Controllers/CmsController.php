<?php

namespace App\Http\Controllers;

use DB;
use Hash;
use Auth;
use Cookie;
use Session;
use Redirect;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Banner;
use App\Models\Faq;
use App\Models\Testimonial;
use App\Models\Cities;
use App\Models\Properties;
use App\Models\Advantages;
use App\Models\Inquiries;
use App\Models\Settings;
use App\Models\Appointments;
use Lang;
use Mail;
use Illuminate\Support\Facades\Input;

class CmsController extends Controller {

	public function __construct()
    {
		//$this->middleware('guest');
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    //privacy_policy function START
    public function privacy_policy(Request $request) {
    	if(Auth::user()){
            $user = Auth::user();
        }else{
            $user = false;
        }
        $cms  = DB::table('cms')->where('slug','privacy_policy')->get()->first();
        $banner_info = Banner::where('slug','front_screen')->first();
       	return view('privacy_policy',compact('user','banner_info','cms'));
    }

    //terms_condition function START
    public function terms_condition(Request $request) {
    	if(Auth::user()){
    		$user = Auth::user();
    	}else{
    		$user = false;
    	}
        $cms  = DB::table('cms')->where('slug','terms_condition')->get()->first();
        $banner_info = Banner::where('slug','front_screen')->first();
       	return view('terms_condition',compact('user','banner_info','cms'));
    }


    //terms_condition function START
    public function faq(Request $request) {
    	if(Auth::user()){
    		$user = Auth::user();
    	}else{
    		$user = false;
    	}
        $faq_details  = Faq::orderBy('display_order', 'asc')->get();
        $banner_info = Banner::where('slug','front_screen')->first();
       	return view('faq_index',compact('user','banner_info','faq_details'));
    }
	
	public function contact_us(Request $request) {
    	
		if(Auth::user()){
    		$user = Auth::user();
    	}
		else{
    		$user = false;
		}
		
		if (!empty($_POST)) {
			$inputData = Input::all();
			$rules = array(
                'name' 		=> 'required',
                'email' 	=> 'required',
                'subject' 	=> 'required',
                'message' 	=> 'required',
               

            );
            $validator = Validator::make($inputData, $rules);
            if ($validator->fails()) {
                $notification = array(
                    'message' =>  $this->success_error_messages['fill_all_required_fields'],
                    'alert-type' => 'error'
				);
				
				return redirect('/contact_us'.$request->property_id)->withErrors($validator)->withInput($request->input());
            } else{
				if($request->subject){
					$inquiries_insert_data = new Inquiries;
					$inquiries_insert_data->name = $request->name;
					$inquiries_insert_data->email = $request->email;
					$inquiries_insert_data->subject = $request->subject;
					$inquiries_insert_data->message = $request->message;
					$inquiries_insert_data->created_at = date('Y-m-d H:i:s');
					$inquiries_insert_data->updated_at = date('Y-m-d H:i:s');
					//Check if values saved in database or not
					if($inquiries_insert_data->save())
					{
						/* Email Notification to user */
						

						$email_information = array('to_name'=>$request->name,'to_email'=>$request->email,'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Thanks to Contact us');
						$email_content = array('name'=>ucfirst($request->name));
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
						return redirect('/contact_us')->with($notification);
					} 
					else{
						$notification = array(
							'message' =>  $this->success_error_messages['common_error'],
							'alert-type' => 'error_flash'
						);
					}
					return redirect('/contact_us')->with($notification);
				}
				
			}
		}
		
		$banner_info = Banner::where('slug','front_screen')->first();
       	return view('contact_us',compact('user','banner_info'));
	}
	
	public function not_found(Request $request){
		if(Auth::user()){
			$user = Auth::user();
		}
		else{
			$user = false;
		}
		$banner_info = Banner::where('slug','dashboard')->first();

		// echo '<pre>',print_r($banner_info),'</pre>';die;
		return view('not_found',compact('user','banner_info'));
	}


	// cron job function start here

	// for remind lanlord 
	public function appointment_reminder_lanlord(Request $request){
		$appoint_information = User::with(array(
			'landlord_appointment'=>function($query){
				$query->select('id','property_id','landlord_id','date','landlord_reminder','time');
			},'landlord_appointment.property'))->orderBy('id', 'Asc')
			->has('landlord_appointment')->get()->toArray();
			//echo '<pre>',print_r($appoint_information),'</pre>';die;
			 if(isset($appoint_information) && count($appoint_information)>0 ){
				// for each lanlord 
				foreach($appoint_information as $landlord_appoint_info){
					$lanlord_data = array();
					$lanlord_data['email'] = $landlord_appoint_info['email'];
					$lanlord_data['name'] = $landlord_appoint_info['name'];
					//  echo '<pre>',print_r($landlord_appoint_info),'</pre>';die;
					
					// for each property
					$property_data = array();
					foreach($landlord_appoint_info['landlord_appointment'] as $property_info){
						//  echo '<pre>',print_r($property_info),'</pre>';die;
						$property_data_info['appointment_date'] = $property_info['date'];
						$property_data_info['time'] = $property_info['time'];
						$property_data_info['property_name'] = $property_info['property']['property_name'];
						$property_data[] = $property_data_info;

						$insert_data['landlord_reminder'] = '1';
						$insert_data['updated_at'] = date('Y-m-d H:i:s');
						
						// update appointments table
						DB::table('appointments')
							->where('id',$property_info['id'])
							->update($insert_data);
					}
					// echo '<pre>',print_r($property_data),'</pre>';die;

					/* Email Notification to Landlord*/
					$email_information = array('to_name'=>$lanlord_data['name'],'to_email'=>$lanlord_data['email'],'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Appointment Reminder');
					$email_content = array('property_data'=>$property_data,'to_name'=>$lanlord_data['name']);
					Mail::send(['html' => 'emails.appointment_reminder'], $email_content, function($message) use ($email_information)
					{
						$message->to($email_information['to_email'], $email_information['to_name'])->subject
							($email_information['subject']);
						$message->from($email_information['from_email'],$email_information['from_name']);
					});

					
					/* Email Notification to Lanlord */
				}
					
			}
	}

	// for remind tenant
	public function appointment_reminder_tenant(Request $request){
		$appoint_information = User::with(array(
			'tenant_appointment'=>function($query){
				$query->select('id','property_id','user_id','date','tenant_reminder','time');
			},'tenant_appointment.property'))
			->orderBy('user_type', 'tenant')
			->orderBy('id', 'Asc')
			->has('tenant_appointment')->get()->toArray();
			// echo '<pre>',print_r($appoint_information),'</pre>';die;
			 if(isset($appoint_information) && count($appoint_information)>0 ){
				// for each lanlord 
				foreach($appoint_information as $landlord_appoint_info){
					$lanlord_data = array();
					$lanlord_data['email'] = $landlord_appoint_info['email'];
					$lanlord_data['name'] = $landlord_appoint_info['name'];
					//  echo '<pre>',print_r($landlord_appoint_info),'</pre>';die;
					
					// for each property
					$property_data = array();
					foreach($landlord_appoint_info['tenant_appointment'] as $property_info){
						//  echo '<pre>',print_r($property_info),'</pre>';die;
						$property_data_info['appointment_date'] = $property_info['date'];
						$property_data_info['time'] = $property_info['time'];
						$property_data_info['property_name'] = $property_info['property']['property_name'];
						$property_data[] = $property_data_info;

						$insert_data['tenant_reminder'] = '1';
						$insert_data['updated_at'] = date('Y-m-d H:i:s');
						
						// update appointments table
						DB::table('appointments')
							->where('id',$property_info['id'])
							->update($insert_data);
					}
					// echo '<pre>',print_r($property_data),'</pre>';die;

					/* Email Notification to Landlord*/
					$email_information = array('to_name'=>$lanlord_data['name'],'to_email'=>$lanlord_data['email'],'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Appointment Reminder');
					$email_content = array('property_data'=>$property_data,'to_name'=>$lanlord_data['name']);
					Mail::send(['html' => 'emails.appointment_reminder'], $email_content, function($message) use ($email_information)
					{
						$message->to($email_information['to_email'], $email_information['to_name'])->subject
							($email_information['subject']);
						$message->from($email_information['from_email'],$email_information['from_name']);
					});

					
					/* Email Notification to Lanlord */
				}
					
			}
	}

	// cron-job funtion for expire appointment
	public function check_appointments() {	
		// get all appointment less than today date and time and whose status is pending
		$appoint_information = Appointments::with(array(
			'property'=>function($query){
			$query->select('id','property_name');
			},
			'tenant_info'=>function($query){
				$query->select('id','name','email');
			},))->orderBy('id', 'DESC')
			->where('status', '=', '0')
			->where('date', '<', date('Y-m-d'))
			->where('time', '<', date('h:i A'))->get();

		 if(isset($appoint_information) && count($appoint_information)>0 ){
			foreach($appoint_information as $appoint_info){
				$prevoius_status = 'pending';
				$current_status = 'expire';
				
				$insert_data['status'] = '3';
				$insert_data['updated_at'] = date('Y-m-d H:i:s');
				
				// update appointments table
				DB::table('appointments')
					->where('id',$request->appointment_id)
					->update($insert_data);
						
				$update_data['is_last'] = '0';
				$update_data['updated_at'] = date('Y-m-d H:i:s');
				
				// update message table
				DB::table('messages')->where(function ($query) use ($appoint_info) {
					$query->where('from_user', '=', $appoint_info->landlord_id)->where('to_user', '=', $appoint_info->user_id);})->orWhere(function ($query) use ($appoint_info) {
						$query->where('to_user', '=', $appoint_info->user_id)->Where('from_user', '=', $appoint_info->landlord_id);})->Where(function ($query) use ($appoint_info) {
						$query->where('property_id', '=', $appoint_info->property_id);})->update($update_data);

				/* Message sent to tenant */
				$message_insert_data['from_user'] = $appoint_info->landlord_id;
				$message_insert_data['to_user'] = $appoint_info->user_id;
				$message_insert_data['property_id'] = $appoint_info->property_id;
				$message_insert_data['message'] = "Your appointment status has been changed from " .$prevoius_status." to  expire for property ".ucfirst($appoint_info->property->property_name)." on date ".$appoint_info->date." at ".$appoint_info->time;
				$message_insert_data['read_status'] = '0';
				$message_insert_data['is_last'] = '1';
				$message_insert_data['created_at'] = date('Y-m-d H:i:s');
				$message_insert_data['updated_at'] = date('Y-m-d H:i:s');
				DB::table('messages')->insert($message_insert_data);
				/* End Here */
			
				/* Email Notification to tenant*/
				$email_information = array('to_name'=>$appoint_info->tenant_info->name,'to_email'=>$appoint_info->tenant_info->email,'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Appointment Expire');
				$email_content = array('property_name'=>ucfirst($appoint_info->property->property_name),'tenant_name'=>ucfirst($appoint_info->tenant_info->name),'date'=>$appoint_info->date,'time'=>$appoint_info->time,'prevoius_status'=>$prevoius_status,'current_status'=>$current_status);
				Mail::send(['html' => 'emails.appointment_rejected'], $email_content, function($message) use ($email_information)
				{
					$message->to($email_information['to_email'], $email_information['to_name'])->subject
						($email_information['subject']);
					$message->from($email_information['from_email'],$email_information['from_name']);
				});
			}
		}	
	}

	// cron job function for availability
	public function property_availability(){
		$property_info = User::with(array(
			'landlord_available_property'=>function($query){
				$query->select('id','user_id','available_from','is_rented','is_deleted','is_deleted','property_name');
			},))
			->where('user_type','=','landlord')
			->orderBy('id', 'Asc')
			->has('landlord_available_property')->get()->toArray();
			// echo '<pre>',print_r($property_info),'</pre>';die;

			if(isset($property_info) && count($property_info)>0 ){
				// for each lanlord 
				foreach($property_info as $property_availabilty_info){
					$lanlord_data = array();
					$lanlord_data['email'] = $property_availabilty_info['email'];
					$lanlord_data['name'] = $property_availabilty_info['name'];
					//   echo '<pre>',print_r($property_availabilty_info),'</pre>';die;
					
					// for each property
					$property_data = array();
					foreach($property_availabilty_info['landlord_available_property'] as $property_available){
						//  echo '<pre>',print_r($property_available),'</pre>';die;
						$property_data_info['available_date'] = $property_available['available_from'];
						$property_data_info['property_name'] = $property_available['property_name'];
						$property_data[] = $property_data_info;
					}
					// echo '<pre>',print_r($property_data),'</pre>';die;

					/* Email Notification to Landlord*/
					$email_information = array('to_name'=>$lanlord_data['name'],'to_email'=>$lanlord_data['email'],'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Property Availability Reminder');
					$email_content = array('property_data'=>$property_data,'to_name'=>$lanlord_data['name']);
					Mail::send(['html' => 'emails.property_availability_reminder'], $email_content, function($message) use ($email_information)
					{
						$message->to($email_information['to_email'], $email_information['to_name'])->subject
							($email_information['subject']);
						$message->from($email_information['from_email'],$email_information['from_name']);
					});

					
					/* Email Notification to Lanlord */
				}
					
			}
	}

	// cron job function end here
}
