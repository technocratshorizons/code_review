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
use App\Models\Banner;
use App\Models\Cities;
use App\Models\Properties;
use App\Models\Occupations;
use App\Models\Favourite_properties;
use App\Models\Property_availabilities;
use App\Models\Appointments;
use App\Models\Rental_forms;
use App\Models\Bank;
use App\Models\Account_type;
use Illuminate\Support\Facades\View;
class RentsearchController extends Controller {

	public $success_error_messages;
	
	public function __construct()
    {
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
	public function index(Request $request) {
		$sendData = array();
		if(isset($_POST) && !empty($_POST)){
			$sendData['search'] = @$_POST['search'];
		}
		$sendData['city'] = session('city');
		$sendData['bedrooms'] = session('bedrooms');
		$sendData['bathroom'] = session('bathroom');
		$sendData['Selected_Min_Price'] = session('Selected_Min_Price');
		$sendData['Selected_Max_price'] = session('Selected_Max_price');
		$sendData['Cities'] = Cities::where('status','1')->get();
        $sendData['Max_price'] = Properties::where('is_deleted', '0')->max('monthly_rent');
        $sendData['Min_price'] = Properties::where('is_deleted', '0')->min('monthly_rent');
        return view('rent_search',$sendData);
    }

    public function fetch_properties_search(Request $request) {
    	if(isset($_POST) && !empty($_POST)){
			$user = Auth::user();
    		$data = Input::all();
    		//Filters start here
    		$where = array('is_deleted'=>'0','is_rented'=>'0');
    		if(!empty($data['selected_city'])) {
				$where['city'] = $data['selected_city'];
            }
   			session(['city' => $data['selected_city']]);

            if(!empty($data['no_of_bedrooms'])) {
    			$where['bedroom'] = $data['no_of_bedrooms'];
            }
			session(['bedrooms' => $data['no_of_bedrooms']]);

    		if(!empty($data['no_of_bathrooms'])) {
    			$where['bathroom'] = $data['no_of_bathrooms'];
            }
			session(['bathroom' => $data['no_of_bathrooms']]);

    		if(!empty($data['Selected_Min_Price']) || !empty($data['Selected_Max_price'])){
            }
			session(['Selected_Min_Price' => $data['Selected_Min_Price']]);
			session(['Selected_Max_price' => $data['Selected_Max_price']]);
    		Session::save();

    		if(!empty($data['search_input'])) {
				$property_list = Properties::with(['property_images','property_amenities' =>function($query) {
     				return $query->where('ammenties_type','Property');
 				},
 				'property_amenities.amenities'])->where($where)->whereBetween('monthly_rent',[$data['Selected_Min_Price'],$data['Selected_Max_price']])->where('property_name', 'like', '%'.$data['search_input'].'%')->orderBy('id', 'DESC')->paginate(9);
            } else{
            	$property_list = Properties::with(['property_images','property_amenities' =>function($query) {
     				return $query->where('ammenties_type','Property');
 				},
 				'property_amenities.amenities'])->where($where)->whereBetween('monthly_rent',[$data['Selected_Min_Price'],$data['Selected_Max_price']])->orderBy('id', 'DESC')->paginate(9);
            }
            
            if(!empty($data['search_input'])) {
				$count = Properties::with(['property_images','property_amenities' =>function($query) {
     				return $query->where('ammenties_type','Property');
 				},
 				'property_amenities.amenities'])->where($where)->whereBetween('monthly_rent',[$data['Selected_Min_Price'],$data['Selected_Max_price']])->where('property_name', 'like', '%'.$data['search_input'].'%')->orderBy('id', 'DESC')->count();
            } else{
            	$count = Properties::with(['property_images','property_amenities' =>function($query) {
     				return $query->where('ammenties_type','Property');
 				},
 				'property_amenities.amenities'])->where($where)->whereBetween('monthly_rent',[$data['Selected_Min_Price'],$data['Selected_Max_price']])->orderBy('id', 'DESC')->count();
            }

	        $view = View::make('ajax.search_property', array('property_list'=>$property_list,'count'=>'count','user'=>$user))->render();

	        $notification = array(
	            'data' =>  true,
	            'append_data' => $view,
	            'count' => $count,
	            'map_data' => $property_list
	        );
		}        
        return response()->json($notification);
    }
	
	public function detail(Request $request) 
	{
		$user = Auth::user();
      	
		$property_detail = Properties::with(['property_images','property_amenities_community',
				 'property_amenities_community.amenities','property_amenities_property','property_amenities_property.amenities','property_availabilities','rental_forms'])->where('id',$request->id)->first();
		
		if(empty($property_detail)){
			return redirect('/not_found');
		}
	
		$favorite_property = Favourite_properties::where('property_id', $request->id)->where('user_id', $user['id'])->first();
		
		$rental_form_info = Rental_forms::where('property_id', $request->id)->where('user_id', $user['id'])->first();
		
		//  echo '<pre>',print_r($rental_form_info),'</pre>';die;
		// get data to insert in property view log
		$insert_log = array(
			'user_id' => $user['id']?$user['id']:0,
			'property_id' => $request->id,
			'ip_address' => request()->ip(),
			'created_at' => date('Y-m-d H:i:s')
		);
		// if data inserted
		if(DB::table('property_view_log')->insert($insert_log)){
			// get total count of this property
			$total_view = DB::table('property_view_log')
			->where('property_id', $request->id)
			->count();

			
			$update_data = array(
				'total_view' => $total_view,
				'modified' => date('Y-m-d H:i:s'),
			);

			DB::table('properties')
				->where('id', $request->id)
				->update($update_data);
		}
		return view('renter_search_detail', compact('property_detail','user','favorite_property','rental_form_info'));
	}
	
	public function appointment_list(Request $request, $property_id){
		$user = Auth::user();

		$appointments_list = Property_availabilities::where('property_id', $property_id)->whereDate('date', '>=', date('Y-m-d'))->where('time_from', '>=', date('h:i A'))->paginate(4);
		
		// echo '<pre>',print_r($appointments_list),'</pre>';die;
        $view = View::make('ajax.appointment_list')->with('appointments_list',$appointments_list)->with('user',$user)->render();

        $notification = array(
            'data' =>  true,
            'append_data' => $view,
        );
        return response()->json($notification);
	}
	public function contact_landlord(Request $request) {
		
		$user = Auth::user();
		$property_detail = Properties::with(['property_images','property_amenities_community',
		'property_amenities_community.amenities','property_amenities_property','property_amenities_property.amenities','property_availabilities'])->where('id',$request->id)->first();
		
		$userinfo = DB::table('user_info')->where('user_id',$user['id'])->first();
		
		$occupations = Occupations::where(array('status'=>'1'))->orderBy('name', 'ASC')->get();
      	return view('contact_landlord', compact('user','property_detail','userinfo','occupations'));
    }
	
	public function fill_form(Request $request) {
		
		$user = Auth::user();
		if(isset($_POST) && !empty($_POST)){
			$inputData = Input::all();
			$rules = array(
                'email' 				=> 'required',
                'legal_name' 			=> 'required',
                'phone' 				=> 'required',
                'date_of_birth' 		=> 'required',
                'housing_type' 			=> 'required',
                'movie_in_date' 		=> 'required',
				'leaving_reason' 		=> 'required',
				'address'				=> 'required',
	
				'any_loan'				=>'required',
				'any_pets'				=>'required',
				'liquid_furniture'		=>'required',
				'bedbugs'				=>'required',
				'evicted'				=>'required',
				'bankruptcy'			=>'required',
				'smoke'					=>'required',
				'convicted'				=>'required',
				'ref_name_first'		=>'required',
				'ref_relation_first'	=>'required',
				'ref_address_first'		=>'required',
				'ref_phone_first'		=>'required',
				'ref_name_second'		=>'required',
				'ref_relation_second'	=>'required',
				'ref_address_second'	=>'required',
				'ref_phone_second'		=>'required',
				'vehicles'				=>'required',

            );
            $validator = Validator::make($inputData, $rules);
            if ($validator->fails()) {
                $notification = array(
                    'message' =>  $this->success_error_messages['fill_all_required_fields'],
                    'alert-type' => 'error'
				);
				
				return redirect('/property/fill_form/'.$request->property_id)->withErrors($validator)->withInput($request->input());
            }else{

				$insert = array(
					'user_id'				=> $user['id'],
					'property_id'			=> $request->property_id,
					'landlord_id'			=> $request->landlord_id,
					'email' 				=> $request->email,
					'legal_name' 			=> $request->legal_name,
					'phone' 				=> $request->phone,
					'date_of_birth' 		=> date('Y-m-d', strtotime($request->date_of_birth)),
					'housing_type' 			=> $request->housing_type,
					'movie_in_date' 		=> date('Y-m-d', strtotime($request->movie_in_date)),
					'leaving_reason' 		=> $request->leaving_reason,
					'address'				=> $request->address,
					'any_loan'				=> $request->any_loan,
					'any_pets'				=> $request->any_pets,
					'liquid_furniture'		=> $request->liquid_furniture,
					'bedbugs'				=> $request->bedbugs,
					'evicted'				=> $request->evicted,
					'bankruptcy'			=> $request->bankruptcy,
					'smoke'					=> $request->smoke,
					'convicted'				=> $request->convicted,
					'ref_name_first'		=> $request->ref_name_first,
					'ref_relation_first'	=> $request->ref_relation_first,
					'ref_address_first'		=> $request->ref_address_first,
					'ref_phone_first'		=> $request->ref_phone_first,
					'ref_name_second'		=> $request->ref_name_second,
					'ref_relation_second'	=> $request->ref_relation_second,
					'ref_address_second'	=> $request->ref_address_second,
					'ref_phone_second'		=> $request->ref_phone_second,
					'vehicles'				=> $request->vehicles,
					'created_at'			=> date('Y-m-d H:i:s'),
					'updated_at'			=> date('Y-m-d H:i:s'),					
				);

				// optional fields start here 
				if($request->any_loan == 'yes'){
					$insert['loan_amount'] = $request->loan_amount;
					$insert['loan_details'] = $request->loan_details;
				}
				if($request->pet_details){
					$insert['pet_details'] = $request->pet_details;
				}
				if($request->occupation_status){
					$insert['occupation_status'] = $request->occupation_status;
				}
				if($request->income_source){
					$insert['income_source'] = $request->income_source;
				}

				if($request->monthly_income){
					$insert['monthly_income'] = $request->monthly_income;
				}
				if($request->additional_monthly_income){
					$insert['additional_monthly_income'] = $request->additional_monthly_income;
				}
				if($request->total_monthly_income){
					$insert['total_monthly_income'] = $request->total_monthly_income;
				}
				if($request->transection_id){
					$insert['transection_id'] = $request->transection_id;
				}

				if($request->bank_name){
					$insert['bank_name'] = $request->bank_name;
					if($request->bank_name == 'Other'){
						$insert['other_bank_name'] = $request->other_bank_name;
					}
				}

				if($request->account_type){
					$insert['account_type'] = $request->account_type;
				}

				if($request->account_number){
					$insert['account_number'] = $request->account_number;
				}
				if($request->insitution_id){
					$insert['insitution_id'] = $request->insitution_id;
				}
				if($request->vehicles_detail){
					$insert['vehicles_detail'] = $request->vehicles_detail;
				}
				// optional fields end here 
				
					$form_info = DB::table('rental_forms')->where('user_id',$user['id'])
					->where('property_id',$request->property_id)
					->first();
				if(isset($form_info) && !empty($form_info)){
					$insert['updated_at'] = date('Y-m-d H:i:s');
					DB::table('rental_forms')
            			->where('id', $form_info->id)
						->update($insert);
						$notification = array(
							'message' =>  $this->success_error_messages['rental_form_submit_success'],
							'alert-type' => 'success'
						);
				}else{
					if(DB::table('rental_forms')->insert($insert)){

						// Email notification to tenant start here
	
						// Email notification to tenant end here
	
						// Email notification to landlord start here
	
						// Email notification to landlord end here
	
						$notification = array(
							'message' =>  $this->success_error_messages['rental_form_submit_success'],
							'alert-type' => 'success'
						);
					} else{
						$notification = array(
							'message' =>  $this->success_error_messages['common_error'],
							'alert-type' => 'error'
						);
					}
				}

				
				
				return redirect('/property/detail/'.$request->property_id)->with($notification);
			}
		}else{
			$property_detail = Properties::with(['property_images','property_amenities_community',
			'property_amenities_community.amenities','property_amenities_property','property_amenities_property.amenities','property_availabilities'])->where('id',$request->id)->first();
			$userinfo = DB::table('user_info')->where('user_id',$user['id'])->first();

			$occupations = Occupations::where(array('status'=>'1'))->orderBy('name', 'ASC')->get();
			$banks = Bank::where(array('status'=>'0'))->orderBy('bank_name', 'ASC')->get();
			$account_types = Account_type::where(array('status'=>'0'))->orderBy('account_name', 'ASC')->get();
			//  echo '<pre>',print_r($Banks),'</pre>';die;
			return view('fill_form', compact('user','property_detail','userinfo','occupations','banks','account_types'));
		}
		
		
		
    }
	
	public function favorites(Request $request) {
		
		$user = Auth::user();
		
      	return view('property_favorite_list', compact('user'));
    }
	
	// for appontment dates
    public function get_appointment_dates(Request $request) {
	   
		$user = Auth::user();

		if (!empty($_POST)) {
		   
			$data = Input::all();
            $rules = array(
                'property_id' => 'required',
            );
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                $messages = $validator->messages();
                $first_msg = '';
                foreach ($messages->all() as $message)
                {
                    $first_msg = stripcslashes($message );
                    break;
                }
                $result = array(
                    "success" => false,
                    "error" => $first_msg
                );
            } 
            else 
            {
                $appointments = DB::table('property_availabilities')
				->where('property_id', $request->property_id)
				->whereDate('date', '=', $request->avail_date)
				->where('time_from', '>=', date('h:i A'))
				->first();

				
				$dates = array();
				$from_time = $appointments->time_from;
				while (strtotime($from_time) <= strtotime($appointments->time_to)) {

					$newTime = strtotime($from_time) + 60*60;
					$time = date('h:i A', $newTime);
					 
					if(strtotime($time) <= strtotime($appointments->time_to)  ){
						$from_to['from'] = $from_time;
						$from_to['to'] = $time;
						$dates[] = $from_to;
						$from_time = $time;
					} 
					else{
						$remain_time = strtotime($time)-strtotime($appointments->time_to);
						$newTime = strtotime($from_time) + $remain_time;
						$time = date('h:i A', $newTime);
						$from_to['from'] = $from_time;
						
						// to check from time is greater than to_time
						if(strtotime($time)<=strtotime($appointments->time_to)){
							$from_to['to'] = $time;
							$dates[] = $from_to;
							break;
						}else{
							break;
						}

						
					}
				}
				$view = View::make('ajax.appointment_popup', array('dates'=>$dates,'user'=>$user))->render();
				$result = array(
					'success' =>  true,
					'append_data' => $view,
				);
			}
        } else{
            $result = array(
                "success" => false,
                "error" => 'No direct access'
            );
        }
        return response()->json($result);
        
	}
	
	// to add user information
	public function add_user_info(Request $request){
		$user = Auth::user();
		if(!empty($_POST)){
			$inputData = Input::all();
            $rules = array(
                'move_in_date' => 'required',
                'adults' => 'required',
                'occupation' => 'required',
                'phone' => 'required',
                'pets' => 'required',
                'smoking' => 'required',
				'message' => 'required',
				'g-recaptcha-response'=>'required'
            );
            
            $validator = Validator::make($inputData, $rules);
            if ($validator->fails()) {
                $notification = array(
                    'message' =>  $this->success_error_messages['fill_all_required_fields'],
                    'alert-type' => 'error'
				);
				
				return redirect('/property/contact_landlord/'.$request->property_id)->withErrors($validator)->withInput($request->input());
            }  
			else{

				$userinfo = DB::table('user_info')->where('user_id',$user['id'])->first();

				$data = array(
					'user_id'	=> $user['id'],
					'move_in_date' 	=> date('Y-m-d', strtotime($request->move_in_date)),
					'adults' 	=> $request->adults,
					'occupation'=> $request->occupation,
					'phone' 	=> $request->phone,
					'pets'		=> $request->pets,
					'smoking'	=> $request->smoking,
					'message' 	=> $request->message,
					'children' 	=> $request->children
				);
				
				if(isset($userinfo) && !empty($userinfo)){
					$data['updated_at'] = date('Y-m-d H:i:s');
					DB::table('user_info')
            			->where('user_id', $userinfo->user_id)
						->update($data);
				}
				else
				{
					$data['created_at'] = date('Y-m-d H:i:s');
					$data['updated_at'] = date('Y-m-d H:i:s');
					DB::table('user_info')->insert($data);
				}
				
				/* Message sent to tenant */
				$update_data['is_last'] = '0';
				$update_data['updated_at'] = date('Y-m-d H:i:s');
				
				DB::table('messages')->where(function ($query) use ($request,$user) {
					$query->where('from_user', '=', $request->landlord_id)->where('to_user', '=', $user['id']);})->orWhere(function ($query) use ($request,$user) {
						$query->where('to_user', '=', $request->landlord_id)->Where('from_user', '=', $user['id']);})->Where(function ($query) use ($request,$user) {
						$query->where('property_id', '=', $request->property_id);})->update($update_data);
				
				$message_insert_data['from_user'] = $user['id'];
				$message_insert_data['to_user'] = $request->landlord_id;
				$message_insert_data['property_id'] = $request->property_id;
				$message_insert_data['message'] = $request->message;
				$message_insert_data['read_status'] = '0';
				$message_insert_data['is_last'] = '1';
				$message_insert_data['created_at'] = date('Y-m-d H:i:s');
				$message_insert_data['updated_at'] = date('Y-m-d H:i:s');
				DB::table('messages')->insert($message_insert_data);
				/* End Here */
				
				$notification = array(
					'message' =>  $this->success_error_messages['message_sent_success'],
					'alert-type' => 'success'
				);
            }
        } 
		else{
			$notification = array(
				'message' =>  $this->success_error_messages['common_error'],
				'alert-type' => 'error'
			);
		}
        return redirect('/messages')->with($notification);
	}

	public function book_appointment(Request $request){
		
		$user = Auth::user();
		if(!empty($_POST)){
            $inputData = Input::all();
            $rules = array(
				'time' 				=> 'required',
				'property_id' 		=> 'required',
				'landlord_id' 		=> 'required',
				'date' 				=> 'required',
				'time' 				=> 'required'
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
					'message' =>  $first_msg,
					'alert-type' => 'error_flash'
				);
            } 
			else{
				$last_request = DB::table('appointments')
				->where('user_id',$user['id'])
				->where('property_id',$request->property_id)
				->where(function ($query) use ($user) {
					$query->where('status', '=','0')
					->orWhere('status', '=','1');
				})->first();
			
				if(isset($last_request) && !empty($last_request)){
				
					$notification = array(
						'data' => false,
						'message' =>  $this->success_error_messages['same_appointment'],
						'alert-type' => 'error_flash',
					);
					return response()->json($notification);
				} 
				else{
					$insert_data = array(
						'time' 				=> $request->time,
						'property_id' 		=> $request->property_id,
						'user_id' 			=> $user['id'],
						'landlord_id' 		=> $request->landlord_id,
						'date' 				=> $request->date,
						'created_at'		=> date('Y-m-d H:i:s'),
						'updated_at'		=> date('Y-m-d H:i:s'),
					);
	
					if(DB::table('appointments')->insert($insert_data)){
						$notification = array(
							'data' => true,	
							'message' =>  $this->success_error_messages['appointment_book_success'],
							'alert-type' => 'success'
						);

						$user_info = User::where('id',$user['id'])->first();
						$landlord_info =DB::table('users')->select('name','email')->where('id',$request->landlord_id)->first();
						$property_info =DB::table('properties')->select('property_name')->where('id',$request->property_id)->first();
						
						/* Email Notification to landlord*/
						$email_information = array('to_name'=>$landlord_info->name,'to_email'=>$landlord_info->email,'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'New Appointment Book');
						$email_content = array('landlord_name'=>ucfirst($landlord_info->name),'property_name'=>ucfirst($property_info->property_name),'tenant_name'=>ucfirst($user_info->name),'date'=>$request->date,'time'=>$request->time);
						Mail::send(['html' => 'emails.landlord_appointment_book'], $email_content, function($message) use ($email_information)
						{
							$message->to($email_information['to_email'], $email_information['to_name'])->subject
								($email_information['subject']);
							$message->from($email_information['from_email'],$email_information['from_name']);
						});
						/* End Here */

						/* Email Notification to tenant*/
						$email_information = array('to_name'=>$user_info->name,'to_email'=>$user_info->email,'from_name'=>config('app.name'),'from_email'=>config('app.email'),'subject'=>'Appointment Book');
						$email_content = array('property_name'=>ucfirst($property_info->property_name),'tenant_name'=>ucfirst($user_info->name),'date'=>$request->date,'time'=>$request->time);
						Mail::send(['html' => 'emails.tenant_appointment_book'], $email_content, function($message) use ($email_information)
						{
							$message->to($email_information['to_email'], $email_information['to_name'])->subject
								($email_information['subject']);
							$message->from($email_information['from_email'],$email_information['from_name']);
						});
						/* End Here */

					}
					else
					{
						$notification = array(
							'data' => false,	
							'message' =>  $this->success_error_messages['common_error'],
							'alert-type' => 'error_flash'
						);
					}
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

	public function favorite_property(Request $request){
		$user = Auth::user();
		if(!empty($_POST)){
            $inputData = Input::all();
            $rules = array(
				'status' 	=> 'required',
				'property_id' 		=> 'required',
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
					'message' =>  $first_msg,
					'alert-type' => 'error_flash'
				);
            } 
			else{

				$userinfo = DB::table('favourite_properties')
				->where('user_id',$user['id'])
				->where('property_id',$request->property_id)
				->first();

				if($request->status == '1'){
					$message = $this->success_error_messages['favorite_success'];
				} 
				else {
					$message = $this->success_error_messages['unfavorite_success'];
				}

				$insert_data = array(
					'property_id' 		=> $request->property_id,
					'user_id' 			=> $user['id'],
					'status'			=> $request->status
				);

				
				if(isset($userinfo) && !empty($userinfo)){
					$insert_data['updated_at'] = date('Y-m-d H:i:s');
					DB::table('favourite_properties')
						->where('user_id', $userinfo->user_id)
						->where('property_id',$request->property_id)
						->update($insert_data);
						
						$notification = array(
							'message' =>  $message,
							'alert-type' => 'success'
						);
				}
				else{
					$insert_data['created_at'] = date('Y-m-d H:i:s');
					$insert_data['updated_at'] = date('Y-m-d H:i:s');
					DB::table('favourite_properties')->insert($insert_data);
					$notification = array(
                        'message' =>  $message,
                        'alert-type' => 'success'
                    );
				}
            }
        } else{
			$notification = array(
				'message' =>  $this->success_error_messages['common_error'],
				'alert-type' => 'error_flash'
			);
		}
		return response()->json($notification);
	}

	public function favorites_listing(Request $request) {
				
		$user = Auth::user();

		$Favourite_list = Favourite_properties::with('property','property.property_images','property_amenities.amenities')
		->has('property')
		->orderBy('id', 'DESC')
		->where('status', '1')
		->where('user_id', $user['id'])->paginate(8);
		//  echo '<pre>',print_r($Favourite_list),'</pre>';die;
				
		$view = View::make('ajax.property_favorite_list')->with('Favourite_list',$Favourite_list)->with('user',$user)->with('success_error_messages',$this->success_error_messages)->render();
	
		$notification = array(
				'data' =>  true,
				'append_data' => $view,
			);
			return response()->json($notification);
			
	}

	
}
