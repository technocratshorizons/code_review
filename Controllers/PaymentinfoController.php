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
use App\Models\Featured_transactions;

class PaymentinfoController extends Controller {

	public function __construct()
    {
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
	public function index(Request $request) {
		$user = Auth::user();
        $bannerDtails = Banner::where('slug','dashboard')->first();
        $payment_info = Featured_transactions::with(array(
					'property'=>function($query) {
						$query->select('id','property_name');
					},
					))->where('user_id',$user['id'])->get();
	    return view('payment_info_index',compact('user','payment_info','bannerDtails'));
	}
    
    public function fetch_payments(Request $request) {
        
        $user = Auth::user();
			$columns = array( 
				0 => "id",
				1 => "amount",
				2 => "transaction_id",
				3 => "payment_for",
				4 => "payment_gateway",
                5 => "payment_by",
                6 => "created_at",
			);
			

			$totalData = Featured_transactions::where('user_id',$user['id'])->count();
			$totalFiltered = $totalData; 

			$limit = $request->input('length');
			$start = $request->input('start');
			$order = $columns[$request->input('order.0.column')];
			$dir = $request->input('order.0.dir');
			
			
			$status = $request->input('status');
			$date = $request->input('date');
			
			// for search over all
			$where = array(array('user_id', '=', $user['id']));
			$where_property = array();
			
			if(isset($status) && $status!="") {
				$where[] = array('status', '=', $status);
			}
			if(isset($date) && !empty($date)) {
				$dates = explode('-',$request->input('date'));
				
				$fromdate = date("Y-m-d", strtotime($dates['0']));
				$enddate = date("Y-m-d", strtotime($dates['1']));

				$where[] = array('created_at', '>=', $fromdate);
				$where[] = array('created_at', '<=', $enddate);
			}
			
			$Featured_transactions = Featured_transactions::where($where)
					->offset($start)
					->limit($limit)
					->orderBy($order,$dir)
					->get();
						
			$totalFiltered = Featured_transactions::where('user_id',$user['id'])->count();
			$data = array();
			
			if(!empty($Featured_transactions))
			{
				foreach ($Featured_transactions as $transactions)
				{
					
					$nestedData['id'] = $transactions->id;
					$nestedData['amount'] = '$'.$transactions->amount;
					$nestedData['transaction_id'] = $transactions->transaction_id;
					$nestedData['payment_for'] = $transactions->payment_for;
					
					$nestedData['payment_gateway'] = $transactions->payment_gateway;
                    $nestedData['payment_by'] = $transactions->payment_by;
                    $nestedData['created_at'] = date('M j, Y',strtotime($transactions->created_at));
				
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

}
