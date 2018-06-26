<?php

namespace App\Http\Controllers\Adminpanel;

use App\Http\Controllers\Controller;

use DB;
use Auth;
use Session;
use Lang;
use File;
use App\Models\Faq;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class FaqController extends Controller
{
	public $success_error_messages;

	public function __construct()
    {
		$this->middleware('admin'); 
		$this->success_error_messages = Lang::get('success_error_messages');
    }
	
    public function index(Request $request) {
    	$faqs = Faq::orderBy('display_order', 'DESC')->get();
		return view('adminpanel.faq',compact('faqs'));
	}

	//Add Faq Function start here
	public function add(Request $request) {

		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
                'question' => 'required|string|max:255',
            	'answer' => 'required|string',
			);
			$validator = Validator::make($data, $rules);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/faqs/add')->withErrors($validator)->withInput($request->input());
			}
			else{
				$faq_insert_data = new Faq;
				$faq_insert_data->question = $request->question;
				$faq_insert_data->answer = htmlentities($request->answer);
				$faq_insert_data->created = date('Y-m-d H:i:s');

				//Check if values saved in database or not
				if($faq_insert_data->save()){
					$insert_id = $faq_insert_data->id;
					$update_faq = array();
					$update_faq['display_order'] = $insert_id;
					Faq::where('id',$insert_id)->update($update_faq);

					Session::flash('success', $this->success_error_messages['faq_success']);
					return redirect('admin/faqs/add');
				}
			}
		}
		return view('adminpanel.faq_add');
	}

	//Edit Faq Function start here
	public function edit(Request $request) {
		$faq = Faq::where('id',$request->id)->first();
		//Check if Form submit
		if (!empty($_POST)) {
			$data = Input::all();

			//Define the rules for validation
			$rules = array(
                'question' => 'required|string|max:255',
            	'answer' => 'required|string',
			);


			$validator = Validator::make($data, $rules);

			//Check if validation goes fail
			if($validator->fails()){
				return redirect()->intended('admin/faqs/edit/'.$request->id)->withErrors($validator)->withInput($request->input());
			}
			else{
				$faq_insert_data = array();
				$faq_insert_data['question'] = $request->question;
				$faq_insert_data['answer'] = htmlentities($request->answer);		
				//Check if values saved in database or not
				if(Faq::where('id',$request->id)->update($faq_insert_data)){
					Session::flash('success', $this->success_error_messages['faq_success_update']);
					return redirect('admin/faqs/edit/'.$request->id);
				}
			}
		}
		return view('adminpanel.faq_edit', compact('faq'));
	}


	//Delete Faq Function start here
	public function delete(Request $request) {
		$faq = Faq::where('id',$request->id)->first();
		// Check if form has data
		if (!empty($faq)) {
			
			//Delete the record from database
			Faq::where('id',$request->id)->delete();
			Session::flash('success', $this->success_error_messages['faq_success_delete']);
					return redirect('admin/faqs');
		}
	}


	//Order  Faq Function start here
	public function order(Request $request) {
		//Check if Form submit
		if (!empty($_POST)) {
			$data = json_decode($request->sorted_value);
			if(!empty($data)){
				foreach ($data as $key => $item) {
					$update_faq = array();
					$update_faq['display_order'] = $item->index;
					Faq::where('id',$item->id)->update($update_faq);
				}
				Session::flash('success', $this->success_error_messages['faq_success_order']);
				return redirect('admin/faqs/order');
			}
		}
		$faqs = Faq::orderBy('display_order', 'ASC')->get();
		return view('adminpanel.faq_order',compact('faqs'));
	}
}
