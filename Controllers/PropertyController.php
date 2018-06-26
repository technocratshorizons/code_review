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
use App\Models\Parking_types;
use App\Models\Banner;
use App\Models\Properties;
use App\Models\Property_view_log;
use App\Models\Rental_forms;
use Cartalyst\Stripe\Stripe;
use Illuminate\Support\Facades\View;
use Eloquent;

class PropertyController extends Controller {

    public $success_error_messages;
    
    public function __construct()
    {
		$this->success_error_messages = Lang::get('success_error_messages');
	}
    
    public function index(Request $request) {
	    
		$user = Auth::user();
		if (!empty($_POST)) {
			$data = Input::all();
        }

        $banner_info = Banner::where('slug','dashboard')->first();
        return view('property_listing', compact('user','banner_info'));
    }

	public function add(Request $request) {
        $data['user'] = Auth::user();
        if(!empty($_POST)){
            $inputData = Input::all();
            $rules = array(
                'property_name' => 'required',
                'setToken' => 'required',
                'address' => 'required',
                'city' => 'required',
                'street' => 'required',
                'postal_code' => 'required',
                'type' => 'required',
                'furnish' => 'required',
                'smoking' => 'required',
                'allowed' => 'required',
                'bedroom' => 'required',
                'bathroom' => 'required',
                'enter_area' => 'required',
                'built_year' => 'required',
                'parking_type' => 'required',
                'monthly_rent' => 'required',
                'security_deposit' => 'required',
                'utilities_included' => 'required',
                'available_from' => 'required',
                'term_of_lease' => 'required',
                'property_amenities' => 'required',
                'community_amenities' => 'required',
                'time_from' => 'required',
                'time_to' => 'required',
                'my_hidden_input' => 'required'
            );
            if(!empty($request->utilities_included)){
                $rules['property_utilities'] = 'required';
            }
            $validator = Validator::make($inputData, $rules);
            if ($validator->fails()) {
                $notification = array(
                    'message' =>  $this->success_error_messages['fill_all_required_fields'],
                    'alert-type' => 'error'
                );
                return redirect('/property/add')->with($notification);
            }  else{
                $properties = array(
                    'user_id' => $data['user']['id'],
                    'address' => $request->address,
                    'property_name' => $request->property_name,
                    'city' => $request->city,
                    'country' => $request->country,
                    'locality' => $request->locality,
                    'street' => $request->street,
                    'postal_code' => $request->postal_code,
                    'type' => ($request->type)?'apartment_condo':'duplex_house',
                    'furnish' => ($request->furnish)?'yes':'no',
                    'smoking' => ($request->smoking)?'yes':'no',
                    'allowed' => ($request->allowed)?'yes':'no',
                    'bedroom' => $request->bedroom,
                    'bathroom' =>$request->bathroom,
                    'enter_area' =>$request->enter_area,
                    'built_year' =>$request->built_year,
                    'parking_type' =>$request->parking_type,
                    'monthly_rent' =>str_replace(',','',$request->monthly_rent),
                    'security_deposit' =>str_replace(',','',$request->security_deposit),
                    'rental_incentives' =>$request->rental_incentives,
                    'utilities_included' =>($request->utilities_included)?'yes':'no',
                    'description' => htmlentities($request->description),
                    'available_from' => date('Y-m-d', strtotime($request->available_from)),
                    'term_of_lease' => $request->term_of_lease,
                    'contact_method_email' => ($request->contact_method_email)?'yes':'no',
                    'contact_method_phone' => ($request->contact_method_phone)?'yes':'no',
                    'contact_method_text_message' => ($request->contact_method_text_message)?'yes':'no',
                    'contact_method_hide_phone' => ($request->contact_method_hide_phone)?'yes':'no',
                    'lat' =>$request->latValue,
                    'lng' =>$request->lngValue,
					'hide_address' => ($request->hide_address)?'yes':'no',
                    'created' => date('Y-m-d H:i:s')
                );
                $id = DB::table('properties')->insertGetId($properties);
                if(!empty($id)){
                    $property_amenities = explode(',', $request->property_amenities);
                    $insert_community_amenities = array();
                    foreach ($property_amenities as $pa) {
                        $insert_community_amenities[] = array(
                            'property_id' => $id,
                            'amenity_id' => $pa,
                            'ammenties_type'=> 'Property'
                        );
                    }
                    $community_amenities = explode(',', $request->community_amenities);
                    foreach ($community_amenities as $ca) {
                        $insert_community_amenities[] = array(
                            'property_id' => $id,
                            'amenity_id' => $ca,
                            'ammenties_type'=> 'Community'
                        );
                    }
                    DB::table('property_amenities')->insert($insert_community_amenities);

                    if(!empty($request->utilities_included)) {
                        $property_utilities = explode(',', $request->property_utilities);
                        $insert_utilities = array();
                        foreach ($property_utilities as $pu) {
                            $insert_utilities[] = array(
                                'property_id' => $id,
                                'utiliti_id' => $ca
                            );
                        }
                        DB::table('property_utilities')->insert($insert_utilities);
                    }

                    $my_hidden_input = explode(',', $request->my_hidden_input);
                    $insert_availabilities = array();
                    foreach ($my_hidden_input as $mhi) {
                        $insert_availabilities[] = array(
                            'property_id' => $id,
                            'date' => date('Y-m-d',strtotime($mhi)),
                            'time_from' => $request->time_from,
                            'time_to' => $request->time_to
                        );
                    }
                    DB::table('property_availabilities')->insert($insert_availabilities);
                    DB::table('property_galleries')->where('property_id', $request->setToken)->update(
                        array(
                            'property_id' => $id,
                            'status' => '1'
                        )
                    );
                    return redirect("/property/make_feature/$id");
                }
                else{
                    $notification = array(
                        'message' => $this->success_error_messages['fill_all_required_fields'],
                        'alert-type' => 'error'
                    );
                    return redirect('/property/add')->with($notification);
                }
            }
        }
        $data['utilities'] = Utilities::where('status','1')->get();
        $data['Parking_types'] = Parking_types::where('status','1')->get();
        $data['banner_info'] = Banner::where('slug','dashboard')->first();
        $data['property_ammenties'] = Ammenties::where(array('ammenties_type'=>'Property','status'=>'1'))->get();
        $data['community_ammenties'] = Ammenties::where(array('ammenties_type'=>'Community','status'=>'1'))->get();
        return view('property_add',$data);
    }

    public function make_feature(Request $request) {
        if($request->id){
            $data['property'] = DB::table('properties')->where('id',$request->id)->get()->first();
        }
        $data['user'] = Auth::user();
        $data['banner_info'] = Banner::where('slug','dashboard')->first();
        return view('property_make_feature',$data);
    }
    
    public function uploadfiles(Request $request) {
        if (!empty($_POST)) {
            $data = Input::all();
            $rules = array(
                'setToken' => 'required',
                'property_galleries' => 'required|image|mimes:jpeg,bmp,png|dimensions:max_height=1000,max_width=2000,min_height=300,min_width=500',
            );
            $message = array(
                'property_galleries.dimensions' => 'The Image dimension should be less than 1000 X 2000 and more then 300 X 500 pixels.'
            );
            $validator = Validator::make($data, $rules,$message);
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
                $length = DB::table('property_galleries')
                ->where('property_id', $request->setToken)
                ->count();
                if($length>=15) {
                    $result = array(
                        "success" => false,
                        "error" => 'No more than 15 images allowed.'
                    );
                } else{
                    $filename = $request->property_galleries->store('properties');
                    $Insert_data = array(
                        'property_id' => $request->setToken,
                        'image' => $filename,
                        'status' => '0'
                    );
                    $id = DB::table('property_galleries')->insertGetId($Insert_data);
                    $result = array(
                        "success" => true,
                        "file_name" => $filename,
                        "file_with_path" => asset("storage/app/public/upload/$filename"),
                        "last_id" => $id
                    );
                }
            }
        } else{
            $result = array(
                "success" => false,
                "error" => 'No direct access'
            );
        }
        echo json_encode($result, JSON_UNESCAPED_SLASHES);
    }

    public function remove_image(Request $request) {
        // echo "I am here";
        if (!empty($_POST)) {
            $data = Input::all();
            $rules = array(
                'id' => 'required'
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
                $record = DB::table('property_galleries')
                ->where('id', $request->id)
                ->get()->first();

                if(!empty($record->image)){
                    $image =  str_replace('/', '\\',$record->image);
                    if(file_exists(storage_path("app\public\upload\\").$image)){
                        unlink(storage_path("app\public\upload\\").$image);
                    }
                }
                
                $length = DB::table('property_galleries')
                ->where('id', $request->id)
                ->delete();
                    $result = array(
                        "success" => true
                    );
            }
        } else{
            $result = array(
                "success" => false,
                "error" => 'No direct access'
            );
        }
        echo json_encode($result, JSON_UNESCAPED_SLASHES);
    }

    // for case when user view and upload new image
    public function uploadfiles_edit(Request $request) {
        if (!empty($_POST)) {
            $data = Input::all();
            $rules = array(
                'setToken' => 'required',
                'property_galleries' => 'required|image|mimes:jpeg,bmp,png|dimensions:max_height=1000,max_width=2000,min_height=300,min_width=500',
            );
            $message = array(
                'property_galleries.dimensions' => 'The Image dimension should be less than 1000 X 2000 and more then 300 X 500 pixels.'
            );
            $validator = Validator::make($data, $rules,$message);
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
                $length = DB::table('property_galleries')
                ->where('property_id', $request->setToken)
                ->count();
                if($length>=15) {
                    $result = array(
                        "success" => false,
                        "error" => 'No more than 15 images allowed.'
                    );
                } else{
                    $filename = $request->property_galleries->store('properties');
                    $Insert_data = array(
                        'property_id' => $request->setToken,
                        'image' => $filename,
                        'status' => '0'
                    );
                    // $id = DB::table('property_galleries')->insertGetId($Insert_data);
                    
                    $property_detail = Properties::with(['property_images','property_amenities','property_amenities.amenities'])
                    ->where('id', '=', $request->setToken)
                    ->first();
                    $view = View::make('ajax.gallery')->with('property_detail',$property_detail)->render();
                    
                     $id = DB::table('property_galleries')->insertGetId($Insert_data);
                    $result = array(
                        "success" => true,
                        "file_name" => $filename,
                        "file_with_path" => asset("storage/app/public/upload/$filename"),
                        "last_id" => $id,
                        "html" => $view
                    );
                }
            }
        } else{
            $result = array(
                "success" => false,
                "error" => 'No direct access'
            );
        }
        return response()->json($result);
        
    }

    // for case when user view and delete a image
    public function remove_image_edit(Request $request) {
        // echo "I am here";
        if (!empty($_POST)) {
            $data = Input::all();
            $rules = array(
                'id' => 'required'
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
                $record = DB::table('property_galleries')
                ->where('id', $request->id)
                ->get()->first();

                
                if(!empty($record->image)){
                    $image =  str_replace('/', '\\',$record->image);
                    if(file_exists(storage_path("app\public\upload\\").$image)){
                        unlink(storage_path("app\public\upload\\").$image);
                    }
                }
                
                $length = DB::table('property_galleries')
                ->where('id', $request->id)
                ->delete();

                $property_detail = Properties::with(['property_images','property_amenities','property_amenities.amenities'])
                    ->where('id', '=', $request->property_id)
                    ->first();

                $view = View::make('ajax.gallery')->with('property_detail',$property_detail)->render();
                    
                // echo $view;die;
                $notification = array(
                    'data' =>  true,
                    'message' => $this->success_error_messages['property_overview_update_successs'],
                    'append_data' => $view
                );   
                return response()->json($notification);
            }
        } else{
            // echo $view;die;
            $notification = array(
                'data' =>  false,
                'message' => $this->success_error_messages['common_error'],
            );   
        }
        return response()->json($notification);
    }

    // for case when user edit from view property
    public function do_payment_edit(Request $request) {
        if (!empty($_POST)) {
            $user = Auth::user();
            $data = Input::all();
            $rules = array(
                'id' => 'required',
                'property_id' => 'required'
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
                $record = DB::table('featured_transactions')
                ->where('property_id', $request->property_id)
                ->get()->first();
                if(!empty($record)){
                    $result = array(
                        "success" => false,
                        "error" => 'Propperty already is in featured state.'
                    );
                } else{
                    $stripe = new Stripe(config('services.stripe.secret'));
                    $charge = $stripe->charges()->create([
                        'source' => $request->id,
                        'currency' => 'USD',
                        'amount'   => 20,
                    ]);
                   
                    if(!empty($charge['id'])){
                        DB::table('featured_transactions')->insert(array(
                            'user_id' => $user['id'],
                            'property_id' => $request->property_id,
                            'amount' => '20',
                            'transaction_id' => $charge['id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ));

                        //Update Property table.
                        DB::table('properties')->where('id', $request->property_id)->update(
                            array(
                                'featured' => 'yes'
                            )
                        );
                        $html = '<p class="just-msg" >This is already a featured property</p>';
                        $result = array(
                            "success" => true,
                            "message" => 'Your property has been marked as featured.',
                            'html' => $html
                        );
                    } else{
                        $result = array(
                            "success" => false,
                            "error" => $charge['message']
                        );
                    }
                }
            }
        } else{
            $result = array(
                "success" => false,
                "error" => 'No direct access'
            );
        }
        echo json_encode($result, JSON_UNESCAPED_SLASHES);
    }


    public function do_payment(Request $request) {
        if (!empty($_POST)) {
            $user = Auth::user();
            $data = Input::all();
            $rules = array(
                'id' => 'required',
                'property_id' => 'required'
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
                $record = DB::table('featured_transactions')
                ->where('property_id', $request->property_id)
                ->get()->first();
                if(!empty($record)){
                    $result = array(
                        "success" => false,
                        "error" => 'Propperty already is in featured state.'
                    );
                } else{
                    $stripe = new Stripe(config('services.stripe.secret'));
                    $charge = $stripe->charges()->create([
                        'source' => $request->id,
                        'currency' => 'USD',
                        'amount'   => 20,
                    ]);
                    if(!empty($charge['id'])){
                        DB::table('featured_transactions')->insert(array(
                            'user_id' => $user['id'],
                            'property_id' => $request->property_id,
                            'amount' => '20',
                            'transaction_id' => $charge['id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ));

                        //Update Property table.
                        DB::table('properties')->where('id', $request->property_id)->update(
                            array(
                                'featured' => 'yes'
                            )
                        );
                        $result = array(
                            "success" => true,
                            "message" => 'Your property has been marked as featured.'
                        );
                    } else{
                        $result = array(
                            "success" => false,
                            "error" => $charge['message']
                        );
                    }
                }
            }
        } else{
            $result = array(
                "success" => false,
                "error" => 'No direct access'
            );
        }
        echo json_encode($result, JSON_UNESCAPED_SLASHES);
    }

    // view property listing 
    public function listing(Request $request) {
        
        $user = Auth::user();
		
		if (!empty($_POST)) {
            $data = Input::all();
        }

        $banner_info = Banner::where('slug','dashboard')->first();

        if($request->search){

            $property_list = Properties::with('property_images')
            ->where('is_deleted', '=', '0')
            ->where('user_id', '=', $user['id'])
            ->where('city', 'like', '%'.$request->search.'%')
            ->orWhere('address','like', '%'.$request->search.'%')
            ->orderBy('id', 'DESC')
            ->paginate(6);
        } 
		else{
           $property_list = Properties::with('property_images')->where('user_id', '=', $user['id'])->where('is_deleted', '=', '0')->orderBy('id', 'DESC')->paginate(6);
		}

        $view = View::make('ajax.property_listing')->with('property_list',$property_list)->with('success_error_messages',$this->success_error_messages)->render();

        $notification = array(
            'data' =>  true,
            'append_data' => $view,
        );
        return response()->json($notification);
    }

    // view property Details
    public function view(Request $request) {
        
        $user = Auth::user();
        if (!empty($_POST)) {
            
        }
        $property_detail = Properties::with(['property_images','property_amenities','property_amenities.amenities','property_availabilities'])
            ->where('id', '=', $request->id)
            ->where('user_id', '=', $user['id'])
            ->first();
        if(empty($property_detail)){
            return redirect('/not_found');
        }
        
        
        $banner_info = Banner::where('slug','dashboard')->first();
        $data['utilities'] = Utilities::where('status','1')->get();
        $Parking_types = Parking_types::where('status','1')->get();
        $data['banner_info'] = Banner::where('slug','dashboard')->first();
        $property_ammenties = Ammenties::where(array('ammenties_type'=>'Property','status'=>'1'))->get();
        
        $community_ammenties = Ammenties::where(array('ammenties_type'=>'Community','status'=>'1'))->get();
        $recent_property = Properties::with('property_images')->where('user_id', '=', $user['id'])->where('is_deleted', '=', '0')->where('id', '!=', $request->id)->orderBy('id', 'DESC')->paginate(4);
        //  echo '<pre>',print_r($recent_property),'</pre>';die;
        return view('property_view_landlord', compact('user','banner_info','property_detail','Parking_types','property_ammenties','community_ammenties','recent_property'));
    }

    // change rented status
    public function update_rented_status(Request $request) {
        $user = Auth::user();
		if (!empty($_POST)) {
			$data = Input::all();
			
            $rules = array(
                'status' => 'required',
                'property_id' => 'required',
			);

            $validator = Validator::make($data, $rules);
            // user set new password
    
            if ($validator->fails()) {
                // return redirect()->intended('/profile')->withErrors($validator)->withInput($request->input());
            } 
			else 
			{ 
                if($request->property_id){
                    $user_update_data = array(
                        'id'  => $request->property_id,
                        'modified'=> date('Y-m-d H:i:s'),
                        
                    );
                    if($request->status=='0'){
                        $user_update_data['is_rented'] = '1';
                        
                        // change property status in rental_forms into rented
                        $rental_status['is_rented'] = '1';
                        DB::table('rental_forms')
                        ->where('property_id',$request->property_id)
                        ->update($rental_status);


                        $applicant_information = Rental_forms::with(array(
                            'property'=>function($query) {
                                $query->select('id','property_name');
                            },
                            'tenant_info'=>function($query){
                                $query->select('id','name','email');
                            },'landlord_info'=>function($query){
                                $query->select('id','name','email');
                            }))->where('property_id',$request->property_id)->get()->toArray();

                        // foreach($applicant_information as $applicant_info){
                        //     /* Message sent to tenant */
                        //     $update_data['is_last'] = '0';
                        //     $update_data['updated_at'] = date('Y-m-d H:i:s');
                            
                        //     DB::table('messages')->where(function ($query) use ($applicant_info,$user) {
                        //         $query->where('from_user', '=', $user['id'])->where('to_user', '=', $applicant_info['user_id']);})->orWhere(function ($query) use ($applicant_info,$user) {
                        //             $query->where('to_user', '=', $applicant_info['user_id'])->Where('from_user', '=', $user['id']);})->Where(function ($query) use ($applicant_info,$user) {
                        //             $query->where('property_id', '=', $applicant_info['property_id']);})->update($update_data);
                            
                        //     $message_insert_data['from_user'] = $user['id'];
                        //     $message_insert_data['to_user'] = $applicant_info['user_id'];
                        //     $message_insert_data['property_id'] = $applicant_info['property_id'];
                        //     $message_insert_data['message'] = 'property '.ucfirst($applicant_info['property']['property_name']) .' status is now rented';
                        //     $message_insert_data['read_status'] = '0';
                        //     $message_insert_data['is_last'] = '1';
                        //     $message_insert_data['created_at'] = date('Y-m-d H:i:s');
                        //     $message_insert_data['updated_at'] = date('Y-m-d H:i:s');
                        //     DB::table('messages')->insert($message_insert_data);
                        // /* End Here */

                        // }
                        // echo '<pre>',print_r($applicant_info),'</pre>';die;
                        $rel_msg = $this->success_error_messages['confirmation_dialog_property_for_unrent'];
                    }else{
                        $user_update_data['is_rented'] = '0';
                        
                        // change property status in rental_forms into not rented
                        $rental_status['is_rented'] = '0';
                        DB::table('rental_forms')
                        ->where('property_id',$request->property_id)
                        ->update($rental_status);
                        $rel_msg = $this->success_error_messages['confirmation_dialog_property_for_rent'];
                    }
                    
                if(DB::table('properties')->where('id',$request->property_id)->update($user_update_data)){
                    $notification = array(
                        'data' =>  true,
                        'message' =>  $this->success_error_messages['rented_status_change'],
                        'rel_msg_new' =>  $rel_msg,
                    );
                } else{
                    $notification = array(
                        'data' =>  false,
                        'message' => $this->success_error_messages['common_error'],
                    );
                }
                } else{
                    $notification = array(
                        'data' =>  false,
                        'message' => $this->success_error_messages['common_error'],
                    );
                   
                }
                
                return response()->json($notification);
            }
        }
        else{
            $notification = array(
                'data' =>  false,
                'message' => $this->success_error_messages['common_error'],
            );
           
        }
        $notification = array(
            'data' =>  false,
            'message' => $this->success_error_messages['common_error'],
        );
        return response()->json($notification);
       
    }

    // delete property
    public function delete_property(Request $request) {

		if (!empty($_POST)) {
			$data = Input::all();
			
            $rules = array(
                'property_id' => 'required',
			);

            $validator = Validator::make($data, $rules);
            // user set new password
    
            if ($validator->fails()) {
                // return redirect()->intended('/profile')->withErrors($validator)->withInput($request->input());
            } 
			else 
			{  
                if($request->property_id){
                    $user_update_data = array(
                        'id'  => $request->property_id,
                        'is_deleted' => '1',
                        'modified'=> date('Y-m-d H:i:s'),
                        
                    );   
                if(DB::table('properties')->where('id',$request->property_id)->update($user_update_data)){
                    $notification = array(
                        'data' =>  true,
                        'property_id' => $request->property_id,
                        'message' => $this->success_error_messages['property_delete'],
                    );
                     
                } else{
                    $notification = array(
                        'data' =>  false,
                        'message' => $this->success_error_messages['common_error'],
                    );
                }
                } else{
                    $notification = array(
                        'data' =>  false,
                        'message' => $this->success_error_messages['common_error'],
                    );
                   
                }
                return response()->json($notification);
            }
        }
        else{
            $notification = array(
                'data' =>  false,
                'message' => $this->success_error_messages['common_error'],
            );
           
        }
        return response()->json($notification);
       
    }
    // update property Overview
    public function update_property_overview(Request $request) {
       
		if (!empty($_POST)) {
			$data = Input::all();

            $rules = array(
                'bathroom' => 'required',
                'bedroom' => 'required',
                'property_name' => 'required',
                'built_year' => 'required',
                'parking_type' => 'required',
                'enter_area' => 'required',
                'available_from' => 'required',
                'monthly_rent' => 'required',
                'security_deposit' => 'required'

            );
            $validator = Validator::make($data, $rules);
            
            // user set new password
    
            if ($validator->fails()) {
                return redirect('/property/update_property_overview')->withErrors($validator)->withInput($request->input());
            } 
			else 
			{ 
                if($request->property_id){
                    $user_update_data = array(
                        'bathroom' => $request->bathroom,
                        'bedroom' => $request->bedroom,
                        'property_name' => $request->property_name,
                        'built_year' => $request->built_year,
                        'parking_type' => $request->parking_type,
                        'enter_area' => $request->enter_area,
                        'monthly_rent' =>str_replace(',','',$request->monthly_rent),
                        'security_deposit' =>str_replace(',','',$request->security_deposit),
                        'available_from' => date('Y-m-d', strtotime($request->available_from)),
                        'modified'=> date('Y-m-d H:i:s'),
                    );
					
					if(DB::table('properties')->where('id',$request->property_id)->update($user_update_data)){
                        $notification = array(
                            'data' =>  true,
                            'message' => $this->success_error_messages['property_overview_update_successs'],
                        );
                    } else{
                            $notification = array(
                                'data' =>  false,
                                'message' => $this->success_error_messages['common_error'],
                            );
                        }
                } else{
                    $notification = array(
                        'data' =>  false,
                        'message' => $this->success_error_messages['common_error'],
                    );
                   
                }
                
                return response()->json($notification);
            }
        } else{
            
            $notification = array(
                'data' =>  false,
                'message' => $this->success_error_messages['common_error'],
            );
        }
        
        return response()->json($notification);
    }

    // update property ammenties
    public function update_property_ammenities(Request $request) {
        
		if (!empty($_POST)) {
			$data = Input::all();

            $rules = array(
                'property_amenities' => 'required',
                // 'property_id' => 'required,'
            );
            $validator = Validator::make($data, $rules);
            
            if ($validator->fails()) {
                return redirect()->intended('/property/update_property_overview')->withErrors($validator)->withInput($request->input());
            } 
			else 
			{ 
                if($request->property_id){
                    
                    DB::table('property_amenities')
                    ->where('property_id', '=', $request->property_id)
                    ->where('ammenties_type', '=', 'Property')
                    ->delete();

                    $update_ammenties_id  = explode(",",$request->property_amenities);
                    foreach($update_ammenties_id as $updata_data){
                        $user_update_data = array(
                            'property_id' => $request->property_id,
                            'amenity_id' => $updata_data,
                            'ammenties_type' => 'Property', 
                        );
                        DB::table('property_amenities')->insert($user_update_data);
                    }

                    $property_detail = Properties::with(['property_images','property_amenities','property_amenities.amenities'])
                    ->where('id', '=', $request->property_id)
                    ->first();

                    $property_ammenties = Ammenties::where(array('ammenties_type'=>'Property','status'=>'1'))->get();
                    $view = View::make('ajax.property_ammnenities_update')->with('property_detail',$property_detail)->with('property_ammenties',$property_ammenties)->render();
                    
                    $notification = array(
                        'data' =>  true,
                        'message' => $this->success_error_messages['property_amenities_update_successs'],
                        'append_data' => $view
                    );   
                } 
				else{
                    $notification = array(
                        'data' =>  false,
                        'message' => $this->success_error_messages['common_error'],
                    );
                }
                
                return response()->json($notification);
            }
        } else{
            
            $notification = array(
                'data' =>  false,
                'message' => $this->success_error_messages['common_error'],
            );
        }
        
        return response()->json($notification);
    }

    // update property ammenties
    public function update_community_ammenities(Request $request) {
       
		if (!empty($_POST)) {
			$data = Input::all();

            $rules = array(
                'community_amenities' => 'required',
                // 'property_id' => 'required,'
            );
            $validator = Validator::make($data, $rules);
            
            // user set new password
    
            if ($validator->fails()) {
                return redirect()->intended('/property/update_community_ammenities')->withErrors($validator)->withInput($request->input());
            } 
			else 
			{ 
                if($request->property_id){
                    
                    DB::table('property_amenities')
                    ->where('property_id', '=', $request->property_id)
                    ->where('ammenties_type', '=', 'Community')
                    ->delete();

                    $update_ammenties_id  = explode(",",$request->community_amenities);
                    foreach($update_ammenties_id as $updata_data){
                        $user_update_data = array(
                            'property_id' => $request->property_id,
                            'amenity_id' => $updata_data,
                            'ammenties_type' => 'Community', 
                        );
                        DB::table('property_amenities')->insert($user_update_data);
                    }

                    $property_detail = Properties::with(['property_images','property_amenities','property_amenities.amenities'])
                    ->where('id', '=', $request->property_id)
                    ->first();
                    $property_ammenties = Ammenties::where(array('ammenties_type'=>'Property','status'=>'1'))->get();
					$view = View::make('ajax.community_ammnenities_update')->with('property_detail',$property_detail)->render();
                    
                    $notification = array(
                        'data' =>  true,
                        'message' => $this->success_error_messages['community_amenities_update_successs'],
                        'append_data' => $view
                    );   
                } 
				else{
                    $notification = array(
                        'data' =>  false,
                        'message' => $this->success_error_messages['common_error'],
                    );
                   
                }
                return response()->json($notification);
            }
        } 
		else{
            
            $notification = array(
                'data' =>  false,
                'message' => $this->success_error_messages['common_error'],
            );
        }
        return response()->json($notification);
    }

    // update property ammenties
    public function update_property_description(Request $request) {
       
		if (!empty($_POST)) {
			$data = Input::all();

            $rules = array(
                // 'property_amenities' => 'required',
                // 'property_id' => 'required,'
            );
            $validator = Validator::make($data, $rules);
            
            // user set new password
    
            if ($validator->fails()) {
                return redirect('/property/update_community_ammenities')->withErrors($validator)->withInput($request->input());
            } 
			else 
			{ 
                if($request->property_id){
                    $user_update_data = array(
                        'description' => $request->description_property,
                        'modified'=> date('Y-m-d H:i:s'),
                        
                    );
                    DB::table('properties')->where('id',$request->property_id)->update($user_update_data);
                    // echo $view;die;
                    $notification = array(
                        'data' =>  true,
                        'message' => $this->success_error_messages['property_description_update_successs'],
                    );   
                } else{
                    $notification = array(
                        'data' =>  false,
                        'message' => $this->success_error_messages['common_error'],
                    );
                   
                }
                return response()->json($notification);
            }
        } else{
            $notification = array(
                'data' =>  false,
                'message' => $this->success_error_messages['common_error'],
            );
        }
        return response()->json($notification);
    }


    // public function download_csv()
    // {
    //     header('Content-Type: text/csv; charset=utf-8');
    //     header('Content-Disposition: attachment; filename=property.csv');
    //     $output = fopen('php://output', 'w');
    //     fputcsv($output, array('Property name', 'Date','Ip address'));

    // }

    /*Export Data*/
    public function download_csv(Request $request ,$type,$property_id){       

        if($type == 'daily'){
            $where = array(array('created_at', '=', date('Y-m-d'))); 
        }
        if($type == 'weekly'){
            $start_date = date('Y-m-d', strtotime('-7 days'));
            $enddate_date = date('Y-m-d');
            $where[] = array('created_at', '>=', $start_date); 
            $where[] = array('created_at', '<=', $enddate_date); 

        }if($type == 'monthly'){
            $start_date = date('Y-m-d', strtotime('-31 days'));
            $enddate_date = date('Y-m-d');
            $where[] = array('created_at', '>=', $start_date); 
            $where[] = array('created_at', '<=', $enddate_date);
        }
        $property_log_detail = Property_view_log::with(array(
			'property'=>function($query){
			$query->select('id','property_name');
			},'user'=>function($query){
                $query->select('id','name','user_type','phone','email');
                },))->where('property_id', '=', $property_id)
            ->where($where)
            ->get();
            //  echo '<pre>',print_r($property_log_detail),'</pre>';die;
        
        $tot_record_found=0;
        if(count($property_log_detail)>0){
            $tot_record_found=1;
            
            $CsvData=array('Property Name, Date , Ip Address , User Name , User Type , Contact Number, Email');          
            $property_name = "";
            foreach($property_log_detail as $value){  
                // echo date('M j, Y',strtotime($value->created_at));die;

                $user_name = $value['user']['name'] ? $value['user']['name'] : 'Guest User';
                $user_type = $value['user']['user_type'] ? $value['user']['user_type'] : 'Guest User';
                $property_name = $value['property']['property_name'];
                $date = date('Y-m-d',strtotime($value->created_at));
                $ip_address = $value->ip_address;
                $user_phone = $value['user']['phone'] ? $value['user']['phone'] : 'Guest User';
                $user_email = $value['user']['email'] ? $value['user']['email'] : 'Guest User';
                // echo '<pre>',print_r($value),'</pre>';die;            
                $CsvData[]=$property_name.','.$date.','.$ip_address.','.ucfirst($user_name).','.ucfirst($user_type).','.$user_phone.','.$user_email;
            }
            
            $filename=$property_name.".csv";
            $file_path=base_path().'/'.$filename;   
            $file = fopen($file_path,"w+");
            foreach ($CsvData as $exp_data){
            fputcsv($file,explode(',',$exp_data));
            }   
            fclose($file);          
    
            $headers = ['Content-Type' => 'application/csv'];
            return response()->download($file_path,$filename,$headers );
        }
        return view('download',['record_found' =>$tot_record_found]);    
    }
    
    public  function view_property($value='')
    {
        // print_r($_POST);
        $user = Auth::user();
        
        if (!empty($_POST)) {
            $data = Input::all();
        }
        // echo '<pre>',print_r($_POST),'</pre>';die;
        // $banner_info = Banner::where('slug','dashboard')->first();
        $property_detail = DB::table('property_galleries')->where('property_id',$data['setToken'])->get();
        $property_amenities = DB::table('ammenties')->whereRaw('FIND_IN_SET(id,"'.$data['property_amenities'].'") = 0')->get();
        $community_amenities = DB::table('ammenties')->whereRaw('FIND_IN_SET(id,"'.$data['community_amenities'].'") = 0')->get();
        $appointment_dates = (explode(",",$data['my_hidden_input']));
        $time['time_from'] = $data['time_from'];
        $time['time_to'] = $data['time_to'];
        $data = (object) $data;

       
        
        

        $view = View::make('ajax.property_view')->with('data',$data)->with('time',$time)->with('appointment_dates',$appointment_dates)->with('property_detail',$property_detail)->with('property_amenities',$property_amenities)->with('community_amenities',$community_amenities)->render();

        $notification = array(
            'data' =>  true,
            'append_data' => $view,
        );
        return response()->json($notification);
    }

     /*Export Data*/
     public function viewStatics(Request $request){       

        if($request->sort == 'daily'){
            $where = array(array('created_at', '=', date('Y-m-d'))); 
        }
        if($request->sort == 'weekly'){
            $start_date = date('Y-m-d', strtotime('-7 days'));
            $enddate_date = date('Y-m-d');
            $where[] = array('created_at', '>=', $start_date); 
            $where[] = array('created_at', '<=', $enddate_date); 

        }if($request->sort == 'monthly'){
            $start_date = date('Y-m-d', strtotime('-31 days'));
            $enddate_date = date('Y-m-d');
            $where[] = array('created_at', '>=', $start_date); 
            $where[] = array('created_at', '<=', $enddate_date);
        }
        $property_log_detail = Property_view_log::with(array(
            'property'=>function($query){
            $query->select('id','property_name');
            },'user'=>function($query){
                $query->select('id','name','user_type','phone','email');
                },))->where('property_id', '=', $request->property_id)
            ->where($where)
            ->count();

            $notification = array(
                'data' =>  true,
                'count' => $property_log_detail
            ); 

            return response()->json($notification);
    }

}
