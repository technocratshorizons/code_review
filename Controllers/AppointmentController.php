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
use App\Models\Ammenties;
use App\Models\Utilities;
use App\Models\Banner;
use App\Models\Appointments;
use App\Models\Messages;


class AppointmentController extends Controller {

	public $success_error_messages;
	
	public function __construct()
    {
		$this->success_error_messages = Lang::get('success_error_messages');
	}
	
	public function index(Request $request) {
        
        $user = Auth::user();
        if (!empty($_POST)) {
            
		}
		
		$appointments = Appointments::with(array(
			'property'=>function($query){
			$query->select('id','property_name');
			},
			'tenant_info'=>function($query){
				$query->select('id','name','email');
			},))->orderBy('id', 'DESC')
			->where('landlord_id', $user['id'])->get();
			
		$banner_info = Banner::where('slug','dashboard')->first();
		return view('appointment_index', compact('user','banner_info','appointments'));
	}

	public function fetch_appointments(Request $request) {
        
        $user = Auth::user();
			$columns = array( 
				0 => "Id",
				1 => "Tenant Name",
				2 => "Email",
				3 => "Property Name",
				4 => "Date",
				5 => "Time",
				6 => "Status",
				7 => "Action"
			);
			
			$totalData = Appointments::where('landlord_id',$user['id'])->count();
			$totalFiltered = $totalData; 

			$limit = $request->input('length');
			$start = $request->input('start');
			$order = $columns[$request->input('order.0.column')];
			$dir = $request->input('order.0.dir');
			
			
			$status = $request->input('status');
			$date = $request->input('date');
			
			// for search over all
			$where = array(array('landlord_id', '=', $user['id']));
			$where_property = array();
			
			if(isset($status) && $status!="") {
				$where[] = array('status', '=', $status);
			}
			if(isset($date) && !empty($date)) {
				$dates = explode('-',$request->input('date'));
				
				$fromdate = date("Y-m-d", strtotime($dates['0']));
				$enddate = date("Y-m-d", strtotime($dates['1']));

				$where[] = array('date', '>=', $fromdate);
				$where[] = array('date', '<=', $enddate);
			}
			
			$appointments = Appointments::with(array(
					'property'=>function($query) use ($where_property) {
						$query->select('id','property_name');
					},
					'tenant_info'=>function($query){
						$query->select('id','name','email');
					},))
					->where($where)
					->offset($start)
					->limit($limit)
					->orderBy($order,$dir)
					->get();
						
			$totalFiltered = Appointments::where($where)->count();
			$data = array();
			
			if(!empty($appointments))
			{
				foreach ($appointments as $appointment)
				{
					if($appointment->status == '1')
					{
						$appointment_status = "Accepted";
					}
					else if($appointment->status == '0')
					{
						$appointment_status = "Pending";
					}
					else if($appointment->status == '3')
					{
						$appointment_status = "Expired";
					}
					else
					{
						$appointment_status = "Rejected";
					}
					$nestedData['Id'] = $appointment->id;
					$nestedData['Tenant Name'] = $appointment->tenant_info->name;
					$nestedData['Email'] = $appointment->tenant_info->email;
					$nestedData['Property Name'] = $appointment->property->property_name;
					$nestedData['Date'] = date('M j, Y',strtotime($appointment->date));
					$nestedData['Time'] = $appointment->time;
					$nestedData['Status'] = $appointment_status;
					if($appointment->status == '1'){
						// accepted
						$action = '<a href="javascript:void(0);" rel-msg="'.$this->success_error_messages['appointment_rejected_question'].'"  rel-id="'.$appointment->id.'" onclick="appointment_table_reject_request(this);" class="icon-red custom_tooltip" title="Reject" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-times" aria-hidden="true"></i></a>';
					} 
					else if($appointment->status == '2'){
						// Rejected
						$action = '<a href="javascript:void(0);" rel-msg="'. $this->success_error_messages["appointment_accept_question"].'" rel-id="'.$appointment->id.'" class="icon-green custom_tooltip" onclick="appointment_table_accept_request(this);" title="Accept" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-check" aria-hidden="true"></i></a>';
					} 
					else if($appointment->status == '3'){
						// For expired
						$action = "";
					} 
					else {
						// Pending
						$action = '<a href="javascript:void(0);" rel-msg="'. $this->success_error_messages["appointment_accept_question"].'" rel-id="'.$appointment->id.'" class="icon-green custom_tooltip" onclick="appointment_table_accept_request(this);" title="Accept" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-check" aria-hidden="true"></i></a> <a href="javascript:void(0);" rel-msg="'.$this->success_error_messages['appointment_rejected_question'].'"  rel-id="'.$appointment->id.'" onclick="appointment_table_reject_request(this)" class="icon-red custom_tooltip" title="Reject" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-times" aria-hidden="true"></i></a>';
					}
					$nestedData['Action'] =	$action;
					$data[] = $nestedData;
				}
			}

			$json_data = array(
				"draw"            => intval($request->input('draw')),  
				"recordsTotal"    => intval($totalData),  
				"recordsFiltered" => intval($totalFiltered), 
				"data"            => $data   
			);

			echo json_encode($json_data); 
	}
	
	public function accept_request(Request $request){
		$user = Auth::user();
		if(!empty($_POST)){
            $inputData = Input::all();
            $rules = array(
				'appointment_id' => 'required',
            );
            $validator = Validator::make($inputData, $rules);
            if ($validator->fails()) {
                $messages = $validator->messages();
                $first_msg = '';
                foreach ($messages->all() as $message)
                {
					$first_msg = stripcslashes($message );
                    break;
                }
				$notification = array(
					'data' => false,
					'message' =>  $first_msg,
					'alert-type' => 'error_flash'
				);
            } 
			else{
				
				$appoint_info = Appointments::with(array(
					'property'=>function($query){
					$query->select('id','property_name');
					},
					'tenant_info'=>function($query){
						$query->select('id','name','email');
					},'landlord_info'=>function($query){
						$query->select('id','name','email');
					}))->where('id', $request->appointment_id)->first();
				// get previous status
				if($appoint_info['status'] =='0'){
					$prevoius_status = 'pending';
				} else if($appoint_info['status'] =='1'){
					$prevoius_status = 'accepted';
				} else if($appoint_info['status'] =='2'){
					$prevoius_status = 'rejected';
				} 
				if(isset($appoint_info) && !empty($appoint_info))
				{
					$insert_data['status'] = '1';
					$insert_data['updated_at'] = date('Y-m-d H:i:s');

					DB::table('appointments')
						->where('id',$request->appointment_id)
						->update($insert_data);
					
					$notification = array(
						'data' =>true,
						'message' =>   $this->success_error_messages['appointment_accepted'],
						'alert-type' => 'success'
					);

					/* Message sent to tenant */
					$update_data['is_last'] = '0';
					$update_data['updated_at'] = date('Y-m-d H:i:s');
					
					DB::table('messages')->where(function ($query) use ($appoint_info) {
						$query->where('from_user', '=', $appoint_info->landlord_id)->where('to_user', '=', $appoint_info->user_id);})->orWhere(function ($query) use ($appoint_info) {
							$query->where('to_user', '=', $appoint_info->user_id)->Where('from_user', '=', $appoint_info->landlord_id);})->Where(function ($query) use ($appoint_info) {
							$query->where('property_id', '=', $appoint_info->property_id);})->update($update_data);
					
					$message_insert_data['from_user'] = $appoint_info->landlord_id;
					$message_insert_data['to_user'] = $appoint_info->user_id;
					$message_insert_data['property_id'] = $appoint_info->property_id;
					$message_insert_data['message'] = "Your appointment status has been changed from " .$prevoius_status." to accepted for property ".ucfirst($appoint_info->property->property_name)." on date ".$appoint_info->date." at ".$appoint_info->time;
					$message_insert_data['read_status'] = '0';
					$message_insert_data['is_last'] = '1';
					$message_insert_data['created_at'] = date('Y-m-d H:i:s');
					$message_insert_data['updated_at'] = date('Y-m-d H:i:s');
					DB::table('messages')->insert($message_insert_data);
					/* End Here */
					
					
					/* Email Notification to tenant*/
					$email_information = array('to_name'=>$appoint_info->tenant_info->name,'to_email'=>$appoint_info->tenant_info->email,'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Appointment Accepted');
					
					$email_content = array('property_name'=>ucfirst($appoint_info->property->property_name),'tenant_name'=>ucfirst($appoint_info->tenant_info->name),'date'=>$appoint_info->date,'time'=>$appoint_info->time,'prevoius_status'=>$prevoius_status);
					Mail::send(['html' => 'emails.appointment_accepted'], $email_content, function($message) use ($email_information)
					{
						$message->to($email_information['to_email'], $email_information['to_name'])->subject
							($email_information['subject']);
						$message->from($email_information['from_email'],$email_information['from_name']);
					});
					/* End Here */

				} 
				else{
					$notification = array(
						'data' => false,	
						'message' =>  $this->success_error_messages['common_error'],
						'alert-type' => 'error_flash'
					);
				}
            }
        } 
		else{
			$notification = array(
				'data' => false,	
				'message' =>  $this->success_error_messages['common_error'],
				'alert-type' => 'error_flash'
			);
		}
		return response()->json($notification);
	}

	public function reject_request(Request $request){
		$user = Auth::user();
		if(!empty($_POST)){
            $inputData = Input::all();
            $rules = array(
				'appointment_id' => 'required',
            );
            
            $validator = Validator::make($inputData, $rules);
            if ($validator->fails()) {
                $messages = $validator->messages();
                $first_msg = '';
                foreach ($messages->all() as $message)
                {
                    $first_msg = stripcslashes($message );
                    break;
                }
				$notification = array(
					'data' => false,
					'message' =>  $first_msg,
					'alert-type' => 'error_flash'
				);
            } 
			else{
				$appoint_info = Appointments::with(array(
					'property'=>function($query){
					$query->select('id','property_name');
					},
					'tenant_info'=>function($query){
						$query->select('id','name','email');
					},'landlord_info'=>function($query){
						$query->select('id','name','email');
					}))->where('id', $request->appointment_id)->first();
				// get previous status
				if($appoint_info['status'] =='0'){
					$prevoius_status = 'pending';
				} else if($appoint_info['status'] =='1'){
					$prevoius_status = 'accepted';
				} else if($appoint_info['status'] =='2'){
					$prevoius_status = 'rejected';
				} 
				
				if(isset($appoint_info) && !empty($appoint_info)){
					$insert_data['status'] = '2';
					$current_status = "rejected";
					$insert_data['updated_at'] = date('Y-m-d H:i:s');
					DB::table('appointments')
						->where('id',$request->appointment_id)
						->update($insert_data);
						$notification = array(
							'data' =>true,
							'message' =>   $this->success_error_messages['appointment_rejected'],
							'alert-type' => 'success'
						);

						$update_data['is_last'] = '0';
						$update_data['updated_at'] = date('Y-m-d H:i:s');
						
						DB::table('messages')->where(function ($query) use ($appoint_info) {
							$query->where('from_user', '=', $appoint_info->landlord_id)->where('to_user', '=', $appoint_info->user_id);})->orWhere(function ($query) use ($appoint_info) {
								$query->where('to_user', '=', $appoint_info->user_id)->Where('from_user', '=', $appoint_info->landlord_id);})->Where(function ($query) use ($appoint_info) {
								$query->where('property_id', '=', $appoint_info->property_id);})->update($update_data);

						/* Message sent to tenant */
						$message_insert_data['from_user'] = $appoint_info->landlord_id;
						$message_insert_data['to_user'] = $appoint_info->user_id;
						$message_insert_data['property_id'] = $appoint_info->property_id;
						$message_insert_data['message'] = "Your appointment status has been changed from " .$prevoius_status." to  Rejected for property ".ucfirst($appoint_info->property->property_name)." on date ".$appoint_info->date." at ".$appoint_info->time;
						$message_insert_data['read_status'] = '0';
						$message_insert_data['is_last'] = '1';
						$message_insert_data['created_at'] = date('Y-m-d H:i:s');
						$message_insert_data['updated_at'] = date('Y-m-d H:i:s');
						DB::table('messages')->insert($message_insert_data);
						/* End Here */
							
						/* Email Notification to tenant*/
						$email_information = array('to_name'=>$appoint_info->tenant_info->name,'to_email'=>$appoint_info->tenant_info->email,'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Appointment Rejected');
						$email_content = array('property_name'=>ucfirst($appoint_info->property->property_name),'tenant_name'=>ucfirst($appoint_info->tenant_info->name),'date'=>$appoint_info->date,'time'=>$appoint_info->time,'prevoius_status'=>$prevoius_status,'current_status'=>$current_status);
						Mail::send(['html' => 'emails.appointment_rejected'], $email_content, function($message) use ($email_information)
						{
							$message->to($email_information['to_email'], $email_information['to_name'])->subject
								($email_information['subject']);
							$message->from($email_information['from_email'],$email_information['from_name']);
						});
						/* End Here */

				} else{
					$notification = array(
						'data' => false,	
						'message' =>  $this->success_error_messages['common_error'],
						'alert-type' => 'error_flash'
					);
				}
            }
        } else{
			$notification = array(
				'data' => false,	
				'message' =>  $this->success_error_messages['common_error'],
				'alert-type' => 'error_flash'
			);
		}
		return response()->json($notification);
	}

	
}
