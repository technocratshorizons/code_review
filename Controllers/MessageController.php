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
use App\Models\Messages;
use App\Models\Utilities;
use App\Models\Banner;

class MessageController extends Controller {

	public $success_error_messages;
	
	public function __construct()
    {
		$this->success_error_messages = Lang::get('success_error_messages');
	}
	
	public function index(Request $request) {
        
        $user = Auth::user();
        
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
			->where('is_last', '1')
			->where(function ($query) use ($user) {
				$query->where('from_user', '=', $user['id'])
				->orWhere('to_user', '=', $user['id']);
			})->paginate(5);
		// echo "<pre>";
		$message_info_array = $message_info->toArray();
		// print_r($message_info_array);
		// die;

		
		if(count($message_info_array) > 0) {
			
			$i=0;
			foreach($message_info_array['data'] as $message) {
				
				$message_info_array['data'][$i]['reply_count'] = DB::table('messages')->where('from_user','=', $user['id'])->where('property_id','=', $message['property_id'])->count();
				$i++;
			}
		} 
		
		
		$banner_info = Banner::where('slug','dashboard')->first();
		return view('message_index', compact('user','banner_info','message_info_array','message_info'));
	}
	
	public function detail(Request $request, $property_id, $to_user) {
        
		$user = Auth::user();		
		$message_info = Messages::with(array(
			'property'=>function($query){
				$query->select('id','property_name','available_from','bedroom','bathroom');
			},
			'property.property_images'=>function($query){
				$query->select('id','property_id','image');
			},
			'from_info'=>function($query){
				$query->select('id','name','email','profile_pic');
			},
			'to_info'=>function($query){
				$query->select('id','name','email','profile_pic');
			}))->orderBy('id', 'DESC')
			->where('property_id', $property_id)
			->where(function ($query) use ($user,$to_user) {
				$query->where(function ($query) use ($user,$to_user) {
					$query->where('from_user', '=', $user['id'])
				    ->where('to_user', '=', $to_user);
				})->orWhere(function($query) use ($user,$to_user) {
					$query->where('from_user', '=', $to_user)
					->where('to_user', '=', $user['id']);
				});
			})->paginate(1);
		$tenant_info = [];
		$userInfo = DB::table('users')->where('id',$request->segment('4'))->pluck('user_type');
		if($userInfo=='["tenant"]'){
			$tenant_info = DB::table('user_info') ->join('users', 'user_info.user_id', '=', 'users.id')->where('user_id',$request->segment('4'))->first();
			
		} else{
			$tenant_info = DB::table('user_info') ->join('users', 'user_info.user_id', '=', 'users.id')->where('user_id',$user['id'])->first();
		}


		$message_info = $message_info->reverse();
		$banner_info = Banner::where('slug','dashboard')->first();
		return view('message_detail', compact('user','banner_info','message_info','tenant_info'));
    }


    public function send(Request $request) {
    	$user = Auth::user();
        if (!empty($_POST)) {
            $data = Input::all();
            $rules = array(
                'message_type_box' => 'required',
                'message_to' => 'required'  
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
                $notification = array(
	                'data' =>  false,
	                'message' => $first_msg
            	);
            } 
            else 
            {
            	//Update last status first
				$update_data['is_last'] = '0';
				$update_data['updated_at'] = date('Y-m-d H:i:s');
            	DB::table('messages')->where('is_last', '1')->where('property_id',$request->property_id)
				->where(function ($query) use ($user) {
					$query->where('from_user', '=', $user['id'])
					->orWhere('to_user', '=', $user['id']);
				})->update($update_data);




     //        	->where(
     //        		function ($query) use ($request,$user) {
					// 	$query->where('from_user', '=', $user['id'])->where('to_user', '=', $request->message_to); 
					// })->orWhere(function ($query) use ($request,$user) {
					// 	$query->where('to_user', '=', $request->message_to)->Where('from_user', '=', $user['id']);})
					// ->Where(function ($query) use ($request) {
					// 	$query->where('property_id', '=', $request->property_id)->where('is_last','1');
					// })->update($update_data);

				//Save message first
            	$message_insert_data =  array(
            		'from_user' => $user['id'],
            		'to_user' => $request->message_to,
            		'property_id' => $request->property_id,
            		'message' => $request->message_type_box,
            		'read_status' =>'0',
            		'created_at' => date('Y-m-d H:i:s'),
            		'updated_at' => date('Y-m-d H:i:s'),
            		'is_last'  => '1'
            	);
            	$message_id = DB::table('messages')->insertGetId($message_insert_data);
				//return success response
				$notification = array(
                	'data' =>  true
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


    //get Messsgae here
    public function get_messages(Request $request) {
    	$user = Auth::user();
        if (!empty($_POST)) {
            $data = Input::all();
            $rules = array(
                'To_Id' => 'required', 
                'Property_Id' => 'required'
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
                $notification = array(
	                'data' =>  false,
	                'message' => $first_msg
            	);
            } 
            else 
            {
            	$where = array(array('property_id', '=', $request->Property_Id));
            	$where['property_id'] = $request->Property_Id;
				if(isset($request->Min_Id) && !empty($request->Min_Id)){
					$where[] = array('id', '<', $request->Min_Id);
				} else {
					if(isset($request->Last_Id) && !empty($request->Last_Id)){
						$where[] = array('id', '>', $request->Last_Id);
					}
				}

            	//get Last Message detail to append in div
            	$message_info = Messages::with(array(
					'property'=>function($query){
						$query->select('id','property_name','available_from','bedroom','bathroom');
					},
					'property.property_images'=>function($query) {
						$query->select('id','property_id','image');
					},
					'from_info'=>function($query){
						$query->select('id','name','email','profile_pic');
					},
					'to_info'=>function($query){
						$query->select('id','name','email','profile_pic');
					}))->orderBy('id', 'DESC')
					->where($where)->where(function ($query) use ($user,$request){
						$query->where(function ($query) use ($user,$request) {
							$query->where('from_user', '=', $user['id'])
						     ->where('to_user', '=', $request->To_Id);
						})->orWhere(function($query) use ($user,$request) {
							$query->where('from_user', '=', $request->To_Id)
					        ->where('to_user', '=', $user['id']);
						});
					})->limit(20)->get();
				$update_data['read_status']  = '1';
				DB::table('messages')->where('to_user', '=', $user['id'])->where('property_id',$request->Property_Id)->update($update_data);

				$notification = array(
                	'data' =>  true,
                	'message' => $message_info,
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
