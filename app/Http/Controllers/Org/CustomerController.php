<?php

namespace App\Http\Controllers\Org;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use App\Models\Org\Customer;
use App\Models\Org\Division;

use App\Models\Finance\Accounting\PaymentTerm;
use App\Currency;
use App\Http\Resources\CustomerResource;



class CustomerController extends Controller
{
    public function __construct()
    {
      //add functions names to 'except' paramert to skip authentication
//      $this->middleware('jwt.verify', ['except' => ['index']]);
    }

    //get customer list
    public function index(Request $request)
    {
      $type = $request->type;
      if($type == 'datatable')   {
        $data = $request->all();
        return response($this->datatable_search($data));
      }
      else if($type == 'auto')    {
        $search = $request->search;
        return response($this->autocomplete_search($search));
      }
      else{
        return response([]);
      }
    }


    //create a customer
    public function store(Request $request)
    {
      $customer = new Customer();
      if($customer->validate($request->all()))
      {
        $customer->fill($request->all());
        $customer->status = 1;
        $customer->save();

        return response([ 'data' => [
          'message' => 'Customer was saved successfully',
          'customer' => $customer
          ]
        ], Response::HTTP_CREATED );
      }
      else
      {
          $errors = $customer->errors();// failure, get errors
          return response(['errors' => ['validationErrors' => $errors]], Response::HTTP_UNPROCESSABLE_ENTITY);
      }
    }


    //get a customer
    public function show($id)
    {
      $customer = Customer::with(['customerCountry','currency','divisions'])->find($id);
      if($customer == null)
        throw new ModelNotFoundException("Requested customer not found", 1);
      else
        return response([ 'data' => $customer ]);
    }


    //update a customer
    public function update(Request $request, $id)
    {
      $customer = Customer::find($id);
      if($customer->validate($request->all()))
      {
        $customer->fill($request->except('customer_code'));
        $customer->save();

        return response([ 'data' => [
          'message' => 'Customer was updated successfully',
          'customer' => $customer
        ]]);
      }
      else
      {
        $errors = $customer->errors();// failure, get errors
        return response(['errors' => ['validationErrors' => $errors]], Response::HTTP_UNPROCESSABLE_ENTITY);
      }
    }


    //deactivate a customer
    public function destroy($id)
    {
      $customer = Customer::where('customer_id', $id)->update(['status' => 0]);
      return response([
        'data' => [
          'message' => 'Customer was deactivated successfully.',
          'customer' => $customer
        ]
      ] , Response::HTTP_NO_CONTENT);
    }


    //validate anything based on requirements
    public function validate_data(Request $request){
      $for = $request->for;
      if($for == 'duplicate')
      {
        return response($this->validate_duplicate_code($request->customer_id , $request->customer_code));
      }
    }


    public function customer_divisions(Request $request) {
        $type = $request->type;
        $customer_id = $request->customer_id;

        if($type == 'selected')
        {
          $selected = Division::select('division_id','division_description')
          ->whereIn('division_id' , function($selected) use ($customer_id){
              $selected->select('division_id')
              ->from('org_customer_divisions')
              ->where('customer_id', $customer_id);
          })->get();
          return response([ 'data' => $selected]);
        }
        else
        {
          $notSelected = Division::select('division_id','division_description')
          ->whereNotIn('division_id' , function($notSelected) use ($customer_id){
              $notSelected->select('division_id')
              ->from('org_customer_divisions')
              ->where('customer_id', $customer_id);
          })->get();
          return response([ 'data' => $notSelected]);
        }

    }

    public function save_customer_divisions(Request $request)
    {
      $customer_id = $request->get('customer_id');
      $divisions = $request->get('divisions');
      if($customer_id != '')
      {
        DB::table('org_customer_divisions')->where('customer_id', '=', $customer_id)->delete();
        $customer = Customer::find($customer_id);
        $save_divisions = array();

        foreach($divisions as $devision)		{
          array_push($save_divisions,Division::find($devision['division_id']));
        }

        $customer->divisions()->saveMany($save_divisions);
        return response([
          'data' => [
            'customer_id' => $customer_id
          ]
        ]);
      }
      else {
        throw new ModelNotFoundException("Requested customer not found", 1);
      }
    }


    //check customer code already exists
    private function validate_duplicate_code($id , $code)
    {
      $customer = Customer::where('customer_code','=',$code)->first();
      if($customer == null){
        return ['status' => 'success'];
      }
      else if($customer->customer_id == $id){
        return ['status' => 'success'];
      }
      else {
        return ['status' => 'error','message' => 'Customer code already exists'];
      }
    }


    //search customer for autocomplete
    private function autocomplete_search($search)
  	{
  		$customer_lists = Customer::select('customer_id','customer_name')
  		->where([['customer_name', 'like', '%' . $search . '%'],]) ->get();
  		return $customer_lists;
  	}


    //get searched customers for datatable plugin format
    private function datatable_search($data)
    {
      $start = $data['start'];
      $length = $data['length'];
      $draw = $data['draw'];
      $search = $data['search']['value'];
      $order = $data['order'][0];
      $order_column = $data['columns'][$order['column']]['data'];
      $order_type = $order['dir'];

      $customer_list = Customer::select('*')
      ->where('customer_code'  , 'like', $search.'%' )
      ->orWhere('customer_name'  , 'like', $search.'%' )
      ->orWhere('customer_short_name'  , 'like', $search.'%' )
      ->orderBy($order_column, $order_type)
      ->offset($start)->limit($length)->get();

      $customer_count = Customer::where('customer_code'  , 'like', $search.'%' )
      ->orWhere('customer_name'  , 'like', $search.'%' )
      ->orWhere('customer_short_name'  , 'like', $search.'%' )
      ->count();

      return [
          "draw" => $draw,
          "recordsTotal" => $customer_count,
          "recordsFiltered" => $customer_count,
          "data" => $customer_list
      ];
    }






//    public function loadCustomer(Request $request) {
//        	}
//    print_r(Customer::where('customer_name', 'LIKE', '%'.$request->search.'%')->get());exit;



   public function loadCustomer(Request $request) {

        try{
            echo json_encode(Customer::where('customer_name', 'LIKE', '%'.$request->search.'%')->get());
//            return CustomerResource::collection(Customer::where('customer_name', 'LIKE', '%'.$request->search.'%')->get() );
        }
        catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
   }

    public function loadCustomerDivision(Request $request) {

        $customer_id = $request->get('customer_id');

        $divisions=DB::table('cust_customer')
            ->join('org_customer_divisions', 'cust_customer.customer_id', '=', 'org_customer_divisions.customer_id')
            ->join('cust_division', 'org_customer_divisions.division_id', '=', 'cust_division.division_id')
            ->select('org_customer_divisions.id AS division_id','cust_division.division_code')
            ->where('cust_customer.status','<>', 0)
            ->where('cust_customer.customer_id','=',$customer_id)
            ->get();
//dd($data);
//        $customer = Customer::find($customer_id);
//        $divisions= $customer->divisions()->get();
//        $data=array();
//        foreach ($divisions as $division){
//            array_push($data,$division);
//        }
        echo json_encode($divisions);

    }

}
