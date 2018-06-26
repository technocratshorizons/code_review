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
use Lang;
use App\Mortgage;
use App\Savingdiary;
use App\Category;
use App\Advice;
use App\Test;
use App\Answer;
use App\TestDetail;
use Illuminate\Support\Facades\Input;
use App\Models\Banner;
use App\Models\Rental_forms;
use App\Models\Occupations;
use App\Models\Bank;
use App\Models\Account_type;

class ApplicantsController extends Controller {

	public function __construct()
    {
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
	public function index(Request $request) {
		$user = Auth::user();
		$bannerDtails = Banner::where('slug','dashboard')->first();
	    return view('applicants',compact('user','bannerDtails'));
	}
	
	public function fetch_applicants(Request $request) {
        
        $user = Auth::user();
			$columns = array( 
				0 => "id",
				1 => "name",
				2 => "movie_in_date",
				3 => "email",
				4 => "property_name",
				5 => "status",
				6 => "action",
			);

			$totalData = Rental_forms::where('landlord_id',$user['id'])->count();
			$totalFiltered = $totalData; 

			$limit = $request->input('length');
			$start = $request->input('start');
			$order = $columns[$request->input('order.0.column')];
			$dir = $request->input('order.0.dir');
			
			$applicants = Rental_forms::with(array(
				'property'=>function($query) {
					$query->select('id','property_name');
				},
				'tenant_info'=>function($query){
					$query->select('id','name','email');
				},))->offset($start)
					->limit($limit)
					->where('landlord_id',$user['id'])
					->orderBy($order,$dir)
					->get();

			$totalFiltered = Rental_forms::where('landlord_id',$user['id'])->count();
			$data = array();
			
			if(!empty($applicants))
			{
				foreach ($applicants as $applicants)
				{
					$nestedData['id'] = $applicants->id;
					$nestedData['name'] = $applicants->tenant_info->name;
					$nestedData['movie_in_date'] = date('M j, Y',strtotime($applicants->movie_in_date));
					$nestedData['email'] = $applicants->email;
					$nestedData['property_name'] = $applicants->property->property_name;
				
					if($applicants->status == '0'){
						$nestedData['status'] = 'Pending';
					}
					if($applicants->status == '1'){
						$nestedData['status'] = 'Accepted';
					}
					if($applicants->status == '2'){
						$nestedData['status'] = 'Rejected';
					}
					

					if($applicants->is_rented == '1'){
						
						$action = '<a href="applicants/review_form/'.$applicants->property_id.'/'.$applicants->user_id.'" class="icon-green custom_tooltip"  title="Review" data-toggle="tooltip" data-placement="bottom"><i style="color:#337ab7;" class="fa fa-eye" aria-hidden="true"></i></a>';

						$action .= '<span style="color:#337ab7;"><b>Rented Property</b></span>';

					}else{
						$action = '<a href="applicants/review_form/'.$applicants->property_id.'/'.$applicants->user_id.'" class="icon-green custom_tooltip"  title="Review" data-toggle="tooltip" data-placement="bottom"><i style="color:#337ab7;" class="fa fa-eye" aria-hidden="true"></i></a>';

						if( $applicants->screeing == '0' ){
							$action .= '<a href="javascript:void(0);" rel-msg="'.$this->success_error_messages['add_to_screening'].'" rel-to-status="1"  rel-id="'.$applicants->id.'"  rel-property-id="'.$applicants->property_id.'" onclick="application_screening(this);" class="icon-green custom_tooltip" title="Add to screening" data-toggle="tooltip" data-placement="bottom"><i style="color:red;"class="fa fa-mobile-phone" aria-hidden="true"></i></a>';
						}
						if( $applicants->screeing == '1' ){
							
							$action .= '<a href="javascript:void(0);" rel-msg="'.$this->success_error_messages['remove_from_screening'].'" rel-to-status="0"  rel-id="'.$applicants->id.'"  rel-property-id="'.$applicants->property_id.'" onclick="application_screening(this);" class="icon-green custom_tooltip" title="Remove from screening" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-mobile-phone" aria-hidden="true"></i></a>';
						}
						


						// if status is pending
						if( $applicants->status == '0' ){
							$action .= '<a href="javascript:void(0);" rel-msg="'.$this->success_error_messages['application_accept_question'].'" rel-to-status="1" rel-id="'.$applicants->id.'" onclick="application_request(this);" rel-property-id="'.$applicants->property_id.'" class="icon-green custom_tooltip"  title="Accept" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-check" aria-hidden="true"></i></a>
							<a href="javascript:void(0);" rel-msg="'.$this->success_error_messages['application_reject_question'].'" rel-to-status="2" rel-id="'.$applicants->id.'" onclick="application_request(this);" rel-property-id="'.$applicants->property_id.'" class="icon-red custom_tooltip" title="Reject" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-times" aria-hidden="true"></i></a>';
						}
						// if status is accepted
						if( $applicants->status == '1' ){
							$action .= '<a href="javascript:void(0);" rel-msg="'.$this->success_error_messages['application_reject_question'].'" rel-to-status="2"  rel-id="'.$applicants->id.'"  rel-property-id="'.$applicants->property_id.'" onclick="application_request(this);" class="icon-red custom_tooltip" title="Reject" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-times" aria-hidden="true"></i></a>';
						}
						// if status is rejected
						if( $applicants->status == '2' ){
							$action .= '<a href="javascript:void(0);" rel-msg="'.$this->success_error_messages['application_accept_question'].'" rel-to-status="1" rel-id="'.$applicants->id.'" rel-property-id="'.$applicants->property_id.'"  onclick="application_request(this);"  class="icon-green custom_tooltip"  title="Accept" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-check" aria-hidden="true"></i></a>';
						}
					}
					
					
					$nestedData['action'] =	$action;
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

	public function accept_reject(Request $request){	
		$user = Auth::user();
		if(!empty($_POST)){
            $inputData = Input::all();
            $rules = array(
				'applicant_id' => 'required',
				'to_status' => 'required',
				'property_id' => 'required'
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
				
				$applicant_info = Rental_forms::with(array(
					'property'=>function($query) {
						$query->select('id','property_name');
					},
					'tenant_info'=>function($query){
						$query->select('id','name','email');
					},'landlord_info'=>function($query){
						$query->select('id','name','email');
					}))->where('id', $request->applicant_id)->first();
				
				if(isset($applicant_info) && !empty($applicant_info))
				{
					// get previous status
					if($applicant_info['status'] =='0'){
						$prevoius_status = 'pending';
					} else if($applicant_info['status'] =='1'){
						$prevoius_status = 'accepted';
					} else if($applicant_info['status'] =='2'){
						$prevoius_status = 'rejected';
					} 
					
					$insert_data['status'] = $request->to_status;
					$insert_data['updated_at'] = date('Y-m-d H:i:s');

					DB::table('rental_forms')
						->where('id',$request->applicant_id)
						->update($insert_data);

					if( $request->to_status=='1' ){
						$notification = array(
							'data' =>true,
							'message' =>   $this->success_error_messages['application_accepted'],
							'alert-type' => 'success'
						);
						$new_status = 'accepted';
					}
					if( $request->to_status=='2' ){
						$notification = array(
							'data' =>true,
							'message' =>   $this->success_error_messages['application_rejected'],
							'alert-type' => 'success'
						);
						$new_status = 'rejected';
						
						/* Message sent to tenant */
							$update_data['is_last'] = '0';
							$update_data['updated_at'] = date('Y-m-d H:i:s');
							
							DB::table('messages')->where(function ($query) use ($applicant_info) {
								$query->where('from_user', '=', $applicant_info->landlord_id)->where('to_user', '=', $applicant_info->user_id);})->orWhere(function ($query) use ($applicant_info) {
									$query->where('to_user', '=', $applicant_info->user_id)->Where('from_user', '=', $applicant_info->landlord_id);})->Where(function ($query) use ($applicant_info) {
									$query->where('property_id', '=', $applicant_info->property_id);})->update($update_data);
							
							$message_insert_data['from_user'] = $applicant_info->landlord_id;
							$message_insert_data['to_user'] = $applicant_info->user_id;
							$message_insert_data['property_id'] = $applicant_info->property_id;
							$message_insert_data['message'] = "Your rental application status has been changed from " .$prevoius_status." to ".$new_status." for property ".ucfirst($applicant_info->property->property_name);
							$message_insert_data['read_status'] = '0';
							$message_insert_data['is_last'] = '1';
							$message_insert_data['created_at'] = date('Y-m-d H:i:s');
							$message_insert_data['updated_at'] = date('Y-m-d H:i:s');
							DB::table('messages')->insert($message_insert_data);
							/* End Here */
							
							/* Email Notification to tenant*/
							$email_information = array('to_name'=>$applicant_info->tenant_info->name,'to_email'=>$applicant_info->tenant_info->email,'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Rental Application Form');
							
							$email_content = array('property_name'=>ucfirst($applicant_info->property->property_name),'tenant_name'=>ucfirst($applicant_info->tenant_info->name),'prevoius_status'=>$prevoius_status,'new_status'=>$new_status);
							Mail::send(['html' => 'emails.rental_application'], $email_content, function($message) use ($email_information)
							{
								$message->to($email_information['to_email'], $email_information['to_name'])->subject
									($email_information['subject']);
								$message->from($email_information['from_email'],$email_information['from_name']);
							});
					/* End Here */
					}
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
	
	public function review_form(Request $request ,$id,$user_id){
		$user = Auth::user();
		$form_info = Rental_forms::where('property_id',$id)
		->where('user_id',$user_id)->first(); 
		$occupations = Occupations::where(array('status'=>'1'))->orderBy('name', 'ASC')->get();
		
		$banks = Bank::where(array('status'=>'0'))->orderBy('bank_name', 'ASC')->get();
		$account_types = Account_type::where(array('status'=>'0'))->orderBy('account_name', 'ASC')->get();
		return view('review_rental_form',compact('user','form_info','occupations','banks','account_types'));
	}


	public function screening(Request $request){	
		$user = Auth::user();
		if(!empty($_POST)){
            $inputData = Input::all();
            $rules = array(
				'applicant_id' => 'required',
				'to_status' => 'required',
				'property_id' => 'required'
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
				
				$applicant_info = Rental_forms::with(array(
					'property'=>function($query) {
						$query->select('id','property_name');
					},
					'tenant_info'=>function($query){
						$query->select('id','name','email');
					},'landlord_info'=>function($query){
						$query->select('id','name','email');
					}))->where('id', $request->applicant_id)->first();
				
				if(isset($applicant_info) && !empty($applicant_info))
				{
					// get previous status
					if($applicant_info['screeing'] =='0'){
						$prevoius_status = 'not screeing';
					} else if($applicant_info['screeing'] =='1'){
						$prevoius_status = 'screeing';
					} 
					
					$insert_data['screeing'] = $request->to_status;
					$insert_data['updated_at'] = date('Y-m-d H:i:s');

					DB::table('rental_forms')
						->where('id',$request->applicant_id)
						->update($insert_data);

					if( $request->to_status=='1' ){
						$notification = array(
							'data' =>true,
							'message' =>   $this->success_error_messages['screening_add'],
							'alert-type' => 'success'
						);
						$new_status = 'screening';
					}
					if( $request->to_status=='0' ){
						$notification = array(
							'data' =>true,
							'message' =>   $this->success_error_messages['screening_remove'],
							'alert-type' => 'success'
						);
						$new_status = 'not screening';
					}
					
					/* Message sent to tenant */
					$update_data['is_last'] = '0';
					$update_data['updated_at'] = date('Y-m-d H:i:s');
					
					DB::table('messages')->where(function ($query) use ($applicant_info) {
						$query->where('from_user', '=', $applicant_info->landlord_id)->where('to_user', '=', $applicant_info->user_id);})->orWhere(function ($query) use ($applicant_info) {
							$query->where('to_user', '=', $applicant_info->user_id)->Where('from_user', '=', $applicant_info->landlord_id);})->Where(function ($query) use ($applicant_info) {
							$query->where('property_id', '=', $applicant_info->property_id);})->update($update_data);
					
					$message_insert_data['from_user'] = $applicant_info->landlord_id;
					$message_insert_data['to_user'] = $applicant_info->user_id;
					$message_insert_data['property_id'] = $applicant_info->property_id;
					$message_insert_data['message'] = "Your rental application screening status has been changed from " .$prevoius_status." to ".$new_status." for property ".ucfirst($applicant_info->property->property_name);
					$message_insert_data['read_status'] = '0';
					$message_insert_data['is_last'] = '1';
					$message_insert_data['created_at'] = date('Y-m-d H:i:s');
					$message_insert_data['updated_at'] = date('Y-m-d H:i:s');
					DB::table('messages')->insert($message_insert_data);
					/* End Here */
					
					/* Email Notification to tenant*/
					$email_information = array('to_name'=>$applicant_info->tenant_info->name,'to_email'=>$applicant_info->tenant_info->email,'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Rental Application Form');
					
					$email_content = array('property_name'=>ucfirst($applicant_info->property->property_name),'tenant_name'=>ucfirst($applicant_info->tenant_info->name),'prevoius_status'=>$prevoius_status,'new_status'=>$new_status);
					Mail::send(['html' => 'emails.rental_application'], $email_content, function($message) use ($email_information)
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
	
}
