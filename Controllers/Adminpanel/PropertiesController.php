<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use Session;
use Lang;
use File;
use App\Models\Properties;
use App\Models\Rental_forms;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use App\Models\Appointments;
use App\Models\Property_view_log;
class PropertiesController extends Controller
{
	public $success_error_messages;

	public function __construct()
    {
		$this->middleware('admin'); 
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    public function index(Request $request) {
        $appointments = Properties::with(array(
            'landlord_info'=>function($query){
            $query->select('id','name');
            },))->orderBy('id', 'DESC')->get();
            
    	$Properties = Properties::orderBy('id', 'DESC')->get();
		return view('adminpanel.properties',compact('Properties'));
	}

	//Add  Properties Function start here
	public function property_appointments(Request $request) {

		$property_appointment = Appointments::with(array(
			'property'=>function($query){
			$query->select('id','property_name');
			},
			'tenant_info'=>function($query){
				$query->select('id','name','email');
			},))->orderBy('id', 'DESC')
			->where('property_id', $request->id)->get();
			$property_id = $request->id;
            
		return view('adminpanel.property_appointment', compact('user','property_appointment','property_id'));
		
	}

	public function fetch_property_appointments(Request $request,$property_id) {
        
        $user = Auth::user();
			$columns = array( 
				0 => "id",
				1 => "tenant_name",
				2 => "email",
				3 => "property_name",
				4 => "date",
				5 => "time",
				6 => "status",
				
			);
			
			$totalData = Appointments::where('property_id',$property_id)->count();
			$totalFiltered = $totalData; 

			$limit = $request->input('length');
			$start = $request->input('start');
			$order = $columns[$request->input('order.0.column')];
			$dir = $request->input('order.0.dir');
			
			
			$status = $request->input('status');
			$date = $request->input('date');
			
			// for search over all
			$where = array(array('property_id', '=', $property_id));
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
						
			$totalFiltered = Appointments::where('property_id',$property_id)->where($where)->count();
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
					$nestedData['id'] = $appointment->id;
					$nestedData['tenant_name'] = $appointment->tenant_info->name;
					$nestedData['email'] = $appointment->tenant_info->email;
					$nestedData['property_name'] = $appointment->property->property_name;
					$nestedData['date'] = date('M j, Y',strtotime($appointment->date));
					$nestedData['time'] = $appointment->time;
					$nestedData['status'] = $appointment_status;
					
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

	//active deactive status Function start here
	public function status(Request $request) {
		$Properties = Properties::where('id',$request->id)->first();
		// Check if form has data
		if (!empty($Properties)) {
			if($Properties->is_deleted == '1'){
				$update_data['is_deleted'] = '0';
				$update_data['modified'] = date('Y-m-d H:i:s');
				$message = $this->success_error_messages['admin_property_success_un_block'];
			} else{
				$update_data['is_deleted'] = '1';
				$update_data['modified'] = date('Y-m-d H:i:s');
				$message = $this->success_error_messages['admin_property_success_block'];
			}
			//Update the record from database
			Properties::where('id',$request->id)->update($update_data);
			Session::flash('success', $message);
			return redirect('admin/properties');
		}
	}

	public function property_applicants(Request $request) {
        $applicants = Rental_forms::with(array(
            'property'=>function($query) {
                $query->select('id','property_name');
            },
            'tenant_info'=>function($query){
                $query->select('id','name','email');
            },))
            ->where('property_id',$request->id)->get();
    	
		return view('adminpanel.property_applicants',compact('applicants'));
	}

	public function viewLogs(Request $request ,$property_id){       

        $property_log_detail = Property_view_log::with(array(
            'property'=>function($query){
            $query->select('id','property_name');
            },'user'=>function($query){
                $query->select('id','name','user_type','phone','email');
                },))->where('property_id', '=', $property_id)
            ->orderBy('id', 'DESC')->get();
			// echo '<pre>',print_r($property_log_detail),'</pre>';die;
			return view('adminpanel.properties_logs',compact('property_log_detail'));
    }
	
}
